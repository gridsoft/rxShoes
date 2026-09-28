<?php
/**
 * PayID payment gateway, via AzuPay.
 *
 * Checkout flow (modelled on WooCommerce's BACS gateway, PROJECT.md §8):
 * placing the order registers a single-use PayID for the exact order
 * total, puts the order on hold, and sends the customer to the thank-you
 * page, which shows the PayID to pay from their banking app. The order
 * moves to processing when AzuPay confirms payment (webhook, or the
 * thank-you page's poll), or is cancelled and restocked when the PayID
 * expires unpaid.
 *
 * Gateway id `azupay_payid` is fixed: the bundle discount's
 * payment-method rule keys off it (PROJECT.md §6.2).
 *
 * @package RX\AzuPay
 */

declare(strict_types=1);

namespace RX\AzuPay;

use WC_Order;
use WC_Payment_Gateway;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The `azupay_payid` WooCommerce gateway.
 */
class PayIdGateway extends WC_Payment_Gateway {

	public const ID = 'azupay_payid';

	/**
	 * Hours a new PayID stays payable, when not set in the settings.
	 */
	private const DEFAULT_WINDOW_HOURS = 24;

	/**
	 * Set up the gateway and load its settings.
	 */
	public function __construct() {
		$this->id                 = self::ID;
		$this->has_fields         = false;
		$this->method_title       = __( 'PayID (AzuPay)', 'rx-azupay' );
		$this->method_description = __( 'Customers pay by PayID from their banking app. A unique PayID is registered with AzuPay for each order, and the order is confirmed automatically once the payment arrives.', 'rx-azupay' );
		$this->supports           = array( 'products', 'refunds' );

		$this->init_form_fields();
		$this->init_settings();

		$this->title       = (string) $this->get_option( 'title' );
		$this->description = (string) $this->get_option( 'description' );

		// @phpstan-ignore return.void (standard WC gateway pattern, WC ignores the returned bool)
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Admin settings. Credentials are deliberately absent — they're
	 * wp-config constants (see Client).
	 */
	public function init_form_fields(): void {
		$this->form_fields = array(
			'enabled'      => array(
				'title'   => __( 'Enable/Disable', 'rx-azupay' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable PayID payments', 'rx-azupay' ),
				'default' => 'no',
			),
			'title'        => array(
				'title'       => __( 'Title', 'rx-azupay' ),
				'type'        => 'text',
				'description' => __( 'Payment method name shown at checkout.', 'rx-azupay' ),
				'default'     => __( 'PayID', 'rx-azupay' ),
				'desc_tip'    => true,
			),
			'description'  => array(
				'title'       => __( 'Description', 'rx-azupay' ),
				'type'        => 'textarea',
				'description' => __( 'Shown under the payment method at checkout.', 'rx-azupay' ),
				'default'     => __( 'Pay instantly from your banking app. After you place your order we\'ll show you a unique PayID to pay to — your order is confirmed as soon as the payment lands.', 'rx-azupay' ),
				'desc_tip'    => true,
			),
			'window_hours' => array(
				'title'             => __( 'Payment window (hours)', 'rx-azupay' ),
				'type'              => 'number',
				'description'       => __( 'How long the order\'s PayID stays payable. Unpaid orders are cancelled and restocked after this.', 'rx-azupay' ),
				'default'           => (string) self::DEFAULT_WINDOW_HOURS,
				'custom_attributes' => array(
					'min'  => '1',
					'max'  => '168',
					'step' => '1',
				),
				'desc_tip'          => true,
			),
			'payid_suffix' => array(
				'title'       => __( 'PayID domain', 'rx-azupay' ),
				'type'        => 'text',
				'description' => __( 'Optional. Domain for generated PayIDs (e.g. pay.example.com.au). Must be set up on the AzuPay account first. Leave blank for the account default.', 'rx-azupay' ),
				'default'     => '',
				'desc_tip'    => true,
			),
			'debug'        => array(
				'title'       => __( 'Debug log', 'rx-azupay' ),
				'type'        => 'checkbox',
				'label'       => __( 'Log API calls', 'rx-azupay' ),
				'description' => __( 'Written to WooCommerce → Status → Logs (source "rx-azupay"). Errors are always logged.', 'rx-azupay' ),
				'default'     => 'no',
			),
		);
	}

	/**
	 * Settings screen, with the credential and webhook status above the
	 * fields so it's obvious why the gateway isn't showing at checkout.
	 */
	public function admin_options(): void {
		$client  = Client::from_config();
		$webhook = PaymentRequests::webhook_url();

		echo '<h2>' . esc_html( $this->get_method_title() ) . '</h2>';
		echo wp_kses_post( wpautop( $this->get_method_description() ) );

		if ( null === $client ) {
			echo '<div class="notice notice-error inline"><p>';
			echo wp_kses_post( __( 'AzuPay credentials missing. Define <code>RX_AZUPAY_CLIENT_ID</code> and <code>RX_AZUPAY_SECRET_KEY</code> (and <code>RX_AZUPAY_SANDBOX</code>, <code>false</code> on production only) in wp-config.php. The gateway stays hidden at checkout until then.', 'rx-azupay' ) );
			echo '</p></div>';
		} elseif ( $client->is_simulated() ) {
			echo '<div class="notice notice-warning inline"><p>';
			echo wp_kses_post( __( '<strong>Simulator mode</strong> (<code>RX_AZUPAY_SIMULATE</code>): no calls reach AzuPay. PayIDs are fake, and the order-received page has buttons to simulate the payment or its expiry. Remove the constant before going live; it is ignored when the environment type is production.', 'rx-azupay' ) );
			echo '</p></div>';
		} else {
			echo '<div class="notice notice-info inline"><p>';
			echo esc_html(
				sprintf(
					/* translators: 1: UAT or Production, 2: AzuPay client id. */
					__( 'Connected to AzuPay %1$s as client %2$s.', 'rx-azupay' ),
					$client->is_sandbox() ? 'UAT (sandbox)' : 'Production',
					$client->client_id()
				)
			);
			echo '</p></div>';
		}

		echo '<div class="notice ' . ( null === $webhook ? 'notice-warning' : 'notice-info' ) . ' inline"><p>';
		if ( null === $webhook ) {
			esc_html_e( 'This site is not on HTTPS, so AzuPay can\'t send payment webhooks here. Payments are still confirmed while the customer has the order-received page open, and by the expiry check — fine for local testing, not for production.', 'rx-azupay' );
		} else {
			echo esc_html__( 'Payment webhook URL (sent with each payment request, nothing to configure in AzuPay):', 'rx-azupay' ) . ' <code>' . esc_html( $webhook ) . '</code>';
		}
		echo '</p></div>';

		echo '<table class="form-table">';
		$this->generate_settings_html();
		echo '</table>';
	}

	/**
	 * Only offer PayID when AzuPay is configured and the order is in AUD
	 * (PayID is an Australian NPP service).
	 */
	public function is_available(): bool {
		return parent::is_available()
			&& Client::is_configured()
			&& 'AUD' === get_woocommerce_currency();
	}

	/**
	 * Register the PayID and put the order on hold awaiting payment.
	 *
	 * @param int $order_id Order id.
	 * @return array<string,string>
	 */
	public function process_payment( $order_id ): array {
		$order  = wc_get_order( $order_id );
		$client = Client::from_config();

		if ( ! $order instanceof WC_Order || null === $client ) {
			wc_add_notice( __( 'PayID is unavailable right now. Please choose another payment method.', 'rx-azupay' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$created = PaymentRequests::create(
			$order,
			$client,
			(int) $this->get_option( 'window_hours', (string) self::DEFAULT_WINDOW_HOURS ),
			trim( (string) $this->get_option( 'payid_suffix' ) )
		);

		if ( is_wp_error( $created ) ) {
			$order->add_order_note(
				sprintf(
					/* translators: %s: error from AzuPay. */
					__( 'Could not create the PayID: %s', 'rx-azupay' ),
					$created->get_error_message()
				)
			);
			wc_add_notice( __( 'We couldn\'t set up your PayID payment. Please try again, or choose another payment method.', 'rx-azupay' ), 'error' );
			return array( 'result' => 'failure' );
		}

		$order->update_status( 'on-hold', __( 'Awaiting PayID payment.', 'rx-azupay' ) );
		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}

	/**
	 * Refund to the payer's account through AzuPay.
	 *
	 * @param int        $order_id Order id.
	 * @param float|null $amount   Amount to refund.
	 * @param string     $reason   Reason entered by the admin.
	 * @return bool|WP_Error
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order  = wc_get_order( $order_id );
		$client = Client::from_config();
		if ( ! $order instanceof WC_Order || null === $client ) {
			return new WP_Error( 'rx_azupay_refund', __( 'AzuPay is not configured.', 'rx-azupay' ) );
		}

		$request_id = (string) $order->get_meta( PaymentRequests::META_REQUEST_ID );
		if ( '' === $request_id || ! $order->is_paid() ) {
			return new WP_Error( 'rx_azupay_refund', __( 'This order has no completed PayID payment to refund.', 'rx-azupay' ) );
		}

		$amount = null === $amount ? (float) $order->get_total() : (float) $amount;
		if ( $amount <= 0 ) {
			return new WP_Error( 'rx_azupay_refund', __( 'Refund amount must be greater than zero.', 'rx-azupay' ) );
		}

		$response = $client->refund(
			$request_id,
			$amount,
			sprintf( 'RX-%d-R%s', $order->get_id(), wp_generate_password( 6, false ) ),
			self::refund_description( (string) $reason )
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$order->add_order_note(
			sprintf(
				/* translators: %s: refund amount. */
				__( 'PayID refund of %s sent to AzuPay. It is returned to the account the customer paid from.', 'rx-azupay' ),
				wc_price( $amount, array( 'currency' => $order->get_currency() ) )
			)
		);

		return true;
	}

	/**
	 * Squeeze an admin's refund reason into AzuPay's allowed description:
	 * 5–280 chars of letters, digits, spaces and /\-?:().,'+ . Null when
	 * nothing usable is left (the field is optional).
	 *
	 * @param string $reason Admin-entered reason.
	 */
	private static function refund_description( string $reason ): ?string {
		$clean = trim( (string) preg_replace( '/\s+/', ' ', (string) preg_replace( "~[^A-Za-z0-9 /\\\\\\-?:().,'+]~", '', wp_strip_all_tags( $reason ) ) ) );

		return mb_strlen( $clean ) >= 5 ? mb_substr( $clean, 0, 280 ) : null;
	}
}
