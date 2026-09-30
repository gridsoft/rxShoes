<?php
/**
 * One filled rotation slot on /build-a-bundle/ — a real cart line that's
 * bundle-eligible. Compact card per the client's Figma redesign
 * (2026-09-29): type pill, image, name, "Colour / Size" line, and two
 * small Edit / Remove buttons.
 *
 * "Remove" is real WooCommerce cart-item removal (the same
 * wc_get_cart_remove_url() the cart page uses).
 *
 * "Edit" does NOT navigate away: it's a pure-CSS checkbox toggle
 * (.rx-bundle-pair__edit-toggle) that swaps this card's body for the real
 * inline variation picker (template-parts/bundle-builder/pair-edit-panel.php),
 * with the card widened to the full slot row while it's open.
 *
 * Expects $args['entry'] (one item from
 * rx_theme_bundle_builder_cart_buckets()['eligible']) and
 * $args['pair_number'].
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_entry = $args['entry'] ?? null;
$rx_theme_pair  = (int) ( $args['pair_number'] ?? 0 );

if ( ! $rx_theme_entry ) {
	return;
}

$rx_theme_product    = $rx_theme_entry['product'];
$rx_theme_parent     = $rx_theme_entry['parent'];
$rx_theme_permalink  = get_permalink( $rx_theme_parent->get_id() );
$rx_theme_type_label = rx_theme_product_type_label( $rx_theme_parent );
$rx_theme_colour     = rx_theme_bundle_builder_configured_colour( $rx_theme_entry['cart_item'] );
$rx_theme_size       = rx_theme_bundle_builder_configured_size( $rx_theme_entry['cart_item'] );
$rx_theme_toggle_id  = 'rx-pair-edit-toggle-' . $rx_theme_pair;
$rx_theme_meta       = array_filter(
	array(
		$rx_theme_colour ? $rx_theme_colour->name : '',
		/* translators: %s: configured shoe size, e.g. "US M10 / W11.5". */
		$rx_theme_size ? sprintf( __( 'Size %s', 'rx-theme' ), $rx_theme_size ) : '',
	)
);
?>
<div class="rx-bundle-slot rx-bundle-slot--filled" id="rx-pair-<?php echo esc_attr( $rx_theme_pair ); ?>">
	<input type="checkbox" id="<?php echo esc_attr( $rx_theme_toggle_id ); ?>" class="rx-bundle-pair__edit-toggle">

	<div class="rx-bundle-slot__body">
		<?php if ( $rx_theme_type_label ) : ?>
			<span class="rx-bundle-slot__type"><?php echo esc_html( $rx_theme_type_label ); ?></span>
		<?php endif; ?>

		<a class="rx-bundle-slot__image" href="<?php echo esc_url( $rx_theme_permalink ); ?>">
			<?php echo wp_kses_post( $rx_theme_product->get_image( 'woocommerce_thumbnail' ) ); ?>
		</a>

		<h3 class="rx-bundle-slot__title">
			<a href="<?php echo esc_url( $rx_theme_permalink ); ?>"><?php echo esc_html( $rx_theme_parent->get_name() ); ?></a>
		</h3>
		<?php if ( $rx_theme_meta ) : ?>
			<p class="rx-bundle-slot__meta"><?php echo esc_html( implode( ' · ', $rx_theme_meta ) ); ?></p>
		<?php endif; ?>

		<div class="rx-bundle-slot__actions">
			<label class="rx-bundle-slot__action" for="<?php echo esc_attr( $rx_theme_toggle_id ); ?>">
				<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 20h4L19 9l-4-4L4 16v4Z"/></svg>
				<?php esc_html_e( 'Edit', 'rx-theme' ); ?>
			</label>
			<a class="rx-bundle-slot__action" href="<?php echo esc_url( wc_get_cart_remove_url( $rx_theme_entry['key'] ) ); ?>">
				<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13M10 11v6M14 11v6"/></svg>
				<?php esc_html_e( 'Remove', 'rx-theme' ); ?>
			</a>
		</div>
	</div>

	<?php
	get_template_part(
		'template-parts/bundle-builder/pair-edit-panel',
		null,
		array(
			'entry'       => $rx_theme_entry,
			'pair_number' => $rx_theme_pair,
			'toggle_id'   => $rx_theme_toggle_id,
			'button_text' => __( 'Update pair', 'rx-theme' ),
		)
	);
	?>
</div>
