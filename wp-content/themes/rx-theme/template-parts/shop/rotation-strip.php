<?php
/**
 * "Dynamic Rotation System" strip shown above the filter bar on product
 * archives (Figma: the compact 1 / 2 / 3 pairs bar).
 *
 * All copy is fixed on purpose, per the client. The only dynamic values
 * are the two percentages, which come from Appearance > Customize >
 * Bundle Discount — the same settings the homepage and the shop cards
 * read, so changing them there updates this strip too.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_two_pack   = rx_theme_format_percent( rx_theme_bundle_two_pack_discount_percent() );
$rx_theme_three_pack = rx_theme_format_percent( rx_theme_bundle_max_discount_percent() );
?>
<section class="rx-rotation-strip" aria-labelledby="rx-rotation-strip-heading">
	<header class="rx-rotation-strip__header">
		<h2 class="rx-rotation-strip__heading" id="rx-rotation-strip-heading">
			<svg class="rx-rotation-strip__chevron" viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M9 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<?php esc_html_e( 'Dynamic Rotation System', 'rx-theme' ); ?>
		</h2>
		<p class="rx-rotation-strip__note"><?php esc_html_e( 'Mix any brands, models, colours, and sizes', 'rx-theme' ); ?></p>
	</header>

	<ol class="rx-rotation-strip__tiers">
		<li class="rx-rotation-strip__tier rx-rotation-strip__tier--1">
			<span class="rx-rotation-strip__number" aria-hidden="true">1</span>
			<span class="rx-rotation-strip__text">
				<strong class="rx-rotation-strip__title"><?php esc_html_e( '1 Pair', 'rx-theme' ); ?></strong>
				<span class="rx-rotation-strip__detail"><?php esc_html_e( 'Daily baseline', 'rx-theme' ); ?></span>
			</span>
			<span class="rx-rotation-strip__value"><?php esc_html_e( 'Standard', 'rx-theme' ); ?></span>
		</li>

		<li class="rx-rotation-strip__tier rx-rotation-strip__tier--2">
			<span class="rx-rotation-strip__number" aria-hidden="true">2</span>
			<span class="rx-rotation-strip__text">
				<strong class="rx-rotation-strip__title"><?php esc_html_e( '2 Pairs', 'rx-theme' ); ?></strong>
				<span class="rx-rotation-strip__detail"><?php esc_html_e( 'Instant $128.40 Saving', 'rx-theme' ); ?></span>
			</span>
			<span class="rx-rotation-strip__value">
				<?php
				printf(
					/* translators: %s: 2-pack discount percentage, e.g. "35". */
					esc_html__( 'Save %s%%', 'rx-theme' ),
					esc_html( $rx_theme_two_pack )
				);
				?>
			</span>
		</li>

		<li class="rx-rotation-strip__tier rx-rotation-strip__tier--3">
			<span class="rx-rotation-strip__number" aria-hidden="true">3</span>
			<span class="rx-rotation-strip__text">
				<strong class="rx-rotation-strip__title"><?php esc_html_e( '3 Pairs (Best Value)', 'rx-theme' ); ?></strong>
				<span class="rx-rotation-strip__detail"><?php esc_html_e( 'Complete Lifter + Runner + WOD', 'rx-theme' ); ?></span>
			</span>
			<span class="rx-rotation-strip__value">
				<?php
				printf(
					/* translators: %s: 3-pack discount percentage, e.g. "45". */
					esc_html__( 'Save %s%%', 'rx-theme' ),
					esc_html( $rx_theme_three_pack )
				);
				?>
			</span>
		</li>
	</ol>
</section>
