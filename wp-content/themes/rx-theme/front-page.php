<?php
/**
 * The homepage.
 *
 * Built from template-parts, one per homepage section (matching the
 * Figma "Home" frame's named sections) — Hero is the first; more are
 * added here as they're built.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

// Convention for every section below (and any added later): the section's
// <h2> uses the shared .rx-section-heading class and its eyebrow the shared
// .rx-eyebrow — no per-section heading styles (client requirement).

get_template_part( 'template-parts/front-page/hero' );
get_template_part( 'template-parts/front-page/category-cards' );
if ( rx_theme_bundle_offer_is_active() ) {
	get_template_part( 'template-parts/front-page/power-rotation' );
}

get_template_part( 'template-parts/front-page/best-sellers' );
get_template_part( 'template-parts/front-page/biomechanics' );
get_template_part( 'template-parts/front-page/shop-by-brand' );
get_template_part( 'template-parts/front-page/trust-bar' );
get_template_part( 'template-parts/front-page/community' );

get_footer();
