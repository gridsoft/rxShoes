<?php
/**
 * Homepage "Best Sellers" section (Figma section 4, "Performance Best
 * Sellers") — the product query.
 *
 * The cards are the theme's own shop product card
 * (woocommerce/content-product.php, loaded with wc_get_template_part()),
 * exactly as on the shop and archive pages — the client asked for the
 * same template, without the Figma card's colour swatches and size
 * picker. So this file only decides *which* four products.
 *
 * "Best sellers" are ranked by WooCommerce's own sales counter
 * (total_sales), highest first. Until there are sales — every count is
 * zero — the tie is broken by the store's product order (Products > Sort
 * products), then oldest first, so the list is stable rather than
 * jumping about.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The best-selling products the catalog would list (visible, and in
 * stock when the store hides out-of-stock items), at most four.
 *
 * @param int $limit How many to fetch (the design shows four).
 */
function rx_theme_best_sellers_query( int $limit = 4 ): WP_Query {
	$ids    = wc_get_product_visibility_term_ids();
	$hidden = array( $ids['exclude-from-catalog'] ?? 0 );

	if ( 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) && isset( $ids['outofstock'] ) ) {
		$hidden[] = $ids['outofstock'];
	}

	return new WP_Query(
		array(
			'post_type'           => 'product',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one small query on the homepage.
				array(
					'taxonomy' => 'product_visibility',
					'field'    => 'term_taxonomy_id',
					'terms'    => $hidden,
					'operator' => 'NOT IN',
				),
			),
			// A product with no counter yet counts as zero rather than dropping out.
			'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- as above.
				'relation'  => 'OR',
				'rx_sales'  => array(
					'key'  => 'total_sales',
					'type' => 'NUMERIC',
				),
				'rx_nosale' => array(
					'key'     => 'total_sales',
					'compare' => 'NOT EXISTS',
				),
			),
			'orderby'             => array(
				'rx_sales'   => 'DESC',
				'menu_order' => 'ASC',
				'date'       => 'ASC',
			),
		)
	);
}
