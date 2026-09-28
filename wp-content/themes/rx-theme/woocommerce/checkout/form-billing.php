<?php
/**
 * Checkout billing information form
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/checkout/form-billing.php,
 * based on core version 3.6.0. The billing fields are split into the
 * mockup's two numbered sections — "1 Details" (email, name, phone:
 * rx_theme_checkout_detail_field_keys()) and "2 Delivery" (the address)
 * — still one billing field set, one wrapper class each for styling.
 * Account creation fields and every hook are core's.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 * @global WC_Checkout $checkout
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template override: WooCommerce's own hooks and loop variables, kept by their core names.

$rx_theme_fields      = $checkout->get_checkout_fields( 'billing' );
$rx_theme_detail_keys = rx_theme_checkout_detail_field_keys();
$rx_theme_details     = array_intersect_key( $rx_theme_fields, array_flip( $rx_theme_detail_keys ) );
$rx_theme_delivery    = array_diff_key( $rx_theme_fields, $rx_theme_details );
?>
<div class="woocommerce-billing-fields">
	<?php do_action( 'woocommerce_before_checkout_billing_form', $checkout ); ?>

	<h3 class="rx-checkout-step"><span class="rx-checkout-step__num">1</span><?php esc_html_e( 'Details', 'rx-theme' ); ?></h3>
	<div class="woocommerce-billing-fields__field-wrapper rx-checkout-fields">
		<?php
		foreach ( $rx_theme_details as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
	</div>

	<h3 class="rx-checkout-step"><span class="rx-checkout-step__num">2</span><?php esc_html_e( 'Delivery', 'rx-theme' ); ?></h3>
	<div class="woocommerce-billing-fields__field-wrapper rx-checkout-fields">
		<?php
		foreach ( $rx_theme_delivery as $key => $field ) {
			woocommerce_form_field( $key, $field, $checkout->get_value( $key ) );
		}
		?>
	</div>

	<?php do_action( 'woocommerce_after_checkout_billing_form', $checkout ); ?>
</div>

<?php if ( ! is_user_logged_in() && $checkout->is_registration_enabled() ) : ?>
	<div class="woocommerce-account-fields">
		<?php if ( ! $checkout->is_registration_required() ) : ?>

			<p class="form-row form-row-wide create-account">
				<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
					<input class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" id="createaccount" <?php checked( ( true === $checkout->get_value( 'createaccount' ) || ( true === apply_filters( 'woocommerce_create_account_default_checked', false ) ) ), true ); ?> type="checkbox" name="createaccount" value="1" /> <span><?php esc_html_e( 'Create an account?', 'woocommerce' ); ?></span>
				</label>
			</p>

		<?php endif; ?>

		<?php do_action( 'woocommerce_before_checkout_registration_form', $checkout ); ?>

		<?php if ( $checkout->get_checkout_fields( 'account' ) ) : ?>

			<div class="create-account">
				<?php foreach ( $checkout->get_checkout_fields( 'account' ) as $key => $field ) : ?>
					<?php woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); ?>
				<?php endforeach; ?>
				<div class="clear"></div>
			</div>

		<?php endif; ?>

		<?php do_action( 'woocommerce_after_checkout_registration_form', $checkout ); ?>
	</div>
<?php endif; ?>
