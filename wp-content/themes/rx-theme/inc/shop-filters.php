<?php
/**
 * Shop filter bar: size chips, "In stock" and "Bundle eligible" toggles,
 * and the sort dropdown, shown above the product grid on every product
 * archive (shop, category, tag, brand, search results).
 *
 * Every control is a plain link (or WooCommerce's own sort form), so the
 * filtering happens on the server and works with pagination, the back
 * button and page caching. Nothing here needs its own JavaScript.
 *
 * Sizes use WooCommerce's native layered-nav parameters
 * (?filter_size=8,9&query_type_size=or), so WC itself matches products to
 * sizes. In stock and Bundle eligible are our own parameters
 * (rx_in_stock=1, rx_bundle=1); with both a size and In stock chosen,
 * the theme additionally checks that size's own stock (see
 * rx_theme_shop_products_with_sizes_in_stock()).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Swap WooCommerce's result count + sort (which sit above the grid by
 * default) for the filter bar. The bar renders the sort dropdown itself.
 * The "no products found" state gets the bar too, otherwise a filter
 * combination with zero matches would leave no way to undo it.
 */
function rx_theme_swap_shop_toolbar(): void {
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

	add_action( 'woocommerce_before_shop_loop', 'rx_theme_render_shop_filters', 20 );
	add_action( 'woocommerce_no_products_found', 'rx_theme_render_shop_filters_when_active', 5 );
	add_action( 'woocommerce_no_products_found', 'rx_theme_render_clear_filters_link', 20 );
}
add_action( 'init', 'rx_theme_swap_shop_toolbar', 20 );

/**
 * Sort-menu wording from the Figma bar. The default option becomes
 * "Featured Collection" (menu order: the order set in Products > Sort
 * products); the others drop WooCommerce's "Sort by" prefix because the
 * bar already says "Sort:". "Average rating" is removed: reviews are out
 * of scope (TODO.md #8), so there is no real rating to sort by.
 *
 * @param array<string,string> $options Sort options keyed by orderby value.
 * @return array<string,string>
 */
function rx_theme_shop_sort_labels( array $options ): array {
	unset( $options['rating'] );

	$labels = array(
		'menu_order' => __( 'Featured Collection', 'rx-theme' ),
		'popularity' => __( 'Popularity', 'rx-theme' ),
		'date'       => __( 'Latest', 'rx-theme' ),
		'price'      => __( 'Price: low to high', 'rx-theme' ),
		'price-desc' => __( 'Price: high to low', 'rx-theme' ),
	);

	foreach ( array_keys( $options ) as $key ) {
		if ( isset( $labels[ $key ] ) ) {
			$options[ $key ] = $labels[ $key ];
		}
	}

	return $options;
}
add_filter( 'woocommerce_catalog_orderby', 'rx_theme_shop_sort_labels' );

/*
 * ---------------------------------------------------------------------
 * Reading the current filter state
 * ---------------------------------------------------------------------
 */

/**
 * Which size mode is in play (?rx_size_mode=men|women|eu), or '' for the
 * plain unisex pa_size list (the default, unchanged behaviour).
 *
 * The pa_mens-size / pa_womens-size taxonomies are filter-only facets (never a variation
 * attribute — see RX\Core\Catalog\SizeFilterAttributes): picking a size
 * from them still buys the same pa_size variation, they just let a
 * shopper find it by their own sizing system instead of the combined
 * "M4 W5.5"-style label. 'eu' isn't a separate taxonomy at all — EU sizes
 * ("40eu") already live in pa_size alongside the US ones, so 'eu' just
 * narrows the same pa_size chip list to the EU-suffixed terms (see
 * rx_theme_shop_size_chips()).
 */
function rx_theme_shop_selected_size_mode(): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter, no state change.
	$mode = isset( $_GET['rx_size_mode'] ) ? sanitize_key( wp_unslash( $_GET['rx_size_mode'] ) ) : '';

	return in_array( $mode, array( 'men', 'women', 'eu' ), true ) ? $mode : '';
}

/**
 * The attribute taxonomy the size chips currently read from. 'eu' still
 * reads pa_size — see rx_theme_shop_selected_size_mode().
 */
function rx_theme_shop_size_taxonomy(): string {
	switch ( rx_theme_shop_selected_size_mode() ) {
		case 'men':
			return 'pa_mens-size';
		case 'women':
			return 'pa_womens-size';
		default:
			return 'pa_size';
	}
}

/**
 * WooCommerce's layered-nav query var for a pa_ attribute taxonomy
 * ("pa_mens-size" -> "filter_mens-size"), and its query-type companion.
 *
 * @param string $taxonomy e.g. 'pa_size', 'pa_mens-size'.
 * @return array{filter:string,query_type:string}
 */
function rx_theme_shop_layered_nav_params( string $taxonomy ): array {
	$attribute = str_replace( 'pa_', '', $taxonomy );

	return array(
		'filter'     => "filter_{$attribute}",
		'query_type' => "query_type_{$attribute}",
	);
}

/**
 * Slugs of the size terms currently chosen in whichever size taxonomy is
 * active (?filter_size=8,9, or ?filter_mens-size=4 / ?filter_womens-size=5.5
 * once a gender is picked).
 *
 * @return string[]
 */
function rx_theme_shop_selected_sizes(): array {
	$chosen   = WC_Query::get_layered_nav_chosen_attributes();
	$taxonomy = rx_theme_shop_size_taxonomy();

	return isset( $chosen[ $taxonomy ]['terms'] ) ? array_values( $chosen[ $taxonomy ]['terms'] ) : array();
}

/**
 * The pa_size variation-attribute slugs that correspond to the currently
 * chosen sizes, translating through pa_mens-size / pa_womens-size when
 * one of those is active. Needed because "in stock" is checked against
 * each variation's own attribute_pa_size meta (see
 * rx_theme_shop_products_with_sizes_in_stock()) — pa_mens-size and
 * pa_womens-size aren't variation attributes, so a chosen "Men's 4" has
 * to be resolved to the pa_size term(s) that actually represent it
 * ("4", or the paired "M4 W5.5"). 'eu' mode (and no mode) already read
 * pa_size directly, so there's nothing to translate.
 *
 * @return string[]
 */
function rx_theme_shop_selected_pa_size_slugs(): array {
	$mode   = rx_theme_shop_selected_size_mode();
	$chosen = rx_theme_shop_selected_sizes();

	if ( ! in_array( $mode, array( 'men', 'women' ), true ) || ! $chosen ) {
		return $chosen;
	}

	$slugs = array();

	foreach ( $chosen as $value_slug ) {
		$term = get_term_by( 'slug', $value_slug, rx_theme_shop_size_taxonomy() );

		if ( $term ) {
			$slugs = array_merge( $slugs, rx_theme_shop_pa_size_slugs_matching( $mode, $term->name ) );
		}
	}

	return array_values( array_unique( $slugs ) );
}

/**
 * Every pa_size term slug whose name represents the given gender/value —
 * either the paired label ("M4 W5.5" contains Men's 4) or, for a
 * single-gender product with plain numeric sizes, the bare number itself.
 *
 * @param string $gender 'men' or 'women'.
 * @param string $value  e.g. '4', '5.5'.
 * @return string[]
 */
function rx_theme_shop_pa_size_slugs_matching( string $gender, string $value ): array {
	static $terms = null;

	if ( null === $terms ) {
		$found = taxonomy_exists( 'pa_size' ) ? get_terms(
			array(
				'taxonomy'   => 'pa_size',
				'hide_empty' => false,
			)
		) : array();
		$terms = is_wp_error( $found ) ? array() : $found;
	}

	$quoted  = preg_quote( $value, '/' );
	$pattern = 'men' === $gender
		? "/^M{$quoted}(\\s+W\\d+(\\.\\d+)?)?$/i"
		: "/^(M\\d+(\\.\\d+)?\\s+)?W{$quoted}$/i";

	$slugs = array();

	foreach ( $terms as $term ) {
		if ( preg_match( $pattern, $term->name ) || strcasecmp( $term->name, $value ) === 0 ) {
			$slugs[] = $term->slug;
		}
	}

	return $slugs;
}

/**
 * Whether "In stock" is switched on (?rx_in_stock=1).
 */
function rx_theme_shop_in_stock_only(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter, no state change.
	return isset( $_GET['rx_in_stock'] ) && '1' === $_GET['rx_in_stock'];
}

/**
 * Whether "Bundle eligible" is switched on (?rx_bundle=1) *and* something
 * can answer it. Eligibility is the rx-core plugin's business rule, so
 * the theme asks it for the meta query instead of knowing the meta key.
 * With rx-core inactive nothing answers, the toggle is hidden and a stray
 * ?rx_bundle=1 is ignored rather than emptying the shop.
 */
function rx_theme_shop_bundle_only(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter, no state change.
	return isset( $_GET['rx_bundle'] ) && '1' === $_GET['rx_bundle'] && array() !== rx_theme_shop_bundle_meta_query();
}

/**
 * The meta-query clause that selects bundle-eligible products, or an
 * empty array when rx-core isn't there to provide it.
 *
 * @return array<string,mixed>
 */
function rx_theme_shop_bundle_meta_query(): array {
	// No "Bundle eligible" filter while the offer is off (inc/bundle-offer.php).
	if ( ! rx_theme_bundle_offer_is_active() ) {
		return array();
	}

	/**
	 * Filters the meta-query clause matching bundle-eligible products.
	 * rx-core answers this from the "Eligible for bundle" checkbox
	 * (see RX\Core\Bundles\BundleEligibility). Default: none.
	 *
	 * @param array<string,mixed> $clause WP_Query meta_query clause.
	 */
	$clause = apply_filters( 'rx_theme_bundle_eligible_meta_query', array() );

	return is_array( $clause ) ? $clause : array();
}

/**
 * What share of the catalog is bundle-eligible, as a percentage: eligible
 * products ÷ all products, across the whole store (not the current
 * category or filters). Shown in the "Bundle eligible (N%)" toggle.
 * Counts published, catalog-visible products only, so hidden products
 * don't skew it. Zero when rx-core isn't there to say what's eligible.
 */
function rx_theme_shop_bundle_share_percent(): float {
	static $percent = null;

	if ( null !== $percent ) {
		return $percent;
	}

	$clause = rx_theme_shop_bundle_meta_query();
	$ids    = wc_get_product_visibility_term_ids();
	$args   = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- two cheap count queries per archive view.
			array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array( $ids['exclude-from-catalog'] ?? 0 ),
				'operator' => 'NOT IN',
			),
		),
	);

	$total = ( new WP_Query( $args ) )->found_posts;

	if ( ! $clause || ! $total ) {
		$percent = 0.0;
		return $percent;
	}

	$args['meta_query'] = array( $clause ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- see above.
	$percent            = round( 100 * ( new WP_Query( $args ) )->found_posts / $total, 2 );

	return $percent;
}

/**
 * Whether any filter is active (drives the Clear button and the
 * zero-results state).
 */
function rx_theme_shop_filters_active(): bool {
	if ( array() !== rx_theme_shop_selected_sizes() || '' !== rx_theme_shop_selected_size_mode() || rx_theme_shop_in_stock_only() || rx_theme_shop_bundle_only() ) {
		return true;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display filter, no state change.
	return array() !== array_intersect_key( $_GET, array_flip( rx_theme_shop_sidebar_params() ) );
}

/*
 * ---------------------------------------------------------------------
 * Applying the filters to the product query
 * ---------------------------------------------------------------------
 */

/**
 * Apply the two custom toggles to the main product query. (The size
 * filter itself is applied by WooCommerce from the filter_size parameter;
 * this only narrows it to in-stock sizes when In stock is on.)
 *
 * @param WP_Query $query Main product query.
 */
function rx_theme_shop_apply_filters( WP_Query $query ): void {
	if ( rx_theme_shop_in_stock_only() ) {
		$ids = wc_get_product_visibility_term_ids();

		if ( isset( $ids['outofstock'] ) ) {
			$tax_query   = (array) $query->get( 'tax_query' );
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array( $ids['outofstock'] ),
				'operator' => 'NOT IN',
			);
			$query->set( 'tax_query', $tax_query );
		}

		// "In stock" + a size chosen has to mean *that size* is in stock,
		// not merely some other size of the same shoe (all the parent's
		// own stock status can tell us). Translated to pa_size slugs since
		// that's the actual variation attribute — see
		// rx_theme_shop_selected_pa_size_slugs().
		$sizes = rx_theme_shop_selected_pa_size_slugs();

		if ( $sizes ) {
			$in_stock = rx_theme_shop_products_with_sizes_in_stock( $sizes );
			$existing = array_filter( array_map( 'absint', (array) $query->get( 'post__in' ) ) );
			$allowed  = $existing ? array_values( array_intersect( $existing, $in_stock ) ) : $in_stock;

			// An empty post__in means "no restriction" to WP_Query; 0 matches nothing.
			$query->set( 'post__in', $allowed ? $allowed : array( 0 ) );
		}
	}

	if ( rx_theme_shop_bundle_only() ) {
		$meta_query   = (array) $query->get( 'meta_query' );
		$meta_query[] = rx_theme_shop_bundle_meta_query();
		$query->set( 'meta_query', $meta_query );
	}
}
add_action( 'woocommerce_product_query', 'rx_theme_shop_apply_filters' );

/**
 * IDs of the products that have at least one purchasable variation in
 * any of the given sizes.
 *
 * Read straight from the variations' own stock status rather than
 * WooCommerce's attribute lookup table: that table is switched off on
 * this store (WooCommerce > Settings > Advanced > Features), and even
 * when on it is refreshed by a background job, so it can trail a sale by
 * a minute or more — wrong for a control labelled "In stock live". A
 * variation whose size is "Any size" (empty attribute) counts for every
 * size. Assumes sizes are a variation attribute, as they are for shoes.
 *
 * @param string[] $slugs pa_size term slugs.
 * @return int[]
 */
function rx_theme_shop_products_with_sizes_in_stock( array $slugs ): array {
	$query = new WP_Query(
		array(
			'post_type'      => 'product_variation',
			'post_status'    => 'publish', // WooCommerce marks disabled variations private.
			'posts_per_page' => -1,
			'fields'         => 'id=>parent',
			'no_found_rows'  => true,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- one bounded query per filtered page view.
				'relation' => 'AND',
				array(
					'key'     => 'attribute_pa_size',
					'value'   => array_merge( $slugs, array( '' ) ),
					'compare' => 'IN',
				),
				array(
					'key'     => '_stock_status',
					'value'   => 'outofstock',
					'compare' => '!=',
				),
			),
		)
	);

	return array_values( array_unique( array_map( 'absint', wp_list_pluck( $query->posts, 'post_parent' ) ) ) );
}

/*
 * ---------------------------------------------------------------------
 * Data for the bar
 * ---------------------------------------------------------------------
 */

/**
 * The archive's own URL with filters changed. The base is the current
 * URL without pagination (a filter change starts back at page 1), with
 * every other query argument (sort, search term, other filters) kept.
 *
 * @param array<string,string|false> $args Query args to set; false removes one.
 */
function rx_theme_shop_url( array $args ): string {
	$url = remove_query_arg( 'paged', get_pagenum_link( 1, false ) );

	return add_query_arg( $args, $url );
}

/**
 * URL that removes every filter (sizes — in every size system — both
 * toggles and the sidebar's Best for / Brands / price).
 */
function rx_theme_shop_clear_all_url(): string {
	$args = array(
		'filter_size'            => false,
		'query_type_size'        => false,
		'filter_mens-size'       => false,
		'query_type_mens-size'   => false,
		'filter_womens-size'     => false,
		'query_type_womens-size' => false,
		'rx_size_mode'           => false,
		'rx_in_stock'            => false,
		'rx_bundle'              => false,
	);

	foreach ( rx_theme_shop_sidebar_params() as $param ) {
		$args[ $param ] = false;
	}

	return rx_theme_shop_url( $args );
}

/**
 * The size chips, in shoe-size order, from whichever size taxonomy is
 * currently active (plain pa_size by default; pa_mens-size / pa_womens-size
 * once a gender is picked — see rx_theme_shop_size_taxonomy()).
 *
 * Each entry has the term's label, whether it's chosen, and the URL that
 * toggles it (adding or removing it from the chosen set; several sizes
 * combine as "any of these").
 *
 * @return array<int,array{label:string,active:bool,url:string}>
 */
function rx_theme_shop_size_chips(): array {
	// No mode chosen yet: rather than fall back to the unfiltered pa_size
	// list (US and EU sizes, plain and paired, all mixed together — the
	// exact confusion the Men's/Women's/EU switch exists to avoid), show
	// no size chips until the shopper picks one. Only applies when that
	// switch actually has something to offer; a category with no
	// gendered/EU size data at all still gets the plain pa_size list.
	if ( '' === rx_theme_shop_selected_size_mode() && array() !== rx_theme_shop_size_mode_options() ) {
		return array();
	}

	$taxonomy = rx_theme_shop_size_taxonomy();

	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		)
	);

	if ( is_wp_error( $terms ) || ! $terms ) {
		return array();
	}

	// 'eu' mode still reads pa_size (there's no separate EU taxonomy) but
	// only shows the EU-suffixed terms ("40eu"), not the US ones mixed
	// into the same taxonomy — see rx_theme_shop_selected_size_mode().
	if ( 'eu' === rx_theme_shop_selected_size_mode() ) {
		$terms = array_values(
			array_filter(
				$terms,
				static function ( WP_Term $term ): bool {
					return (bool) preg_match( '/eu$/i', $term->name );
				}
			)
		);

		if ( ! $terms ) {
			return array();
		}
	}

	// Creation order puts half sizes after the whole ones (7, 8, 9, 10, 8.5…),
	// so order numerically — including EU sizes ("40eu"), whose leading
	// digits PHP's float cast reads correctly and ignores the suffix on.
	// Non-numeric sizes (S/M/L) keep the admin's order.
	$rx_theme_size_is_numeric = static function ( string $name ): bool {
		return (bool) preg_match( '/^\d+(\.\d+)?(eu)?$/i', $name );
	};

	if ( count( $terms ) === count( array_filter( wp_list_pluck( $terms, 'name' ), $rx_theme_size_is_numeric ) ) ) {
		usort(
			$terms,
			static function ( WP_Term $a, WP_Term $b ): int {
				return (float) $a->name <=> (float) $b->name;
			}
		);
	}

	$selected = rx_theme_shop_selected_sizes();
	$params   = rx_theme_shop_layered_nav_params( $taxonomy );
	$chips    = array();

	foreach ( $terms as $term ) {
		$active = in_array( $term->slug, $selected, true );
		$next   = $active ? array_values( array_diff( $selected, array( $term->slug ) ) ) : array_merge( $selected, array( $term->slug ) );

		$chips[] = array(
			'label'  => $term->name,
			'active' => $active,
			'url'    => rx_theme_shop_url(
				array(
					$params['filter']     => $next ? implode( ',', $next ) : false,
					$params['query_type'] => $next ? 'or' : false,
				)
			),
		);
	}

	return $chips;
}

/**
 * The label above the size chips ("My US size:" / "My EU size:") — the
 * unit changes with the size mode, even though Men's/Women's are still
 * US sizes under the hood.
 */
function rx_theme_shop_size_chips_label(): string {
	return 'eu' === rx_theme_shop_selected_size_mode()
		? __( 'My EU size:', 'rx-theme' )
		: __( 'My US size:', 'rx-theme' );
}

/**
 * URL that resets the Men's / Women's / EU switch back to its starting
 * state (no mode chosen) and drops whatever size was picked under it —
 * the explicit "Reset" next to the switch, for when a shopper wants back
 * to the choice screen without hunting for the currently-active chip.
 */
function rx_theme_shop_size_mode_clear_url(): string {
	return rx_theme_shop_url(
		array(
			'filter_size'            => false,
			'query_type_size'        => false,
			'filter_mens-size'       => false,
			'query_type_mens-size'   => false,
			'filter_womens-size'     => false,
			'query_type_womens-size' => false,
			'rx_size_mode'           => false,
		)
	);
}

/**
 * URL that clears just the size selection, in whichever size taxonomy is
 * currently active — leaves the size-mode switch and every other filter as-is.
 */
function rx_theme_shop_size_clear_url(): string {
	$params = rx_theme_shop_layered_nav_params( rx_theme_shop_size_taxonomy() );

	return rx_theme_shop_url(
		array(
			$params['filter']     => false,
			$params['query_type'] => false,
		)
	);
}

/**
 * The Men's / Women's / EU switch shown above the size chips. Switching
 * mode drops whatever size was chosen in the old system —
 * "Men's 4" and "Women's 4" aren't the same shoe, and a US size and a EU
 * size are different numbers entirely, so carrying a selection across
 * would silently change what's being filtered for.
 *
 * EU has no gender split of its own (see rx_theme_shop_size_chips()): the
 * EU-sized products here are each already a single, explicit gender by
 * title, so there's no "Men's 4 vs Women's 5.5" ambiguity to resolve the
 * way there is for the paired US sizing.
 *
 * @return array<int,array{label:string,mode:string,active:bool,url:string}>
 */
function rx_theme_shop_size_mode_options(): array {
	$has_gendered_sizes = taxonomy_exists( 'pa_mens-size' ) || taxonomy_exists( 'pa_womens-size' );
	$size_names         = taxonomy_exists( 'pa_size' ) ? get_terms(
		array(
			'taxonomy'   => 'pa_size',
			'hide_empty' => true,
			'fields'     => 'names',
		)
	) : array();
	$has_eu_sizes       = ! is_wp_error( $size_names ) && (bool) array_filter(
		$size_names,
		static function ( string $name ): bool {
			return (bool) preg_match( '/eu$/i', $name );
		}
	);

	if ( ! $has_gendered_sizes && ! $has_eu_sizes ) {
		return array();
	}

	$current    = rx_theme_shop_selected_size_mode();
	$reset_args = array(
		'filter_size'            => false,
		'query_type_size'        => false,
		'filter_mens-size'       => false,
		'query_type_mens-size'   => false,
		'filter_womens-size'     => false,
		'query_type_womens-size' => false,
	);

	$options = array();

	if ( $has_gendered_sizes ) {
		$options[] = array(
			'label' => __( "Men's", 'rx-theme' ),
			'mode'  => 'men',
		);
		$options[] = array(
			'label' => __( "Women's", 'rx-theme' ),
			'mode'  => 'women',
		);
	}

	if ( $has_eu_sizes ) {
		$options[] = array(
			'label' => __( 'EU', 'rx-theme' ),
			'mode'  => 'eu',
		);
	}

	foreach ( $options as &$option ) {
		$option['active'] = $current === $option['mode'];
		// Clicking the active mode again deselects it (back to no mode
		// chosen), same toggle behaviour as the size chips themselves —
		// otherwise, once picked, there'd be no way back to the "choose
		// Men's / Women's / EU" starting state.
		$next_mode     = $option['active'] ? false : $option['mode'];
		$option['url'] = rx_theme_shop_url( array_merge( $reset_args, array( 'rx_size_mode' => $next_mode ) ) );
	}
	unset( $option );

	return $options;
}

/*
 * ---------------------------------------------------------------------
 * Rendering
 * ---------------------------------------------------------------------
 */

/**
 * Print the top of a product archive: the rotation-system strip, then
 * the filter bar (both above the grid).
 */
function rx_theme_render_shop_filters(): void {
	if ( rx_theme_bundle_offer_is_active() ) {
		get_template_part( 'template-parts/shop/rotation-strip' );
	}
	get_template_part( 'template-parts/shop/filter-bar' );
}

/**
 * Print the filter bar in the "no products found" state, but only when a
 * filter is what caused it: on a genuinely empty category the bar would
 * offer controls with nothing to act on.
 */
function rx_theme_render_shop_filters_when_active(): void {
	if ( rx_theme_shop_filters_active() ) {
		rx_theme_render_shop_filters();
	}
}

/**
 * Under the "no products found" message: a one-click way out.
 */
function rx_theme_render_clear_filters_link(): void {
	if ( ! rx_theme_shop_filters_active() ) {
		return;
	}

	printf(
		'<p class="rx-no-results"><a href="%1$s">%2$s</a></p>',
		esc_url( rx_theme_shop_clear_all_url() ),
		esc_html__( 'Clear all filters', 'rx-theme' )
	);
}
