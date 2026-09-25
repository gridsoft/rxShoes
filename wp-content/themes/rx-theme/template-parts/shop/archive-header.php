<?php
/**
 * Archive header markup (see inc/archive-header.php for the data).
 *
 * Every archive: breadcrumb + title. Shop page and taxonomy pages also get
 * the stats card; taxonomy pages also show the term description.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_is_tax    = rx_theme_archive_is_taxonomy();
$rx_theme_has_stats = rx_theme_archive_has_stats();

if ( $rx_theme_has_stats ) {
	$rx_theme_brands     = rx_theme_archive_brand_count( rx_theme_archive_product_ids() );
	$rx_theme_save_up_to = rx_theme_format_percent( rx_theme_bundle_max_discount_percent() );
	// "Save up to …" only while the bundle offer runs (inc/bundle-offer.php).
	$rx_theme_show_save = rx_theme_bundle_offer_is_active();
	$rx_theme_has_stats = $rx_theme_brands > 0 || $rx_theme_show_save;
}
?>
<div class="rx-archive-header<?php echo $rx_theme_has_stats ? ' rx-archive-header--stats' : ''; ?>">
	<div class="rx-archive-header__top">
		<?php woocommerce_breadcrumb(); ?>
	</div>

	<div class="rx-archive-header__main">
		<div class="rx-archive-header__intro">
			<h1 class="rx-archive-header__title"><?php woocommerce_page_title(); ?></h1>
			<?php
			if ( $rx_theme_is_tax ) {
				woocommerce_taxonomy_archive_description(); // Outputs <div class="term-description"> only when the term has one.
			}
			?>
		</div>

		<?php if ( $rx_theme_has_stats ) : ?>
			<div class="rx-archive-stats">
				<?php if ( $rx_theme_brands > 0 ) : ?>
					<p class="rx-archive-stats__item">
						<strong class="rx-archive-stats__value">
							<?php
							printf(
								/* translators: %s: number of brands on this page. */
								esc_html( _n( '%s Brand', '%s Brands', $rx_theme_brands, 'rx-theme' ) ),
								esc_html( number_format_i18n( $rx_theme_brands ) )
							);
							?>
						</strong>
						<span class="rx-archive-stats__label"><?php esc_html_e( 'Cross-discipline', 'rx-theme' ); ?></span>
					</p>
					<?php if ( $rx_theme_show_save ) : ?>
						<span class="rx-archive-stats__divider" aria-hidden="true"></span>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( $rx_theme_show_save ) : ?>
				<p class="rx-archive-stats__item">
					<strong class="rx-archive-stats__value rx-archive-stats__value--save">
						<?php
						printf(
							/* translators: %s: top-tier bundle discount percentage, e.g. "45". */
							esc_html__( 'Save up to %s%%', 'rx-theme' ),
							esc_html( $rx_theme_save_up_to )
						);
						?>
					</strong>
					<span class="rx-archive-stats__label"><?php esc_html_e( 'Rotation incentive', 'rx-theme' ); ?></span>
				</p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</div>
