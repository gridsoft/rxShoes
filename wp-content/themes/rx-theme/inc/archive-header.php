<?php
/**
 * Product archive header: breadcrumb + title on every archive, plus the
 * stats card (brand count, "Save up to N%") on the shop page and on
 * taxonomy archives (category, tag, brand, …), and the term description
 * on taxonomy archives. Search results get just breadcrumb + title.
 *
 * Replaces WooCommerce's default title/breadcrumb block through its
 * hooks (no template override). Copy is fixed per the client; what
 * varies per page is the title, the term description, the brand count,
 * and the "Save up to N%" figure, which comes from Appearance > Customize
 * > Bundle Discount.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Take over the archive header. The breadcrumb moves inside the theme's
 * own header and the default title + description block is replaced.
 */
function rx_theme_swap_archive_header(): void {
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	remove_action( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header' );

	add_action( 'woocommerce_shop_loop_header', 'rx_theme_render_archive_header' );
}
add_action( 'init', 'rx_theme_swap_archive_header', 20 );

/**
 * Wrap each breadcrumb item so the current page (the last one) can be
 * styled bold, as in the Figma header.
 *
 * @param array<string,string> $defaults WooCommerce breadcrumb defaults.
 * @return array<string,string>
 */
function rx_theme_breadcrumb_defaults( array $defaults ): array {
	$defaults['before'] = '<span class="rx-breadcrumb__item">';
	$defaults['after']  = '</span>';

	return $defaults;
}
add_filter( 'woocommerce_breadcrumb_defaults', 'rx_theme_breadcrumb_defaults' );

/**
 * Print the archive header.
 */
function rx_theme_render_archive_header(): void {
	get_template_part( 'template-parts/shop/archive-header' );
}

/**
 * Whether this is a taxonomy archive (category, tag, brand, …) — the
 * pages that also show the term description.
 */
function rx_theme_archive_is_taxonomy(): bool {
	return is_product_taxonomy() && get_queried_object() instanceof WP_Term;
}

/**
 * Whether the archive header shows the stats card: the shop page and
 * taxonomy archives. Search results don't — WooCommerce's is_shop() is
 * also true for a product search, where a whole-catalog brand count
 * would misdescribe the results.
 */
function rx_theme_archive_has_stats(): bool {
	return rx_theme_archive_is_taxonomy() || ( is_shop() && ! is_search() );
}

/**
 * IDs of the products the current page covers: on a taxonomy archive,
 * everything in the term (and its children); on a search, the matches;
 * on the shop page, the whole catalog. Only products the catalog would
 * list. Independent of the size / stock / bundle / sidebar filters, so
 * the header and sidebar describe the page, not the current selection.
 *
 * @return int[]
 */
function rx_theme_archive_product_ids(): array {
	static $cache = array();

	$term = get_queried_object();
	$term = $term instanceof WP_Term ? $term : null;
	$key  = $term ? $term->term_taxonomy_id : ( is_search() ? 's:' . get_search_query( false ) : 'shop' );

	if ( ! isset( $cache[ $key ] ) ) {
		$ids       = wc_get_product_visibility_term_ids();
		$hidden    = array( $ids[ is_search() ? 'exclude-from-search' : 'exclude-from-catalog' ] ?? 0 );
		$hide_oos  = 'yes' === get_option( 'woocommerce_hide_out_of_stock_items' );
		$hidden    = $hide_oos && isset( $ids['outofstock'] ) ? array_merge( $hidden, array( $ids['outofstock'] ) ) : $hidden;
		$tax_query = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one bounded query per archive view.
			'relation' => 'AND',
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => $hidden,
				'operator' => 'NOT IN',
			),
		);

		if ( $term ) {
			$tax_query[] = array(
				'taxonomy' => $term->taxonomy,
				'field'    => 'term_id',
				'terms'    => array( $term->term_id ),
			);
		}

		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'tax_query'      => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- see above.
		);

		if ( ! $term && is_search() ) {
			$args['s'] = get_search_query( false );
		}

		$query = new WP_Query( $args );

		$cache[ $key ] = array_map( 'absint', $query->posts );
	}

	return $cache[ $key ];
}

/**
 * How many different brands the page's products belong to (WooCommerce's
 * own Brands taxonomy, Products > Brands). Zero until products have been
 * given a brand.
 *
 * @param int[] $product_ids Products on the page.
 */
function rx_theme_archive_brand_count( array $product_ids ): int {
	if ( ! $product_ids || ! taxonomy_exists( 'product_brand' ) ) {
		return 0;
	}

	$brands = wp_get_object_terms( $product_ids, 'product_brand', array( 'fields' => 'ids' ) );

	return is_wp_error( $brands ) ? 0 : count( array_unique( $brands ) );
}
