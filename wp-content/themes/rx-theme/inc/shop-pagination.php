<?php
/**
 * Shop pagination footer: a "Showing X of Y items" summary paired with
 * the page-number pills, below the product grid on every product archive.
 *
 * Figma pairs the result count with pagination at the *bottom* of the
 * grid, not WooCommerce's default placement (result count above the
 * grid, pagination below it as two separate, unstyled pieces) — so both
 * default hooks are swapped for one combined render here. The page
 * numbers themselves still come from WooCommerce's own woocommerce_pagination()
 * (built on paginate_links(), so prev/next, custom permalinks and the
 * "nothing to paginate" case are already handled correctly) — this file
 * only restyles that markup (see style.css) and adds the summary line
 * beside it.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Swap WooCommerce's separate result-count-above / pagination-below for
 * the combined summary-and-pagination bar below the grid.
 */
function rx_theme_swap_shop_pagination(): void {
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
	add_action( 'woocommerce_after_shop_loop', 'rx_theme_render_shop_pagination', 10 );
}
add_action( 'init', 'rx_theme_swap_shop_pagination', 20 );

/**
 * "Next" / "Prev" instead of WooCommerce's default bare arrow entities,
 * with the arrow marked decorative so screen readers hear "Next" once,
 * not "Next" then a redundant arrow glyph.
 *
 * @param array<string,mixed> $args paginate_links() args.
 * @return array<string,mixed>
 */
function rx_theme_shop_pagination_args( array $args ): array {
	$args['prev_text'] = '<span aria-hidden="true">&larr;</span> ' . esc_html__( 'Prev', 'rx-theme' );
	$args['next_text'] = esc_html__( 'Next', 'rx-theme' ) . ' <span aria-hidden="true">&rarr;</span>';

	return $args;
}
add_filter( 'woocommerce_pagination_args', 'rx_theme_shop_pagination_args' );

/**
 * "Showing N of M items" for the current archive page — N is however
 * many products actually landed on this page (short on the last page),
 * M is every product the current filters match.
 */
function rx_theme_shop_pagination_summary(): string {
	global $wp_query;

	return sprintf(
		/* translators: 1: number of items on this page, 2: total matching items. */
		__( 'Showing %1$d of %2$d items', 'rx-theme' ),
		(int) $wp_query->post_count,
		(int) $wp_query->found_posts
	);
}

/**
 * Print the summary-and-pagination bar.
 */
function rx_theme_render_shop_pagination(): void {
	get_template_part( 'template-parts/shop/pagination' );
}
