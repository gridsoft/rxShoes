<?php
/**
 * Shop sidebar: "Best for", "Brands" and "Price range" widgets beside the
 * product grid on every product archive (shop, category, tag, brand,
 * search results).
 *
 * The widgets are dynamic in scope: each lists only what exists among the
 * products on that page — a category page offers that category's brands
 * and "best for" terms, and a price range from its cheapest to its dearest
 * product. Counts are products on the page, not on the current filter
 * selection, so options don't vanish as the customer ticks them.
 *
 * Best for and Brands are plain links (server-side, no JS); several ticked
 * in one widget mean "any of these", and widgets combine with each other,
 * the filter bar and the size chips. Price uses WooCommerce's own
 * min_price / max_price parameters; the dual slider needs
 * assets/js/shop-sidebar.js (with an Apply button as the no-JS fallback).
 *
 * The layout is hooked into WooCommerce's loop actions: a container opens
 * after the filter bar, holds the sidebar and a main column with the
 * product list + pagination, and closes after the loop. Nothing here
 * overrides a WooCommerce template.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'woocommerce_before_shop_loop', 'rx_theme_open_shop_layout', 40 );
add_action( 'woocommerce_after_shop_loop', 'rx_theme_close_shop_layout', 100 );

/* ---------------------------------------------------------------------
 * Parameters and the product query
 * ------------------------------------------------------------------ */

/**
 * The taxonomy behind each sidebar facet, keyed by its query-string
 * parameter. Our own parameter names rather than the taxonomies' query
 * vars: a taxonomy query var on /shop/ would turn the request into a
 * taxonomy archive (and change the page header and template with it).
 *
 * @return array<string,string> Parameter => taxonomy.
 */
function rx_theme_shop_facets(): array {
	return array(
		'rx_best'  => 'rx_best_for', // Registered by rx-core.
		'rx_brand' => 'product_brand', // WooCommerce's own Brands.
	);
}

/**
 * Every query-string parameter the sidebar owns (for "clear all").
 *
 * @return string[]
 */
function rx_theme_shop_sidebar_params(): array {
	return array_merge( array_keys( rx_theme_shop_facets() ), array( 'min_price', 'max_price' ) );
}

/**
 * Term slugs chosen for a facet (?rx_best=a,b).
 *
 * @param string $param Facet parameter.
 * @return string[]
 */
function rx_theme_shop_selected_slugs( string $param ): array {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter, no state change.
	$raw = isset( $_GET[ $param ] ) && is_string( $_GET[ $param ] ) ? wp_unslash( $_GET[ $param ] ) : '';

	return array_values( array_filter( array_map( 'sanitize_title', explode( ',', $raw ) ) ) );
}

/**
 * Narrow the main product query to the ticked Best for / Brands terms.
 * (Price is applied by WooCommerce from min_price / max_price.)
 *
 * @param WP_Query $query Main product query.
 */
function rx_theme_shop_apply_sidebar_filters( WP_Query $query ): void {
	foreach ( rx_theme_shop_facets() as $param => $taxonomy ) {
		$slugs = rx_theme_shop_selected_slugs( $param );

		if ( ! $slugs || ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$tax_query   = (array) $query->get( 'tax_query' );
		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => $slugs,
			'operator' => 'IN',
		);
		$query->set( 'tax_query', $tax_query );
	}
}
add_action( 'woocommerce_product_query', 'rx_theme_shop_apply_sidebar_filters' );

/* ---------------------------------------------------------------------
 * Widget data
 * ------------------------------------------------------------------ */

/**
 * The options for one facet widget: the terms that at least one product
 * on this page has, with how many, and the link that ticks/unticks each.
 *
 * @param string $param Facet parameter (rx_best / rx_brand).
 * @return array<int,array{label:string,count:int,active:bool,url:string}>
 */
function rx_theme_shop_facet_options( string $param ): array {
	$taxonomy = rx_theme_shop_facets()[ $param ] ?? '';
	$ids      = rx_theme_archive_product_ids();

	if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) || ! $ids ) {
		return array();
	}

	$rows = wp_get_object_terms( $ids, $taxonomy, array( 'fields' => 'all_with_object_id' ) );

	if ( is_wp_error( $rows ) ) {
		return array();
	}

	// One row per (product, term): tally the distinct products per term.
	$terms    = array();
	$products = array();

	foreach ( $rows as $row ) {
		$terms[ $row->term_id ]                    = $row;
		$products[ $row->term_id ][ $row->object_id ] = true;
	}

	$selected = rx_theme_shop_selected_slugs( $param );
	$options  = array();

	foreach ( $terms as $term_id => $term ) {
		$active = in_array( $term->slug, $selected, true );
		$next   = $active ? array_values( array_diff( $selected, array( $term->slug ) ) ) : array_merge( $selected, array( $term->slug ) );

		$options[] = array(
			'label'  => $term->name,
			'count'  => count( $products[ $term_id ] ),
			'active' => $active,
			'url'    => rx_theme_shop_url( array( $param => $next ? implode( ',', $next ) : false ) ),
		);
	}

	usort(
		$options,
		static function ( array $a, array $b ): int {
			return strnatcasecmp( $a['label'], $b['label'] );
		}
	);

	return $options;
}

/**
 * The price slider's data: the cheapest and dearest price among the
 * page's products (whole dollars, widened outwards so every product is
 * inside), and the range currently chosen. Null when the page's products
 * don't differ in price — there's nothing to slide.
 *
 * @return array{min:int,max:int,from:int,to:int}|null
 */
function rx_theme_shop_price_range(): ?array {
	global $wpdb;

	$ids = rx_theme_archive_product_ids();

	if ( ! $ids ) {
		return null;
	}

	$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- placeholder list is built from a count.
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT MIN( min_price ) AS lo, MAX( max_price ) AS hi FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id IN ( $placeholders )",
			$ids
		)
	);
	// phpcs:enable

	if ( ! $row || null === $row->lo || null === $row->hi ) {
		return null;
	}

	$min = (int) floor( (float) $row->lo );
	$max = (int) ceil( (float) $row->hi );

	if ( $max <= $min ) {
		return null;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only display filter, no state change.
	$from = isset( $_GET['min_price'] ) && is_numeric( $_GET['min_price'] ) ? (int) floor( (float) $_GET['min_price'] ) : $min;
	$to   = isset( $_GET['max_price'] ) && is_numeric( $_GET['max_price'] ) ? (int) ceil( (float) $_GET['max_price'] ) : $max;
	// phpcs:enable

	$from = max( $min, min( $from, $max ) );
	$to   = max( $from, min( $to, $max ) );

	return array(
		'min'  => $min,
		'max'  => $max,
		'from' => $from,
		'to'   => $to,
	);
}

/**
 * Everything the sidebar template needs, or an empty array when no
 * widget has anything to offer (the layout then drops the sidebar).
 *
 * @return array{best:array,brands:array,price:array|null,active:int}|array{}
 */
function rx_theme_shop_sidebar_data(): array {
	static $data = null;

	if ( null !== $data ) {
		return $data;
	}

	$best   = rx_theme_shop_facet_options( 'rx_best' );
	$brands = rx_theme_shop_facet_options( 'rx_brand' );
	$price  = rx_theme_shop_price_range();

	if ( ! $best && ! $brands && ! $price ) {
		$data = array();
		return $data;
	}

	$active = count( rx_theme_shop_selected_slugs( 'rx_best' ) ) + count( rx_theme_shop_selected_slugs( 'rx_brand' ) );

	if ( $price && ( $price['from'] > $price['min'] || $price['to'] < $price['max'] ) ) {
		++$active;
	}

	$data = array(
		'best'   => $best,
		'brands' => $brands,
		'price'  => $price,
		'active' => $active,
	);

	return $data;
}

/**
 * Link that unticks every option in one facet ("Reset").
 *
 * @param string $param Facet parameter.
 */
function rx_theme_shop_facet_reset_url( string $param ): string {
	return rx_theme_shop_url( array( $param => false ) );
}

/* ---------------------------------------------------------------------
 * Layout and assets
 * ------------------------------------------------------------------ */

/**
 * Open the layout container, print the sidebar (when it has anything to
 * show) and open the main column that the product list and pagination
 * land in. Closed by rx_theme_close_shop_layout() after the loop.
 */
function rx_theme_open_shop_layout(): void {
	$has_sidebar = array() !== rx_theme_shop_sidebar_data();

	printf( '<div class="rx-shop-layout%s">', $has_sidebar ? ' rx-shop-layout--sidebar' : '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literals.

	if ( $has_sidebar ) {
		get_template_part( 'template-parts/shop/sidebar' );
	}

	echo '<div class="rx-shop-layout__main">';
}

/**
 * Close the main column and the layout container.
 */
function rx_theme_close_shop_layout(): void {
	echo '</div></div>';
}

/**
 * The price slider's script, on product archives only.
 */
function rx_theme_enqueue_shop_sidebar_script(): void {
	if ( ! is_shop() && ! is_product_taxonomy() ) {
		return;
	}

	$file = RX_THEME_DIR . '/assets/js/shop-sidebar.js';

	wp_enqueue_script(
		'rx-theme-shop-sidebar',
		RX_THEME_URI . '/assets/js/shop-sidebar.js',
		array(),
		file_exists( $file ) ? (string) filemtime( $file ) : RX_THEME_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'rx_theme_enqueue_shop_sidebar_script' );
