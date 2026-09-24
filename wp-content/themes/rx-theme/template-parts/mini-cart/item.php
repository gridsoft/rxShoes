<?php
/**
 * One cart line in the rotation drawer: thumbnail, pair label, name,
 * size/colour, price (discounted at the rotation's current real tier,
 * with the regular price struck through when a discount applies), and a
 * remove button.
 *
 * The remove link is WooCommerce's own nonce'd wc_get_cart_remove_url(),
 * so it works without JavaScript; assets/js/mini-cart.js intercepts it
 * and removes over WooCommerce's remove_from_cart AJAX endpoint instead.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_entry = $args['entry'] ?? null;

if ( ! $rx_theme_entry ) {
	return;
}

$rx_theme_label   = (string) ( $args['label'] ?? '' );
$rx_theme_percent = (float) ( $args['percent'] ?? 0 );
$rx_theme_item    = $rx_theme_entry['cart_item'];
$rx_theme_product = $rx_theme_entry['product'];
$rx_theme_parent  = $rx_theme_entry['parent'];
$rx_theme_qty     = (int) $rx_theme_item['quantity'];
$rx_theme_regular = rx_theme_product_price( $rx_theme_product ) * $rx_theme_qty;
$rx_theme_price   = round( $rx_theme_regular * ( 1 - $rx_theme_percent / 100 ), 2 );
$rx_theme_link    = get_permalink( $rx_theme_parent->get_id() );
$rx_theme_details = rx_theme_cart_item_details( $rx_theme_item );
?>
<li class="rx-mini-cart__item">
	<a class="rx-mini-cart__thumb" href="<?php echo esc_url( $rx_theme_link ); ?>" tabindex="-1" aria-hidden="true">
		<?php echo wp_kses_post( $rx_theme_product->get_image( 'woocommerce_gallery_thumbnail' ) ); ?>
	</a>
	<div class="rx-mini-cart__item-text">
		<?php if ( $rx_theme_label ) : ?>
			<p class="rx-mini-cart__item-label"><?php echo esc_html( $rx_theme_label ); ?></p>
		<?php endif; ?>
		<p class="rx-mini-cart__item-name"><a href="<?php echo esc_url( $rx_theme_link ); ?>" title="<?php echo esc_attr( $rx_theme_parent->get_name() ); ?>"><?php echo esc_html( $rx_theme_parent->get_name() ); ?></a></p>
		<?php if ( $rx_theme_details ) : ?>
			<p class="rx-mini-cart__item-meta"><?php echo esc_html( implode( ' • ', $rx_theme_details ) ); ?></p>
		<?php endif; ?>
		<p class="rx-mini-cart__item-price">
			<strong><?php echo esc_html( rx_theme_format_money( $rx_theme_price ) ); ?></strong>
			<?php if ( $rx_theme_price < $rx_theme_regular ) : ?>
				<del><?php echo esc_html( rx_theme_format_money( $rx_theme_regular, false, false ) ); ?></del>
			<?php endif; ?>
		</p>
	</div>
	<a class="rx-mini-cart__remove" href="<?php echo esc_url( wc_get_cart_remove_url( $rx_theme_entry['key'] ) ); ?>" data-cart-item-key="<?php echo esc_attr( $rx_theme_entry['key'] ); ?>" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name. */ __( 'Remove %s from cart', 'rx-theme' ), $rx_theme_parent->get_name() ) ); ?>">
		<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
	</a>
</li>
