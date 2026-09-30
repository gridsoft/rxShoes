<?php
/**
 * Homepage "Power Rotation" section — a minimal bundle-offer strip:
 * section heading, the two discount tiers with their footnote, and the
 * "Build my bundle" / "Shop all shoes" buttons.
 *
 * The tier and button copy deliberately reuses the Hero's Customizer
 * fields (Appearance > Customize > RX Homepage > Hero) so the offer
 * reads identically in both places and is edited once. The heading
 * still comes from the Power Rotation panel.
 *
 * Like the hero, the whole offer hides outside the bundle offer's run
 * window, leaving only the "Shop all shoes" button.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_rotation_resolve_url = static function ( string $url ): string {
	return preg_match( '#^https?://#i', $url ) ? $url : home_url( $url );
};

$rx_theme_rotation_bundles      = rx_theme_bundle_offer_is_active();
$rx_theme_rotation_primary_url  = $rx_theme_rotation_resolve_url( rx_theme_get_mod( 'rx_hero_cta_primary_url' ) );
$rx_theme_rotation_show_primary = $rx_theme_rotation_bundles || false === strpos( $rx_theme_rotation_primary_url, '/build-a-bundle' );
?>
<section class="rx-rotation">
	<h2 class="rx-section-heading rx-rotation__heading" data-customize-partial="rx_rotation_heading">
		<?php echo esc_html( rx_theme_get_mod( 'rx_rotation_heading' ) ); ?>
	</h2>

	<?php if ( $rx_theme_rotation_bundles ) : ?>
	<div class="rx-rotation__offer">
		<div class="rx-rotation__tiers">
			<p class="rx-rotation__tier">
				<span class="rx-rotation__flag rx-rotation__flag--tier1" data-customize-partial="rx_hero_tier_1_flag"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_tier_1_flag' ) ); ?></span>
				<span class="rx-rotation__tier-text" data-customize-partial="rx_hero_tier_1_text"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_tier_1_text' ) ); ?></span>
			</p>
			<p class="rx-rotation__tier">
				<span class="rx-rotation__flag rx-rotation__flag--tier2" data-customize-partial="rx_hero_tier_2_flag"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_tier_2_flag' ) ); ?></span>
				<span class="rx-rotation__tier-text rx-rotation__tier-text--blue" data-customize-partial="rx_hero_tier_2_text"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_tier_2_text' ) ); ?></span>
			</p>
		</div>
		<p class="rx-rotation__note" data-customize-partial="rx_hero_tier_note">
			<?php echo esc_html( rx_theme_get_mod( 'rx_hero_tier_note' ) ); ?>
		</p>
	</div>
	<?php endif; ?>

	<div class="rx-rotation__actions">
		<?php if ( $rx_theme_rotation_show_primary ) : ?>
		<a class="rx-btn rx-btn--bundle" href="<?php echo esc_url( $rx_theme_rotation_primary_url ); ?>">
			<span data-customize-partial="rx_hero_cta_primary_text"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_cta_primary_text' ) ); ?></span>
		</a>
		<?php endif; ?>
		<a class="rx-btn rx-btn--secondary" href="<?php echo esc_url( $rx_theme_rotation_resolve_url( rx_theme_get_mod( 'rx_hero_cta_secondary_url' ) ) ); ?>">
			<span data-customize-partial="rx_hero_cta_secondary_text"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_cta_secondary_text' ) ); ?></span>
		</a>
	</div>
</section>
