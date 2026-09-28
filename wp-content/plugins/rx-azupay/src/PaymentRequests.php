<?php
/**
 * Order ⇄ AzuPay PaymentRequest bookkeeping.
 *
 * One order has at most one live PaymentRequest. Its ids, PayID and
 * expiry are kept in order meta (HPOS-safe CRUD only). Payment is
 * asynchronous: the order sits in on-hold (like WooCommerce's BACS
 * gateway) until AzuPay reports COMPLETE, and three paths can bring that
 * news in — the webhook, the thank-you page's status poll, and the
 * expiry check. They all end in apply_status(), which is idempotent and
 * locked per order, so it's safe for them to race.
 *
 * Kept separate from the gateway class so the PayTo gateway (same API,
 * PROJECT.md §8) can reuse it.
 *
 * @package RX\AzuPay
 */

declare(strict_types=1);

namespace RX\AzuPay;

use WC_Order;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates, refreshes and settles PaymentRequests for orders.
 */
final class PaymentRequests {

	public const META_REQUEST_ID     = '_rx_azupay_payment_request_id';
	public const META_TRANSACTION_ID = '_rx_azupay_client_transaction_id';
	public const META_PAYID          = '_rx_azupay_payid';
	public const META_AMOUNT         = '_rx_azupay_amount';
	public const META_DESCRIPTION    = '_rx_azupay_description';
	public const META_CHECKOUT_URL   = '_rx_azupay_checkout_url';
	public const META_EXPIRES_AT     = '_rx_azupay_expires_at';
	public const META_WEBHOOK_TOKEN  = '_rx_azupay_webhook_token';
	public const META_STATUS         = '_rx_azupay_status';
	public const META_SETTLED_BY     = '_rx_azupay_settled_by';

	/**
	 * Action Scheduler hook that settles an order once its request has
	 * expired (AzuPay only sends a webhook for COMPLETE, never EXPIRED).
	 */
	public const EXPIRY_ACTION = 'rx_azupay_expiry_check';

	/**
	 * Minutes after expiry before the expiry check runs, so a payment made
	 * in the last seconds has time to arrive first.
	 */
	private const EXPIRY_GRACE_MINUTES = 10;

	/**
	 * Minimum seconds between remote status fetches for one order, so a
	 * busy poller or a flood of forged webhooks can't hammer AzuPay.
	 */
	private const REFRESH_THROTTLE = 10;

	/**
	 * Format of clientTransactionId: RX-{order id}-{random}. The random part
	 * keeps it unique across retries and across environments sharing one
	 * AzuPay UAT account.
	 */
	private const TRANSACTION_ID_PATTERN = '/^RX-(\d+)-[A-Za-z0-9]+$/';

	/**
	 * Payment methods whose orders this class manages.
	 *
	 * @var string[]
	 */
	private const GATEWAY_IDS = array( PayIdGateway::ID );

	/**
	 * Whether an order was placed with one of the AzuPay gateways.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function is_azupay_order( WC_Order $order ): bool {
		return in_array( $order->get_payment_method(), self::GATEWAY_IDS, true );
	}

	/**
	 * Register a new PayID for the order's total and store it on the
	 * order. Any earlier request for the order is deleted first, so an
	 * order can only ever be paid to its latest PayID.
	 *
	 * @param WC_Order $order        Order.
	 * @param Client   $client       API client.
	 * @param int      $window_hours Hours the PayID stays payable.
	 * @param string   $payid_suffix Domain for AzuPay-generated PayIDs, or '' for the account default.
	 * @return true|WP_Error
	 */
	public static function create( WC_Order $order, Client $client, int $window_hours, string $payid_suffix ): bool|WP_Error {
		self::delete_remote( $order, $client );

		$amount         = round( (float) $order->get_total(), 2 );
		$transaction_id = sprintf( 'RX-%d-%s', $order->get_id(), wp_generate_password( 8, false ) );
		$expires_at     = time() + max( 1, $window_hours ) * HOUR_IN_SECONDS;
		$token          = wp_generate_password( 40, false );
		$description    = self::description( $order );

		$payment_request = array(
			'clientTransactionId'   => $transaction_id,
			'paymentDescription'    => $description,
			'paymentAmount'         => $amount,
			'paymentExpiryDatetime' => gmdate( 'Y-m-d\TH:i:s\Z', $expires_at ),
		);
		if ( '' !== $payid_suffix ) {
			$payment_request['payIDSuffix'] = $payid_suffix;
		}

		$webhook_url = self::webhook_url();
		if ( null !== $webhook_url ) {
			$payment_request['paymentNotification'] = array(
				'paymentNotificationEndpointUrl' => $webhook_url,
				'paymentNotificationAuthorizationHeaderValue' => $token,
			);
		}

		$response = $client->create_payment_request( $payment_request );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$request = is_array( $response['PaymentRequest'] ?? null ) ? $response['PaymentRequest'] : array();
		$status  = Client::status_of( $response );
		$payid   = (string) ( $request['payID'] ?? '' );
		$id      = (string) ( $status['paymentRequestId'] ?? '' );

		if ( '' === $payid || '' === $id ) {
			Logger::error( sprintf( 'AzuPay create for order %d returned no PayID/paymentRequestId.', $order->get_id() ) );
			return new WP_Error( 'rx_azupay_response', __( 'The payment provider returned an incomplete response.', 'rx-azupay' ) );
		}

		$order->update_meta_data( self::META_REQUEST_ID, $id );
		$order->update_meta_data( self::META_TRANSACTION_ID, $transaction_id );
		$order->update_meta_data( self::META_PAYID, $payid );
		$order->update_meta_data( self::META_AMOUNT, (string) $amount );
		$order->update_meta_data( self::META_DESCRIPTION, $description );
		$order->update_meta_data( self::META_CHECKOUT_URL, esc_url_raw( (string) ( $request['checkoutUrl'] ?? '' ) ) );
		$order->update_meta_data( self::META_EXPIRES_AT, (string) $expires_at );
		$order->update_meta_data( self::META_WEBHOOK_TOKEN, $token );
		$order->update_meta_data( self::META_STATUS, (string) ( $status['status'] ?? 'WAITING' ) );
		$order->save();

		as_unschedule_all_actions( self::EXPIRY_ACTION, array( $order->get_id() ), 'rx-azupay' );
		as_schedule_single_action(
			$expires_at + self::EXPIRY_GRACE_MINUTES * MINUTE_IN_SECONDS,
			self::EXPIRY_ACTION,
			array( $order->get_id() ),
			'rx-azupay'
		);

		Logger::info( sprintf( 'Order %d: PayID %s registered (request %s, %s).', $order->get_id(), $payid, $id, $client->is_sandbox() ? 'UAT' : 'production' ) );

		return true;
	}

	/**
	 * Apply an AzuPay status to its order. Idempotent: an already-paid
	 * order is left alone, so webhook retries and poll races are harmless.
	 *
	 * @param WC_Order            $order  Order.
	 * @param array<string,mixed> $status PaymentRequestStatus object.
	 */
	public static function apply_status( WC_Order $order, array $status ): void {
		$request_id = (string) ( $status['paymentRequestId'] ?? '' );
		if ( '' === $request_id || $request_id !== $order->get_meta( self::META_REQUEST_ID ) ) {
			Logger::error( sprintf( 'Order %d: ignored status for request "%s", not the order\'s current request.', $order->get_id(), $request_id ) );
			return;
		}

		if ( ! self::lock( $order->get_id() ) ) {
			return;
		}

		try {
			// Re-read under the lock: another path may have just settled it.
			$order = wc_get_order( $order->get_id() );
			if ( ! $order instanceof WC_Order ) {
				return;
			}

			$state = (string) ( $status['status'] ?? '' );
			if ( $state === $order->get_meta( self::META_STATUS ) && 'COMPLETE' !== $state ) {
				return;
			}

			$order->update_meta_data( self::META_STATUS, $state );
			$order->save_meta_data();

			switch ( $state ) {
				case 'COMPLETE':
					self::complete( $order, $status );
					break;

				case 'EXPIRED':
					if ( ! $order->is_paid() && $order->has_status( array( 'on-hold', 'pending' ) ) ) {
						$order->update_status( 'cancelled', __( 'PayID payment window expired without payment.', 'rx-azupay' ) );
					}
					break;

				case 'RETURN_IN_PROGRESS':
				case 'RETURN_COMPLETE':
				case 'RETURN_FAILED':
					$order->add_order_note(
						sprintf(
							/* translators: %s: AzuPay status, e.g. RETURN_COMPLETE. */
							__( 'AzuPay reports the PayID payment status as %s.', 'rx-azupay' ),
							$state
						)
					);
					break;
			}
		} finally {
			self::unlock( $order->get_id() );
		}
	}

	/**
	 * Fetch the order's request from AzuPay and apply it. Throttled per
	 * order unless $force.
	 *
	 * @param WC_Order $order Order.
	 * @param bool     $force Skip the throttle (scheduled checks).
	 * @return bool False when skipped or the fetch failed.
	 */
	public static function refresh( WC_Order $order, bool $force = false ): bool {
		$request_id = (string) $order->get_meta( self::META_REQUEST_ID );
		$client     = Client::from_config();
		if ( '' === $request_id || null === $client ) {
			return false;
		}

		$throttle_key = 'rx_azupay_refresh_' . $order->get_id();
		if ( ! $force && false !== get_transient( $throttle_key ) ) {
			return false;
		}
		set_transient( $throttle_key, 1, self::REFRESH_THROTTLE );

		$response = $client->get_payment_request( $request_id );
		if ( is_wp_error( $response ) ) {
			return false;
		}

		self::apply_status( $order, Client::status_of( $response ) );

		return true;
	}

	/**
	 * Action Scheduler callback: settle an order whose PayID window has
	 * passed. Normally the remote status is EXPIRED and the order gets
	 * cancelled (restocking it); a late COMPLETE marks it paid instead.
	 *
	 * @param int $order_id Order id.
	 */
	public static function expiry_check( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order || $order->is_paid() || ! $order->has_status( array( 'on-hold', 'pending' ) ) ) {
			return;
		}

		if ( ! self::refresh( $order, true ) ) {
			// AzuPay unreachable — try again rather than leave stock held forever.
			as_schedule_single_action( time() + 15 * MINUTE_IN_SECONDS, self::EXPIRY_ACTION, array( $order_id ), 'rx-azupay' );
		}
	}

	/**
	 * Delete the order's request on AzuPay (de-registering its PayID)
	 * unless it has already finished. Used when an order is cancelled or
	 * a fresh request replaces it.
	 *
	 * @param WC_Order    $order  Order.
	 * @param Client|null $client API client, or null to build one from config.
	 */
	public static function delete_remote( WC_Order $order, ?Client $client = null ): void {
		$request_id = (string) $order->get_meta( self::META_REQUEST_ID );
		$client     = $client ?? Client::from_config();
		if ( '' === $request_id || null === $client || 'WAITING' !== $order->get_meta( self::META_STATUS ) ) {
			return;
		}

		if ( ! is_wp_error( $client->delete_payment_request( $request_id ) ) ) {
			$order->update_meta_data( self::META_STATUS, 'DELETED' );
			$order->save_meta_data();
			Logger::info( sprintf( 'Order %d: request %s deleted, PayID de-registered.', $order->get_id(), $request_id ) );
		}
	}

	/**
	 * Find the order a clientTransactionId belongs to.
	 *
	 * @param string $transaction_id clientTransactionId from AzuPay.
	 */
	public static function order_for_transaction( string $transaction_id ): ?WC_Order {
		if ( ! preg_match( self::TRANSACTION_ID_PATTERN, $transaction_id, $m ) ) {
			return null;
		}

		$order = wc_get_order( (int) $m[1] );
		if ( ! $order instanceof WC_Order || ! hash_equals( (string) $order->get_meta( self::META_TRANSACTION_ID ), $transaction_id ) ) {
			return null;
		}

		return $order;
	}

	/**
	 * The public webhook URL AzuPay should call, or null when this site
	 * can't receive one. AzuPay requires HTTPS (TLS 1.2, public CA) and a
	 * path ending in /paymentRequestStatus; a local http:// site gets no
	 * webhook and relies on the thank-you page poll instead. Filterable so
	 * a tunnel URL can be used for local webhook testing.
	 */
	public static function webhook_url(): ?string {
		$url = (string) apply_filters( 'rx_azupay_webhook_url', rest_url( RestController::NAMESPACE . '/paymentRequestStatus' ) );

		return str_starts_with( $url, 'https://' ) ? $url : null;
	}

	/**
	 * Mark the order paid, provided the amount received covers what was
	 * requested.
	 *
	 * @param WC_Order            $order  Order.
	 * @param array<string,mixed> $status PaymentRequestStatus object.
	 */
	private static function complete( WC_Order $order, array $status ): void {
		if ( $order->is_paid() ) {
			return;
		}

		$requested  = (float) $order->get_meta( self::META_AMOUNT );
		$received   = isset( $status['amountReceived'] ) ? (float) $status['amountReceived'] : $requested;
		$settled_by = (string) ( $status['settledBy'] ?? '' );
		$request_id = (string) $status['paymentRequestId'];

		if ( $received + 0.005 < $requested ) {
			$order->add_order_note(
				sprintf(
					/* translators: 1: amount received, 2: amount requested. */
					__( 'PayID payment received but short: %1$s of %2$s. Order left on hold — check AzuPay before fulfilling.', 'rx-azupay' ),
					wc_price( $received, array( 'currency' => $order->get_currency() ) ),
					wc_price( $requested, array( 'currency' => $order->get_currency() ) )
				)
			);
			Logger::error( sprintf( 'Order %d: short payment %.2f of %.2f.', $order->get_id(), $received, $requested ) );
			return;
		}

		if ( '' !== $settled_by ) {
			$order->update_meta_data( self::META_SETTLED_BY, $settled_by );
		}
		$order->add_order_note(
			sprintf(
				/* translators: 1: amount, 2: settlement method (PayID, PayTo, BSBAndAccountNumber), 3: AzuPay paymentRequestId. */
				__( 'PayID payment of %1$s received via %2$s (AzuPay request %3$s).', 'rx-azupay' ),
				wc_price( $received, array( 'currency' => $order->get_currency() ) ),
				'' !== $settled_by ? $settled_by : 'PayID',
				$request_id
			)
		);
		$order->payment_complete( $request_id );

		as_unschedule_all_actions( self::EXPIRY_ACTION, array( $order->get_id() ), 'rx-azupay' );
		Logger::info( sprintf( 'Order %d: paid (%.2f, %s).', $order->get_id(), $received, $settled_by ) );
	}

	/**
	 * The paymentDescription, shown in the customer's banking app when they
	 * look up the PayID. 5–140 chars; kept under 110 to avoid truncation,
	 * and no personal information.
	 *
	 * @param WC_Order $order Order.
	 */
	private static function description( WC_Order $order ): string {
		$text = sprintf(
			/* translators: 1: site name, 2: order number. */
			__( '%1$s order %2$s', 'rx-azupay' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$order->get_order_number()
		);

		return mb_substr( str_pad( trim( $text ), 5, '.' ), 0, 110 );
	}

	/**
	 * Take a short-lived per-order lock. add_option() is an atomic INSERT
	 * on a unique key, so only one caller wins; a lock older than a minute
	 * is treated as abandoned.
	 *
	 * @param int $order_id Order id.
	 */
	private static function lock( int $order_id ): bool {
		$key = 'rx_azupay_lock_' . $order_id;
		if ( add_option( $key, (string) time(), '', false ) ) {
			return true;
		}

		if ( (int) get_option( $key ) < time() - MINUTE_IN_SECONDS ) {
			update_option( $key, (string) time(), false );
			return true;
		}

		return false;
	}

	/**
	 * Release the per-order lock.
	 *
	 * @param int $order_id Order id.
	 */
	private static function unlock( int $order_id ): void {
		delete_option( 'rx_azupay_lock_' . $order_id );
	}
}
