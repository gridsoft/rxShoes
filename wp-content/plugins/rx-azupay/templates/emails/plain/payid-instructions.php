<?php
/**
 * PayID payment instructions in the customer's on-hold email (plain text).
 *
 * Override by copying to yourtheme/rx-azupay/emails/plain/payid-instructions.php.
 *
 * @package RX\AzuPay
 *
 * @var WC_Order $order        Order.
 * @var string   $payid        PayID to pay to.
 * @var float    $amount       Amount to pay (AUD).
 * @var string   $reference    Description the customer's bank shows for the PayID.
 * @var string   $expires_text Formatted expiry date/time.
 */

defined( 'ABSPATH' ) || exit;

echo esc_html( strtoupper( __( 'Complete your payment by PayID', 'rx-azupay' ) ) ) . "\n\n";
echo esc_html__( 'Your order is reserved until you pay. Open your banking app, choose Pay → PayID, and use these details:', 'rx-azupay' ) . "\n\n";
echo esc_html__( 'PayID (email)', 'rx-azupay' ) . ': ' . esc_html( $payid ) . "\n";
echo esc_html__( 'Amount', 'rx-azupay' ) . ': ' . esc_html( html_entity_decode( wp_strip_all_tags( wc_price( $amount, array( 'currency' => $order->get_currency() ) ) ), ENT_QUOTES, 'UTF-8' ) ) . "\n";
echo esc_html__( 'Your bank will show', 'rx-azupay' ) . ': ' . esc_html( $reference ) . "\n\n";
printf(
	/* translators: %s: expiry date and time. */
	esc_html__( 'Pay the exact amount. This PayID is for this order only and expires %s — unpaid orders are then cancelled.', 'rx-azupay' ),
	esc_html( $expires_text )
);
echo "\n\n----------------------------------------\n\n";
