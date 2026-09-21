<?php
/**
 * Customizer: site-wide bundle settings (Appearance > Customize > Bundle
 * Discount). Separate from the homepage panel because this isn't
 * homepage content — it feeds every shop card.
 *
 * The setting is registered with 'type' => 'option', so it's stored in
 * wp_options as `rx_bundle_max_discount_percent` rather than as a
 * theme_mod. theme_mods belong to one theme: switching or duplicating
 * the theme would silently reset a pricing number. An option survives
 * that, and the rx-core pricing engine can read the same option later
 * without depending on the theme.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Bundle Discount section, setting and control.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_customize_bundle_register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'rx_bundle',
		array(
			'title'       => __( 'Bundle Discount', 'rx-theme' ),
			'description' => __( 'Used on shop product cards ("As low as … in 3-pack" and the tier shown on the rotation row). Homepage copy that mentions discount percentages is edited separately under RX Homepage.', 'rx-theme' ),
			'priority'    => 31,
		)
	);

	$wp_customize->add_setting(
		'rx_bundle_max_discount_percent',
		array(
			'type'              => 'option',
			'default'           => rx_theme_bundle_default_discount_percent(),
			'sanitize_callback' => 'rx_theme_sanitize_percent',
			'transport'         => 'refresh',
		)
	);

	$wp_customize->add_control(
		'rx_bundle_max_discount_percent',
		array(
			'section'     => 'rx_bundle',
			'type'        => 'number',
			'label'       => __( 'Top-tier (3-pack) discount, %', 'rx-theme' ),
			'description' => __( 'A number from 0 to 100.', 'rx-theme' ),
			'input_attrs' => array(
				'min'  => 0,
				'max'  => 100,
				'step' => 0.01,
			),
		)
	);
}
add_action( 'customize_register', 'rx_theme_customize_bundle_register' );
