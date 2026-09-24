<?php
/**
 * Single product page helpers: the colour swatch / size pill rendering
 * used by woocommerce/single-product/add-to-cart/variable.php, plus the
 * script that wires the swatch buttons to WooCommerce's real variation
 * form (see assets/js/variation-swatches.js for what it does and why).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The swatch-driving script — single product pages, and the bundle
 * builder's inline "Edit size / colour" panels (page-build-a-bundle.php),
 * which render the exact same real variation form/swatch markup (see
 * template-parts/product/variation-swatch-rows.php).
 */
function rx_theme_enqueue_variation_swatches_script(): void {
	if ( ! is_product() && ! is_page( 'build-a-bundle' ) && ! is_cart() ) {
		return;
	}

	$file = RX_THEME_DIR . '/assets/js/variation-swatches.js';

	wp_enqueue_script(
		'rx-theme-variation-swatches',
		RX_THEME_URI . '/assets/js/variation-swatches.js',
		array( 'jquery', 'wc-add-to-cart-variation' ),
		file_exists( $file ) ? (string) filemtime( $file ) : RX_THEME_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'rx_theme_enqueue_variation_swatches_script' );

/**
 * Colour always shown before size, regardless of the order the product's
 * own attributes happen to be saved in — the older dev-test products
 * (built by a different script, before the import command existed) have
 * size first; the imported catalogue has colour first. Any other
 * attribute keeps its relative position after those two.
 *
 * @param array<string,mixed> $attributes Keyed by attribute taxonomy/name, in the product's own order.
 * @return array<string,mixed> Same entries, colour-then-size-first.
 */
function rx_theme_order_variation_attributes( array $attributes ): array {
	$priority = array( 'pa_colour', 'pa_size' );
	$ordered  = array();

	foreach ( $priority as $key ) {
		if ( isset( $attributes[ $key ] ) ) {
			$ordered[ $key ] = $attributes[ $key ];
		}
	}

	return $ordered + $attributes;
}

/**
 * Black or white — whichever reads more clearly on top of a given hex
 * colour, by relative luminance (WCAG's own formula, simplified for sRGB
 * without the full linearisation curve — close enough for picking a
 * label colour, not for a contrast-ratio compliance claim).
 *
 * @param string $hex '#rrggbb' or '#rgb'.
 */
function rx_theme_readable_text_color( string $hex ): string {
	$hex = ltrim( $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return '#111111';
	}

	$r = hexdec( substr( $hex, 0, 2 ) );
	$g = hexdec( substr( $hex, 2, 2 ) );
	$b = hexdec( substr( $hex, 4, 2 ) );

	$luminance = ( 0.299 * $r + 0.587 * $g + 0.114 * $b ) / 255;

	return $luminance > 0.6 ? '#111111' : '#ffffff';
}

/**
 * The colour(s) behind one pa_colour term's swatch: an admin's own pick
 * (RX\Core\Catalog\ColourSwatches term meta) always wins when set;
 * otherwise a best-effort guess from recognisable colour words anywhere
 * in the term's name (e.g. picks out "black" and "gold" from "BLACK/
 * METALLIC GOLD-WHITE") — not a confirmed colour, but better than
 * nothing for the many brand-marketing names nobody has picked a real
 * colour for yet. [null, null] when nothing recognisable is found.
 *
 * @param WP_Term $term pa_colour term.
 * @return array{0:?string,1:?string}
 */
function rx_theme_colour_swatch_hexes( WP_Term $term ): array {
	list( $hex1, $hex2 ) = class_exists( \RX\Core\Catalog\ColourSwatches::class )
		? \RX\Core\Catalog\ColourSwatches::get_colours( $term->term_id )
		: array( null, null );

	if ( ! $hex1 ) {
		list( $hex1, $hex2 ) = rx_theme_guess_colour_words( $term->name );
	}

	return array( $hex1, $hex2 );
}

/**
 * The inline `style` attribute value for one pa_colour swatch's colour
 * box — a solid background for a single-colour term, a 50/50 diagonal
 * split for a two-colour one, or a neutral grey/white stripe ("unknown",
 * not an actual wrong colour) when rx_theme_colour_swatch_hexes() found
 * nothing.
 *
 * @param WP_Term $term pa_colour term.
 */
function rx_theme_colour_swatch_style( WP_Term $term ): string {
	list( $hex1, $hex2 ) = rx_theme_colour_swatch_hexes( $term );

	if ( $hex1 && $hex2 ) {
		return sprintf( 'background:linear-gradient(135deg, %1$s 50%%, %2$s 50%%);', esc_attr( $hex1 ), esc_attr( $hex2 ) );
	}

	if ( $hex1 ) {
		return sprintf( 'background:%s;', esc_attr( $hex1 ) );
	}

	return 'background:repeating-linear-gradient(135deg, #e8e8e8, #e8e8e8 4px, #ffffff 4px, #ffffff 8px);';
}

/**
 * Readable text colour for the short label printed inside a swatch's
 * colour box — black or white, whichever reads on that swatch's colour
 * (the first one, for a two-tone swatch — see rx_theme_readable_text_color()).
 * Grey when the swatch is the "unknown colour" placeholder.
 *
 * @param WP_Term $term pa_colour term.
 */
function rx_theme_colour_swatch_label_color( WP_Term $term ): string {
	list( $hex1 ) = rx_theme_colour_swatch_hexes( $term );

	return $hex1 ? rx_theme_readable_text_color( $hex1 ) : '#767676';
}

/**
 * Pull up to two recognisable colour words out of a term name, in the
 * order they appear — "CORE BLACK/RADIANT AQUA/CORE BLACK" -> black,
 * aqua. Deliberately a plain word dictionary (real colour names, not
 * invented hexes), so it only ever guesses a real colour, never a made-up
 * shade — and only when a whole word matches, not a substring (so
 * "GOLD" matches but the "old" inside "BOLD" or "GOLDEN" doesn't).
 *
 * @param string $name Term name.
 * @return array{0:?string,1:?string}
 */
function rx_theme_guess_colour_words( string $name ): array {
	static $dictionary = array(
		'black'     => '#000000',
		'white'     => '#ffffff',
		'grey'      => '#808080',
		'gray'      => '#808080',
		'silver'    => '#c0c0c0',
		'navy'      => '#000080',
		'blue'      => '#0000ff',
		'red'       => '#ff0000',
		'green'     => '#008000',
		'orange'    => '#ffa500',
		'pink'      => '#ffc0cb',
		'beige'     => '#f5f5dc',
		'gold'      => '#ffd700',
		'turquoise' => '#40e0d0',
		'olive'     => '#808000',
		'slate'     => '#708090',
		'brown'     => '#a52a2a',
		'purple'    => '#800080',
		'violet'    => '#8a2be2',
		'plum'      => '#dda0dd',
		'yellow'    => '#ffd700',
		'cyan'      => '#00ffff',
		'aqua'      => '#00ffff',
		'maroon'    => '#800000',
		'tan'       => '#d2b48c',
		'coral'     => '#ff7f50',
		'crimson'   => '#dc143c',
		'cherry'    => '#dc143c',
		'lime'      => '#32cd32',
		'charcoal'  => '#36454f',
		'gum'       => '#c9a66b',
		'sand'      => '#c2b280',
		'mocha'     => '#7b4b31',
		'smoke'     => '#b0b0b0',
		'rose'      => '#ff007f',
		'chalk'     => '#f2f2f2',
		'cloud'     => '#f2f2f2',
		'moon'      => '#d8d8d8',
		'clay'      => '#b66a4f',
		'earth'     => '#6b4f3a',
		'fuchsia'   => '#ff00ff',
	);

	$words   = preg_split( '/[^a-z]+/i', strtolower( $name ) );
	$matches = array();

	foreach ( $words as $word ) {
		if ( isset( $dictionary[ $word ] ) && ! in_array( $dictionary[ $word ], $matches, true ) ) {
			$matches[] = $dictionary[ $word ];
			if ( count( $matches ) === 2 ) {
				break;
			}
		}
	}

	return array( $matches[0] ?? null, $matches[1] ?? null );
}

/**
 * A pa_size term's button label. The paired "M9 W10.5" terms (see the
 * import command's pairing logic) render as two stacked lines — "M 9.0"
 * / "W 10.5", whole numbers formatted to one decimal place for visual
 * consistency with the halves — to match the reference design; anything
 * else (EU sizes, plain numbers) renders as-is on one line.
 *
 * Also includes a "Sold out" line, hidden unless the button carries
 * .is-disabled (see style.css) — added by assets/js/variation-swatches.js
 * from WooCommerce's own real per-option availability, not guessed here.
 *
 * @param WP_Term $term pa_size term.
 */
function rx_theme_variation_term_label( WP_Term $term ): string {
	if ( preg_match( '/^M(\S+)\s+W(\S+)$/i', $term->name, $matches ) ) {
		return sprintf(
			'<span class="rx-swatch__line">M %1$s</span><span class="rx-swatch__line rx-swatch__line--secondary">W %2$s</span><span class="rx-swatch__line rx-swatch__line--sold-out">%3$s</span>',
			esc_html( number_format( (float) $matches[1], 1 ) ),
			esc_html( number_format( (float) $matches[2], 1 ) ),
			esc_html__( 'Sold out', 'rx-theme' )
		);
	}

	return '<span class="rx-swatch__line">' . esc_html( $term->name ) . '</span><span class="rx-swatch__line rx-swatch__line--sold-out">' . esc_html__( 'Sold out', 'rx-theme' ) . '</span>';
}

/**
 * The pa_size terms, smallest to largest — wc_get_product_terms() otherwise
 * returns them in the taxonomy's own (effectively alphabetical) term
 * order, which sorts "10" and "11" before "7", "8", "9" as strings. Reads
 * the leading number out of each term's name regardless of format (plain
 * "9", paired "M9 W10.5" — sorted by the men's number, EU sizes like
 * "40eu") and sorts on that; a name with no leading number sorts after
 * every real size rather than throwing it away.
 *
 * @param WP_Term[] $terms pa_size terms, any order.
 * @return WP_Term[] Same terms, ascending by size.
 */
function rx_theme_sort_size_terms( array $terms ): array {
	usort(
		$terms,
		static function ( WP_Term $a, WP_Term $b ): int {
			$rx_theme_key = static function ( WP_Term $term ): float {
				return preg_match( '/-?\d+(\.\d+)?/', $term->name, $matches )
					? (float) $matches[0]
					: PHP_FLOAT_MAX;
			};

			return $rx_theme_key( $a ) <=> $rx_theme_key( $b );
		}
	);

	return $terms;
}

/**
 * The single-product title, split into a bold lead and a lighter tail
 * when the real product name has an em/en-dash break in it ("ONE V2 —
 * Functional Training Shoe" -> "ONE V2" / "Functional Training Shoe").
 * Purely reading the actual title text, not inventing a subtitle.
 * Deliberately only em/en dash, not a plain hyphen — most imported
 * product titles use " - " as a generic Brand - Model - Colour
 * separator (see the import command), so splitting on a plain hyphen
 * would cut nearly every title at an arbitrary point rather than at a
 * genuine subtitle. A title without an em/en dash renders unsplit.
 *
 * @param WC_Product $product Product being viewed.
 * @return array{0:string,1:string} [lead, tail] — tail is '' when there's no split.
 */
function rx_theme_product_title_parts( WC_Product $product ): array {
	$name = $product->get_name();

	if ( preg_match( '/^(.+?)\s+[—–]\s+(.+)$/u', $name, $matches ) ) {
		return array( $matches[1], $matches[2] );
	}

	return array( $name, '' );
}

/**
 * The product's Men/Women/Unisex label, from the same product_cat terms
 * the shop's category cards and size filters already use (see the
 * import command's gender classification) — not a new field.
 *
 * @param WC_Product $product Product being viewed.
 */
function rx_theme_product_gender_label( WC_Product $product ): string {
	$terms = get_the_terms( $product->get_id(), 'product_cat' );

	if ( ! is_array( $terms ) ) {
		return '';
	}

	foreach ( $terms as $term ) {
		if ( in_array( $term->slug, array( 'men', 'women', 'unisex' ), true ) ) {
			return $term->name;
		}
	}

	return '';
}

/**
 * "Mens US 9.0 = Womens US 10.5" for the product's default (or first
 * available) paired size — real data straight from the pa_size term
 * ("M9 W10.5"), not a fabricated UK/EU conversion table (no such mapping
 * exists here). Static per page load, not yet live-updating as a
 * different size is picked — that needs the same swatch-click event the
 * variation JS already fires, a follow-up once this layout pass is
 * confirmed. Empty string when the product has no paired sizing (a
 * single-gender or EU-only product).
 *
 * @param WC_Product $product Product being viewed.
 */
function rx_theme_product_size_equivalent_line( WC_Product $product ): string {
	if ( ! $product instanceof WC_Product_Variable || ! taxonomy_exists( 'pa_size' ) ) {
		return '';
	}

	$default_slug = $product->get_variation_default_attribute( 'pa_size' );
	$term         = $default_slug ? get_term_by( 'slug', $default_slug, 'pa_size' ) : null;

	if ( ! $term ) {
		$terms = wc_get_product_terms( $product->get_id(), 'pa_size', array( 'fields' => 'all' ) );
		foreach ( $terms as $candidate ) {
			if ( preg_match( '/^M(\S+)\s+W(\S+)$/i', $candidate->name ) ) {
				$term = $candidate;
				break;
			}
		}
	}

	if ( ! $term || ! preg_match( '/^M(\S+)\s+W(\S+)$/i', $term->name, $matches ) ) {
		return '';
	}

	return sprintf(
		/* translators: 1: men's US size, 2: women's US size. */
		__( 'Mens US %1$s = Womens US %2$s', 'rx-theme' ),
		$matches[1],
		$matches[2]
	);
}

/**
 * The "Build Your Rotation" tile numbers for one tier (2-pack or
 * 3-pack) — real maths off the product's own price and the discount %
 * actually configured under Appearance > Customize > Bundle Discount
 * (rx_theme_bundle_two_pack_discount_percent() /
 * rx_theme_bundle_max_discount_percent()), the same settings the shop
 * card already uses. Not the reference mockup's own numbers — those
 * ($160.30/pair, $138 saved) work out to a 30% discount, which doesn't
 * match either of this store's configured tiers (35%/45% by default);
 * copying them would show a discount nobody actually set.
 *
 * @param WC_Product $product         Product being viewed.
 * @param float      $discount_percent e.g. 35.0.
 * @param int        $pairs            2 or 3.
 * @return array{price_per_pair:float,total_savings:float,discount_percent:float}
 */
function rx_theme_bundle_tier_data( WC_Product $product, float $discount_percent, int $pairs ): array {
	$price          = rx_theme_product_price( $product );
	$price_per_pair = round( $price * ( 1 - $discount_percent / 100 ), 2 );

	return array(
		'price_per_pair'   => $price_per_pair,
		'total_savings'    => round( ( $price - $price_per_pair ) * $pairs, 2 ),
		'discount_percent' => $discount_percent,
	);
}

/**
 * Up to $limit products for the "Complete Your Rotation" cross-sell —
 * and, via rx_theme_bundle_builder_recommendation() (inc/bundle-builder.php),
 * the bundle builder's "recommended pairing" card, whose whole point is
 * a product a shopper can add straight into the rotation. Every result
 * is filtered to bundle-eligible products only: a shoe that isn't itself
 * eligible can't actually earn a "Pair N" slot or a tier discount, so
 * recommending one would be a dead end (found the hard way — WooCommerce's
 * own related-products fallback below has no concept of this store's
 * eligibility flag and happily returned a non-eligible shoe, which the
 * bundle builder's add flow then couldn't redirect back to, or ever
 * show as filled).
 *
 * Prefers the curated Pair 2 / Pair 3 picks set on the product edit
 * screen's Bundle tab (RX\Core\Bundles\BundleRotationPairs, via the
 * rx_theme_product_bundle_pair_ids filter) — a merchandiser's actual
 * "this shoe pairs with that shoe" call. Only falls back to
 * WooCommerce's generic related-products algorithm (shared categories/
 * tags) for products nobody has curated yet, so the section still shows
 * something rather than nothing.
 *
 * @param WC_Product $product Product being viewed.
 * @param int        $limit   Max number of products to return.
 * @return WC_Product[]
 */
function rx_theme_rotation_related_products( WC_Product $product, int $limit ): array {
	$rx_theme_eligible_only = static function ( array $ids ) use ( $limit ): array {
		$rx_theme_products = array();

		foreach ( $ids as $rx_theme_id ) {
			$rx_theme_candidate = wc_get_product( $rx_theme_id );

			if ( $rx_theme_candidate instanceof WC_Product && rx_theme_product_is_bundle_eligible( $rx_theme_candidate ) ) {
				$rx_theme_products[] = $rx_theme_candidate;
			}

			if ( count( $rx_theme_products ) >= $limit ) {
				break;
			}
		}

		return $rx_theme_products;
	};

	/**
	 * Filters the curated rotation-pair product IDs for this product, in
	 * Pair 2, Pair 3, ... order.
	 *
	 * @param int[]      $ids     Default: none — rx-core answers from the Bundle tab.
	 * @param WC_Product $product Product being viewed.
	 */
	$curated_ids = (array) apply_filters( 'rx_theme_product_bundle_pair_ids', array(), $product );
	$curated_ids = array_map( 'absint', $curated_ids );
	$curated     = $curated_ids ? $rx_theme_eligible_only( $curated_ids ) : array();

	if ( $curated ) {
		return $curated;
	}

	// Over-fetch before filtering — WC's algorithm doesn't know about
	// eligibility, so asking for exactly $limit and then filtering could
	// under-return even when enough eligible products exist.
	$related_ids = wc_get_related_products( $product->get_id(), max( $limit * 4, 12 ) );

	return $rx_theme_eligible_only( $related_ids );
}

/**
 * The "Pair N recommendation" card's numbers for one related product —
 * its own price discounted at the given tier %, the combined total for
 * every pair up to and including this one (current product + each
 * related product picked so far), and what that combined total would be
 * at full price. All real maths off real prices and the Customizer's
 * configured discount, not the reference mockup's own (30%/45%) numbers
 * — see rx_theme_bundle_tier_data().
 *
 * @param WC_Product   $current_product The product being viewed (always "Pair 1").
 * @param WC_Product[] $pair_products   The related products making up pairs 2..N, in order.
 * @param int          $pair_index      Which pair this card is for (2 or 3).
 * @param float        $discount_percent Tier discount, e.g. 35.0 for a 2-pack.
 * @return array{item_price:float,item_regular_price:float,item_savings:float,combined_total:float,combined_regular_total:float}
 */
function rx_theme_rotation_pair_data( WC_Product $current_product, array $pair_products, int $pair_index, float $discount_percent ): array {
	$item            = $pair_products[ $pair_index - 2 ];
	$item_regular    = rx_theme_product_price( $item );
	$item_discounted = round( $item_regular * ( 1 - $discount_percent / 100 ), 2 );

	$regular_total = rx_theme_product_price( $current_product );
	foreach ( array_slice( $pair_products, 0, $pair_index - 1 ) as $included ) {
		$regular_total += rx_theme_product_price( $included );
	}

	return array(
		'item_price'             => $item_discounted,
		'item_regular_price'     => $item_regular,
		'item_savings'           => round( $item_regular - $item_discounted, 2 ),
		'combined_total'         => round( $regular_total * ( 1 - $discount_percent / 100 ), 2 ),
		'combined_regular_total' => $regular_total,
	);
}

/**
 * The product's "Key features" tiles (up to 3 of {title, text, icon}),
 * entered on the product's "Key features" tab — owned by rx-core
 * (RX\Core\Catalog\KeyFeatures). Empty when rx-core is inactive or the
 * product has none, so the PDP simply leaves the row out.
 *
 * @param WC_Product $product Product being rendered.
 * @return array<int,array{title:string,text:string,icon:string}>
 */
function rx_theme_product_key_features( WC_Product $product ): array {
	if ( ! class_exists( '\RX\Core\Catalog\KeyFeatures' ) ) {
		return array();
	}

	return \RX\Core\Catalog\KeyFeatures::get_rows( $product );
}
