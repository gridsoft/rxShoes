<?php
/**
 * Local stand-in for AzuPay, for clicking through the PayID flow before
 * real UAT credentials exist.
 *
 * Turn on in wp-config.php (no AzuPay credentials needed):
 *
 *     define( 'RX_AZUPAY_SIMULATE', true );
 *
 * Client then sends its requests here instead of over HTTP, so the rest
 * of the plugin runs its real code path. The order-received page gains
 * "Simulate payment" / "Simulate expiry" buttons; paying fires the real
 * webhook handler, with the real per-order token, so that path is
 * exercised too. Fake payment requests live in one option.
 *
 * Refuses to run when WP_ENVIRONMENT_TYPE is 'production'.
 *
 * @package RX\AzuPay
 */

declare(strict_types=1);

namespace RX\AzuPay;

use WC_Order;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fake AzuPay API plus the simulate-payment endpoint.
 */
final class Simulator {

	private const OPTION = 'rx_azupay_simulator_requests';

	/**
	 * Whether simulator mode is on (and allowed on this environment).
	 */
	public static function enabled(): bool {
		return defined( 'RX_AZUPAY_SIMULATE' ) && (bool) constant( 'RX_AZUPAY_SIMULATE' )
			&& 'production' !== wp_get_environment_type();
	}

	/**
	 * Answer one API call the way AzuPay would.
	 *
	 * @param string              $method HTTP method.
	 * @param string              $path   Path under /v1.
	 * @param array<string,mixed> $query  Query-string arguments.
	 * @param array<string,mixed> $body   JSON body.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function handle( string $method, string $path, array $query, array $body ): array|WP_Error {
		$requests = self::all();
		$id       = (string) ( $query['id'] ?? '' );

		if ( 'POST' === $method && '/paymentRequest' === $path ) {
			$request = (array) ( $body['PaymentRequest'] ?? array() );
			$id      = 'SIM' . strtoupper( wp_generate_password( 12, false ) );

			$request['payID']       = strtolower( 'rx-' . wp_generate_password( 8, false ) ) . '@sim.azupay.test';
			$request['checkoutUrl'] = '';
			$requests[ $id ]        = array(
				'PaymentRequest'       => $request,
				'PaymentRequestStatus' => array(
					'paymentRequestId' => $id,
					'status'           => 'WAITING',
					'createdDateTime'  => gmdate( 'c' ),
				),
			);
			self::save( $requests );

			return $requests[ $id ];
		}

		if ( ! isset( $requests[ $id ] ) ) {
			return new WP_Error( 'rx_azupay_api', 'Payment Request not found (simulator).', array( 'status' => 404 ) );
		}

		if ( 'GET' === $method ) {
			return $requests[ $id ];
		}

		if ( 'DELETE' === $method ) {
			unset( $requests[ $id ] );
			self::save( $requests );
			return array();
		}

		if ( 'POST' === $method && '/paymentRequest/refund' === $path ) {
			$requests[ $id ]['PaymentRequestStatus']['status'] = 'RETURN_IN_PROGRESS';
			self::save( $requests );
			return $requests[ $id ];
		}

		return new WP_Error( 'rx_azupay_api', 'Not supported by the simulator.', array( 'status' => 400 ) );
	}

	/**
	 * Register the simulate endpoint. Called on rest_api_init.
	 */
	public function register_routes(): void {
		register_rest_route(
			RestController::NAMESPACE,
			'/simulate/(?P<id>\d+)/(?P<outcome>pay|expire)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'simulate' ),
				'permission_callback' => array( self::class, 'enabled' ),
			)
		);
	}

	/**
	 * Make the order's fake PayID paid or expired, then deliver it the way
	 * AzuPay would: a webhook for a payment, the scheduled expiry check
	 * for an expiry.
	 *
	 * @param WP_REST_Request $request Request (id, outcome, key).
	 */
	public function simulate( WP_REST_Request $request ): WP_REST_Response {
		$order = wc_get_order( (int) $request['id'] );
		if ( ! $order instanceof WC_Order || ! hash_equals( $order->get_order_key(), (string) $request->get_param( 'key' ) ) ) {
			return new WP_REST_Response( array( 'code' => 'not_found' ), 404 );
		}

		$requests = self::all();
		$id       = (string) $order->get_meta( PaymentRequests::META_REQUEST_ID );
		if ( ! isset( $requests[ $id ] ) ) {
			return new WP_REST_Response( array( 'code' => 'no_simulated_request' ), 404 );
		}

		if ( 'pay' === $request['outcome'] ) {
			$requests[ $id ]['PaymentRequestStatus'] = array_merge(
				$requests[ $id ]['PaymentRequestStatus'],
				array(
					'status'            => 'COMPLETE',
					'amountReceived'    => (float) ( $requests[ $id ]['PaymentRequest']['paymentAmount'] ?? 0 ),
					'settledBy'         => 'PayID',
					'completedDatetime' => gmdate( 'c' ),
				)
			);
			self::save( $requests );

			$webhook = new WP_REST_Request( 'POST', '/' . RestController::NAMESPACE . '/paymentRequestStatus' );
			$webhook->set_header( 'content-type', 'application/json' );
			$webhook->set_header( 'authorization', (string) $order->get_meta( PaymentRequests::META_WEBHOOK_TOKEN ) );
			$webhook->set_body( (string) wp_json_encode( $requests[ $id ] ) );
			rest_do_request( $webhook );
		} else {
			$requests[ $id ]['PaymentRequestStatus']['status'] = 'EXPIRED';
			self::save( $requests );
			PaymentRequests::expiry_check( $order->get_id() );
		}

		$order = wc_get_order( $order->get_id() );

		return new WP_REST_Response(
			array(
				'paid'   => $order instanceof WC_Order && $order->is_paid(),
				'status' => $order instanceof WC_Order ? $order->get_status() : '',
			)
		);
	}

	/**
	 * All fake payment requests, keyed by paymentRequestId.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private static function all(): array {
		$requests = get_option( self::OPTION, array() );

		return is_array( $requests ) ? $requests : array();
	}

	/**
	 * Persist the fake payment requests.
	 *
	 * @param array<string,array<string,mixed>> $requests Requests.
	 */
	private static function save( array $requests ): void {
		update_option( self::OPTION, $requests, false );
	}
}
