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

get_template_part( 'template-parts/front-page/hero' );

// Remaining homepage sections (Category Cards, Power Rotation tiers,
// Best Sellers grid, Educational comparison, Shop by Brand, Community
// Rotations) land here as their own template-parts, in Figma order.

get_footer();
