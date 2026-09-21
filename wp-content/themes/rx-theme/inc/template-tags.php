<?php
/**
 * Shared rendering helpers used by both template parts and Customizer
 * selective-refresh partials, so the live preview and the real page
 * always render a field the same way.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All theme_mod field definitions, keyed by ID, merged from every
 * homepage section's field-definition function — add each new
 * section's function name to the list below as it's built. Single
 * source of truth for both rx_theme_get_mod()'s fallback default and
 * the Customizer's auto-derived selective-refresh partials (see
 * rx_theme_customize_partials() in inc/customizer.php).
 *
 * @return array<string,array{default:string,label:string,type:string,sanitize:string,transport:string}>
 */
function rx_theme_all_mod_fields(): array {
	static $fields = null;

	if ( null === $fields ) {
		$fields                = array();
		$section_field_sources = array(
			'rx_theme_hero_fields',
			'rx_theme_category_cards_fields',
			'rx_theme_rotation_fields',
		);
		foreach ( $section_field_sources as $source ) {
			if ( function_exists( $source ) ) {
				$fields += $source();
			}
		}
	}

	return $fields;
}

/**
 * Get_theme_mod() wrapper that falls back to the default declared in the
 * field's own definition (rx_theme_hero_fields(), etc.) instead of
 * silently returning an empty string. get_theme_mod() does NOT read a
 * Customizer setting's 'default' automatically — that value only
 * pre-fills the Customizer UI, never a normal front-end request — so
 * every template read should go through this function, not a raw
 * get_theme_mod() call.
 *
 * @param string $theme_mod_id Registered theme_mod/Customizer setting ID.
 */
function rx_theme_get_mod( string $theme_mod_id ): string {
	$fields  = rx_theme_all_mod_fields();
	$default = $fields[ $theme_mod_id ]['default'] ?? '';

	return (string) get_theme_mod( $theme_mod_id, $default );
}

/**
 * Render a possibly multi-line theme_mod value as escaped HTML with <br>
 * between lines (used for the hero headline, which is a 2-line textarea
 * in the Figma design).
 *
 * @param string $theme_mod_id Registered theme_mod/Customizer setting ID.
 */
function rx_theme_multiline_html( string $theme_mod_id ): string {
	$raw   = rx_theme_get_mod( $theme_mod_id );
	$lines = preg_split( '/\r\n|\r|\n/', $raw );
	$lines = array_map( 'esc_html', $lines );
	return implode( '<br>', $lines );
}
