<?php
/**
 * Shop pagination footer markup. Page numbers come from WooCommerce's
 * own woocommerce_pagination() — see inc/shop-pagination.php for why.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="rx-shop-pagination">
	<p class="rx-shop-pagination__summary">
		<?php echo esc_html( rx_theme_shop_pagination_summary() ); ?>
		<span class="rx-shop-pagination__dot" aria-hidden="true">&bull;</span>
		<span class="rx-shop-pagination__sync"><?php esc_html_e( 'WooCommerce synchronised', 'rx-theme' ); ?></span>
	</p>
	<?php woocommerce_pagination(); ?>
</div>
