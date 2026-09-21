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
 * pre-fills the Customizer UI itself, so every template read goes
 * through rx_theme_get_mod() (see inc/template-tags.php) instead of a
 * raw get_theme_mod() call.
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
}
add_action( 'customize_register', 'rx_theme_customize_register' );

/**
 * Field definitions for the Hero section: id => [default, label, type,
 * sanitize callback, transport]. Shared between registration
 * (add_setting/add_control) and rx_theme_get_mod()'s default lookup.
 *
 * Defaults match the Figma "Home" frame's hero copy as-is (2026-09-21) —
 * including the 2-pairs/3-pairs percentages, which are known to conflict
 * with other numbers elsewhere in the Figma file (see PROJECT.md §6.2 dev
 * log). That's exactly why these are editable fields and not hardcoded:
 * whoever confirms the real number can fix it here without a code change.
 *
 * @return array<string,array{default:string,label:string,type:string,sanitize:string,transport:string}>
 */
function rx_theme_hero_fields(): array {
	return array(
		'rx_hero_eyebrow'            => array(
			'default'   => __( 'Athlete Spec Training Rotation', 'rx-theme' ),
			'label'     => __( 'Eyebrow text', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_heading'            => array(
			'default'   => __( "One shoe can't do everything.", 'rx-theme' ),
			'label'     => __( 'Headline', 'rx-theme' ),
			'type'      => 'textarea',
			'sanitize'  => 'sanitize_textarea_field',
			'transport' => 'postMessage',
		),
		'rx_hero_subheading'         => array(
			'default'   => __( 'Build your training rotation.', 'rx-theme' ),
			'label'     => __( 'Sub-headline (accent colour)', 'rx-theme' ),
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
		'rx_hero_tier_1_text'        => array(
			'default'   => __( '2 Pairs → Save 40%', 'rx-theme' ),
			'label'     => __( 'Tier badge 1', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_tier_2_text'        => array(
			'default'   => __( '3 Pairs → Save 55%', 'rx-theme' ),
			'label'     => __( 'Tier badge 2', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_tier_2_flag'        => array(
			'default'   => __( 'Max Value', 'rx-theme' ),
			'label'     => __( 'Tier badge 2 flag (e.g. "Max Value")', 'rx-theme' ),
			'type'      => 'text',
			'sanitize'  => 'sanitize_text_field',
			'transport' => 'postMessage',
		),
		'rx_hero_cta_primary_text'   => array(
			'default'   => __( 'Build My Bundle', 'rx-theme' ),
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
			'default'   => __( 'Shop All Shoes', 'rx-theme' ),
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
			'title' => __( 'Hero', 'rx-theme' ),
			'panel' => 'rx_homepage',
		)
	);

	foreach ( rx_theme_hero_fields() as $id => $field ) {
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
				'section' => 'rx_hero',
				'label'   => $field['label'],
				'type'    => $field['type'],
			)
		);
	}

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
 * Selective-refresh partials for the postMessage-transport hero fields,
 * so the Customizer preview updates live without a full page reload.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_customize_partials( WP_Customize_Manager $wp_customize ): void {
	if ( ! isset( $wp_customize->selective_refresh ) ) {
		return;
	}

	$partial_fields = array(
		'rx_hero_eyebrow',
		'rx_hero_heading',
		'rx_hero_subheading',
		'rx_hero_description',
		'rx_hero_tier_1_text',
		'rx_hero_tier_2_text',
		'rx_hero_tier_2_flag',
		'rx_hero_cta_primary_text',
		'rx_hero_cta_secondary_text',
	);

	foreach ( $partial_fields as $id ) {
		$wp_customize->selective_refresh->add_partial(
			$id,
			array(
				'selector'        => '[data-customize-partial="' . $id . '"]',
				'render_callback' => function () use ( $id ) {
					// Heading is multi-line (2-line headline in the Figma
					// design) — render it the same way the template does,
					// via the shared helper, so the live preview matches.
					if ( 'rx_hero_heading' === $id ) {
						return rx_theme_multiline_html( $id );
					}
					return esc_html( rx_theme_get_mod( $id ) );
				},
			)
		);
	}
}
add_action( 'customize_register', 'rx_theme_customize_partials', 20 );
