<?php
/**
 * PayID payment instructions in the customer's on-hold email (HTML).
 *
 * Override by copying to yourtheme/rx-azupay/emails/payid-instructions.php.
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
?>
<h2><?php esc_html_e( 'Complete your payment by PayID', 'rx-azupay' ); ?></h2>
<p><?php esc_html_e( 'Your order is reserved until you pay. Open your banking app, choose Pay → PayID, and use these details:', 'rx-azupay' ); ?></p>
<table class="td" cellspacing="0" cellpadding="6" border="1" style="width: 100%; margin-bottom: 16px;">
	<tr>
		<th class="td" scope="row" style="text-align: left;"><?php esc_html_e( 'PayID (email)', 'rx-azupay' ); ?></th>
		<td class="td" style="text-align: left;"><strong><?php echo esc_html( $payid ); ?></strong></td>
	</tr>
	<tr>
		<th class="td" scope="row" style="text-align: left;"><?php esc_html_e( 'Amount', 'rx-azupay' ); ?></th>
		<td class="td" style="text-align: left;"><strong><?php echo wp_kses_post( wc_price( $amount, array( 'currency' => $order->get_currency() ) ) ); ?></strong></td>
	</tr>
	<tr>
		<th class="td" scope="row" style="text-align: left;"><?php esc_html_e( 'Your bank will show', 'rx-azupay' ); ?></th>
		<td class="td" style="text-align: left;"><?php echo esc_html( $reference ); ?></td>
	</tr>
</table>
<p>
	<?php
	printf(
		/* translators: %s: expiry date and time. */
		esc_html__( 'Pay the exact amount. This PayID is for this order only and expires %s — unpaid orders are then cancelled.', 'rx-azupay' ),
		'<strong>' . esc_html( $expires_text ) . '</strong>'
	);
	?>
</p>
