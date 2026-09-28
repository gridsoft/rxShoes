<?php
/**
 * Plugin bootstrap: registers the PayID gateway and everything around it
 * — webhook/status routes, the expiry check, cancelling the PayID when an
 * order is cancelled, and the payment instructions on the thank-you page,
 * My Account order view and on-hold email.
 *
 * Markup lives in overridable templates (templates/, override in the
 * theme at rx-azupay/). assets/css/payid.css styles them with the
 * theme's design tokens where present and plain fallbacks otherwise, so
 * the plugin works under any theme.
 *
 * @package RX\AzuPay
 */

declare(strict_types=1);

namespace RX\AzuPay;

use WC_Email;
use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Wires the AzuPay gateway into WordPress/WooCommerce.
 */
final class Plugin {

	/**
	 * Add hooks. Called once, on plugins_loaded, after WooCommerce is
	 * confirmed active.
	 */
	public function boot(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'payid_first' ), 20 );
		add_action( 'rest_api_init', array( new RestController(), 'register_routes' ) );
		if ( Simulator::enabled() ) {
			add_action( 'rest_api_init', array( new Simulator(), 'register_routes' ) );
		}
		add_action( PaymentRequests::EXPIRY_ACTION, array( PaymentRequests::class, 'expiry_check' ) );
		add_action( 'woocommerce_order_status_cancelled', array( $this, 'on_cancelled' ), 10, 2 );

		add_action( 'woocommerce_thankyou_' . PayIdGateway::ID, array( $this, 'render_thankyou' ) );
		add_action( 'woocommerce_view_order', array( $this, 'render_view_order' ), 5 );
		add_action( 'woocommerce_email_before_order_table', array( $this, 'render_email' ), 10, 4 );
	}

	/**
	 * Register the gateway classes with WooCommerce. The test card
	 * stand-in only exists in simulator mode (never on production).
	 *
	 * @param array<int,mixed> $gateways Gateway class names/instances.
	 * @return array<int,mixed>
	 */
	public function add_gateways( array $gateways ): array {
		$gateways[] = PayIdGateway::class;
		if ( Simulator::enabled() ) {
			$gateways[] = TestCardGateway::class;
		}

		return $gateways;
	}

	/**
	 * List PayID first at checkout, so it's the default choice (WooCommerce
	 * preselects the first available method) — PayID is the method the
	 * rotation discount needs. The admin's saved gateway order still
	 * applies to everything after it.
	 *
	 * @param array<string,\WC_Payment_Gateway> $gateways Available gateways, keyed by id.
	 * @return array<string,\WC_Payment_Gateway>
	 */
	public function payid_first( array $gateways ): array {
		if ( isset( $gateways[ PayIdGateway::ID ] ) ) {
			$gateways = array( PayIdGateway::ID => $gateways[ PayIdGateway::ID ] ) + $gateways;
		}

		return $gateways;
	}

	/**
	 * De-register the PayID when an unpaid order is cancelled (by an
	 * admin, or the customer), so it can no longer be paid.
	 *
	 * @param int      $order_id Order id.
	 * @param WC_Order $order    Order.
	 */
	public function on_cancelled( int $order_id, WC_Order $order ): void {
		unset( $order_id );

		if ( PaymentRequests::is_azupay_order( $order ) && ! $order->is_paid() ) {
			PaymentRequests::delete_remote( $order );
			as_unschedule_all_actions( PaymentRequests::EXPIRY_ACTION, array( $order->get_id() ), 'rx-azupay' );
		}
	}

	/**
	 * Payment instructions on the order-received page.
	 *
	 * @param int $order_id Order id.
	 */
	public function render_thankyou( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order ) {
			$this->render_instructions( $order );
		}
	}

	/**
	 * Payment instructions on My Account → Orders → View, while unpaid.
	 *
	 * @param int $order_id Order id.
	 */
	public function render_view_order( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( $order instanceof WC_Order && PaymentRequests::is_azupay_order( $order ) && $order->has_status( 'on-hold' ) ) {
			$this->render_instructions( $order );
		}
	}

	/**
	 * PayID details in the customer's on-hold email, so they can pay
	 * later without finding the thank-you page again.
	 *
	 * @param WC_Order $order         Order.
	 * @param bool     $sent_to_admin Whether the email goes to the store.
	 * @param bool     $plain_text    Plain-text email.
	 * @param WC_Email $email         Email object.
	 */
	public function render_email( $order, $sent_to_admin, $plain_text, $email = null ): void {
		unset( $email );

		if ( $sent_to_admin || ! PaymentRequests::is_azupay_order( $order )
			|| ! $order->has_status( 'on-hold' ) || '' === $order->get_meta( PaymentRequests::META_PAYID ) ) {
			return;
		}

		wc_get_template(
			$plain_text ? 'emails/plain/payid-instructions.php' : 'emails/payid-instructions.php',
			self::template_args( $order ),
			'rx-azupay/',
			RX_AZUPAY_DIR . '/templates/'
		);
	}

	/**
	 * Instructions stylesheet, only on the two pages that can show them.
	 */
	public function enqueue_styles(): void {
		if ( ! is_wc_endpoint_url( 'order-received' ) && ! is_wc_endpoint_url( 'view-order' ) ) {
			return;
		}

		$file = RX_AZUPAY_DIR . '/assets/css/payid.css';
		wp_enqueue_style(
			'rx-azupay-payid',
			RX_AZUPAY_URL . 'assets/css/payid.css',
			array(),
			file_exists( $file ) ? (string) filemtime( $file ) : RX_AZUPAY_VERSION
		);
	}

	/**
	 * Render the on-page instructions block and its status-poll script.
	 *
	 * @param WC_Order $order Order.
	 */
	private function render_instructions( WC_Order $order ): void {
		if ( '' === $order->get_meta( PaymentRequests::META_PAYID ) ) {
			return;
		}

		$file = RX_AZUPAY_DIR . '/assets/js/payid.js';
		wp_enqueue_script(
			'rx-azupay-payid',
			RX_AZUPAY_URL . 'assets/js/payid.js',
			array(),
			file_exists( $file ) ? (string) filemtime( $file ) : RX_AZUPAY_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		wc_get_template( 'payid-instructions.php', self::template_args( $order ), 'rx-azupay/', RX_AZUPAY_DIR . '/templates/' );
	}

	/**
	 * Variables shared by the page and email templates.
	 *
	 * @param WC_Order $order Order.
	 * @return array<string,mixed>
	 */
	private static function template_args( WC_Order $order ): array {
		$expires_at = (int) $order->get_meta( PaymentRequests::META_EXPIRES_AT );

		if ( $order->is_paid() ) {
			$state = 'paid';
		} elseif ( $order->has_status( 'on-hold' ) && $expires_at > time() ) {
			$state = 'waiting';
		} else {
			$state = 'closed';
		}

		return array(
			'order'        => $order,
			'state'        => $state,
			'payid'        => (string) $order->get_meta( PaymentRequests::META_PAYID ),
			'amount'       => (float) $order->get_meta( PaymentRequests::META_AMOUNT ),
			'reference'    => (string) $order->get_meta( PaymentRequests::META_DESCRIPTION ),
			'checkout_url' => (string) $order->get_meta( PaymentRequests::META_CHECKOUT_URL ),
			'expires_at'   => $expires_at,
			'expires_text' => $expires_at ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $expires_at ) : '',
			'status_url'   => add_query_arg(
				'key',
				$order->get_order_key(),
				rest_url( RestController::NAMESPACE . '/orders/' . $order->get_id() . '/status' )
			),
			// Simulator mode only: base URL for the "Simulate payment/expiry" buttons.
			'simulate_url' => Simulator::enabled()
				? rest_url( RestController::NAMESPACE . '/simulate/' . $order->get_id() . '/' )
				: '',
		);
	}
}
