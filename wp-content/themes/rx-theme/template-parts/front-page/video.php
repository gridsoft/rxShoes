<?php
/**
 * Homepage video, right under the hero — also reused on checkout, under
 * the PayID notice ($args['modifier'] = 'checkout' adds
 * .rx-video--checkout for that column's spacing). A click-to-play facade
 * — only the cover image and play button load with the page; the
 * YouTube/Vimeo player is swapped in on click (assets/js/home-video.js).
 * See inc/home-video.php. Not rendered at all when no video is set.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_video = rx_theme_home_video();

if ( ! $rx_theme_video ) {
	return;
}

$rx_theme_video_label = $rx_theme_video['title']
	/* translators: %s: video title. */
	? sprintf( __( 'Play video: %s', 'rx-theme' ), $rx_theme_video['title'] )
	: __( 'Play video', 'rx-theme' );
?>
<section class="rx-video<?php echo empty( $args['modifier'] ) ? '' : ' rx-video--' . esc_attr( $args['modifier'] ); ?>">
	<div class="rx-video__frame">
		<button type="button" class="rx-video__play" data-rx-video-embed="<?php echo esc_url( $rx_theme_video['embed'] ); ?>" data-rx-video-title="<?php echo esc_attr( $rx_theme_video['title'] ? $rx_theme_video['title'] : __( 'Video', 'rx-theme' ) ); ?>" aria-label="<?php echo esc_attr( $rx_theme_video_label ); ?>">
			<?php if ( $rx_theme_video['poster'] ) : ?>
				<img class="rx-video__poster" src="<?php echo esc_url( $rx_theme_video['poster'] ); ?>" alt="" loading="lazy" decoding="async" width="1280" height="720">
			<?php endif; ?>
			<span class="rx-video__icon" aria-hidden="true">
				<svg viewBox="0 0 24 24" width="28" height="28" focusable="false"><path fill="currentColor" d="M8 5.5v13l11-6.5z"/></svg>
			</span>
		</button>
	</div>
</section>
