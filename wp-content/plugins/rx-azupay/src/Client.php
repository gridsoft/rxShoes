<?php
/**
 * Thin HTTP client for AzuPay's PaymentRequest API.
 *
 * AzuPay has no WooCommerce plugin — it's a raw REST API
 * (developer.azupay.com.au), so this wraps the four calls the gateways
 * need: create, get, delete (de-registers the PayID) and refund.
 *
 * Credentials come from wp-config.php constants, never the database
 * (PROJECT.md §4.3):
 *
 *     define( 'RX_AZUPAY_CLIENT_ID', 'CLIENT1TEST' );   // Differs per environment.
 *     define( 'RX_AZUPAY_SECRET_KEY', '...' );
 *     define( 'RX_AZUPAY_SANDBOX', true );              // false on production only.
 *
 * RX_AZUPAY_SANDBOX defaults to true, so an environment that forgets it
 * talks to UAT rather than taking real money.
 *
 * @package RX\AzuPay
 */

declare(strict_types=1);

namespace RX\AzuPay;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AzuPay API calls. Every method returns the decoded response body or a
 * WP_Error carrying AzuPay's failureReason, so callers never see raw
 * HTTP.
 */
final class Client {

	private const URL_UAT        = 'https://api-uat.azupay.com.au/v1';
	private const URL_PRODUCTION = 'https://api.azupay.com.au/v1';

	/**
	 * Seconds to wait for AzuPay before giving up. Create runs inside the
	 * customer's place-order request, so this is kept short.
	 */
	private const TIMEOUT = 20;

	/**
	 * Build a client from the wp-config constants.
	 *
	 * @return self|null Null when the constants aren't set.
	 */
	public static function from_config(): ?self {
		if ( ! self::is_configured() ) {
			return null;
		}

		if ( Simulator::enabled() ) {
			return new self( 'SIMULATOR', '', true, true );
		}

		return new self(
			(string) constant( 'RX_AZUPAY_CLIENT_ID' ),
			(string) constant( 'RX_AZUPAY_SECRET_KEY' ),
			! defined( 'RX_AZUPAY_SANDBOX' ) || (bool) constant( 'RX_AZUPAY_SANDBOX' )
		);
	}

	/**
	 * Whether the credential constants are present and non-empty, or
	 * simulator mode is on.
	 */
	public static function is_configured(): bool {
		return Simulator::enabled() || (
			defined( 'RX_AZUPAY_CLIENT_ID' ) && '' !== (string) constant( 'RX_AZUPAY_CLIENT_ID' )
			&& defined( 'RX_AZUPAY_SECRET_KEY' ) && '' !== (string) constant( 'RX_AZUPAY_SECRET_KEY' )
		);
	}

	/**
	 * Constructor.
	 *
	 * @param string $client_id  AzuPay clientId (per environment).
	 * @param string $secret_key AzuPay secret key, sent as the Authorization header.
	 * @param bool   $sandbox    Use the UAT host.
	 * @param bool   $simulated  Answer from Simulator instead of calling AzuPay.
	 */
	public function __construct(
		private string $client_id,
		private string $secret_key,
		private bool $sandbox,
		private bool $simulated = false
	) {}

	/**
	 * Whether this client is the local simulator.
	 */
	public function is_simulated(): bool {
		return $this->simulated;
	}

	/**
	 * The clientId every PaymentRequest must carry.
	 */
	public function client_id(): string {
		return $this->client_id;
	}

	/**
	 * Whether this client talks to UAT.
	 */
	public function is_sandbox(): bool {
		return $this->sandbox;
	}

	/**
	 * POST /paymentRequest. Not retried: a timeout may still have
	 * registered a PayID, and a blind retry would register a second one.
	 *
	 * @param array<string,mixed> $payment_request The PaymentRequest object (clientId is added here).
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_payment_request( array $payment_request ): array|WP_Error {
		$payment_request['clientId'] = $this->client_id;

		return $this->request( 'POST', '/paymentRequest', array(), array( 'PaymentRequest' => $payment_request ) );
	}

	/**
	 * GET /paymentRequest?id=. Retried once on a transport error — it's a
	 * read, so that's safe.
	 *
	 * @param string $payment_request_id AzuPay's paymentRequestId.
	 * @return array<string,mixed>|WP_Error
	 */
	public function get_payment_request( string $payment_request_id ): array|WP_Error {
		$response = $this->request( 'GET', '/paymentRequest', array( 'id' => $payment_request_id ) );

		if ( is_wp_error( $response ) && 'rx_azupay_http' === $response->get_error_code() ) {
			$response = $this->request( 'GET', '/paymentRequest', array( 'id' => $payment_request_id ) );
		}

		return $response;
	}

	/**
	 * DELETE /paymentRequest?id= — cancels the request and de-registers
	 * its PayID so nothing more can be paid to it.
	 *
	 * @param string $payment_request_id AzuPay's paymentRequestId.
	 * @return array<string,mixed>|WP_Error Empty array on success (AzuPay returns 204).
	 */
	public function delete_payment_request( string $payment_request_id ): array|WP_Error {
		return $this->request( 'DELETE', '/paymentRequest', array( 'id' => $payment_request_id ) );
	}

	/**
	 * POST /paymentRequest/refund. All arguments go in the query string,
	 * per AzuPay's spec; there is no body.
	 *
	 * @param string      $payment_request_id AzuPay's paymentRequestId.
	 * @param float       $amount             Amount to refund, AUD.
	 * @param string      $client_refund_id   Our reference for this refund.
	 * @param string|null $description        Reason (5–280 chars of AzuPay's allowed set), or null.
	 * @return array<string,mixed>|WP_Error
	 */
	public function refund( string $payment_request_id, float $amount, string $client_refund_id, ?string $description ): array|WP_Error {
		$query = array(
			'id'             => $payment_request_id,
			'refundAmount'   => number_format( $amount, 2, '.', '' ),
			'clientRefundID' => $client_refund_id,
		);
		if ( null !== $description ) {
			$query['description'] = $description;
		}

		return $this->request( 'POST', '/paymentRequest/refund', $query );
	}

	/**
	 * Pull the status half out of a PaymentRequest response. Create and
	 * the webhook nest it under PaymentRequestStatus; GET is documented
	 * as a merged object, so fall back to the top level.
	 *
	 * @param array<string,mixed> $response Decoded response or webhook body.
	 * @return array<string,mixed>
	 */
	public static function status_of( array $response ): array {
		if ( isset( $response['PaymentRequestStatus'] ) && is_array( $response['PaymentRequestStatus'] ) ) {
			return $response['PaymentRequestStatus'];
		}

		return $response;
	}

	/**
	 * Send one request and decode the result.
	 *
	 * @param string              $method HTTP method.
	 * @param string              $path   Path under /v1.
	 * @param array<string,mixed> $query  Query-string arguments.
	 * @param array<string,mixed> $body   JSON body, or empty for none.
	 * @return array<string,mixed>|WP_Error
	 */
	private function request( string $method, string $path, array $query = array(), array $body = array() ): array|WP_Error {
		$url = ( $this->sandbox ? self::URL_UAT : self::URL_PRODUCTION ) . $path;
		if ( $query ) {
			$url = add_query_arg( array_map( 'rawurlencode', array_map( 'strval', $query ) ), $url );
		}

		$args = array(
			'method'  => $method,
			'timeout' => self::TIMEOUT,
			'headers' => array(
				'Authorization' => $this->secret_key,
				'Accept'        => 'application/json',
			),
		);
		if ( $body ) {
			$args['headers']['Content-Type'] = 'application/json';
			$args['body']                    = (string) wp_json_encode( $body );
		}

		Logger::debug( sprintf( 'AzuPay %s %s', $method, $path ), array( 'query' => $query ) );

		if ( $this->simulated ) {
			return Simulator::handle( $method, $path, $query, $body );
		}

		$response = wp_remote_request( $url, $args );

		if ( is_wp_error( $response ) ) {
			Logger::error( sprintf( 'AzuPay %s %s transport error: %s', $method, $path, $response->get_error_message() ) );
			return new WP_Error( 'rx_azupay_http', $response->get_error_message() );
		}

		$code    = (int) wp_remote_retrieve_response_code( $response );
		$raw     = (string) wp_remote_retrieve_body( $response );
		$decoded = '' === $raw ? array() : json_decode( $raw, true );
		$decoded = is_array( $decoded ) ? $decoded : array();

		if ( $code < 200 || $code >= 300 ) {
			$reason = $decoded['details']['failureReason'] ?? $decoded['message'] ?? $raw;
			$fcode  = $decoded['details']['failureCode'] ?? '';
			Logger::error( sprintf( 'AzuPay %s %s failed (HTTP %d %s): %s', $method, $path, $code, $fcode, $reason ) );

			return new WP_Error( 'rx_azupay_api', (string) $reason, array( 'status' => $code ) );
		}

		return $decoded;
	}
}
