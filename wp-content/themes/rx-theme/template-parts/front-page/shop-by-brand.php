<?php
/**
 * Homepage "Shop by Brand" section (Figma section 6, "Official
 * Partners"): a row of brand tiles — logo and "N models" — from the
 * store's product brands (see inc/brand-tiles.php). Shows at most seven
 * tiles (the design's row), the brands with the most products first; the
 * link on the right carries the total.
 *
 * Eyebrow and heading are Customizer fields (rx_theme_brands_fields()).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_all_brands = rx_theme_brands_with_products();

if ( ! $rx_theme_all_brands ) {
	return;
}

$rx_theme_tiles = array_slice( $rx_theme_all_brands, 0, 7 );
?>
<section class="rx-brands" aria-labelledby="rx-brands-heading">
	<header class="rx-brands__header">
		<div class="rx-brands__title">
			<p class="rx-eyebrow" data-customize-partial="rx_brands_eyebrow"><?php echo esc_html( rx_theme_get_mod( 'rx_brands_eyebrow' ) ); ?></p>
			<h2 class="rx-section-heading" id="rx-brands-heading" data-customize-partial="rx_brands_heading"><?php echo esc_html( rx_theme_get_mod( 'rx_brands_heading' ) ); ?></h2>
		</div>

		<a class="rx-brands__all" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
			<?php
			printf(
				/* translators: %d: total number of brands. */
				esc_html( _n( 'View all %d brand', 'View all %d brands', count( $rx_theme_all_brands ), 'rx-theme' ) ),
				count( $rx_theme_all_brands )
			);
			?>
			<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
		</a>
	</header>

	<ul class="rx-brands__grid" style="--rx-brand-cols:<?php echo count( $rx_theme_tiles ); ?>">
		<?php foreach ( $rx_theme_tiles as $rx_theme_brand ) : ?>
			<?php $rx_theme_logo_id = rx_theme_brand_logo_id( $rx_theme_brand ); ?>
			<li>
				<a class="rx-brand-tile" href="<?php echo esc_url( get_term_link( $rx_theme_brand ) ); ?>">
					<?php if ( $rx_theme_logo_id ) : ?>
						<?php
						echo wp_get_attachment_image(
							$rx_theme_logo_id,
							'medium',
							false,
							array(
								'class' => 'rx-brand-tile__logo',
								'alt'   => $rx_theme_brand->name,
							)
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-generated <img>. 
						?>
					<?php else : ?>
						<span class="rx-brand-tile__name"><?php echo esc_html( $rx_theme_brand->name ); ?></span>
					<?php endif; ?>
					<span class="rx-brand-tile__count">
						<?php
						printf(
							/* translators: %s: number of products (models) by this brand. */
							esc_html( _n( '%s Model', '%s Models', (int) $rx_theme_brand->count, 'rx-theme' ) ),
							esc_html( number_format_i18n( (int) $rx_theme_brand->count ) )
						);
						?>
					</span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
