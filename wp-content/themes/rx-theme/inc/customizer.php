<?php
/**
 * Customizer: homepage content editable from the dashboard.
 *
 * Deliberately using core Customizer API, not a fields plugin (ACF, etc.)
 * — the plan (PROJECT.md §3) keeps plugins to only what justifies itself,
 * and a handful of scalar fields + one image per homepage section doesn't
 * justify a new dependency. If a later section needs repeatable/complex
 * fields this decision should be revisited.
 *
 * One panel ("RX Homepage") holds one section per homepage block, so this
 * file grows as more sections (category cards, product grid, etc.) get
 * built — Hero is the first.
 *
 * Field definitions (rx_theme_hero_fields()) are the single source of
 * truth for both registration and runtime defaults — get_theme_mod()
 * does NOT automatically use a Customizer setting's 'default', it only
 * pre-fills the Customizer UI, so every template read goes through
 * rx_theme_get_mod() (see inc/template-tags.php) instead of a raw
 * get_theme_mod() call.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the RX Homepage panel, Hero section, and its settings/controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_customize_register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_panel(
		'rx_homepage',
		array(
			'title'       => __( 'RX Homepage', 'rx-theme' ),
			'description' => __( 'Content for the homepage template parts. Each section below maps to one block on the front page.', 'rx-theme' ),
			'priority'    => 30,
		)
	);

	rx_theme_register_hero_section( $wp_customize );
	rx_theme_register_category_cards_section( $wp_customize );
	rx_theme_register_rotation_section( $wp_customize );
	rx_theme_register_biomech_section( $wp_customize );
	rx_theme_register_brands_section( $wp_customize );
	rx_theme_register_trust_section( $wp_customize );
	rx_theme_register_community_section( $wp_customize );
	rx_theme_register_bestsellers_section( $wp_customize );
}
add_action( 'customize_register', 'rx_theme_customize_register' );

/**
 * Register a Customizer section's settings/controls from a field
 * definitions array (shape: rx_theme_hero_fields()'s return type).
 * Shared by every homepage section so each one doesn't repeat the same
 * add_setting()/add_control() loop.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 * @param string               $section_id   Registered section ID.
 * @param array<string,array>  $fields       Field definitions.
 */
function rx_theme_register_fields( WP_Customize_Manager $wp_customize, string $section_id, array $fields ): void {
	foreach ( $fields as $id => $field ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $field['default'],
				'sanitize_callback' => $field['sanitize'],
				'transport'         => $field['transport'],
			)
		);

		$wp_customize->add_control(
			$id,
			array(
				'section' => $section_id,
				'label'   => $field['label'],
				'type'    => $field['type'],
			)
		);
	}
}

/**
 * Field definitions for the Hero section: id => [default, label, type,
 * sanitize callback, transport]. Shared between registration
 * (add_setting/add_control) and rx_theme_get_mod()'s default lookup.
 *
 * Defaults and copy casing match the Figma "Home" frame's hero node tree
 * exactly, pulled from the Figma API (2026-09-21) — not eyeballed off a
 * screenshot. The 2-pairs/3-pairs percentages are known to conflict with
 * other numbers elsewhere in the Figma file (see PROJECT.md §6.2 dev
 * log); that's exactly why they're editable fields and not hardcoded —
 * whoever confirms the real number can fix it here without a code change.
 *
 * @return array<string,array{default:string,label:string,type:string,sanitize:string,transport:string}>
 */
function rx_theme_hero_fields(): array {
	return array(
		'rx_hero_eyebrow'            => array(
			'default'   => __( 'ATHLETE SPEC TRAINING ROTATION', 'rx-theme' ),
			'label'     => __( 'Eyebrow text (black pill badge)', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_heading'            => array(
			'default'   => __( "ONE SHOE CAN'T DO\nEVERYTHING.", 'rx-theme' ),
			'label'     => __( 'Headline', 'rx-theme' ),
			'type'      => 'textarea',
			'sanitize'  => 'sanitize_textarea_field',
			'transport' => 'postMessage',
		),
		'rx_hero_subheading'         => array(
			'default'   => __( 'BUILD YOUR TRAINING ROTATION.', 'rx-theme' ),
			'label'     => __( 'Sub-headline (blue)', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_description'        => array(
			'default'   => __( 'Find the right shoes for the way you train. Lifting, running, conditioning — match your footwear to the session.', 'rx-theme' ),
			'label'     => __( 'Description', 'rx-theme' ),
			'type'      => 'textarea',
			'sanitize'  => 'sanitize_textarea_field',
			'transport' => 'postMessage',
		),
		'rx_hero_tier_1_flag'        => array(
			'default'   => __( 'TIER 1', 'rx-theme' ),
			'label'     => __( 'Tier 1 badge (red)', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_tier_1_text'        => array(
			'default'   => __( '2 PAIRS → SAVE {two_pack}%', 'rx-theme' ),
			'label'     => __( 'Tier 1 text', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_tier_2_flag'        => array(
			'default'   => __( 'MAX VALUE', 'rx-theme' ),
			'label'     => __( 'Tier 2 badge (lime)', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_tier_2_text'        => array(
			'default'   => __( '3 PAIRS → SAVE {three_pack}%', 'rx-theme' ),
			'label'     => __( 'Tier 2 text (blue)', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_tier_note'          => array(
			'default'   => __( 'Mix brands. Mix styles. Pick your colours and sizes. Discount applied automatically at checkout.', 'rx-theme' ),
			'label'     => __( 'Tier box footnote', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_cta_primary_text'   => array(
			'default'   => __( 'BUILD MY BUNDLE', 'rx-theme' ),
			'label'     => __( 'Primary button text', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_cta_primary_url'    => array(
			'default'   => '/build-a-bundle/',
			'label'     => __( 'Primary button link', 'rx-theme' ),
			'type'      => 'url',
			'sanitize'  => 'esc_url_raw',
			'transport' => 'refresh',
		),
		'rx_hero_cta_secondary_text' => array(
			'default'   => __( 'SHOP ALL SHOES', 'rx-theme' ),
			'label'     => __( 'Secondary button text', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_cta_secondary_url'  => array(
			'default'   => '/shop/',
			'label'     => __( 'Secondary button link', 'rx-theme' ),
			'type'      => 'url',
			'sanitize'  => 'esc_url_raw',
			'transport' => 'refresh',
		),
	);
}

/**
 * Register the Hero section's controls from rx_theme_hero_fields(), plus
 * the background image control (which isn't a scalar field).
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_register_hero_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'rx_hero',
		array(
			'title'       => __( 'Hero', 'rx-theme' ),
			'description' => rx_theme_bundle_tokens_help(),
			'panel'       => 'rx_homepage',
		)
	);

	rx_theme_register_fields( $wp_customize, 'rx_hero', rx_theme_hero_fields() );

	// Background image — own setting/control type (WP_Customize_Image_Control).
	$wp_customize->add_setting(
		'rx_hero_image',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'rx_hero_image',
			array(
				'section'     => 'rx_hero',
				'label'       => __( 'Background image', 'rx-theme' ),
				'description' => __( 'Placeholder is a Figma mockup photo — not a licensed asset. Replace before launch.', 'rx-theme' ),
			)
		)
	);
}

/**
 * Field definitions for the Power Rotation section (Figma: "Section -
 * 3", "Build Your 3-Stage Power Rotation") — eyebrow/heading/
 * description plus 7 fields per tier card × 3 fixed tiers, plus the
 * example calculator box. This is the most content-heavy homepage
 * section so far; still flat Customizer fields rather than a custom
 * post type / repeater, same reasoning as Category Cards: exactly 3
 * fixed tiers, not a variable-length list, so there's nothing a
 * repeater buys here that grouped fields don't already give.
 *
 * Copy/values verified 2026-09-21 against a client-supplied clean
 * export of this exact section, cross-checked with pixel-sampling —
 * Figma's API was rate-limited for this build (see PROJECT.md §13).
 *
 * The 2-pair/3-pair toggle % (-40%/-55%) again conflicts with the
 * tier cards' own numbers (30%/45%) and the calculator's own computed
 * total (which matches 45%, not 55%) — same unresolved inconsistency
 * as Hero's tier badges (§6.2 dev log). Kept as separate editable
 * fields rather than silently reconciled.
 *
 * @return array<string,array{default:string,label:string,type:string,sanitize:string,transport:string}>
 */
function rx_theme_rotation_fields(): array {
	$fields = array(
		'rx_rotation_eyebrow'     => array(
			'default'   => __( 'How the RX Rotation Works', 'rx-theme' ),
			'label'     => __( 'Eyebrow text', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_rotation_heading'     => array(
			'default'   => __( 'Build Your 3-Stage Power Rotation', 'rx-theme' ),
			'label'     => __( 'Heading', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_rotation_description' => array(
			'default'   => __( 'Specialised footwear doubles shoe lifespan and prevents overuse injury. Combine any brand or discipline to instantly unlock automated tiered savings.', 'rx-theme' ),
			'label'     => __( 'Description', 'rx-theme' ),
			'type'      => 'textarea',
			'sanitize'  => 'sanitize_textarea_field',
			'transport' => 'postMessage',
		),
	);

	// Per-tier fields: tag_primary, tag_secondary (empty on tier 1 — no
	// second tag there), title, description, example product + price,
	// and the bottom status line. Built from compact data so 3×7
	// nearly-identical fields don't have to be hand-repeated.
	$tiers = array(
		1 => array(
			'tag_primary'     => 'Base Foundation',
			'tag_secondary'   => '',
			'title'           => 'Pair 1: Daily Training / Metcon',
			'description'     => 'High-durability all-rounder for box jumps, kettlebell work, short shuttles, and rope climbs.',
			'example_product' => 'R.A.D ONE V2',
			'example_price'   => '$240.00 AUD',
			'status_text'     => 'Status: Standard Pricing',
		),
		2 => array(
			'tag_primary'     => 'Unlock {two_pack}% Off',
			'tag_secondary'   => '{two_pack}% Tier Active',
			'title'           => 'Pair 2: Heavy Lifting / Stability',
			'description'     => 'Zero-drop grounding or elevated wooden/TPU heel wedges for squats, cleans, snatches, and deadlifts.',
			'example_product' => 'TYR DropZero Lifter',
			'example_price'   => '$210.00 AUD',
			'status_text'     => 'Bundle 2 Pairs: Save {two_pack}% Instantly',
		),
		3 => array(
			'tag_primary'     => 'Max Tier: Unlock {three_pack}% Off',
			'tag_secondary'   => 'Best Value',
			'title'           => 'Pair 3: Intervals / Road Running',
			'description'     => 'High-cushion nitrogen-infused superfoam for track tempo workouts, recovery miles, and aerobic conditioning.',
			'example_product' => 'Inov-8 F-Fly Speed',
			'example_price'   => '$200.00 AUD',
			'status_text'     => 'Bundle 3 Pairs: Save {three_pack}% on Entire Cart',
		),
	);

	$field_labels = array(
		'tag_primary'     => 'Tag',
		'tag_secondary'   => 'Second tag (leave blank for none)',
		'title'           => 'Title',
		'description'     => 'Description',
		'example_product' => 'Example product',
		'example_price'   => 'Example price',
		'status_text'     => 'Bottom status line',
	);

	foreach ( $tiers as $n => $tier_data ) {
		foreach ( $tier_data as $key => $default ) {
			$id = "rx_rotation_tier_{$n}_{$key}";

			$fields[ $id ] = array(
				'default'   => $default,
				/* translators: 1: tier number (1-3), 2: field label, e.g. "Title". */
				'label'     => sprintf( __( 'Pair %1$d — %2$s', 'rx-theme' ), $n, $field_labels[ $key ] ),
				'type'      => 'description' === $key ? 'textarea' : 'text',
				'sanitize'  => 'description' === $key ? 'sanitize_textarea_field' : 'sanitize_text_field',
				'transport' => 'postMessage',
			);
		}
	}

	// Example calculator box.
	$fields += array(
		'rx_rotation_calc_heading'        => array(
			'default'   => __( 'Example Athlete Rotation Calculation', 'rx-theme' ),
			'label'     => __( 'Calculator — heading', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_rotation_calc_formula'        => array(
			'default'   => __( 'R.A.D ONE V2 ($240) + TYR DropZero ($210) + Inov-8 F-Fly ($200) =', 'rx-theme' ),
			'label'     => __( 'Calculator — formula (sum expression, before the total)', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		// Split from the formula above so the original (pre-discount)
		// total can be struck through in the template — a single text
		// blob has no way to style just part of itself.
		'rx_rotation_calc_original_total' => array(
			'default'   => '$650.00 AUD',
			'label'     => __( 'Calculator — original total (shown crossed out)', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_rotation_calc_toggle_2pair'   => array(
			'default'   => __( '2-Pair Rotation (-{two_pack}%)', 'rx-theme' ),
			'label'     => __( 'Calculator — 2-pair toggle label', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_rotation_calc_toggle_3pair'   => array(
			'default'   => __( '3-Pair Rotation (-{three_pack}%)', 'rx-theme' ),
			'label'     => __( 'Calculator — 3-pair toggle label', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_rotation_calc_total_label'    => array(
			'default'   => __( 'Bundle Checkout Total', 'rx-theme' ),
			'label'     => __( 'Calculator — total label', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_rotation_calc_total_price'    => array(
			'default'   => '{three_pack_total}',
			'label'     => __( 'Calculator — total price', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_rotation_calc_save_text'      => array(
			'default'   => __( 'You Save {three_pack_savings} AUD ({three_pack}%)', 'rx-theme' ),
			'label'     => __( 'Calculator — savings line', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
	);

	return $fields;
}

/**
 * Register the Power Rotation section's controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_register_rotation_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'rx_rotation',
		array(
			'title'       => __( 'Power Rotation', 'rx-theme' ),
			'description' => rx_theme_bundle_tokens_help(),
			'panel'       => 'rx_homepage',
		)
	);

	rx_theme_register_fields( $wp_customize, 'rx_rotation', rx_theme_rotation_fields() );
}

/**
 * Field definitions for the Category Cards section (eyebrow, heading,
 * description only — the three cards themselves come from the Men/
 * Women/Unisex product categories, not from separate Customizer
 * fields, so category data stays in one place. See
 * inc/taxonomy-fields.php for the per-category "Shop label" and
 * thumbnail fields on the category edit screen).
 *
 * Defaults match the Figma "Home" frame's copy for this section.
 *
 * @return array<string,array{default:string,label:string,type:string,sanitize:string,transport:string}>
 */
function rx_theme_category_cards_fields(): array {
	return array(
		'rx_category_cards_eyebrow'     => array(
			'default'   => __( 'CURATED CATEGORIES', 'rx-theme' ),
			'label'     => __( 'Eyebrow text', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_category_cards_heading'     => array(
			'default'   => __( 'Discover by Fit & Gender', 'rx-theme' ),
			'label'     => __( 'Heading', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_category_cards_description' => array(
			'default'   => __( 'Precision-molded lasts engineered for anatomical performance profiles across Olympic lifting, cross-training, and track conditioning.', 'rx-theme' ),
			'label'     => __( 'Description', 'rx-theme' ),
			'type'      => 'textarea',
			'sanitize'  => 'sanitize_textarea_field',
			'transport' => 'postMessage',
		),
	);
}

/**
 * Register the Category Cards section's controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_register_category_cards_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'rx_category_cards',
		array(
			'title'       => __( 'Category Cards', 'rx-theme' ),
			'description' => __( 'The three cards themselves (image, name, product count) come from the Men/Women/Unisex product categories — edit those under Products > Categories, including the category image (native "Thumbnail" field) and the "Shop label" field this theme adds there.', 'rx-theme' ),
			'panel'       => 'rx_homepage',
		)
	);

	rx_theme_register_fields( $wp_customize, 'rx_category_cards', rx_theme_category_cards_fields() );
}

/**
 * Field definitions for the "Different session. Different shoe." section
 * (Figma section 5): its eyebrow, heading and description. The three
 * cards come from blog posts, not from fields — see inc/biomechanics.php.
 *
 * Defaults match the Figma "Home" frame's copy for this section.
 *
 * @return array<string,array{default:string,label:string,type:string,sanitize:string,transport:string}>
 */
function rx_theme_biomech_fields(): array {
	return array(
		'rx_biomech_eyebrow'     => array(
			'default'   => __( 'Biomechanical principle', 'rx-theme' ),
			'label'     => __( 'Eyebrow text', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_biomech_heading'     => array(
			'default'   => __( 'Different session. Different shoe.', 'rx-theme' ),
			'label'     => __( 'Heading', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_biomech_description' => array(
			'default'   => __( 'Why elite cross-training athletes never run in their squat shoes or lift heavy in running shoes.', 'rx-theme' ),
			'label'     => __( 'Description', 'rx-theme' ),
			'type'      => 'textarea',
			'sanitize'  => 'sanitize_textarea_field',
			'transport' => 'postMessage',
		),
	);
}

/**
 * Register the "Different session" section's controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_register_biomech_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'rx_biomech',
		array(
			'title'       => __( 'Different Session (Biomechanics)', 'rx-theme' ),
			'description' => __( 'The three cards are blog posts: the earliest three published posts in the "Biomechanics" category, oldest first (publish date decides Discipline 01 / 02 / 03). On each post: the title is the card title, the featured image is the photo, the excerpt is the card text, and the first bullet list in the post is the two points. The section is hidden until that category has a post.', 'rx-theme' ),
			'panel'       => 'rx_homepage',
		)
	);

	rx_theme_register_fields( $wp_customize, 'rx_biomech', rx_theme_biomech_fields() );
}

/**
 * Field definitions for the "Shop by Brand" section (Figma section 6):
 * eyebrow and heading. The tiles are the store's product brands — see
 * inc/brand-tiles.php.
 *
 * @return array<string,array{default:string,label:string,type:string,sanitize:string,transport:string}>
 */
function rx_theme_brands_fields(): array {
	return array(
		'rx_brands_eyebrow' => array(
			'default'   => __( 'Official partners', 'rx-theme' ),
			'label'     => __( 'Eyebrow text', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_brands_heading' => array(
			'default'   => __( 'Shop by brand', 'rx-theme' ),
			'label'     => __( 'Heading', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
	);
}

/**
 * Register the "Shop by Brand" section's controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_register_brands_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'rx_brands',
		array(
			'title'       => __( 'Shop by Brand', 'rx-theme' ),
			'description' => __( 'The tiles are your product brands (Products > Brands): the seven with the most products, each showing its logo and product count. Set a brand\'s logo with the Thumbnail field on the brand\'s edit screen; without one the tile shows the brand name. The section is hidden until a brand has a product.', 'rx-theme' ),
			'panel'       => 'rx_homepage',
		)
	);

	rx_theme_register_fields( $wp_customize, 'rx_brands', rx_theme_brands_fields() );
}

/**
 * Field definitions for the four trust tiles (Figma section 7): a title
 * and a line of text each. The icons are positional (truck, swap,
 * shield, pin) and not editable.
 *
 * @return array<string,array{default:string,label:string,type:string,sanitize:string,transport:string}>
 */
function rx_theme_trust_fields(): array {
	$defaults = array(
		1 => array( __( 'Free Express Shipping', 'rx-theme' ), __( 'Complimentary Australia Post Express on all orders over $150 AUD.', 'rx-theme' ) ),
		2 => array( __( '30-Day Hassle-Free Swaps', 'rx-theme' ), __( 'Instant size exchanges with pre-paid return labels included.', 'rx-theme' ) ),
		3 => array( __( '100% Authentic Guarantee', 'rx-theme' ), __( 'Direct manufacturer verification and full athlete brand warranties.', 'rx-theme' ) ),
		4 => array( __( 'Sydney Dispatch & Support', 'rx-theme' ), __( 'Australian-owned with real athlete customer care in NSW.', 'rx-theme' ) ),
	);
	$fields   = array();

	foreach ( $defaults as $n => $copy ) {
		$fields[ "rx_trust_{$n}_title" ] = array(
			'default'   => $copy[0],
			/* translators: %d: tile number. */
			'label'     => sprintf( __( 'Tile %d — title', 'rx-theme' ), $n ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		);
		$fields[ "rx_trust_{$n}_text" ]  = array(
			'default'   => $copy[1],
			/* translators: %d: tile number. */
			'label'     => sprintf( __( 'Tile %d — text', 'rx-theme' ), $n ),
			'type'      => 'textarea',
			'sanitize'  => 'sanitize_textarea_field',
			'transport' => 'postMessage',
		);
	}

	return $fields;
}

/**
 * Register the trust tiles' Customizer section.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_register_trust_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'rx_trust',
		array(
			'title'       => __( 'Trust Tiles', 'rx-theme' ),
			'description' => __( 'The four reassurance cards above "Community Rotations" (shipping, swaps, authenticity, dispatch). Each has a title and a line of text; the icons are fixed.', 'rx-theme' ),
			'panel'       => 'rx_homepage',
		)
	);

	rx_theme_register_fields( $wp_customize, 'rx_trust', rx_theme_trust_fields() );
}

/**
 * Field definitions for the "Community Rotations" section (Figma section
 * 8): eyebrow, heading and the note at the right of the heading. The
 * testimonial cards themselves are Community posts — see inc/community.php.
 *
 * @return array<string,array{default:string,label:string,type:string,sanitize:string,transport:string}>
 */
function rx_theme_community_fields(): array {
	return array(
		'rx_community_eyebrow' => array(
			'default'   => __( 'Tested on the platform', 'rx-theme' ),
			'label'     => __( 'Eyebrow text', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_community_heading' => array(
			'default'   => __( 'Community rotations', 'rx-theme' ),
			'label'     => __( 'Heading', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_community_note'    => array(
			'default'   => __( 'Over 14,000 Rotations Built Across Australia', 'rx-theme' ),
			'label'     => __( 'Note beside the heading', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
	);
}

/**
 * Register the "Community Rotations" Customizer section.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_register_community_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'rx_community',
		array(
			'title'       => __( 'Community Rotations', 'rx-theme' ),
			'description' => __( 'The testimonial cards are entries under Community in the dashboard menu (rating, quote, name and surname, position, company, address; the "Order" box sets the sequence, and the first three published show). The section is hidden until there is one.', 'rx-theme' ),
			'panel'       => 'rx_homepage',
		)
	);

	rx_theme_register_fields( $wp_customize, 'rx_community', rx_theme_community_fields() );
}

/**
 * Field definitions for the "Best Sellers" section (Figma section 4):
 * eyebrow, heading and the note at the right of the heading. The four
 * cards are products — see inc/best-sellers.php.
 *
 * @return array<string,array{default:string,label:string,type:string,sanitize:string,transport:string}>
 */
function rx_theme_bestsellers_fields(): array {
	return array(
		'rx_bestsellers_eyebrow' => array(
			'default'   => __( 'Performance best sellers', 'rx-theme' ),
			'label'     => __( 'Eyebrow text', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_bestsellers_heading' => array(
			'default'   => __( 'Verified for the rotation', 'rx-theme' ),
			'label'     => __( 'Heading', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_bestsellers_note'    => array(
			'default'   => __( 'All footwear eligible for automated bundle discounts', 'rx-theme' ),
			'label'     => __( 'Note beside the heading', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
	);
}

/**
 * Register the "Best Sellers" Customizer section.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_register_bestsellers_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'rx_bestsellers',
		array(
			'title'       => __( 'Best Sellers', 'rx-theme' ),
			'description' => __( 'The four cards are your best-selling products, shown with the same card as the shop pages. They rank by number sold; until there are sales, the store\'s own product order (Products > Sort products) decides, then oldest first. The section is hidden if there are no products.', 'rx-theme' ),
			'panel'       => 'rx_homepage',
		)
	);

	rx_theme_register_fields( $wp_customize, 'rx_bestsellers', rx_theme_bestsellers_fields() );
}

/**
 * Selective-refresh partials for every postMessage-transport field
 * across every homepage section, so the Customizer preview updates
 * live without a full page reload. Auto-derived from
 * rx_theme_all_mod_fields() (see inc/template-tags.php) rather than a
 * manually maintained ID list — with 40+ fields across Hero/Category
 * Cards/Power Rotation and more sections still to come, hand-listing
 * every ID here would drift out of sync fast.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_customize_partials( WP_Customize_Manager $wp_customize ): void {
	if ( ! isset( $wp_customize->selective_refresh ) ) {
		return;
	}

	// Known multi-line fields need the <br>-joining helper, not a
	// plain esc_html(), to render the same way the template does.
	$multiline_ids = array( 'rx_hero_heading' );

	foreach ( rx_theme_all_mod_fields() as $id => $field ) {
		if ( 'postMessage' !== ( $field['transport'] ?? '' ) ) {
			continue;
		}

		$wp_customize->selective_refresh->add_partial(
			$id,
			array(
				'selector'        => '[data-customize-partial="' . $id . '"]',
				'render_callback' => function () use ( $id, $multiline_ids ) {
					if ( in_array( $id, $multiline_ids, true ) ) {
						return rx_theme_multiline_html( $id );
					}
					return esc_html( rx_theme_get_mod( $id ) );
				},
			)
		);
	}
}
add_action( 'customize_register', 'rx_theme_customize_partials', 20 );
