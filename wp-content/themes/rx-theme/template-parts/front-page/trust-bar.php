<?php
/**
 * Homepage trust tiles (Figma section 7): four reassurance cards —
 * shipping, swaps, authenticity, dispatch. Title and text of each are
 * Customizer fields (rx_theme_trust_fields()); the icons are positional.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="rx-trust" aria-label="<?php esc_attr_e( 'Why shop with us', 'rx-theme' ); ?>">
	<ul class="rx-trust__grid">
		<?php for ( $rx_theme_n = 1; $rx_theme_n <= 4; $rx_theme_n++ ) : ?>
			<li class="rx-trust-tile">
				<span class="rx-trust-tile__icon"><?php echo rx_theme_trust_icon( $rx_theme_n ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?></span>
				<h3 class="rx-trust-tile__title" data-customize-partial="rx_trust_<?php echo (int) $rx_theme_n; ?>_title"><?php echo esc_html( rx_theme_get_mod( "rx_trust_{$rx_theme_n}_title" ) ); ?></h3>
				<p class="rx-trust-tile__text" data-customize-partial="rx_trust_<?php echo (int) $rx_theme_n; ?>_text"><?php echo esc_html( rx_theme_get_mod( "rx_trust_{$rx_theme_n}_text" ) ); ?></p>
			</li>
		<?php endfor; ?>
	</ul>
</section>
