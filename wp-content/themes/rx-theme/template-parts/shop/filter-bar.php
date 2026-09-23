<?php
/**
 * Shop filter bar markup. Data and URLs come from inc/shop-filters.php.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_chips        = rx_theme_shop_size_chips();
$rx_theme_size_modes   = rx_theme_shop_size_mode_options();
$rx_theme_size_mode_on = '' !== rx_theme_shop_selected_size_mode();
$rx_theme_sizes_on     = array() !== rx_theme_shop_selected_sizes();
$rx_theme_stock_on     = rx_theme_shop_in_stock_only();
$rx_theme_bundle_on    = rx_theme_shop_bundle_only();
$rx_theme_has_bundle   = array() !== rx_theme_shop_bundle_meta_query();

/**
 * Attribute for an active toggle/chip. aria-current marks the pressed
 * state for screen readers; these are links, so aria-pressed isn't valid.
 *
 * @param bool $active Whether the control is on.
 */
$rx_theme_current = static function ( bool $active ): string {
	return $active ? ' aria-current="true"' : '';
};
?>
<section class="rx-filters" aria-label="<?php esc_attr_e( 'Filter products', 'rx-theme' ); ?>">
	<?php if ( $rx_theme_size_modes ) : ?>
		<div class="rx-filters__sizes">
			<span class="rx-filters__label" id="rx-filters-size-mode-label">
				<?php esc_html_e( 'Size for:', 'rx-theme' ); ?>
			</span>
			<ul class="rx-filters__chips" aria-labelledby="rx-filters-size-mode-label">
				<?php foreach ( $rx_theme_size_modes as $rx_theme_size_mode_option ) : ?>
					<li>
						<a class="rx-chip<?php echo $rx_theme_size_mode_option['active'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $rx_theme_size_mode_option['url'] ); ?>" rel="nofollow"<?php echo $rx_theme_current( $rx_theme_size_mode_option['active'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal. ?>><?php echo esc_html( $rx_theme_size_mode_option['label'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $rx_theme_size_mode_on ) : ?>
				<a class="rx-chip rx-chip--clear" href="<?php echo esc_url( rx_theme_shop_size_mode_clear_url() ); ?>" rel="nofollow"><?php esc_html_e( 'Reset', 'rx-theme' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<?php if ( $rx_theme_chips ) : ?>
		<div class="rx-filters__sizes">
			<span class="rx-filters__label" id="rx-filters-size-label">
				<svg class="rx-filters__label-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><rect x="2" y="8" width="20" height="8" rx="1" fill="none" stroke="currentColor" stroke-width="2"/><path d="M6 8v4M10 8v3M14 8v4M18 8v3" fill="none" stroke="currentColor" stroke-width="2"/></svg>
				<?php echo esc_html( rx_theme_shop_size_chips_label() ); ?>
			</span>
			<ul class="rx-filters__chips" aria-labelledby="rx-filters-size-label">
				<?php foreach ( $rx_theme_chips as $rx_theme_chip ) : ?>
					<li>
						<a class="rx-chip<?php echo $rx_theme_chip['active'] ? ' is-active' : ''; ?>" href="<?php echo esc_url( $rx_theme_chip['url'] ); ?>" rel="nofollow"<?php echo $rx_theme_current( $rx_theme_chip['active'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal. ?>><?php echo esc_html( $rx_theme_chip['label'] ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $rx_theme_sizes_on ) : ?>
				<a class="rx-chip rx-chip--clear" href="<?php echo esc_url( rx_theme_shop_size_clear_url() ); ?>" rel="nofollow"><?php esc_html_e( 'Clear', 'rx-theme' ); ?></a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="rx-filters__options">
		<a class="rx-toggle rx-toggle--stock<?php echo $rx_theme_stock_on ? ' is-active' : ''; ?>" href="<?php echo esc_url( rx_theme_shop_url( array( 'rx_in_stock' => $rx_theme_stock_on ? false : '1' ) ) ); ?>" rel="nofollow"<?php echo $rx_theme_current( $rx_theme_stock_on ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal. ?>>
			<span class="rx-toggle__box" aria-hidden="true"></span>
			<?php esc_html_e( 'In stock live', 'rx-theme' ); ?>
		</a>

		<?php if ( $rx_theme_has_bundle ) : ?>
			<a class="rx-toggle rx-toggle--bundle<?php echo $rx_theme_bundle_on ? ' is-active' : ''; ?>" href="<?php echo esc_url( rx_theme_shop_url( array( 'rx_bundle' => $rx_theme_bundle_on ? false : '1' ) ) ); ?>" rel="nofollow"<?php echo $rx_theme_current( $rx_theme_bundle_on ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed literal. ?>>
				<span class="rx-toggle__box" aria-hidden="true"></span>
				<?php
				printf(
					/* translators: %s: share of products that are bundle-eligible, e.g. "75" or "33.33". */
					esc_html__( 'Bundle eligible (%s%%)', 'rx-theme' ),
					esc_html( rx_theme_format_percent( rx_theme_shop_bundle_share_percent() ) )
				);
				?>
			</a>
		<?php endif; ?>

		<div class="rx-filters__sort">
			<span class="rx-filters__sort-label" aria-hidden="true"><?php esc_html_e( 'Sort:', 'rx-theme' ); ?></span>
			<?php woocommerce_catalog_ordering(); ?>
		</div>
	</div>
</section>
