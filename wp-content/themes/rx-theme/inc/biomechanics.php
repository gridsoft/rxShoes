<?php
/**
 * Homepage "Different session. Different shoe." section (Figma: the
 * "Biomechanical Principle" band) — data helpers.
 *
 * The three cards are standard blog posts from the "Biomechanics" post
 * category, oldest first (publish date decides Discipline 01 / 02 / 03).
 * Everything on a card comes from the post itself, so editing it is just
 * editing a post: title = card title, featured image = photo, excerpt =
 * card text, and the first bullet list in the post body = the two points.
 * Only the "Discipline 0N" label and its icon are the section's own
 * (positional, not content).
 *
 * The section's eyebrow / heading / description are Customizer fields
 * (Appearance > Customize > RX Homepage), like the other homepage
 * sections.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The posts that fill the three cards: the earliest three published posts
 * in the "Biomechanics" category. Empty when the category doesn't exist
 * or has no posts, in which case the section isn't shown.
 *
 * @return WP_Post[]
 */
function rx_theme_biomech_posts(): array {
	$query = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'category_name'       => 'biomechanics',
			'orderby'             => 'date',
			'order'               => 'ASC',
			'posts_per_page'      => 3,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	return $query->posts;
}

/**
 * The first bullet list in a post's body, as plain-text points. Reads the
 * stored content rather than rendering it, so it needs no filters or
 * output buffering and gives the same result in any context.
 *
 * @param WP_Post $post Post to read.
 * @param int     $max  Most points to return.
 * @return string[]
 */
function rx_theme_post_bullets( WP_Post $post, int $max = 2 ): array {
	if ( ! preg_match( '#<ul\b[^>]*>(.*?)</ul>#is', $post->post_content, $list ) ) {
		return array();
	}

	if ( ! preg_match_all( '#<li\b[^>]*>(.*?)</li>#is', $list[1], $items ) ) {
		return array();
	}

	$points = array();

	foreach ( $items[1] as $item ) {
		$text = trim( wp_strip_all_tags( html_entity_decode( $item, ENT_QUOTES, 'UTF-8' ) ) );

		if ( '' !== $text ) {
			$points[] = $text;
		}
	}

	return array_slice( $points, 0, $max );
}

/**
 * Icon for a card's "Discipline 0N" label, by position: expand arrows
 * (lifting), gauge (conditioning), runner (running). Inline SVG so it
 * takes the label's colour.
 *
 * @param int $position 1-based card position.
 */
function rx_theme_biomech_icon( int $position ): string {
	$icons = array(
		1 => '<path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7"/>',
		2 => '<path d="M3.34 19a10 10 0 1 1 17.32 0M12 14l4-4"/>',
		3 => '<circle cx="15" cy="4" r="1.6"/><path d="M13 8l-4 3-3-1M13 8l4 3 3-2M13 8l-2 7-4 4M11 15l4 2-1 4"/>',
	);

	$paths = $icons[ $position ] ?? $icons[1];

	return '<svg class="rx-biomech-card__icon" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round">' . $paths . '</svg>';
}
