<?php
/**
 * PDP "Key features" tiles under the gallery (client reference design:
 * icon, short uppercase title, one line). Rows come from the product's
 * "Key features" tab (RX\Core\Catalog\KeyFeatures in rx-core) via
 * rx_theme_product_key_features(); the theme only owns the icon artwork.
 *
 * Args: rows (array of {title, text, icon}).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_rows = $args['rows'] ?? array();

if ( ! $rx_theme_rows ) {
	return;
}

/* Stroke icons, 24px grid, drawn in currentColor (blue via CSS). */
$rx_theme_icons = array(
	'stability'   => '<path d="M4 9v6M7 7v10M17 7v10M20 9v6M7 12h10"/>',
	'cushioning'  => '<path d="M3 15c2 0 2-2 4.5-2S10 15 12 15s2-2 4.5-2 2.5 2 4.5 2M3 19c2 0 2-2 4.5-2S10 19 12 19s2-2 4.5-2 2.5 2 4.5 2M8 5h8l1 4H7z"/>',
	'durability'  => '<path d="M12 3 5 6v5c0 4.5 3 8 7 10 4-2 7-5.5 7-10V6z"/>',
	'grip'        => '<path d="M5 20c0-6 3-15 8-15 3 0 5 3 5 7 0 5-3 8-6 8z"/><path d="M9 11h5M8.5 14.5h5M10 8h4"/>',
	'fit'         => '<path d="M6 21V11a6 6 0 0 1 12 0v10M6 16h12"/>',
	'breathable'  => '<path d="M3 8h11a3 3 0 1 0-3-3M3 12h15a3 3 0 1 1-3 3M3 16h8"/>',
	'flex'        => '<path d="M4 16c4 0 5-8 8-8s4 8 8 8"/><path d="M4 20h16"/>',
	'lightweight' => '<path d="M20 4C11 4 5 10 5 19M5 19l4-4M12 11l-3 1M16 8l-4 1"/>',
	'feature'     => '<path d="m12 3 2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4l-5.2 2.7 1-5.8L3.5 9.2l5.9-.9z"/>',
);
?>
<ul class="rx-key-features" aria-label="<?php esc_attr_e( 'Key features', 'rx-theme' ); ?>">
	<?php foreach ( $rx_theme_rows as $rx_theme_row ) : ?>
		<li class="rx-key-features__item">
			<svg class="rx-key-features__icon" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<?php echo $rx_theme_icons[ $rx_theme_row['icon'] ] ?? $rx_theme_icons['feature']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG paths defined above. ?>
			</svg>
			<span class="rx-key-features__title"><?php echo esc_html( $rx_theme_row['title'] ); ?></span>
			<?php if ( '' !== $rx_theme_row['text'] ) : ?>
				<span class="rx-key-features__text"><?php echo esc_html( $rx_theme_row['text'] ); ?></span>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
