<?php
/**
 * My Account dashboard.
 *
 * Theme override of woocommerce/templates/myaccount/dashboard.php: the
 * default paragraph of links becomes quick-link tiles (one per account
 * section, from the real menu so removed items like Downloads stay gone).
 * All of WooCommerce's dashboard hooks are kept.
 *
 * @package RX_Theme
 * @version 4.4.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- template override: WooCommerce's own hooks, kept by their core names.

$rx_theme_tile_text = array(
	'orders'          => __( 'Track, view and re-check your recent orders.', 'rx-theme' ),
	'edit-address'    => __( 'Your billing and shipping addresses.', 'rx-theme' ),
	'payment-methods' => __( 'Saved cards for faster checkout.', 'rx-theme' ),
	'edit-account'    => __( 'Name, email and password.', 'rx-theme' ),
);
?>

<h2 class="rx-account-section-title"><?php esc_html_e( 'Dashboard', 'rx-theme' ); ?></h2>

<ul class="rx-account-tiles">
	<?php foreach ( wc_get_account_menu_items() as $rx_theme_endpoint => $rx_theme_label ) : ?>
		<?php
		if ( ! isset( $rx_theme_tile_text[ $rx_theme_endpoint ] ) ) {
			continue;
		}
		?>
		<li class="rx-account-tile">
			<a class="rx-account-tile__link" href="<?php echo esc_url( wc_get_account_endpoint_url( $rx_theme_endpoint ) ); ?>">
				<span class="rx-account-tile__title"><?php echo esc_html( $rx_theme_label ); ?></span>
				<span class="rx-account-tile__text"><?php echo esc_html( $rx_theme_tile_text[ $rx_theme_endpoint ] ); ?></span>
				<span class="rx-account-tile__arrow" aria-hidden="true">→</span>
			</a>
		</li>
	<?php endforeach; ?>
</ul>

<?php
/**
 * My Account dashboard.
 *
 * @since 2.6.0
 */
do_action( 'woocommerce_account_dashboard' );

/**
 * Deprecated woocommerce_before_my_account action.
 *
 * @deprecated 2.6.0
 */
do_action( 'woocommerce_before_my_account' );

/**
 * Deprecated woocommerce_after_my_account action.
 *
 * @deprecated 2.6.0
 */
do_action( 'woocommerce_after_my_account' );
