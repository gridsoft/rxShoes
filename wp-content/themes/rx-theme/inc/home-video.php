<?php
/**
 * Homepage video (template-parts/front-page/video.php), right under the
 * hero: a YouTube or Vimeo link set in the Customizer (RX Homepage >
 * Video).
 *
 * Loaded as a "facade" to keep the homepage fast: the page only carries a
 * lazy-loaded cover image and a play button. The real player iframe
 * (hundreds of KB of third-party JS, cookies, extra connections) is only
 * created when the visitor clicks play (assets/js/home-video.js). The
 * cover is the video's own thumbnail unless a custom image is chosen.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse a YouTube or Vimeo URL.
 *
 * Handles youtube.com/watch?v=, youtu.be/, youtube.com/shorts|embed/,
 * vimeo.com/ID, vimeo.com/ID/HASH (unlisted) and player.vimeo.com/video/ID.
 *
 * @param string $url Video page URL.
 * @return array{provider:string,id:string,hash:string}|null
 */
function rx_theme_home_video_parse( string $url ): ?array {
	$url = trim( $url );

	if ( preg_match( '~(?:youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		return array(
			'provider' => 'youtube',
			'id'       => $m[1],
			'hash'     => '',
		);
	}

	if ( preg_match( '~vimeo\.com/(?:video/)?(\d+)(?:/([a-f0-9]+))?~', $url, $m ) ) {
		$hash = $m[2] ?? '';
		if ( ! $hash && preg_match( '~[?&]h=([a-f0-9]+)~', $url, $h ) ) {
			$hash = $h[1];
		}

		return array(
			'provider' => 'vimeo',
			'id'       => $m[1],
			'hash'     => $hash,
		);
	}

	return null;
}

/**
 * Player URL loaded on click — autoplays, since the click was the
 * visitor asking for it. YouTube's privacy-enhanced domain and Vimeo's
 * dnt=1 keep tracking cookies off until then too.
 *
 * @param array{provider:string,id:string,hash:string} $video Parsed video.
 */
function rx_theme_home_video_embed_url( array $video ): string {
	if ( 'youtube' === $video['provider'] ) {
		return 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $video['id'] ) . '?autoplay=1&rel=0&playsinline=1&modestbranding=1';
	}

	$args = array(
		'autoplay' => 1,
		'dnt'      => 1,
		'title'    => 0,
		'byline'   => 0,
		'portrait' => 0,
	);
	if ( $video['hash'] ) {
		$args['h'] = $video['hash'];
	}

	return add_query_arg( $args, 'https://player.vimeo.com/video/' . rawurlencode( $video['id'] ) );
}

/**
 * The video's own thumbnail. YouTube's is a fixed URL; Vimeo's comes
 * from its oEmbed API, fetched once and cached for a week so a homepage
 * view never waits on Vimeo.
 *
 * @param array{provider:string,id:string,hash:string} $video Parsed video.
 */
function rx_theme_home_video_thumbnail( array $video ): string {
	$cache_key = 'rx_video_thumb_' . md5( $video['provider'] . $video['id'] . $video['hash'] );
	$cached    = get_transient( $cache_key );

	if ( false !== $cached ) {
		return (string) $cached;
	}

	if ( 'youtube' === $video['provider'] ) {
		// 1280px "maxres" only exists for HD uploads; otherwise the 480px one always does.
		$base  = 'https://i.ytimg.com/vi/' . rawurlencode( $video['id'] ) . '/';
		$head  = wp_remote_head( $base . 'maxresdefault.jpg', array( 'timeout' => 3 ) );
		$thumb = $base . ( 200 === wp_remote_retrieve_response_code( $head ) ? 'maxresdefault.jpg' : 'hqdefault.jpg' );

		set_transient( $cache_key, $thumb, is_wp_error( $head ) ? HOUR_IN_SECONDS : WEEK_IN_SECONDS );

		return $thumb;
	}

	$page     = 'https://vimeo.com/' . $video['id'] . ( $video['hash'] ? '/' . $video['hash'] : '' );
	$response = wp_remote_get(
		add_query_arg(
			array(
				'url'   => rawurlencode( $page ),
				'width' => 1280,
			),
			'https://vimeo.com/api/oembed.json'
		),
		array( 'timeout' => 3 )
	);
	$data     = is_wp_error( $response ) ? null : json_decode( (string) wp_remote_retrieve_body( $response ), true );
	$thumb    = is_array( $data ) && ! empty( $data['thumbnail_url'] ) ? esc_url_raw( $data['thumbnail_url'] ) : '';

	// A failed lookup is cached briefly so it's retried soon, not on every view.
	set_transient( $cache_key, $thumb, $thumb ? WEEK_IN_SECONDS : HOUR_IN_SECONDS );

	return $thumb;
}

/**
 * Everything the template needs, or null when no valid video is set
 * (the section is then left out entirely).
 *
 * @return array{embed:string,poster:string,provider:string,title:string}|null
 */
function rx_theme_home_video(): ?array {
	$video = rx_theme_home_video_parse( (string) get_theme_mod( 'rx_video_url', '' ) );

	if ( ! $video ) {
		return null;
	}

	$poster = (string) get_theme_mod( 'rx_video_poster', '' );

	return array(
		'embed'    => rx_theme_home_video_embed_url( $video ),
		'poster'   => $poster ? rx_theme_local_upload_url( $poster ) : rx_theme_home_video_thumbnail( $video ),
		'provider' => $video['provider'],
		'title'    => (string) get_theme_mod( 'rx_video_title', '' ),
	);
}

/**
 * Customizer: RX Homepage > Video.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_register_video_section( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'rx_video',
		array(
			'title'       => __( 'Video', 'rx-theme' ),
			'description' => __( 'Shown right under the hero. Paste a YouTube or Vimeo link (e.g. https://www.youtube.com/watch?v=… or https://vimeo.com/…). Leave empty to hide the section. Only a cover image loads with the page; the player loads when a visitor presses play, so the video doesn\'t slow the homepage down.', 'rx-theme' ),
			'panel'       => 'rx_homepage',
			'priority'    => 15,
		)
	);

	$wp_customize->add_setting(
		'rx_video_url',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'rx_video_url',
		array(
			'section' => 'rx_video',
			'label'   => __( 'YouTube or Vimeo link', 'rx-theme' ),
			'type'    => 'url',
		)
	);

	$wp_customize->add_setting(
		'rx_video_title',
		array(
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		'rx_video_title',
		array(
			'section'     => 'rx_video',
			'label'       => __( 'Video title', 'rx-theme' ),
			'description' => __( 'Not shown on the page — read out by screen readers on the play button.', 'rx-theme' ),
			'type'        => 'text',
		)
	);

	$wp_customize->add_setting(
		'rx_video_poster',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
			'transport'         => 'refresh',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'rx_video_poster',
			array(
				'section'     => 'rx_video',
				'label'       => __( 'Cover image (optional)', 'rx-theme' ),
				'description' => __( 'Shown before the video plays. Leave empty to use the video\'s own thumbnail. Best at 16:9, e.g. 1600×900.', 'rx-theme' ),
			)
		)
	);
}
add_action( 'customize_register', 'rx_theme_register_video_section', 11 );

/**
 * The click-to-play script — only on the pages showing the video
 * (homepage and checkout), and only when a video is set.
 */
function rx_theme_enqueue_home_video_script(): void {
	if ( ! ( is_front_page() || ( is_checkout() && ! is_wc_endpoint_url() ) ) || ! rx_theme_home_video() ) {
		return;
	}

	$file = RX_THEME_DIR . '/assets/js/home-video.js';

	wp_enqueue_script(
		'rx-theme-home-video',
		RX_THEME_URI . '/assets/js/home-video.js',
		array(),
		file_exists( $file ) ? (string) filemtime( $file ) : RX_THEME_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'rx_theme_enqueue_home_video_script' );
