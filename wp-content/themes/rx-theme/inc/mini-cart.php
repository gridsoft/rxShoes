<?php
/**
 * Mini cart "Rotation drawer": the panel that opens from the page's
 * top-right corner when the header cart icon (.rx-icon-link--cart) is
 * hovered (pointer devices) or tapped/clicked (every device — ~90% of
 * this store's traffic is mobile, where hover doesn't exist).
 *
 * Every number in it comes from the same helpers the /build-a-bundle/
 * page's order-breakdown sidebar uses (inc/bundle-builder.php): the cart
 * split into eligible pairs vs other items, the Customizer tier
 * percentages, the real applied discount, and the next-slot savings off
 * the same recommendation. So the drawer and the bundle builder can never
 * disagree about what a rotation costs.
 *
 * Kept current by WooCommerce's own cart fragments (wc-cart-fragments):
 * the drawer content and the header count badge are registered as
 * fragments, so an AJAX add-to-cart (shop grid, cross-sell cards) or a
 * remove from inside the drawer swaps in fresh markup, and a full-page
 * cache in production can't serve one shopper's cart to another.
 *
 * Not output on the cart or checkout pages: the page itself is the cart
 * there, so the icon stays a plain link.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the drawer runs on this request.
 */
function rx_theme_mini_cart_enabled(): bool {
	return class_exists( 'WooCommerce' ) && ! is_cart() && ! is_checkout();
}

/**
 * "second" / "third" for the slot copy and the "Choose my … pair" button.
 *
 * @param int $number Pair number (1-based).
 */
function rx_theme_mini_cart_ordinal( int $number ): string {
	$ordinals = array(
		1 => __( 'first', 'rx-theme' ),
		2 => __( 'second', 'rx-theme' ),
		3 => __( 'third', 'rx-theme' ),
	);

	return $ordinals[ $number ] ?? (string) $number;
}

/**
 * Everything the drawer template shows, worked out once. Mirrors
 * page-build-a-bundle.php's own set-up (target slots, recommendation,
 * next-slot pricing), so both surfaces read the same numbers.
 *
 * @return array<string,mixed>
 */
function rx_theme_mini_cart_data(): array {
	$buckets  = rx_theme_bundle_builder_cart_buckets();
	$eligible = $buckets['eligible'];
	$other    = $buckets['other'];
	$totals   = rx_theme_bundle_builder_totals( $eligible );
	$percent  = (float) $totals['tier']['percent'];

	// Outside the offer's run window the drawer is a plain cart: no open slot, no progress.
	$bundles      = rx_theme_bundle_offer_is_active();
	$target_slots = max( 3, count( $eligible ) );
	$next_pair    = $bundles && count( $eligible ) < $target_slots ? count( $eligible ) + 1 : null;

	/*
	 * Same "next unlock" figure as the builder's progress bar: real
	 * savings off the real recommendation for the next slot. Null (and so
	 * not shown) when there's nothing to base a number on.
	 */
	$next_pricing = null;
	if ( $next_pair && $eligible ) {
		$anchor    = end( $eligible )['parent'];
		$in_cart   = array_map( static fn( WC_Product $p ): int => $p->get_id(), wp_list_pluck( $eligible, 'parent' ) );
		$recommend = rx_theme_bundle_builder_recommendation( $anchor, $in_cart );

		if ( $recommend instanceof WC_Product ) {
			$next_pricing = rx_theme_bundle_builder_recommendation_pricing( $eligible, $recommend, $next_pair );
		}
	}

	$other_total = 0.0;
	foreach ( $other as $entry ) {
		$other_total += rx_theme_product_price( $entry['product'] ) * (int) $entry['cart_item']['quantity'];
	}

	$regular_total = round( $totals['regular_total'] + $other_total, 2 );

	return array(
		'bundles'       => $bundles,
		'is_empty'      => ! $eligible && ! $other,
		'eligible'      => $eligible,
		'other'         => $other,
		'pairs_count'   => (int) $totals['pairs_count'],
		'percent'       => $percent,
		'savings'       => (float) $totals['savings'],
		'target_slots'  => $target_slots,
		'next_pair'     => $next_pair,
		'next_percent'  => $next_pair ? (float) rx_theme_bundle_builder_tier( $next_pair )['percent'] : 0.0,
		'next_pricing'  => $next_pricing,
		'regular_total' => $regular_total,
		'total'         => round( $regular_total - $totals['savings'], 2 ),
		'vault_url'     => $bundles ? add_query_arg( 'rx_bundle', '1', wc_get_page_permalink( 'shop' ) ) : wc_get_page_permalink( 'shop' ),
	);
}

/**
 * The drawer's inner content, as a string (for both the page render and
 * the cart fragment).
 */
function rx_theme_mini_cart_content(): string {
	ob_start();
	get_template_part( 'template-parts/mini-cart/content', null, rx_theme_mini_cart_data() );

	return (string) ob_get_clean();
}

/**
 * The header's count badge, as a string (shared by header.php and the
 * cart fragment so the two can't drift).
 */
function rx_theme_mini_cart_count_badge(): string {
	$count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;

	return '<span class="rx-icon-link__count">' . esc_html( (string) $count ) . '</span>';
}

/**
 * Print the drawer shell (backdrop + panel) at the end of the page, out
 * of the header's stacking context.
 */
function rx_theme_mini_cart_output(): void {
	if ( ! rx_theme_mini_cart_enabled() ) {
		return;
	}
	?>
	<div class="rx-mini-cart-backdrop" data-rx-mini-cart-close hidden></div>
	<aside id="rx-mini-cart" class="rx-mini-cart" role="dialog" aria-modal="false" aria-labelledby="rx-mini-cart-title" aria-hidden="true">
		<div class="rx-mini-cart__head">
			<h2 id="rx-mini-cart-title" class="rx-mini-cart__title"><span class="rx-mini-cart__title-dot" aria-hidden="true"></span><?php echo esc_html( rx_theme_bundle_offer_is_active() ? __( 'Rotation drawer', 'rx-theme' ) : __( 'Your cart', 'rx-theme' ) ); ?></h2>
			<button type="button" class="rx-mini-cart__close" data-rx-mini-cart-close aria-label="<?php esc_attr_e( 'Close cart', 'rx-theme' ); ?>">
				<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
			</button>
		</div>
		<?php echo rx_theme_mini_cart_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template escapes its own output. ?>
	</aside>
	<?php
}
add_action( 'wp_footer', 'rx_theme_mini_cart_output' );

/**
 * Register the drawer content and the count badge as cart fragments.
 *
 * @param array<string,string> $fragments Selector => replacement HTML.
 * @return array<string,string>
 */
function rx_theme_mini_cart_fragments( array $fragments ): array {
	$fragments['div.rx-mini-cart__content'] = rx_theme_mini_cart_content();
	$fragments['span.rx-icon-link__count']  = rx_theme_mini_cart_count_badge();

	return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'rx_theme_mini_cart_fragments' );

/**
 * Enqueue the drawer script plus WooCommerce's cart-fragments script
 * (not loaded by default since WC 7.8).
 */
function rx_theme_enqueue_mini_cart_script(): void {
	if ( ! rx_theme_mini_cart_enabled() ) {
		return;
	}

	$file = RX_THEME_DIR . '/assets/js/mini-cart.js';

	wp_enqueue_script(
		'rx-theme-mini-cart',
		RX_THEME_URI . '/assets/js/mini-cart.js',
		array( 'jquery', 'wc-cart-fragments' ),
		file_exists( $file ) ? (string) filemtime( $file ) : RX_THEME_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'rx_theme_enqueue_mini_cart_script' );
