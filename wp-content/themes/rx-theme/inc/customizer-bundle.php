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
			'description' => __( 'These two numbers drive every discount shown on the site: the shop product cards ("As low as … in 3-pack", the tier on the rotation row) and the homepage Hero and Power Rotation sections (their texts use the placeholders {two_pack} and {three_pack}). The 2-pack number will also drive the cart discount calculation. "Offer starts" / "Offer ends" limit when bundles run at all.', 'rx-theme' ),
			'priority'    => 31,
		)
	);

	$tiers = array(
		'rx_bundle_max_discount_percent'      => array(
			'default' => rx_theme_bundle_default_discount_percent(),
			'label'   => __( '3-pack discount (customer buys 3), %', 'rx-theme' ),
		),
		'rx_bundle_two_pack_discount_percent' => array(
			'default' => rx_theme_bundle_default_two_pack_discount_percent(),
			'label'   => __( '2-pack discount (customer buys 2), %', 'rx-theme' ),
		),
	);

	foreach ( $tiers as $id => $tier ) {
		$wp_customize->add_setting(
			$id,
			array(
				'type'              => 'option',
				'default'           => $tier['default'],
				'sanitize_callback' => 'rx_theme_sanitize_percent',
				'validate_callback' => 'rx_theme_validate_bundle_tiers',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$id,
			array(
				'section'     => 'rx_bundle',
				'type'        => 'number',
				'label'       => $tier['label'],
				'description' => __( 'A number from 0 to 100.', 'rx-theme' ),
				'input_attrs' => array(
					'min'  => 0,
					'max'  => 100,
					'step' => 0.01,
				),
			)
		);
	}

	/*
	 * The offer's run window (inc/bundle-offer.php). Options, like the
	 * percentages, so a theme switch can't silently reopen an ended offer.
	 */
	$dates = array(
		'rx_bundle_offer_start' => array(
			'label'       => __( 'Offer starts', 'rx-theme' ),
			'description' => __( 'Leave empty to run the offer from now.', 'rx-theme' ),
		),
		'rx_bundle_offer_end'   => array(
			'label'       => __( 'Offer ends', 'rx-theme' ),
			'description' => sprintf(
				/* translators: %s: current bundle offer status. */
				__( 'From this moment bundles switch off everywhere (discount, bundle badges and buttons, Build a Bundle page) and the store sells at normal prices. Leave empty to run with no end. Site time zone (Settings > General). %s', 'rx-theme' ),
				rx_theme_bundle_offer_status_text()
			),
		),
	);

	foreach ( $dates as $id => $date ) {
		$wp_customize->add_setting(
			$id,
			array(
				'type'              => 'option',
				'default'           => '',
				'sanitize_callback' => 'rx_theme_sanitize_bundle_offer_date',
				'validate_callback' => 'rx_theme_validate_bundle_offer_dates',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$id,
			array(
				'section'     => 'rx_bundle',
				'type'        => 'datetime-local',
				'label'       => $date['label'],
				'description' => $date['description'],
			)
		);
	}
}
add_action( 'customize_register', 'rx_theme_customize_bundle_register' );

/**
 * Reject an offer window that ends before (or when) it starts. Same
 * pattern as rx_theme_validate_bundle_tiers(): compare with the other
 * date's pending (or saved) value, read without post_value() to avoid
 * recursion.
 *
 * @param WP_Error             $validity Validity so far.
 * @param mixed                $value    Submitted value.
 * @param WP_Customize_Setting $setting  Setting being validated.
 */
function rx_theme_validate_bundle_offer_dates( WP_Error $validity, $value, WP_Customize_Setting $setting ): WP_Error {
	$is_start = 'rx_bundle_offer_start' === $setting->id;
	$other_id = $is_start ? 'rx_bundle_offer_end' : 'rx_bundle_offer_start';
	$posted   = $setting->manager->unsanitized_post_values();
	$other    = rx_theme_sanitize_bundle_offer_date( $posted[ $other_id ] ?? get_option( $other_id, '' ) );
	$value    = rx_theme_sanitize_bundle_offer_date( $value );
	$start    = $is_start ? $value : $other;
	$end      = $is_start ? $other : $value;

	// The fixed-width stored format compares correctly as a string.
	if ( '' !== $start && '' !== $end && $end <= $start ) {
		$validity->add(
			'rx_bundle_offer_order',
			__( 'The offer has to end after it starts.', 'rx-theme' )
		);
	}

	return $validity;
}

/**
 * Reject a 2-pack discount that is bigger than the 3-pack one: shop
 * cards advertise the 3-pack as the best price ("As low as … in
 * 3-pack"), which would quietly become false. Runs for whichever of
 * the two settings changed, comparing it with the other's pending
 * (or saved) value. Equal is allowed.
 *
 * @param WP_Error             $validity Validity so far.
 * @param mixed                $value    Submitted value.
 * @param WP_Customize_Setting $setting  Setting being validated.
 */
function rx_theme_validate_bundle_tiers( WP_Error $validity, $value, WP_Customize_Setting $setting ): WP_Error {
	$is_three_pack = 'rx_bundle_max_discount_percent' === $setting->id;
	$other_id      = $is_three_pack ? 'rx_bundle_two_pack_discount_percent' : 'rx_bundle_max_discount_percent';

	// The other tier's value: what's being submitted right now if it's
	// part of this save, otherwise what's stored. Read WITHOUT
	// WP_Customize_Setting::post_value() — that method validates, and
	// validating tier B calls this function for tier A, which would
	// call post_value() on B again: infinite recursion (Xdebug aborts
	// at 256 frames; on production PHP it's a fatal 500 on Save).
	$posted      = $setting->manager->unsanitized_post_values();
	$other_value = (float) ( $posted[ $other_id ] ?? get_option(
		$other_id,
		$is_three_pack ? rx_theme_bundle_default_two_pack_discount_percent() : rx_theme_bundle_default_discount_percent()
	) );
	$three_pack  = $is_three_pack ? (float) $value : $other_value;
	$two_pack    = $is_three_pack ? $other_value : (float) $value;

	if ( $two_pack > $three_pack ) {
		$validity->add(
			'rx_bundle_tier_order',
			__( 'The 2-pack discount can’t be bigger than the 3-pack discount — shop cards advertise the 3-pack as the best price.', 'rx-theme' )
		);
	}

	return $validity;
}
