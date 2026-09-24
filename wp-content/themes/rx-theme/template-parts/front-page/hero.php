<?php
/**
 * Homepage hero.
 *
 * All copy comes from the Customizer (see inc/customizer.php) — nothing
 * here is hardcoded content, only markup/structure. Edit copy under
 * Appearance > Customize > RX Homepage > Hero.
 *
 * Structure and styling match the Figma "Home" frame's hero node tree
 * exactly (colours, gradient direction, type, spacing pulled from the
 * Figma API, not eyeballed) — full-bleed photo with a white-to-
 * transparent gradient fading in from the left so the dark text stays
 * legible; NOT a dark-overlay/white-text hero. See PROJECT.md §13 dev
 * log, 2026-09-21, for the correction and why it was wrong the first
 * time.
 *
 * The 2-pairs/3-pairs tier percentages are known to be inconsistent
 * with other numbers elsewhere in the Figma file (§6.2) — they're
 * editable fields here rather than hardcoded specifically so that gets
 * fixed from the dashboard once confirmed, no deploy needed.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_hero_image = rx_theme_local_upload_url( (string) get_theme_mod( 'rx_hero_image', '' ) );

/**
 * A Customizer URL field may hold a relative path ("/shop/") or a full
 * external URL — only resolve relative-looking values against home_url()
 * so an admin-entered external link isn't mangled.
 */
$rx_theme_hero_resolve_url = static function ( string $url ): string {
	return preg_match( '#^https?://#i', $url ) ? $url : home_url( $url );
};
?>
<section class="rx-hero"<?php echo $rx_theme_hero_image ? ' style="--rx-hero-image: url(' . esc_url( $rx_theme_hero_image ) . ');"' : ''; ?>>
	<div class="rx-hero__content">
		<p class="rx-hero__eyebrow" data-customize-partial="rx_hero_eyebrow">
			<span class="rx-hero__eyebrow-icon" aria-hidden="true"></span>
			<?php echo esc_html( rx_theme_get_mod( 'rx_hero_eyebrow' ) ); ?>
		</p>

		<h1 class="rx-hero__heading" data-customize-partial="rx_hero_heading">
			<?php echo wp_kses( rx_theme_multiline_html( 'rx_hero_heading' ), array( 'br' => array() ) ); ?>
		</h1>

		<p class="rx-hero__subheading" data-customize-partial="rx_hero_subheading">
			<?php echo esc_html( rx_theme_get_mod( 'rx_hero_subheading' ) ); ?>
		</p>

		<p class="rx-hero__description" data-customize-partial="rx_hero_description">
			<?php echo esc_html( rx_theme_get_mod( 'rx_hero_description' ) ); ?>
		</p>

		<div class="rx-hero__callout">
			<div class="rx-hero__tiers">
				<div class="rx-hero__tier">
					<span class="rx-hero__tier-flag rx-hero__tier-flag--tier1" data-customize-partial="rx_hero_tier_1_flag"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_tier_1_flag' ) ); ?></span>
					<span class="rx-hero__tier-text rx-hero__tier-text--ink" data-customize-partial="rx_hero_tier_1_text"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_tier_1_text' ) ); ?></span>
				</div>
				<div class="rx-hero__tier">
					<span class="rx-hero__tier-flag rx-hero__tier-flag--tier2" data-customize-partial="rx_hero_tier_2_flag"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_tier_2_flag' ) ); ?></span>
					<span class="rx-hero__tier-text rx-hero__tier-text--blue" data-customize-partial="rx_hero_tier_2_text"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_tier_2_text' ) ); ?></span>
				</div>
			</div>
			<p class="rx-hero__tier-note" data-customize-partial="rx_hero_tier_note">
				<?php echo esc_html( rx_theme_get_mod( 'rx_hero_tier_note' ) ); ?>
			</p>
		</div>

		<div class="rx-hero__actions">
			<a class="rx-btn rx-btn--bundle" href="<?php echo esc_url( $rx_theme_hero_resolve_url( rx_theme_get_mod( 'rx_hero_cta_primary_url' ) ) ); ?>">
				<span data-customize-partial="rx_hero_cta_primary_text"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_cta_primary_text' ) ); ?></span>
			</a>
			<a class="rx-btn rx-btn--secondary" href="<?php echo esc_url( $rx_theme_hero_resolve_url( rx_theme_get_mod( 'rx_hero_cta_secondary_url' ) ) ); ?>">
				<span data-customize-partial="rx_hero_cta_secondary_text"><?php echo esc_html( rx_theme_get_mod( 'rx_hero_cta_secondary_text' ) ); ?></span>
			</a>
		</div>
	</div>
</section>
