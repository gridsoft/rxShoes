<?php
/**
 * One filled rotation slot on /build-a-bundle/ — a real cart line that's
 * bundle-eligible. Matches the client's Figma reference card design
 * (2026-09-22 screenshot) layout-for-layout: badge row, price + eligible
 * note, divider, image with type badge + colour dot, title, "best for"
 * line, a 3-column spec strip, and two actions.
 *
 * Two of that reference card's fields aren't real data here and are
 * swapped for an honest equivalent rather than reproduced as-is (see
 * inc/bundle-builder.php's rx_theme_bundle_builder_stock_state() and the
 * SKU field below): "Stack / Drop: 6mm Dual-Foam" — no shoe-spec field
 * exists in this catalogue — becomes the product's real SKU; "Status: In
 * Sydney Hub" — an unconfirmed warehouse location, the same claim already
 * kept out of the footer's trust copy — becomes WooCommerce's own real
 * stock status.
 *
 * "Replace shoe" is real WooCommerce cart-item removal (same
 * wc_get_cart_remove_url() the cart page itself uses) — "replacing" a
 * shoe means clearing this slot so a different one can be added.
 *
 * "Edit size / colour" does NOT navigate away (client correction,
 * 2026-09-23): it's a pure-CSS checkbox toggle (.rx-bundle-pair__edit-toggle)
 * that swaps this card's static body for a real inline variation-picker
 * form (template-parts/bundle-builder/pair-edit-panel.php) showing that
 * exact product's actual available sizes/colours — same swatch rendering
 * and JS the single product page uses, not a mock picker.
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

$rx_theme_cart_item  = $rx_theme_entry['cart_item'];
$rx_theme_product    = $rx_theme_entry['product'];
$rx_theme_parent     = $rx_theme_entry['parent'];
$rx_theme_permalink  = get_permalink( $rx_theme_parent->get_id() );
$rx_theme_qty        = (int) $rx_theme_cart_item['quantity'];
$rx_theme_line_total = rx_theme_product_price( $rx_theme_product ) * $rx_theme_qty;

$rx_theme_type_label = rx_theme_product_type_label( $rx_theme_parent );
$rx_theme_series     = rx_theme_bundle_builder_gender_series_label( $rx_theme_parent );
$rx_theme_best_for   = rx_theme_product_best_for( $rx_theme_parent );
$rx_theme_colour     = rx_theme_bundle_builder_configured_colour( $rx_theme_cart_item );
$rx_theme_size       = rx_theme_bundle_builder_configured_size( $rx_theme_cart_item );
$rx_theme_sku        = $rx_theme_product->get_sku() ?: $rx_theme_parent->get_sku();
$rx_theme_stock      = rx_theme_bundle_builder_stock_state( $rx_theme_product );
$rx_theme_max_pack   = rx_theme_bundle_max_discount_percent();
$rx_theme_toggle_id  = 'rx-pair-edit-toggle-' . $rx_theme_pair;
?>
<div class="rx-bundle-pair rx-bundle-pair--filled" id="rx-pair-<?php echo esc_attr( $rx_theme_pair ); ?>">
	<input type="checkbox" id="<?php echo esc_attr( $rx_theme_toggle_id ); ?>" class="rx-bundle-pair__edit-toggle">

	<div class="rx-bundle-pair__head">
		<div class="rx-bundle-pair__head-badges">
			<span class="rx-badge rx-badge--lime rx-bundle-pair__complete-badge">
				<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" fill="#111"/><path d="m8 12.5 2.8 2.8L16 9.5" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<?php echo esc_html( sprintf( /* translators: %d: pair number. */ __( 'Pair %d • Complete', 'rx-theme' ), $rx_theme_pair ) ); ?>
			</span>
			<?php if ( $rx_theme_series ) : ?>
				<span class="rx-badge"><?php echo esc_html( $rx_theme_series ); ?></span>
			<?php endif; ?>
		</div>
		<div class="rx-bundle-pair__head-price">
			<span class="rx-bundle-pair__price"><?php echo esc_html( rx_theme_format_money( $rx_theme_line_total, true ) ); ?></span>
			<span class="rx-bundle-pair__eligible"><?php echo esc_html( sprintf( /* translators: %s: top bundle discount percentage. */ __( '(Eligible for -%s%%)', 'rx-theme' ), rx_theme_format_percent( $rx_theme_max_pack ) ) ); ?></span>
		</div>
	</div>

	<hr class="rx-bundle-pair__divider">

	<div class="rx-bundle-pair__body">
		<div class="rx-bundle-pair__media">
			<?php if ( $rx_theme_type_label ) : ?>
				<span class="rx-bundle-pair__type-badge"><?php echo esc_html( $rx_theme_type_label ); ?></span>
			<?php endif; ?>
			<a class="rx-bundle-pair__image" href="<?php echo esc_url( $rx_theme_permalink ); ?>">
				<?php echo wp_kses_post( $rx_theme_product->get_image( 'woocommerce_thumbnail' ) ); ?>
			</a>
			<?php if ( $rx_theme_colour ) : ?>
				<p class="rx-bundle-pair__colour">
					<span class="rx-bundle-pair__colour-dot" style="<?php echo esc_attr( rx_theme_colour_swatch_style( $rx_theme_colour ) ); ?>"></span>
					<?php echo esc_html( $rx_theme_colour->name ); ?>
				</p>
			<?php endif; ?>
		</div>

		<div class="rx-bundle-pair__info">
			<h3 class="rx-bundle-pair__title">
				<a href="<?php echo esc_url( $rx_theme_permalink ); ?>"><?php echo esc_html( $rx_theme_parent->get_name() ); ?></a>
			</h3>

			<?php if ( $rx_theme_best_for ) : ?>
				<p class="rx-bundle-pair__bestfor"><?php echo esc_html( $rx_theme_best_for ); ?></p>
			<?php endif; ?>

			<div class="rx-bundle-pair__specs">
				<?php if ( $rx_theme_size ) : ?>
					<div class="rx-bundle-pair__spec">
						<span class="rx-bundle-pair__spec-label"><?php esc_html_e( 'Configured size', 'rx-theme' ); ?></span>
						<strong><?php echo esc_html( $rx_theme_size ); ?></strong>
					</div>
				<?php endif; ?>
				<?php if ( $rx_theme_sku ) : ?>
					<div class="rx-bundle-pair__spec">
						<span class="rx-bundle-pair__spec-label"><?php esc_html_e( 'SKU', 'rx-theme' ); ?></span>
						<strong><?php echo esc_html( $rx_theme_sku ); ?></strong>
					</div>
				<?php endif; ?>
				<div class="rx-bundle-pair__spec">
					<span class="rx-bundle-pair__spec-label"><?php esc_html_e( 'Stock', 'rx-theme' ); ?></span>
					<strong class="rx-bundle-pair__stock<?php echo $rx_theme_stock['is_ok'] ? '' : ' rx-bundle-pair__stock--low'; ?>"><?php echo esc_html( $rx_theme_stock['label'] ); ?></strong>
				</div>
			</div>

			<div class="rx-bundle-pair__actions">
				<label class="rx-bundle-pair__action rx-bundle-pair__action--button" for="<?php echo esc_attr( $rx_theme_toggle_id ); ?>">
					<svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M4 6h10M4 12h6M4 18h12"/><circle cx="17" cy="6" r="2" fill="currentColor"/><circle cx="13" cy="12" r="2" fill="currentColor"/><circle cx="19" cy="18" r="2" fill="currentColor"/></svg>
					<?php esc_html_e( 'Edit size / colour', 'rx-theme' ); ?>
				</label>
				<a class="rx-bundle-pair__action rx-bundle-pair__action--divided" href="<?php echo esc_url( wc_get_cart_remove_url( $rx_theme_entry['key'] ) ); ?>">
					<svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M17 2l4 4-4 4M3 11V9a4 4 0 0 1 4-4h14M7 22l-4-4 4-4M21 13v2a4 4 0 0 1-4 4H3"/></svg>
					<?php esc_html_e( 'Replace shoe', 'rx-theme' ); ?>
				</a>
			</div>
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
		)
	);
	?>
</div>
