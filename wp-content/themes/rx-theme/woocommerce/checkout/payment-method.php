<?php
/**
 * Output a single payment method
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/checkout/payment-method.php,
 * based on core version 3.5.0. Adds the mockup's "Recommended for
 * discount / N% saving applied" badge to the PayID row while the cart
 * has a rotation discount (inc/checkout.php). Everything else is core's.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- the gateway title / icon / fields markup is echoed exactly as WooCommerce's own template does.

$rx_theme_rotation = rx_theme_checkout_rotation();
$rx_theme_discount = $rx_theme_rotation['available'] > 0 && $rx_theme_rotation['pairs'] > 1;
$rx_theme_badge    = $rx_theme_discount && 'azupay_payid' === $gateway->id;
// Methods that don't qualify for the rotation discount say so (mockup: "Discount will be removed").
$rx_theme_loses = $rx_theme_discount && ! in_array( $gateway->id, rx_theme_bundle_discount_payment_methods(), true );
?>
<li class="wc_payment_method payment_method_<?php echo esc_attr( $gateway->id ); ?><?php echo $rx_theme_badge ? ' rx-payment-method--recommended' : ''; ?>">
	<input id="payment_method_<?php echo esc_attr( $gateway->id ); ?>" type="radio" class="input-radio" name="payment_method" value="<?php echo esc_attr( $gateway->id ); ?>" <?php checked( $gateway->chosen, true ); ?> data-order_button_text="<?php echo esc_attr( $gateway->order_button_text ); ?>" />

	<label for="payment_method_<?php echo esc_attr( $gateway->id ); ?>">
		<?php echo $gateway->get_title(); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?> <?php echo $gateway->get_icon(); /* phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped */ ?>
		<?php if ( $rx_theme_badge ) : ?>
			<span class="rx-payment-method__badge">
				<strong><?php esc_html_e( 'Recommended for discount', 'rx-theme' ); ?></strong>
				<?php
				/* translators: %s: discount percentage. */
				echo esc_html( sprintf( __( '%s%% saving applied', 'rx-theme' ), rx_theme_format_percent( $rx_theme_rotation['available'] ) ) );
				?>
			</span>
		<?php elseif ( $rx_theme_loses ) : ?>
			<span class="rx-payment-method__loses"><?php esc_html_e( 'Discount will be removed', 'rx-theme' ); ?></span>
		<?php endif; ?>
	</label>
	<?php if ( $gateway->has_fields() || $gateway->get_description() ) : ?>
		<div class="payment_box payment_method_<?php echo esc_attr( $gateway->id ); ?>" <?php if ( ! $gateway->chosen ) : /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>style="display:none;"<?php endif; /* phpcs:ignore Squiz.ControlStructures.ControlSignature.NewlineAfterOpenBrace */ ?>>
			<?php $gateway->payment_fields(); ?>
		</div>
	<?php endif; ?>
</li>
