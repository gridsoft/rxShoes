<?php
/**
 * "Build Your Rotation" page (/build-a-bundle/, page-build-a-bundle.php):
 * cart-reading helpers, the tier/discount maths, the add-to-cart redirect
 * that sends a shopper here, and the real cart-wide discount itself
 * (rx_theme_bundle_builder_apply_tier_discount(), bottom of this file) —
 * the client's explicit correction (2026-09-23): a completed 2- or
 * 3-pair rotation must genuinely be charged at the discounted price at
 * checkout, not just shown a preview number beside the real one. That
 * function is the one real discount engine on this site; everywhere else
 * a bundle discount is shown (inc/single-product.php's PDP rotation box,
 * template-parts/shop/rotation-cross-sell.php) is still illustrative
 * "if you bought this many" maths about products NOT yet in the cart,
 * which stays a preview since nothing to discount actually exists yet.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The real WooCommerce cart's contents, split into bundle-eligible items
 * (the rotation "pairs") and everything else. Eligibility lives on the
 * parent product (RX\Core\Bundles\BundleEligibility's checkbox is on the
 * product edit screen, not per-variation), so a cart line for a variation
 * is classified by its parent's flag.
 *
 * @return array{eligible: array<int,array{key:string,cart_item:array,product:WC_Product,parent:WC_Product}>, other: array<int,array{key:string,cart_item:array,product:WC_Product,parent:WC_Product}>}
 */
function rx_theme_bundle_builder_cart_buckets(): array {
	$eligible = array();
	$other    = array();

	if ( ! WC()->cart ) {
		return array(
			'eligible' => $eligible,
			'other'    => $other,
		);
	}

	foreach ( WC()->cart->get_cart() as $rx_theme_key => $rx_theme_cart_item ) {
		$rx_theme_product = $rx_theme_cart_item['data'] ?? null;

		if ( ! $rx_theme_product instanceof WC_Product ) {
			continue;
		}

		$rx_theme_parent_id = $rx_theme_product->get_parent_id();
		$rx_theme_parent    = $rx_theme_parent_id ? wc_get_product( $rx_theme_parent_id ) : $rx_theme_product;

		if ( ! $rx_theme_parent instanceof WC_Product ) {
			continue;
		}

		$rx_theme_entry = array(
			'key'       => $rx_theme_key,
			'cart_item' => $rx_theme_cart_item,
			'product'   => $rx_theme_product,
			'parent'    => $rx_theme_parent,
		);

		if ( rx_theme_product_is_bundle_eligible( $rx_theme_parent ) ) {
			$eligible[] = $rx_theme_entry;
		} else {
			$other[] = $rx_theme_entry;
		}
	}

	return array(
		'eligible' => $eligible,
		'other'    => $other,
	);
}

/**
 * Which discount tier a given number of rotation pairs (summed cart
 * quantity of eligible items, not just distinct line items — two pairs of
 * the same shoe still count as 2) currently sits in, using the same
 * Customizer-configured percentages as the rest of the site.
 *
 * @param int $pairs_count Total eligible-item quantity in the cart.
 * @return array{percent:float,label:string}
 */
function rx_theme_bundle_builder_tier( int $pairs_count ): array {
	if ( $pairs_count >= 3 ) {
		return array(
			'percent' => rx_theme_bundle_max_discount_percent(),
			'label'   => 'max',
		);
	}

	if ( 2 === $pairs_count ) {
		return array(
			'percent' => rx_theme_bundle_two_pack_discount_percent(),
			'label'   => 'two',
		);
	}

	return array(
		'percent' => 0.0,
		'label'   => 'none',
	);
}

/**
 * Real totals + a savings preview for the eligible bucket: what these
 * items actually cost today (their real prices, summed), and what they'd
 * cost at the tier the current pair-count qualifies for. Not applied to
 * the real WooCommerce cart total anywhere — see this file's top comment.
 *
 * @param array $eligible_entries As returned by rx_theme_bundle_builder_cart_buckets()['eligible'].
 * @return array{pairs_count:int,regular_total:float,preview_total:float,savings:float,tier:array{percent:float,label:string}}
 */
function rx_theme_bundle_builder_totals( array $eligible_entries ): array {
	$pairs_count   = 0;
	$regular_total = 0.0;

	foreach ( $eligible_entries as $rx_theme_entry ) {
		$rx_theme_qty   = (int) $rx_theme_entry['cart_item']['quantity'];
		$pairs_count   += $rx_theme_qty;
		$regular_total += rx_theme_product_price( $rx_theme_entry['product'] ) * $rx_theme_qty;
	}

	$rx_theme_tier    = rx_theme_bundle_builder_tier( $pairs_count );
	$rx_theme_preview = round( $regular_total * ( 1 - $rx_theme_tier['percent'] / 100 ), 2 );

	return array(
		'pairs_count'   => $pairs_count,
		'regular_total' => round( $regular_total, 2 ),
		'preview_total' => $rx_theme_preview,
		'savings'       => round( $regular_total - $rx_theme_preview, 2 ),
		'tier'          => $rx_theme_tier,
	);
}

/**
 * "7 / Royal Blue" for one cart line's chosen variation attributes.
 *
 * Deliberately not wc_get_formatted_cart_item_data() — that function
 * skips any attribute value it finds already present in the variation's
 * own auto-generated product name ("Puma Fuse 3.0 - 7, Royal Blue"), on
 * the assumption a caller displays that full variation name elsewhere.
 * The pair card shows the parent's plain name ("Puma Fuse 3.0") instead
 * (matching every other product name shown on this page), so that
 * skip-logic would silently drop the chosen size/colour entirely rather
 * than deduplicate it.
 *
 * @param array $cart_item One WC()->cart->get_cart() entry.
 */
function rx_theme_bundle_builder_variation_summary( array $cart_item ): string {
	$rx_theme_product = $cart_item['data'] ?? null;

	if ( ! $rx_theme_product instanceof WC_Product || ! $rx_theme_product->is_type( 'variation' ) || empty( $cart_item['variation'] ) || ! is_array( $cart_item['variation'] ) ) {
		return '';
	}

	$rx_theme_parts = array();

	foreach ( $cart_item['variation'] as $rx_theme_name => $rx_theme_value ) {
		$rx_theme_taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $rx_theme_name ) ) );

		if ( taxonomy_exists( $rx_theme_taxonomy ) ) {
			$rx_theme_term  = get_term_by( 'slug', $rx_theme_value, $rx_theme_taxonomy );
			$rx_theme_value = ( $rx_theme_term && ! is_wp_error( $rx_theme_term ) ) ? $rx_theme_term->name : $rx_theme_value;
		} else {
			$rx_theme_value = apply_filters( 'woocommerce_variation_option_name', $rx_theme_value, null, $rx_theme_taxonomy, $rx_theme_product ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core filter, applied as core does.
		}

		if ( '' !== $rx_theme_value ) {
			$rx_theme_parts[] = $rx_theme_value;
		}
	}

	return implode( ' / ', $rx_theme_parts );
}

/**
 * "US M10 / W11.5" (or the term's plain name for a non-paired size) for
 * one cart line's chosen pa_size — real data straight from the same
 * paired-size term the single product page's swatches already read, not
 * a fabricated spec.
 *
 * @param array $cart_item One WC()->cart->get_cart() entry.
 */
function rx_theme_bundle_builder_configured_size( array $cart_item ): string {
	if ( empty( $cart_item['variation']['attribute_pa_size'] ) || ! taxonomy_exists( 'pa_size' ) ) {
		return '';
	}

	$rx_theme_term = get_term_by( 'slug', $cart_item['variation']['attribute_pa_size'], 'pa_size' );

	if ( ! $rx_theme_term || is_wp_error( $rx_theme_term ) ) {
		return '';
	}

	if ( preg_match( '/^M(\S+)\s+W(\S+)$/i', $rx_theme_term->name, $rx_theme_matches ) ) {
		return sprintf( 'US M%1$s / W%2$s', $rx_theme_matches[1], $rx_theme_matches[2] );
	}

	return $rx_theme_term->name;
}

/**
 * The pa_colour term for one cart line's chosen colour, so the pair card
 * can show the same swatch dot + real colour name the single product
 * page's own swatch picker uses (rx_theme_colour_swatch_style()).
 *
 * @param array $cart_item One WC()->cart->get_cart() entry.
 */
function rx_theme_bundle_builder_configured_colour( array $cart_item ): ?WP_Term {
	if ( empty( $cart_item['variation']['attribute_pa_colour'] ) || ! taxonomy_exists( 'pa_colour' ) ) {
		return null;
	}

	$rx_theme_term = get_term_by( 'slug', $cart_item['variation']['attribute_pa_colour'], 'pa_colour' );

	return ( $rx_theme_term && ! is_wp_error( $rx_theme_term ) ) ? $rx_theme_term : null;
}

/**
 * The short detail parts shown under a cart line's name in the mini cart
 * and the checkout order review: "Size: US M5 / W6.5", the colour name,
 * and "Qty N" when more than one. Falls back to the raw variation summary
 * for attributes other than size/colour.
 *
 * @param array $cart_item   One WC()->cart->get_cart() entry.
 * @param bool  $include_qty Add "Qty N" when the quantity is above 1.
 * @return string[]
 */
function rx_theme_cart_item_details( array $cart_item, bool $include_qty = true ): array {
	$rx_theme_details = array();
	$rx_theme_size    = rx_theme_bundle_builder_configured_size( $cart_item );
	$rx_theme_colour  = rx_theme_bundle_builder_configured_colour( $cart_item );

	if ( $rx_theme_size ) {
		/* translators: %s: chosen size. */
		$rx_theme_details[] = sprintf( __( 'Size: %s', 'rx-theme' ), $rx_theme_size );
	}
	if ( $rx_theme_colour ) {
		$rx_theme_details[] = $rx_theme_colour->name;
	}
	if ( ! $rx_theme_details ) {
		$rx_theme_summary = rx_theme_bundle_builder_variation_summary( $cart_item );
		if ( $rx_theme_summary ) {
			$rx_theme_details[] = $rx_theme_summary;
		}
	}
	if ( $include_qty && (int) $cart_item['quantity'] > 1 ) {
		/* translators: %d: quantity. */
		$rx_theme_details[] = sprintf( __( 'Qty %d', 'rx-theme' ), (int) $cart_item['quantity'] );
	}

	return $rx_theme_details;
}

/**
 * "Pair 1: Agility • Barefoot Training" — a rotation pair's label, from
 * the product's "Best for" terms (falling back to its type label, then to
 * just "Pair N").
 *
 * @param WC_Product $product     The pair's parent product.
 * @param int        $pair_number 1-based position in the rotation.
 */
function rx_theme_bundle_pair_label( WC_Product $product, int $pair_number ): string {
	$rx_theme_label = rx_theme_product_best_for( $product );
	$rx_theme_label = $rx_theme_label ? $rx_theme_label : rx_theme_product_type_label( $product );
	/* translators: %d: pair number. */
	$rx_theme_pair = sprintf( __( 'Pair %d', 'rx-theme' ), $pair_number );

	if ( ! $rx_theme_label ) {
		return $rx_theme_pair;
	}

	/* translators: 1: "Pair N", 2: what the shoe is best for. */
	return sprintf( __( '%1$s: %2$s', 'rx-theme' ), $rx_theme_pair, $rx_theme_label );
}

/**
 * Grouping data for the cart page and checkout order-review tables:
 * rotation pairs first (in rotation order), then everything else, plus
 * the rotation header's note ("2 pairs • 35% off applied").
 *
 * @return array{items: array<string,array>, pair_numbers: array<string,int>, rotation_note: string, tier_percent: float}
 */
function rx_theme_cart_grouping(): array {
	$rx_theme_eligible     = rx_theme_bundle_builder_cart_buckets()['eligible'];
	$rx_theme_totals       = rx_theme_bundle_builder_totals( $rx_theme_eligible );
	$rx_theme_pair_numbers = array();

	foreach ( $rx_theme_eligible as $rx_theme_index => $rx_theme_entry ) {
		$rx_theme_pair_numbers[ $rx_theme_entry['key'] ] = $rx_theme_index + 1;
	}

	$rx_theme_items = WC()->cart ? WC()->cart->get_cart() : array();
	if ( $rx_theme_pair_numbers ) {
		// "+" (not array_merge) keeps the cart keys exactly as they are.
		$rx_theme_items = array_intersect_key( $rx_theme_items, $rx_theme_pair_numbers ) + array_diff_key( $rx_theme_items, $rx_theme_pair_numbers );
	}

	if ( $rx_theme_totals['tier']['percent'] > 0 ) {
		$rx_theme_note = sprintf(
			/* translators: 1: pairs in the rotation, 2: discount percentage. */
			_n( '%1$d pair • %2$s%% off applied', '%1$d pairs • %2$s%% off applied', $rx_theme_totals['pairs_count'], 'rx-theme' ),
			$rx_theme_totals['pairs_count'],
			rx_theme_format_percent( $rx_theme_totals['tier']['percent'] )
		);
	} else {
		$rx_theme_note = sprintf(
			/* translators: %s: 2-pack discount percentage. */
			__( '1 pair • add 1 more for %s%% off', 'rx-theme' ),
			rx_theme_format_percent( rx_theme_bundle_two_pack_discount_percent() )
		);
	}

	return array(
		'items'         => $rx_theme_items,
		'pair_numbers'  => $rx_theme_pair_numbers,
		'rotation_note' => $rx_theme_note,
		'tier_percent'  => (float) $rx_theme_totals['tier']['percent'],
	);
}

/**
 * Non-attribute item data other plugins add to a cart line
 * (woocommerce_get_item_data), normalised the same way
 * wc_get_formatted_cart_item_data() does. The variation attributes
 * themselves are left out — the "Size • Colour" line already shows them.
 *
 * @param array $cart_item One WC()->cart->get_cart() entry.
 * @return array<int,array{key:string,display:string}>
 */
function rx_theme_cart_item_extra_data( array $cart_item ): array {
	$rx_theme_extra = array();

	foreach ( (array) apply_filters( 'woocommerce_get_item_data', array(), $cart_item ) as $rx_theme_data ) { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core filter.
		$rx_theme_extra[] = array(
			'key'     => $rx_theme_data['key'] ?? ( $rx_theme_data['name'] ?? '' ),
			'display' => $rx_theme_data['display'] ?? ( $rx_theme_data['value'] ?? '' ),
		);
	}

	return $rx_theme_extra;
}

/**
 * "Men's Series" / "Women's Series" / "Unisex Series" — the same real
 * product_cat gender term rx_theme_product_gender_label() already reads,
 * just grammatically formatted for this page's card header.
 *
 * @param WC_Product $product Product being rendered.
 */
function rx_theme_bundle_builder_gender_series_label( WC_Product $product ): string {
	$rx_theme_gender = rx_theme_product_gender_label( $product );

	if ( '' === $rx_theme_gender ) {
		return '';
	}

	if ( 'Unisex' === $rx_theme_gender ) {
		return __( 'Unisex Series', 'rx-theme' );
	}

	return sprintf(
		/* translators: %s: gender label, e.g. "Men". */
		__( '%s\'s Series', 'rx-theme' ),
		$rx_theme_gender
	);
}

/**
 * A real stock-status label + a CSS state for the pair card's "Stock"
 * column — WooCommerce's own get_stock_status(), not an invented
 * warehouse location (the Figma reference's "In Sydney Hub" isn't real
 * data here — see PROJECT.md's "no unconfirmed stock location" rule,
 * already applied to the footer's trust copy).
 *
 * @param WC_Product $product Product being rendered.
 * @return array{label:string,is_ok:bool}
 */
function rx_theme_bundle_builder_stock_state( WC_Product $product ): array {
	$rx_theme_status = $product->get_stock_status();
	$rx_theme_labels = array(
		'instock'     => __( 'In stock', 'rx-theme' ),
		'outofstock'  => __( 'Out of stock', 'rx-theme' ),
		'onbackorder' => __( 'On backorder', 'rx-theme' ),
	);

	return array(
		'label' => $rx_theme_labels[ $rx_theme_status ] ?? ucfirst( $rx_theme_status ),
		'is_ok' => 'instock' === $rx_theme_status,
	);
}

/**
 * One product to recommend for the next open rotation slot: the same
 * curated-pairs-first, related-products-fallback logic the single product
 * page's cross-sell section already uses (rx_theme_rotation_related_products()),
 * based on the most recently added eligible item — skipping anything
 * that's already in the eligible bucket.
 *
 * @param ?WC_Product $anchor      The eligible item to base the recommendation on, or null if the cart has none yet.
 * @param int[]       $exclude_ids Parent product IDs already in the rotation.
 */
function rx_theme_bundle_builder_recommendation( ?WC_Product $anchor, array $exclude_ids ): ?WC_Product {
	if ( ! $anchor ) {
		return null;
	}

	foreach ( rx_theme_rotation_related_products( $anchor, 4 ) as $rx_theme_candidate ) {
		if ( ! in_array( $rx_theme_candidate->get_id(), $exclude_ids, true ) ) {
			return $rx_theme_candidate;
		}
	}

	return null;
}

/**
 * Real pricing for a recommended product if it were added to a specific
 * slot: its own price at THAT SLOT's real Customizer-configured tier
 * (rx_theme_bundle_builder_tier( $pair_number ) — Pair 2 is always the
 * two-pack tier, Pair 3+ is always the max tier, by slot position, not
 * by the cart's current actual fill count), and the total dollar amount
 * the whole rotation would save at that tier. Slot position rather than
 * "current count + 1" per the client's explicit correction (2026-09-22):
 * with only Pair 1 filled, both the Pair 2 and Pair 3 empty slots had
 * been showing "unlock 35%" (both computed as "1 filled + 1 more"),
 * where Pair 3 should always read as the 45% (max) tier regardless of
 * what's actually filled yet — matching the reference design's own
 * Pair-N-specific framing. Same maths rx_theme_rotation_pair_data()
 * already does for the single product page's cross-sell cards, adapted
 * to start from the real cart's current eligible total instead of one
 * anchor product.
 *
 * @param array      $eligible_entries As returned by rx_theme_bundle_builder_cart_buckets()['eligible'].
 * @param WC_Product $recommended      The product being recommended for this slot.
 * @param int        $pair_number      Which slot this recommendation is for (2, 3, ...).
 * @return array{item_price:float,item_regular_price:float,tier_percent:float,combined_savings:float,combined_regular_total:float,combined_preview_total:float}
 */
function rx_theme_bundle_builder_recommendation_pricing( array $eligible_entries, WC_Product $recommended, int $pair_number ): array {
	$rx_theme_before  = rx_theme_bundle_builder_totals( $eligible_entries );
	$rx_theme_tier    = rx_theme_bundle_builder_tier( $pair_number );
	$rx_theme_regular = rx_theme_product_price( $recommended );
	$rx_theme_item    = round( $rx_theme_regular * ( 1 - $rx_theme_tier['percent'] / 100 ), 2 );

	$rx_theme_combined_regular    = $rx_theme_before['regular_total'] + $rx_theme_regular;
	$rx_theme_combined_discounted = round( $rx_theme_combined_regular * ( 1 - $rx_theme_tier['percent'] / 100 ), 2 );

	return array(
		'item_price'             => $rx_theme_item,
		'item_regular_price'     => $rx_theme_regular,
		'tier_percent'           => $rx_theme_tier['percent'],
		'combined_savings'       => round( $rx_theme_combined_regular - $rx_theme_combined_discounted, 2 ),
		'combined_regular_total' => round( $rx_theme_combined_regular, 2 ),
		'combined_preview_total' => $rx_theme_combined_discounted,
	);
}

/**
 * "Recommended to complement your Puma Training & Inov-8 Athletics." —
 * built from the real _rx_type_label of whatever's already in the
 * rotation (rx_theme_product_type_label(), the same admin-set field the
 * pair card's image badge uses), not invented shoe-category copy.
 * Generic fallback when the cart has no eligible items yet, or none of
 * them have that field set.
 *
 * @param array $eligible_entries As returned by rx_theme_bundle_builder_cart_buckets()['eligible'].
 */
function rx_theme_bundle_builder_complement_text( array $eligible_entries ): string {
	$rx_theme_types = array();

	foreach ( $eligible_entries as $rx_theme_entry ) {
		$rx_theme_label = rx_theme_product_type_label( $rx_theme_entry['parent'] );

		if ( $rx_theme_label && ! in_array( $rx_theme_label, $rx_theme_types, true ) ) {
			$rx_theme_types[] = $rx_theme_label;
		}
	}

	if ( ! $rx_theme_types ) {
		return __( 'Add a bundle-eligible shoe to start building your rotation.', 'rx-theme' );
	}

	return sprintf(
		/* translators: %s: comma/and-separated list of shoe type labels already in the rotation. */
		__( 'Recommended to complement your %s.', 'rx-theme' ),
		wp_sprintf_l( '%l', $rx_theme_types )
	);
}

/**
 * "Matches Pair 1 profile" / "Matches Pair 1 + 2 profile" — which real
 * filled slots a recommendation is based on.
 *
 * @param int $filled_count How many eligible slots are already filled.
 */
function rx_theme_bundle_builder_matches_label( int $filled_count ): string {
	if ( $filled_count <= 0 ) {
		return '';
	}

	$rx_theme_numbers = implode( ' + ', range( 1, $filled_count ) );

	return sprintf(
		/* translators: %s: pair numbers already filled, e.g. "1 + 2". */
		__( 'Matches Pair %s profile', 'rx-theme' ),
		$rx_theme_numbers
	);
}

/**
 * Send a shopper here after a real, successful add-to-cart from the
 * single product page — instead of WooCommerce's default of staying on
 * the product page (this site has `woocommerce_cart_redirect_after_add`
 * set to "no", so normally nothing redirects at all).
 *
 * The single product page's "+ Add to rotation bundle" button (see
 * woocommerce/single-product/add-to-cart/variable.php) carries a
 * `formaction` that appends `rx_add_to_rotation=1` to the form's target
 * URL — it still submits the same `add-to-cart`/`variation_id` fields as
 * the real WooCommerce button next to it, so the add itself is entirely
 * WooCommerce's own standard handling; this filter only decides where to
 * send the shopper afterwards. Its sibling "Buy this pair only" button
 * (and any other add-to-cart submit on the site) carries no such flag, so
 * it falls through to the cart page — the client's explicit rule: a
 * non-eligible add, or an eligible product bought standalone, goes
 * straight to the cart; only a genuine "add to rotation" click goes to
 * the bundle builder.
 *
 * This site's only classic (non-AJAX) add-to-cart form is that single
 * product page one — the shop grid and the cross-sell cards use
 * WooCommerce's AJAX add-to-cart (a different code path this filter never
 * sees) — so redirecting every classic submit doesn't affect them.
 *
 * @param string|false $url            Default redirect target (false = none).
 * @param WC_Product   $adding_to_cart The parent product that was added (WC_Form_Handler resolves variations back to their parent before this filter runs).
 */
function rx_theme_bundle_builder_add_to_cart_redirect( $url, $adding_to_cart ) {
	if (
		! empty( $_REQUEST['rx_add_to_rotation'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only chooses a redirect destination, doesn't gate the add itself.
		&& $adding_to_cart instanceof WC_Product
		&& rx_theme_product_is_bundle_eligible( $adding_to_cart )
	) {
		return home_url( '/build-a-bundle/' );
	}

	return wc_get_cart_url();
}
add_filter( 'woocommerce_add_to_cart_redirect', 'rx_theme_bundle_builder_add_to_cart_redirect', 10, 2 );

/**
 * The real work behind the bundle builder's inline "Edit size / colour"
 * panel (template-parts/bundle-builder/pair-edit-panel.php): once its
 * form's real add-to-cart succeeds (same WooCommerce handling as any
 * other variation form — nothing about the add itself is special-cased),
 * remove the cart line it was editing, so the net effect is a genuine
 * swap rather than a second line sitting next to the first.
 *
 * Removing only AFTER the new line is confirmed added — never before —
 * means a failed/rejected add (e.g. the chosen combination turned out to
 * be out of stock) leaves the shopper's original pair untouched instead
 * of losing it.
 *
 * The nonce is checked against the specific cart item key being
 * replaced (rx_bundle_replace_{key}), not just verified generically, so
 * one edit panel's submission can't be replayed to remove a different
 * line.
 *
 * Quantity is force-reset to 1 on the resulting line regardless: if a
 * shopper opens the panel and confirms without actually changing
 * anything, the "new" variation is identical to the old one, so
 * WooCommerce's own add_to_cart() resolves to the SAME cart item key and
 * merges by incrementing its quantity (real, standard WC behaviour for
 * re-adding an identical line) rather than adding a fresh one — left
 * alone, that would silently double the quantity every time someone
 * opened and re-confirmed an unchanged pair. This page's whole model
 * assumes exactly one of each pair (see the PDP rotation box's own
 * "always buys exactly one pair" quantity-hiding rule), so forcing 1 is
 * the correct real state either way, not just a same-key special case.
 *
 * @param string $cart_item_key The newly added (or merged-into) cart item's key.
 */
function rx_theme_bundle_builder_swap_variation( string $cart_item_key ): void {
	$rx_theme_replace_key = isset( $_REQUEST['rx_bundle_replace_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rx_bundle_replace_key'] ) ) : '';
	$rx_theme_nonce       = isset( $_REQUEST['rx_bundle_replace_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rx_bundle_replace_nonce'] ) ) : '';

	if ( '' === $rx_theme_replace_key ) {
		return;
	}

	if ( ! wp_verify_nonce( $rx_theme_nonce, 'rx_bundle_replace_' . $rx_theme_replace_key ) ) {
		return;
	}

	WC()->cart->set_quantity( $cart_item_key, 1 );

	if ( $rx_theme_replace_key === $cart_item_key ) {
		return;
	}

	if ( ! WC()->cart->get_cart_item( $rx_theme_replace_key ) ) {
		return;
	}

	/*
	 * Keep the pair's place in the rotation: WooCommerce appends the new
	 * line at the end, so an edited Pair 1 would otherwise come back as
	 * the last pair. Move it to right after the line it replaces, then
	 * remove that line.
	 */
	$rx_theme_contents = WC()->cart->get_cart_contents();
	$rx_theme_new_item = $rx_theme_contents[ $cart_item_key ] ?? null;

	if ( $rx_theme_new_item ) {
		unset( $rx_theme_contents[ $cart_item_key ] );
		$rx_theme_ordered = array();

		foreach ( $rx_theme_contents as $rx_theme_key => $rx_theme_item ) {
			$rx_theme_ordered[ $rx_theme_key ] = $rx_theme_item;

			if ( $rx_theme_key === $rx_theme_replace_key ) {
				$rx_theme_ordered[ $cart_item_key ] = $rx_theme_new_item;
			}
		}

		WC()->cart->set_cart_contents( $rx_theme_ordered );
	}

	WC()->cart->remove_cart_item( $rx_theme_replace_key );
}
add_action( 'woocommerce_add_to_cart', 'rx_theme_bundle_builder_swap_variation' );

/**
 * "Size / colour updated for “Puma Fuse 3.0”." instead of WooCommerce's
 * "… has been added to your cart" when the add was really an edit-panel
 * swap (same nonce check as rx_theme_bundle_builder_swap_variation()) —
 * the shopper changed a pair, they didn't add one.
 *
 * @param string    $message  Default notice HTML.
 * @param int|array $products Product ID(s) added, keyed by ID => quantity.
 */
function rx_theme_bundle_builder_swap_message( $message, $products ) {
	$rx_theme_replace_key = isset( $_REQUEST['rx_bundle_replace_key'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rx_bundle_replace_key'] ) ) : '';
	$rx_theme_nonce       = isset( $_REQUEST['rx_bundle_replace_nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['rx_bundle_replace_nonce'] ) ) : '';

	if ( '' === $rx_theme_replace_key || ! wp_verify_nonce( $rx_theme_nonce, 'rx_bundle_replace_' . $rx_theme_replace_key ) ) {
		return $message;
	}

	$rx_theme_ids     = is_array( $products ) ? array_keys( $products ) : array( $products );
	$rx_theme_product = wc_get_product( (int) reset( $rx_theme_ids ) );

	if ( ! $rx_theme_product ) {
		return $message;
	}

	return esc_html(
		sprintf(
			/* translators: %s: product name. */
			__( 'Size / colour updated for “%s”.', 'rx-theme' ),
			$rx_theme_product->get_name()
		)
	);
}
add_filter( 'wc_add_to_cart_message_html', 'rx_theme_bundle_builder_swap_message', 10, 2 );

/**
 * The real bundle discount — a negative WC_Cart fee applied whenever the
 * cart's real eligible items reach a real tier (2 or 3+ pairs), using
 * this store's actual Customizer-configured percentages
 * (rx_theme_bundle_two_pack_discount_percent() /
 * rx_theme_bundle_max_discount_percent(), via rx_theme_bundle_builder_tier()
 * and rx_theme_bundle_builder_totals() — the exact same maths every
 * preview figure on /build-a-bundle/ already uses, now actually charged
 * rather than only shown beside the real price).
 *
 * A negative fee is WooCommerce's own standard mechanism for a cart-wide
 * discount that isn't a coupon code — WC_Cart::calculate_fees() totals it
 * in with everything else, and the classic cart/checkout templates
 * already render a fee line automatically (see
 * woocommerce/templates/checkout/review-order.php's own `foreach (
 * WC()->cart->get_fees() as $fee )` loop — nothing needed on the
 * template side here). Runs on every real cart calculation, not only on
 * /build-a-bundle/, so the discount is genuinely present on the cart and
 * checkout pages too — that's the point: a real 2- or 3-pair rotation is
 * charged at the discounted price wherever a shopper checks out, not
 * just previewed on this one page.
 *
 * `$cart` and WC()->cart are the same object here — this hook fires from
 * inside WC_Cart::calculate_fees() as `do_action( '...', $this )` — so
 * rx_theme_bundle_builder_cart_buckets()'s own WC()->cart read is safe to
 * reuse rather than re-deriving the same eligible-items loop a second
 * time.
 *
 * @param WC_Cart $cart The cart being totalled.
 */
function rx_theme_bundle_builder_apply_tier_discount( WC_Cart $cart ): void {
	if ( ( is_admin() && ! defined( 'DOING_AJAX' ) ) || ! rx_theme_bundle_offer_is_active() ) {
		return;
	}

	$rx_theme_eligible = rx_theme_bundle_builder_cart_buckets()['eligible'];
	$rx_theme_totals   = rx_theme_bundle_builder_totals( $rx_theme_eligible );

	if ( $rx_theme_totals['tier']['percent'] <= 0 || $rx_theme_totals['savings'] <= 0 ) {
		return;
	}

	$cart->add_fee(
		sprintf(
			/* translators: %s: discount percentage. */
			__( 'Rotation bundle discount (-%s%%)', 'rx-theme' ),
			rx_theme_format_percent( $rx_theme_totals['tier']['percent'] )
		),
		-$rx_theme_totals['savings'],
		false
	);
}
add_action( 'woocommerce_cart_calculate_fees', 'rx_theme_bundle_builder_apply_tier_discount' );
