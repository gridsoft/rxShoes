<?php
/**
 * "Card (test stand-in)" — a second, non-PayID payment method for local
 * testing only, so the checkout can be tried with a method that does NOT
 * qualify for the PayID-only rotation discount (it stands in for the
 * Stripe card gateway that isn't installed yet).
 *
 * Registered only while simulator mode is on (Simulator::enabled(): the
 * RX_AZUPAY_SIMULATE constant, never on a production environment). It
 * is always enabled while registered and has no settings screen, so
 * nothing about it is stored in the database — moving the site live
 * can't carry it over. Takes no payment: orders go on hold, like a
 * bank transfer.
 *
 * @package RX\AzuPay
 */

declare(strict_types=1);

namespace RX\AzuPay;

use WC_Order;
use WC_Payment_Gateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The `rx_test_card` gateway.
 */
class TestCardGateway extends WC_Payment_Gateway {

	public const ID = 'rx_test_card';

	/**
	 * Set up the gateway. No init_settings(): nothing is read from or
	 * written to the database.
	 */
	public function __construct() {
		$this->id                 = self::ID;
		$this->has_fields         = false;
		$this->method_title       = __( 'Card (test stand-in)', 'rx-azupay' );
		$this->method_description = __( 'Local testing only (AzuPay simulator mode). Takes no payment. Not available on production.', 'rx-azupay' );
		$this->title              = __( 'Card (test stand-in)', 'rx-azupay' );
		$this->description        = __( 'Test only — no payment is taken. Stands in for card payments so the checkout can be tried without PayID.', 'rx-azupay' );
		$this->enabled            = 'yes';
		$this->supports           = array( 'products' );
	}

	/**
	 * No settings screen for this gateway.
	 *
	 * @param array<string,mixed> $form_fields Unused.
	 * @param bool                $output      Unused.
	 */
	public function generate_settings_html( $form_fields = array(), $output = true ): string {
		unset( $form_fields, $output );
		echo '<p>' . esc_html( $this->method_description ) . '</p>';

		return '';
	}

	/**
	 * Available only while simulator mode is on.
	 */
	public function is_available(): bool {
		return Simulator::enabled() && parent::is_available();
	}

	/**
	 * Put the order on hold (no payment taken) and go to the thank-you page.
	 *
	 * @param int $order_id Order id.
	 * @return array<string,string>
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return array( 'result' => 'failure' );
		}

		$order->update_status( 'on-hold', __( 'Test order (Card test stand-in) — no payment taken.', 'rx-azupay' ) );
		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
}
