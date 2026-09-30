<?php
/**
 * One "Complete your rotation" pick on /build-a-bundle/ (client's Figma
 * redesign, 2026-09-29): image, type label, name, short description and
 * "Add to bundle".
 *
 * For a variable product, "Add to bundle" is a pure-CSS checkbox toggle
 * (.rx-bundle-pair__add-toggle) that swaps the card for the real inline
 * size/colour picker (pair-edit-panel.php, "adding" mode) — its
 * add-to-cart redirects straight back here. Any other product type links
 * to its own page instead, since there's no picker to show.
 *
 * Expects $args['product'] (WC_Product) and $args['pair_number'] (the
 * slot it would fill).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_product = $args['product'] ?? null;
$rx_theme_pair    = (int) ( $args['pair_number'] ?? 0 );

if ( ! $rx_theme_product instanceof WC_Product ) {
	return;
}

$rx_theme_permalink = get_permalink( $rx_theme_product->get_id() );
$rx_theme_type      = rx_theme_product_type_label( $rx_theme_product );
$rx_theme_excerpt   = wp_strip_all_tags( $rx_theme_product->get_short_description() );
$rx_theme_inline    = $rx_theme_product instanceof WC_Product_Variable;
$rx_theme_toggle_id = 'rx-rec-add-toggle-' . $rx_theme_product->get_id();
?>
<div class="rx-bundle-rec">
	<?php if ( $rx_theme_inline ) : ?>
		<input type="checkbox" id="<?php echo esc_attr( $rx_theme_toggle_id ); ?>" class="rx-bundle-pair__add-toggle">
	<?php endif; ?>

	<div class="rx-bundle-rec__body">
		<a class="rx-bundle-rec__image" href="<?php echo esc_url( $rx_theme_permalink ); ?>">
			<?php echo wp_kses_post( $rx_theme_product->get_image( 'woocommerce_thumbnail' ) ); ?>
		</a>
		<div class="rx-bundle-rec__text">
			<?php if ( $rx_theme_type ) : ?>
				<p class="rx-bundle-rec__type"><?php echo esc_html( $rx_theme_type ); ?></p>
			<?php endif; ?>
			<h3 class="rx-bundle-rec__title">
				<a href="<?php echo esc_url( $rx_theme_permalink ); ?>"><?php echo esc_html( $rx_theme_product->get_name() ); ?></a>
			</h3>
			<?php if ( $rx_theme_excerpt ) : ?>
				<p class="rx-bundle-rec__excerpt"><?php echo esc_html( wp_trim_words( $rx_theme_excerpt, 12 ) ); ?></p>
			<?php endif; ?>
			<?php if ( $rx_theme_inline ) : ?>
				<label class="rx-bundle-rec__add" for="<?php echo esc_attr( $rx_theme_toggle_id ); ?>"><?php esc_html_e( 'Add to bundle', 'rx-theme' ); ?> &rarr;</label>
			<?php else : ?>
				<a class="rx-bundle-rec__add" href="<?php echo esc_url( $rx_theme_permalink ); ?>"><?php esc_html_e( 'Add to bundle', 'rx-theme' ); ?> &rarr;</a>
			<?php endif; ?>
		</div>
	</div>

	<?php
	if ( $rx_theme_inline ) {
		get_template_part(
			'template-parts/bundle-builder/pair-edit-panel',
			null,
			array(
				'product'     => $rx_theme_product,
				'pair_number' => $rx_theme_pair,
				'toggle_id'   => $rx_theme_toggle_id,
				'id_prefix'   => 'rx-rec-add-' . $rx_theme_product->get_id() . '-',
				'button_text' => __( 'Add to bundle', 'rx-theme' ),
			)
		);
	}
	?>
</div>
