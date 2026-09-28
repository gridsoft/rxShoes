<?php
/**
 * My Account page shell.
 *
 * Theme override of woocommerce/templates/myaccount/my-account.php: the
 * same navigation + content hooks, plus the site-style page header
 * (rx_theme_account_header(), inc/my-account.php) and a wrapper so the
 * menu and content can sit side by side on desktop.
 *
 * @package RX_Theme
 * @version 3.5.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- template override: WooCommerce's own hooks, kept by their core names.

$rx_theme_user = wp_get_current_user();
$rx_theme_name = $rx_theme_user->first_name ? $rx_theme_user->first_name : $rx_theme_user->display_name;

rx_theme_account_header(
	__( 'My account', 'rx-theme' ),
	sprintf(
		/* translators: 1: customer first name, 2: logout URL. */
		__( 'Hi %1$s — not you? <a href="%2$s">Log out</a>', 'rx-theme' ),
		esc_html( $rx_theme_name ),
		esc_url( wc_logout_url() )
	)
);
?>
<div class="rx-account">
	<?php
	/**
	 * My Account navigation.
	 *
	 * @since 2.6.0
	 */
	do_action( 'woocommerce_account_navigation' );
	?>

	<div class="woocommerce-MyAccount-content">
		<?php
		/**
		 * My Account content.
		 *
		 * @since 2.6.0
		 */
		do_action( 'woocommerce_account_content' );
		?>
	</div>
</div>
