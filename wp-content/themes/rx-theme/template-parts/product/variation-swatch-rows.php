<?php
/**
 * The colour-card / size-pill attribute rows shared by every place a
 * shopper picks a variation on this site: the single product page
 * (woocommerce/single-product/add-to-cart/variable.php) and the bundle
 * builder's inline "Edit size / colour" panel
 * (template-parts/bundle-builder/pair-edit-panel.php). Extracted here so
 * both real, working pickers render from one source instead of two
 * copies that could quietly drift apart.
 *
 * Caller is responsible for the surrounding `.rx-variations.variations`
 * wrapper (WooCommerce's own add-to-cart-variation.js requires that
 * exact ancestor class — see the single product page template's own
 * comment) and for enqueuing assets/js/variation-swatches.js, which
 * reads the hidden <select> this renders and drives the swatch buttons'
 * active/disabled state from WooCommerce's own real matching logic.
 *
 * Expects $args['product'] (WC_Product_Variable) and $args['attributes']
 * (its variation attributes, already colour-then-size ordered via
 * rx_theme_order_variation_attributes() — the caller's job, since both
 * callers already have their own reasons to touch that array first).
 * Optional $args['id_prefix']: when a page can render more than one
 * variation form at once (the bundle builder's inline "Edit size /
 * colour" panels — see template-parts/bundle-builder/pair-edit-panel.php),
 * every form on the page would otherwise render the exact same
 * `id="pa_colour"` / `id="pa_size"` — duplicate DOM ids break
 * assets/js/variation-swatches.js's `$form.find('#' + selectId)` lookups
 * (jQuery's ID lookups can resolve document-wide, not just within the
 * expected form), so a unique-per-form prefix (e.g. the cart item key)
 * keeps every form's selects distinct even when several are open
 * together. The single product page (one form per page) doesn't need
 * one.
 *
 * Optional $args['selected_values']: attribute name ("pa_colour") =>
 * slug, to pre-select a SPECIFIC variation instead of the product's own
 * generic default — the bundle builder's edit panel passes the cart
 * line's actual current colour/size here, so re-opening "Edit size /
 * colour" shows what's really in the cart already picked (matching the
 * client's reference design, where the current choice is shown pre-
 * confirmed, not a blank picker) instead of the single product page's
 * generic starting point.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_product         = $args['product'] ?? null;
$rx_theme_attributes      = $args['attributes'] ?? array();
$rx_theme_id_prefix       = $args['id_prefix'] ?? '';
$rx_theme_selected_values = $args['selected_values'] ?? array();

if ( ! $rx_theme_product instanceof WC_Product || ! $rx_theme_attributes ) {
	return;
}

$rx_theme_attribute_keys = array_keys( $rx_theme_attributes );
?>
<?php foreach ( $rx_theme_attributes as $rx_theme_attribute_name => $rx_theme_options ) : ?>
	<?php
	$rx_theme_select_id = $rx_theme_id_prefix . sanitize_title( $rx_theme_attribute_name );
	$rx_theme_is_colour = 'pa_colour' === $rx_theme_attribute_name;
	$rx_theme_terms     = taxonomy_exists( $rx_theme_attribute_name )
		? wc_get_product_terms( $rx_theme_product->get_id(), $rx_theme_attribute_name, array( 'fields' => 'all' ) )
		: array();

	if ( 'pa_size' === $rx_theme_attribute_name ) {
		$rx_theme_terms = rx_theme_sort_size_terms( $rx_theme_terms );
	}
	/**
	 * Priority: an explicit override (the bundle edit panel's current
	 * cart-item colour/size) first; otherwise the same fallback chain
	 * wc_dropdown_variation_attribute_options() itself uses when no
	 * 'selected' is passed — a resubmitted query-string selection (e.g.
	 * after WC reloads the page on an out-of-stock error), then the
	 * product's own generic default — so leaving $rx_theme_selected_values
	 * empty (the single product page's case) behaves exactly as before.
	 */
	$rx_theme_request_key   = 'attribute_' . sanitize_title( $rx_theme_attribute_name );
	$rx_theme_default       = $rx_theme_selected_values[ $rx_theme_attribute_name ]
		?? ( isset( $_REQUEST[ $rx_theme_request_key ] ) ? wc_clean( wp_unslash( $_REQUEST[ $rx_theme_request_key ] ) ) : null ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- matches wc_dropdown_variation_attribute_options()'s own unnonced read of the same key; only affects which option is pre-selected, not any state change.
		?? $rx_theme_product->get_variation_default_attribute( $rx_theme_attribute_name );
	$rx_theme_selected_term = null;
	if ( $rx_theme_is_colour ) {
		foreach ( $rx_theme_terms as $rx_theme_candidate ) {
			if ( $rx_theme_candidate->slug === $rx_theme_default ) {
				$rx_theme_selected_term = $rx_theme_candidate;
				break;
			}
		}
	}
	?>
	<div class="rx-variations__row">
		<div class="rx-variations__row-header">
			<?php if ( $rx_theme_is_colour ) : ?>
				<label class="rx-variations__label" for="<?php echo esc_attr( $rx_theme_select_id ); ?>">
					<?php esc_html_e( 'Colourway:', 'rx-theme' ); ?>
					<span class="rx-variations__label-value" data-select-id="<?php echo esc_attr( $rx_theme_select_id ); ?>"><?php echo esc_html( $rx_theme_selected_term ? $rx_theme_selected_term->name : '' ); ?></span>
				</label>
				<span class="rx-variations__count">
					<?php
					printf(
						/* translators: %d: number of colour options. */
						esc_html( _n( '%d variation', '%d variations', count( $rx_theme_options ), 'rx-theme' ) ),
						count( $rx_theme_options )
					);
					?>
				</span>
			<?php else : ?>
				<?php
				$rx_theme_has_paired_sizes = 'pa_size' === $rx_theme_attribute_name && (bool) array_filter(
					$rx_theme_terms,
					static function ( WP_Term $term ): bool {
						return (bool) preg_match( '/^M\S+\s+W\S+$/i', $term->name );
					}
				);
				?>
				<label class="rx-variations__label" for="<?php echo esc_attr( $rx_theme_select_id ); ?>">
					<?php if ( $rx_theme_has_paired_sizes ) : ?>
						<?php esc_html_e( 'Select size (US mens / womens equiv)', 'rx-theme' ); ?>
					<?php else : ?>
						<?php echo wc_attribute_label( $rx_theme_attribute_name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WC core escapes this. ?>
					<?php endif; ?>
				</label>
				<?php if ( end( $rx_theme_attribute_keys ) === $rx_theme_attribute_name ) : ?>
					<?php echo wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#" aria-label="' . esc_attr__( 'Clear options', 'woocommerce' ) . '">' . esc_html__( 'Clear', 'woocommerce' ) . '</a>' ) ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WooCommerce core filter. ?>
				<?php endif; ?>
			<?php endif; ?>
		</div>

		<div class="rx-variation-select-wrap">
			<?php
			wc_dropdown_variation_attribute_options(
				array(
					'options'   => $rx_theme_options,
					'attribute' => $rx_theme_attribute_name,
					'product'   => $rx_theme_product,
					'class'     => 'rx-variation-select',
					'id'        => $rx_theme_select_id,
					'selected'  => $rx_theme_default,
				)
			);
			?>
		</div>

		<?php if ( $rx_theme_terms && ! is_wp_error( $rx_theme_terms ) ) : ?>
			<div class="rx-variation-swatches rx-variation-swatches--<?php echo esc_attr( $rx_theme_is_colour ? 'colour' : 'text' ); ?>" data-select-id="<?php echo esc_attr( $rx_theme_select_id ); ?>">
				<?php foreach ( $rx_theme_terms as $rx_theme_term ) : ?>
					<?php
					if ( ! in_array( $rx_theme_term->slug, $rx_theme_options, true ) ) :
						continue;
endif;
					?>
					<?php if ( $rx_theme_is_colour ) : ?>
						<button type="button" class="rx-swatch-card" data-value="<?php echo esc_attr( $rx_theme_term->slug ); ?>" data-name="<?php echo esc_attr( $rx_theme_term->name ); ?>" aria-pressed="false" aria-label="<?php echo esc_attr( $rx_theme_term->name ); ?>">
							<span class="rx-swatch-card__box" style="<?php echo esc_attr( rx_theme_colour_swatch_style( $rx_theme_term ) . 'color:' . rx_theme_colour_swatch_label_color( $rx_theme_term ) . ';' ); ?>">
								<?php echo esc_html( $rx_theme_term->name ); ?>
							</span>
							<span class="rx-swatch-card__name"><?php echo esc_html( $rx_theme_term->name ); ?></span>
						</button>
					<?php else : ?>
						<button type="button" class="rx-swatch rx-swatch--text" data-value="<?php echo esc_attr( $rx_theme_term->slug ); ?>" aria-pressed="false">
							<?php echo wp_kses_post( rx_theme_variation_term_label( $rx_theme_term ) ); ?>
						</button>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
<?php endforeach; ?>
