<?php
/**
 * REST endpoints for AzuPay payments.
 *
 * - POST rx-azupay/v1/paymentRequestStatus — AzuPay's webhook.
 *   AzuPay requires the path to end in /paymentRequestStatus. It retries
 *   up to 45× every 20s until it gets a 200, so this must be idempotent.
 * - GET rx-azupay/v1/orders/{id}/status?key= — polled by the
 *   thank-you page so the customer sees "payment received" without
 *   reloading.
 *
 * @package RX\AzuPay
 */

declare(strict_types=1);

namespace RX\AzuPay;

use WC_Order;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and answers the AzuPay routes.
 */
final class RestController {

	public const NAMESPACE = 'rx-azupay/v1';

	/**
	 * Register the routes. Called on rest_api_init.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/paymentRequestStatus',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'webhook' ),
				// Public by necessity; authenticated inside (see webhook()).
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/orders/(?P<id>\d+)/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'order_status' ),
				// Guests poll too; the order key in the query is the credential.
				'permission_callback' => '__return_true',
				'args'                => array(
					'id'  => array(
						'type'     => 'integer',
						'required' => true,
					),
					'key' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * AzuPay webhook.
	 *
	 * The body is only trusted when the Authorization header matches the
	 * per-order token we sent with the request. Otherwise (a forged call,
	 * or a host that strips the Authorization header) the call is treated
	 * as a nudge: the real status is fetched from AzuPay instead. Either
	 * way a request can't mark an order paid unless AzuPay agrees.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function webhook( WP_REST_Request $request ): WP_REST_Response {
		$body           = (array) $request->get_json_params();
		$transaction_id = (string) ( $body['PaymentRequest']['clientTransactionId'] ?? '' );
		$order          = PaymentRequests::order_for_transaction( $transaction_id );

		if ( null === $order ) {
			// Unknown to this site — ack so AzuPay stops retrying.
			Logger::error( sprintf( 'Webhook for unknown clientTransactionId "%s" ignored.', $transaction_id ) );
			return new WP_REST_Response( array( 'ok' => true ), 200 );
		}

		$token  = (string) $order->get_meta( PaymentRequests::META_WEBHOOK_TOKEN );
		$header = (string) $request->get_header( 'authorization' );
		$status = Client::status_of( $body );

		if ( '' !== $token && hash_equals( $token, $header ) ) {
			Logger::debug( sprintf( 'Order %d: authenticated webhook, status %s.', $order->get_id(), (string) ( $status['status'] ?? '' ) ) );
			PaymentRequests::apply_status( $order, $status );
			return new WP_REST_Response( array( 'ok' => true ), 200 );
		}

		Logger::debug( sprintf( 'Order %d: unauthenticated webhook, verifying with AzuPay.', $order->get_id() ) );
		if ( PaymentRequests::refresh( $order ) ) {
			return new WP_REST_Response( array( 'ok' => true ), 200 );
		}

		// Couldn't verify (throttled or AzuPay unreachable): non-200 so AzuPay retries.
		return new WP_REST_Response( array( 'ok' => false ), 503 );
	}

	/**
	 * Order payment status for the thank-you page. Refreshes from AzuPay
	 * (throttled) while the order is still unpaid, so payment is picked up
	 * even where webhooks can't arrive (local dev, a webhook outage).
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public function order_status( WP_REST_Request $request ): WP_REST_Response {
		$order = wc_get_order( (int) $request['id'] );
		if ( ! $order instanceof WC_Order || ! hash_equals( $order->get_order_key(), (string) $request['key'] ) || ! PaymentRequests::is_azupay_order( $order ) ) {
			return new WP_REST_Response( array( 'code' => 'not_found' ), 404 );
		}

		if ( ! $order->is_paid() && $order->has_status( 'on-hold' ) ) {
			PaymentRequests::refresh( $order );
			$order = wc_get_order( $order->get_id() );
		}

		$response = new WP_REST_Response(
			array(
				'paid'   => $order instanceof WC_Order && $order->is_paid(),
				'status' => $order instanceof WC_Order ? $order->get_status() : '',
			)
		);
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}
}
