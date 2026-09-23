<?php
/**
 * Homepage last band — data helpers for the trust tiles and the
 * "Community Rotations" testimonials (Figma sections 7 and 8).
 *
 * The four trust tiles are Customizer fields (title + text each; the
 * icons are positional). The testimonials are entries of the "Community"
 * post type, which the rx-core plugin owns (quote = post content; rating,
 * first name, surname, position, company and address are meta). The theme
 * only reads them, by key, like the product-card meta; with rx-core
 * inactive the post type doesn't exist and the section is simply hidden.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Icon for a trust tile, by position: truck, swap arrows, shield-check,
 * map pin. Inline SVG so it takes the tile's colour.
 *
 * @param int $position 1-based tile position.
 */
function rx_theme_trust_icon( int $position ): string {
	$icons = array(
		1 => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2M15 18H9M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
		2 => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8M21 3v5h-5M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16M8 16H3v5"/>',
		3 => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
		4 => '<path d="M20 10c0 4.993-5.539 10.193-7.399 11.799a1 1 0 0 1-1.202 0C9.539 20.193 4 14.993 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
	);

	return '<svg class="rx-trust-tile__svg" viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' . ( $icons[ $position ] ?? '' ) . '</svg>';
}

/**
 * The published testimonials for the section, in the order set on each
 * entry's "Order" box (then newest first).
 *
 * @param int $limit How many to fetch (the design shows three).
 * @return WP_Post[]
 */
function rx_theme_community_posts( int $limit = 3 ): array {
	if ( ! post_type_exists( 'rx_community' ) ) {
		return array();
	}

	$query = new WP_Query(
		array(
			'post_type'           => 'rx_community',
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'orderby'             => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);

	return $query->posts;
}

/**
 * What a testimonial card shows, worked out from the entry's meta.
 * The name is shown as first name + surname initial ("Marcus L."), as in
 * the design; the avatar carries the two initials.
 *
 * @param WP_Post $post Community entry.
 * @return array{rating:int,quote:string,name:string,initials:string,details:string[]}
 */
function rx_theme_community_entry( WP_Post $post ): array {
	$meta = static function ( string $key ) use ( $post ): string {
		return trim( (string) get_post_meta( $post->ID, '_rx_community_' . $key, true ) );
	};

	$first   = $meta( 'first_name' );
	$surname = $meta( 'surname' );
	$rating  = '' === $meta( 'rating' ) ? 5 : min( 5, max( 1, (int) $meta( 'rating' ) ) );

	if ( '' === $first && '' === $surname ) {
		$name     = get_the_title( $post );
		$initials = mb_strtoupper( mb_substr( $name, 0, 2 ) );
	} else {
		$name     = trim( $first . ( '' !== $surname ? ' ' . mb_strtoupper( mb_substr( $surname, 0, 1 ) ) . '.' : '' ) );
		$initials = mb_strtoupper( mb_substr( $first, 0, 1 ) . mb_substr( $surname, 0, 1 ) );
	}

	return array(
		'rating'   => $rating,
		'quote'    => trim( wp_strip_all_tags( $post->post_content ) ),
		'name'     => $name,
		'initials' => $initials,
		'details'  => array_values( array_filter( array( $meta( 'position' ), $meta( 'company' ), $meta( 'address' ) ) ) ),
	);
}

/**
 * Five stars, filled up to the rating, as inline SVG with a text
 * alternative.
 *
 * @param int $rating 1–5.
 */
function rx_theme_star_rating_html( int $rating ): string {
	$path  = 'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z';
	$stars = '';

	for ( $i = 1; $i <= 5; $i++ ) {
		$stars .= sprintf(
			'<svg class="rx-stars__star%1$s" viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path fill="currentColor" d="%2$s"/></svg>',
			$i <= $rating ? '' : ' is-empty',
			$path
		);
	}

	return sprintf(
		'<span class="rx-stars" role="img" aria-label="%1$s">%2$s</span>',
		esc_attr(
			sprintf(
				/* translators: %d: rating out of 5. */
				__( 'Rated %d out of 5', 'rx-theme' ),
				$rating
			)
		),
		$stars
	);
}
