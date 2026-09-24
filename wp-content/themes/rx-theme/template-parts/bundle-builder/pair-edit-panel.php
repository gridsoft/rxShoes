<?php
/**
 * Inline size/colour picker for a rotation slot on /build-a-bundle/ —
 * shared by two real, working cases, per the client's explicit
 * correction (2026-09-23) that neither should navigate away from this
 * page:
 *
 * 1. EDITING a filled pair ($args['entry'] set): "Edit size / colour"
 *    swaps that pair's static display for this real variation form,
 *    pre-filled with what's actually in the cart (see
 *    $rx_theme_selected_values below), with a hidden
 *    `rx_bundle_replace_key` naming the cart line to remove once the new
 *    one is confirmed added (rx_theme_bundle_builder_swap_variation(),
 *    inc/bundle-builder.php) — a genuine swap, not a second line.
 * 2. ADDING a recommended product to an open slot ($args['product'] set):
 *    the "Recommended rotation pairing" card's CTA opens this same form
 *    for THAT product instead of linking to its own page — no replace
 *    key, since there's nothing to remove, just a real add.
 *
 * Both cases share the exact same swatch rendering/JS as the single
 * product page (template-parts/product/variation-swatch-rows.php,
 * assets/js/variation-swatches.js) and the exact same real
 * add-to-cart-and-redirect mechanism the PDP's own "Add to rotation
 * bundle" button uses (rx_theme_bundle_builder_add_to_cart_redirect()).
 *
 * Laid out as two columns (.rx-bundle-pair__edit-layout): the product's
 * own real image + title on the left (.rx-bundle-pair__edit-media — the
 * only place either appears while this panel is open, since it replaces
 * the static card content that would otherwise show them), the actual
 * picker + confirm button on the right (.rx-bundle-pair__edit-fields).
 *
 * Toggled open/closed by a pure-CSS checkbox (see
 * .rx-bundle-pair__edit-toggle / .rx-bundle-pair__add-toggle in
 * pair-card.php / empty-slot.php / style.css) — no JS needed for the
 * open/close interaction itself, only for the real variation picking
 * inside it.
 *
 * Expects $args['pair_number'] and $args['toggle_id'], plus EITHER
 * $args['entry'] (one item from
 * rx_theme_bundle_builder_cart_buckets()['eligible'], for case 1) OR
 * $args['product'] (a WC_Product_Variable, for case 2).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_entry      = $args['entry'] ?? null;
$rx_theme_pair       = (int) ( $args['pair_number'] ?? 0 );
$rx_theme_toggle_id  = $args['toggle_id'] ?? '';
$rx_theme_parent     = $rx_theme_entry ? $rx_theme_entry['parent'] : ( $args['product'] ?? null );
$rx_theme_is_editing = null !== $rx_theme_entry;

if ( ! $rx_theme_parent instanceof WC_Product_Variable ) {
	return;
}

$rx_theme_attributes           = rx_theme_order_variation_attributes( $rx_theme_parent->get_variation_attributes() );
$rx_theme_available_variations = $rx_theme_parent->get_available_variations();
$rx_theme_variations_json      = wp_json_encode( $rx_theme_available_variations );
$rx_theme_variations_attr      = function_exists( 'wc_esc_json' ) ? wc_esc_json( $rx_theme_variations_json ) : _wp_specialchars( $rx_theme_variations_json, ENT_QUOTES, 'UTF-8', true );
$rx_theme_id_prefix            = ( $rx_theme_is_editing ? 'rx-pair-edit-' : 'rx-pair-add-' ) . $rx_theme_pair . '-';

/**
 * Editing: what's actually in the cart right now for this line —
 * "attribute_pa_size" => "11" — remapped to plain attribute names
 * ("pa_size" => "11") to match $rx_theme_attributes' own keys, and
 * passed to the swatch partial so the panel opens already showing the
 * real current choice (pre-selected, button already valid/enabled)
 * instead of a blank picker. Adding: no current choice exists yet, so
 * this stays empty and the swatch partial falls back to the product's
 * own generic default, same as the single product page.
 */
$rx_theme_selected_values = array();
if ( $rx_theme_is_editing ) {
	foreach ( (array) ( $rx_theme_entry['cart_item']['variation'] ?? array() ) as $rx_theme_key => $rx_theme_value ) {
		$rx_theme_selected_values[ str_replace( 'attribute_', '', $rx_theme_key ) ] = $rx_theme_value;
	}
}

/**
 * The confirm button's label, scoped to this exact product so it
 * doesn't leak onto any other variation form WooCommerce might render
 * later in the same request.
 */
add_filter(
	'woocommerce_product_single_add_to_cart_text',
	static function ( string $text, $filtered_product ) use ( $rx_theme_parent, $rx_theme_pair ): string {
		if ( $filtered_product->get_id() !== $rx_theme_parent->get_id() ) {
			return $text;
		}

		return sprintf(
			/* translators: %d: pair number. */
			__( 'Confirm Pair %d in rotation', 'rx-theme' ),
			$rx_theme_pair
		);
	},
	20,
	2
);

/**
 * WooCommerce's woocommerce_single_variation_add_to_cart_button() (hooked to
 * woocommerce_single_variation at priority 20, called below) reads
 * `global $product` to build the button — this page's global $product
 * isn't set to any one product (it's not a single-product template), so
 * it has to be set here for the real button/quantity markup to render
 * for the right item, then restored so nothing after this panel is
 * affected.
 */
global $product;
$rx_theme_previous_global_product = $product;
$product                          = $rx_theme_parent; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- WooCommerce's global, deliberately swapped and restored below.
?>
<div class="rx-bundle-pair__edit-panel">
	<div class="rx-bundle-pair__edit-panel-head">
		<p class="rx-bundle-pair__edit-panel-title">
			<?php
			echo esc_html(
				$rx_theme_is_editing
					? __( 'Choose a different size or colour', 'rx-theme' )
					: __( 'Choose your size and colour', 'rx-theme' )
			);
			?>
		</p>
		<?php if ( $rx_theme_toggle_id ) : ?>
			<label class="rx-bundle-pair__edit-panel-cancel" for="<?php echo esc_attr( $rx_theme_toggle_id ); ?>"><?php esc_html_e( 'Cancel', 'rx-theme' ); ?></label>
		<?php endif; ?>
	</div>

	<form class="variations_form cart" action="<?php echo esc_url( add_query_arg( 'rx_add_to_rotation', '1', $rx_theme_parent->get_permalink() ) ); ?>" method="post" enctype="multipart/form-data" data-product_id="<?php echo absint( $rx_theme_parent->get_id() ); ?>" data-product_variations="<?php echo $rx_theme_variations_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above with wc_esc_json(). ?>">
		<?php if ( $rx_theme_is_editing ) : ?>
			<input type="hidden" name="rx_bundle_replace_key" value="<?php echo esc_attr( $rx_theme_entry['key'] ); ?>">
			<?php wp_nonce_field( 'rx_bundle_replace_' . $rx_theme_entry['key'], 'rx_bundle_replace_nonce' ); ?>
		<?php endif; ?>

		<div class="rx-bundle-pair__edit-layout">
			<div class="rx-bundle-pair__edit-media">
				<a class="rx-bundle-pair__image" href="<?php echo esc_url( $rx_theme_parent->get_permalink() ); ?>">
					<?php echo wp_kses_post( $rx_theme_parent->get_image( 'woocommerce_thumbnail' ) ); ?>
				</a>
				<h3 class="rx-bundle-pair__title">
					<a href="<?php echo esc_url( $rx_theme_parent->get_permalink() ); ?>"><?php echo esc_html( $rx_theme_parent->get_name() ); ?></a>
				</h3>
			</div>

			<div class="rx-bundle-pair__edit-fields">
				<div class="rx-variations variations rx-bundle-pair__edit-variations">
					<?php
					get_template_part(
						'template-parts/product/variation-swatch-rows',
						null,
						array(
							'product'         => $rx_theme_parent,
							'attributes'      => $rx_theme_attributes,
							'id_prefix'       => $rx_theme_id_prefix,
							'selected_values' => $rx_theme_selected_values,
						)
					);
					?>
				</div>

				<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>

				<div class="single_variation_wrap rx-bundle-pair__edit-confirm">
					<?php
					// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce's own variation hooks, fired as the single-product form does.
					do_action( 'woocommerce_before_single_variation' );
					do_action( 'woocommerce_single_variation' );
					do_action( 'woocommerce_after_single_variation' );
					// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
					?>
				</div>
			</div>
		</div>
	</form>
</div>
<?php
$product = $rx_theme_previous_global_product; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, WordPress.WP.GlobalVariablesOverride.Prohibited -- restoring WooCommerce's global.
