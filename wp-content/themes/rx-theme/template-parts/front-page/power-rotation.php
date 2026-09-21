<?php
/**
 * Homepage "Power Rotation" section (Figma: "Section - 3", "Build Your
 * 3-Stage Power Rotation") — three illustrative pricing tiers plus an
 * example calculator box.
 *
 * All copy comes from the Customizer (Appearance > Customize > RX
 * Homepage > Power Rotation) — see rx_theme_rotation_fields() in
 * inc/customizer.php for the full field list and the reasoning for
 * keeping this as flat fields rather than a repeater/CPT.
 *
 * This is explicitly illustrative example content, not a live pricing
 * calculator — no bundle/pricing rules engine exists yet (Milestone 4,
 * not started; see PROJECT.md §6.2). The 2-pair/3-pair toggle is
 * rendered in its Figma default state (3-pair selected) and is NOT
 * interactive. Making it a real toggle that recalculates is separate
 * future work once real pricing rules exist — don't build fake
 * interactivity against fake numbers.
 *
 * Colours/copy verified 2026-09-21 against a client-supplied clean
 * export of this exact section (Figma's API was rate-limited for this
 * build — see PROJECT.md §13).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Tier number (1-3) and CSS modifier are structural/positional, not
// editable content — only the copy per tier is a Customizer field.
$rx_theme_tiers = array( 1, 2, 3 );
?>
<section class="rx-rotation">
	<header class="rx-rotation__header">
		<p class="rx-eyebrow" data-customize-partial="rx_rotation_eyebrow">
			<?php echo esc_html( rx_theme_get_mod( 'rx_rotation_eyebrow' ) ); ?>
		</p>
		<h2 class="rx-rotation__heading" data-customize-partial="rx_rotation_heading">
			<?php echo esc_html( rx_theme_get_mod( 'rx_rotation_heading' ) ); ?>
		</h2>
		<p class="rx-rotation__description" data-customize-partial="rx_rotation_description">
			<?php echo esc_html( rx_theme_get_mod( 'rx_rotation_description' ) ); ?>
		</p>
	</header>

	<div class="rx-rotation__grid">
		<?php foreach ( $rx_theme_tiers as $rx_theme_n ) : ?>
			<?php
			$rx_theme_tag_secondary = rx_theme_get_mod( "rx_rotation_tier_{$rx_theme_n}_tag_secondary" );
			?>
			<div class="rx-rotation-card rx-rotation-card--tier-<?php echo esc_attr( $rx_theme_n ); ?>">
				<div class="rx-rotation-card__top">
					<span class="rx-rotation-card__number"><?php echo esc_html( str_pad( (string) $rx_theme_n, 2, '0', STR_PAD_LEFT ) ); ?></span>
					<div class="rx-rotation-card__tags">
						<span class="rx-rotation-card__tag rx-rotation-card__tag--primary" data-customize-partial="rx_rotation_tier_<?php echo esc_attr( $rx_theme_n ); ?>_tag_primary">
							<?php echo esc_html( rx_theme_get_mod( "rx_rotation_tier_{$rx_theme_n}_tag_primary" ) ); ?>
						</span>
						<?php if ( $rx_theme_tag_secondary ) : ?>
							<span class="rx-rotation-card__tag rx-rotation-card__tag--secondary" data-customize-partial="rx_rotation_tier_<?php echo esc_attr( $rx_theme_n ); ?>_tag_secondary">
								<?php echo esc_html( $rx_theme_tag_secondary ); ?>
							</span>
						<?php endif; ?>
					</div>
				</div>

				<h3 class="rx-rotation-card__title" data-customize-partial="rx_rotation_tier_<?php echo esc_attr( $rx_theme_n ); ?>_title">
					<?php echo esc_html( rx_theme_get_mod( "rx_rotation_tier_{$rx_theme_n}_title" ) ); ?>
				</h3>
				<p class="rx-rotation-card__description" data-customize-partial="rx_rotation_tier_<?php echo esc_attr( $rx_theme_n ); ?>_description">
					<?php echo esc_html( rx_theme_get_mod( "rx_rotation_tier_{$rx_theme_n}_description" ) ); ?>
				</p>

				<div class="rx-rotation-card__example">
					<div class="rx-rotation-card__example-row">
						<span><?php esc_html_e( 'Example Pick:', 'rx-theme' ); ?></span>
						<strong data-customize-partial="rx_rotation_tier_<?php echo esc_attr( $rx_theme_n ); ?>_example_product">
							<?php echo esc_html( rx_theme_get_mod( "rx_rotation_tier_{$rx_theme_n}_example_product" ) ); ?>
						</strong>
					</div>
					<div class="rx-rotation-card__example-row">
						<span><?php esc_html_e( 'Standard RRP:', 'rx-theme' ); ?></span>
						<strong data-customize-partial="rx_rotation_tier_<?php echo esc_attr( $rx_theme_n ); ?>_example_price">
							<?php echo esc_html( rx_theme_get_mod( "rx_rotation_tier_{$rx_theme_n}_example_price" ) ); ?>
						</strong>
					</div>
				</div>

				<p class="rx-rotation-card__status" data-customize-partial="rx_rotation_tier_<?php echo esc_attr( $rx_theme_n ); ?>_status_text">
					<?php echo esc_html( rx_theme_get_mod( "rx_rotation_tier_{$rx_theme_n}_status_text" ) ); ?>
				</p>
			</div>
		<?php endforeach; ?>
	</div>

	<div class="rx-rotation-calc">
		<div class="rx-rotation-calc__example">
			<p class="rx-rotation-calc__heading">
				<span class="rx-rotation-calc__icon" aria-hidden="true"></span>
				<span data-customize-partial="rx_rotation_calc_heading"><?php echo esc_html( rx_theme_get_mod( 'rx_rotation_calc_heading' ) ); ?></span>
			</p>
			<p class="rx-rotation-calc__formula">
				<span data-customize-partial="rx_rotation_calc_formula"><?php echo esc_html( rx_theme_get_mod( 'rx_rotation_calc_formula' ) ); ?></span>
				<s class="rx-rotation-calc__original-total" data-customize-partial="rx_rotation_calc_original_total"><?php echo esc_html( rx_theme_get_mod( 'rx_rotation_calc_original_total' ) ); ?></s>
			</p>
			<div class="rx-rotation-calc__toggle" role="group" aria-label="<?php esc_attr_e( 'Example rotation size (illustrative only)', 'rx-theme' ); ?>">
				<span class="rx-rotation-calc__toggle-option" data-customize-partial="rx_rotation_calc_toggle_2pair">
					<?php echo esc_html( rx_theme_get_mod( 'rx_rotation_calc_toggle_2pair' ) ); ?>
				</span>
				<span class="rx-rotation-calc__toggle-option rx-rotation-calc__toggle-option--active" data-customize-partial="rx_rotation_calc_toggle_3pair">
					<?php echo esc_html( rx_theme_get_mod( 'rx_rotation_calc_toggle_3pair' ) ); ?>
				</span>
			</div>
		</div>

		<div class="rx-rotation-calc__total">
			<p class="rx-rotation-calc__total-label" data-customize-partial="rx_rotation_calc_total_label">
				<?php echo esc_html( rx_theme_get_mod( 'rx_rotation_calc_total_label' ) ); ?>
			</p>
			<p class="rx-rotation-calc__total-price" data-customize-partial="rx_rotation_calc_total_price">
				<?php echo esc_html( rx_theme_get_mod( 'rx_rotation_calc_total_price' ) ); ?>
			</p>
			<p class="rx-rotation-calc__save" data-customize-partial="rx_rotation_calc_save_text">
				<?php echo esc_html( rx_theme_get_mod( 'rx_rotation_calc_save_text' ) ); ?>
			</p>
		</div>
	</div>
</section>
