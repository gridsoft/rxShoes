<?php
/**
 * Variable product add to cart.
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/single-product/add-to-cart/variable.php,
 * based on core version 11.1.0. Restyles the attribute pickers as colour
 * swatches (pa_colour) and size pills (pa_size) to match the client's
 * single-product reference (2026-09-22) — everything else (the form
 * action, the JSON variation data blob, the single_variation_wrap and
 * its hooks for price/stock/add-to-cart, the gallery reset template) is
 * unchanged from core.
 *
 * Each attribute's real <select> (built by
 * wc_dropdown_variation_attribute_options() exactly as core does) is
 * kept in the DOM, visually hidden — see .rx-variation-select in
 * style.css — so WooCommerce's own add-to-cart-variation.js keeps
 * driving real stock/price/availability logic unchanged. The swatch/pill
 * buttons next to it are a pure display layer that clicks the hidden
 * select and reads its computed disabled/selected state back — see
 * assets/js/variation-swatches.js. No stock/price/matching logic is
 * reimplemented here.
 *
 * Colour swatches read their colour(s) from rx-core's ColourSwatches
 * term meta (Products > Attributes > Colour > term) via
 * rx_theme_colour_swatch_style() in inc/single-product.php — many terms
 * don't have one set yet (brand marketing names, not guessed — see that
 * file), and show a neutral placeholder swatch until an admin picks one.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.1.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

$attributes               = rx_theme_order_variation_attributes( $attributes );
$rx_theme_variations_json = wp_json_encode( $available_variations );
$rx_theme_variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $rx_theme_variations_json ) : _wp_specialchars( $rx_theme_variations_json, ENT_QUOTES, 'UTF-8', true );

$rx_theme_is_bundle  = rx_theme_product_is_bundle_eligible( $product );
$rx_theme_two_pack   = $rx_theme_is_bundle ? rx_theme_bundle_tier_data( $product, rx_theme_bundle_two_pack_discount_percent(), 2 ) : null;
$rx_theme_three_pack = $rx_theme_is_bundle ? rx_theme_bundle_tier_data( $product, rx_theme_bundle_max_discount_percent(), 3 ) : null;

/**
 * The "Buy this pair only" button's label, on bundle-eligible products
 * only — real product price, real add-to-cart button underneath (see
 * single-product/add-to-cart/variation-add-to-cart-button.php, not
 * overridden — only its text is filtered here and its quantity input
 * hidden in CSS, since a shopper always buys exactly one pair here).
 */
if ( $rx_theme_is_bundle ) {
	add_filter(
		'woocommerce_product_single_add_to_cart_text',
		static function ( string $text, $filtered_product ) use ( $product ): string {
			if ( $filtered_product->get_id() !== $product->get_id() ) {
				return $text;
			}

			return sprintf(
				/* translators: %s: product price, e.g. "$229 AUD". */
				__( 'Buy this pair only (%s)', 'rx-theme' ),
				rx_theme_format_money( rx_theme_product_price( $product ), true )
			);
		},
		10,
		2
	);
}

do_action( 'woocommerce_before_add_to_cart_form' ); ?>

<form class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $rx_theme_variations_attr; // WPCS: XSS ok. ?>">
	<?php do_action( 'woocommerce_before_variations_form' ); ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		<div class="rx-variations variations">
			<?php
			/**
			 * WooCommerce's own add-to-cart-variation.js finds the
			 * attribute <select> elements via `$form.find('.variations
			 * select')` — literally requiring an ancestor with class
			 * "variations" (core's own <table class="variations"> gave it
			 * that for free; this div needs the same class kept, or WC's
			 * own change handling silently never fires at all).
			 */
			?>
			<?php
			get_template_part(
				'template-parts/product/variation-swatch-rows',
				null,
				array(
					'product'    => $product,
					'attributes' => $attributes,
				)
			);
			?>
		</div>

		<?php
		/**
		 * Fixed copy, the same on every product — not read from any
		 * per-product field (no such field exists yet). Added at the
		 * client's explicit request (2026-09-22) to match the reference
		 * design as-is; revisit if/when this needs to vary per shoe.
		 */
		?>
		<p class="rx-fit-advice">
			<svg class="rx-fit-advice__icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="M7 12.5l3 3 7-7" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span><strong><?php esc_html_e( 'Fit advice:', 'rx-theme' ); ?></strong> <?php esc_html_e( 'True to size • Snug architectural midfoot lock for heavy barbell lifts.', 'rx-theme' ); ?></span>
		</p>

		<div class="reset_variations_alert screen-reader-text" role="alert" aria-live="polite" aria-relevant="all"></div>
		<?php
		// Reset snapshot for cases where a theme/plugin loads the variation form later, like quick-view modals.
		?>
		<template class="wc-product-gallery-default-template"><?php echo wc_get_product_gallery_html( $product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></template>
		<?php do_action( 'woocommerce_after_variations_table' ); ?>

		<?php if ( $rx_theme_is_bundle && $rx_theme_two_pack && $rx_theme_three_pack ) : ?>
			<div class="rx-rotation-box">
				<span class="rx-rotation-box__ribbon"><?php esc_html_e( 'Australian exclusive', 'rx-theme' ); ?></span>

				<h2 class="rx-rotation-box__heading">
					<span class="rx-rotation-box__heading-dot" aria-hidden="true"></span>
					<?php esc_html_e( 'Build your rotation & save', 'rx-theme' ); ?>
				</h2>
				<p class="rx-rotation-box__description">
					<?php esc_html_e( 'Modern athletes rotate shoes to preserve joints and elevate performance.', 'rx-theme' ); ?>
				</p>

				<div class="rx-rotation-box__tiers" role="radiogroup" aria-label="<?php esc_attr_e( 'Rotation size', 'rx-theme' ); ?>">
					<label class="rx-rotation-tier">
						<input type="radio" name="rx_rotation_tier" value="2" checked>
						<span class="rx-rotation-tier__save"><?php echo esc_html( sprintf( /* translators: %s: discount percentage. */ __( 'Save %s%%', 'rx-theme' ), rx_theme_format_percent( $rx_theme_two_pack['discount_percent'] ) ) ); ?></span>
						<span class="rx-rotation-tier__title"><?php esc_html_e( '2 pairs rotation', 'rx-theme' ); ?></span>
						<span class="rx-rotation-tier__price"><?php echo esc_html( rx_theme_format_money( $rx_theme_two_pack['price_per_pair'], true, false ) ); ?> <small><?php esc_html_e( '/ pair', 'rx-theme' ); ?></small></span>
						<span class="rx-rotation-tier__note">
							<?php
							printf(
								/* translators: %s: total dollar amount saved across the rotation. */
								esc_html__( 'Total bundle discount approx %s', 'rx-theme' ),
								esc_html( rx_theme_format_money( $rx_theme_two_pack['total_savings'], true ) )
							);
							?>
						</span>
					</label>

					<label class="rx-rotation-tier rx-rotation-tier--best">
						<span class="rx-rotation-tier__badge"><?php esc_html_e( 'Best athlete value', 'rx-theme' ); ?></span>
						<input type="radio" name="rx_rotation_tier" value="3">
						<span class="rx-rotation-tier__save rx-rotation-tier__save--lime"><?php echo esc_html( sprintf( /* translators: %s: discount percentage. */ __( 'Save %s%%', 'rx-theme' ), rx_theme_format_percent( $rx_theme_three_pack['discount_percent'] ) ) ); ?></span>
						<span class="rx-rotation-tier__title"><?php esc_html_e( '3 pairs complete', 'rx-theme' ); ?></span>
						<span class="rx-rotation-tier__price"><?php echo esc_html( rx_theme_format_money( $rx_theme_three_pack['price_per_pair'], true, false ) ); ?> <small><?php esc_html_e( '/ pair', 'rx-theme' ); ?></small></span>
						<span class="rx-rotation-tier__note rx-rotation-tier__note--highlight">
							<?php
							printf(
								/* translators: %s: total dollar amount saved across the rotation. */
								esc_html__( 'Save up to %s on full setup', 'rx-theme' ),
								esc_html( rx_theme_format_money( $rx_theme_three_pack['total_savings'], true ) )
							);
							?>
						</span>
					</label>
				</div>

				<p class="rx-rotation-box__info">
					<svg class="rx-rotation-box__info-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 11v6M12 7v.5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					<?php
					printf(
						/* translators: %s: this product's own title. */
						esc_html__( 'Add this %s as Pair 1, then choose from complementary Lifters or Hybrid Trail Runners below to lock in automatic checkout savings. No discount code needed.', 'rx-theme' ),
						esc_html( $product->get_name() )
					);
					?>
				</p>

				<?php
				/**
				 * Real add-to-cart, not a separate "bundle basket" — it
				 * submits the same variations_form (product_id,
				 * variation_id, attributes) as the real WooCommerce
				 * button next to it in single_variation_wrap below, via
				 * `formaction` on this button specifically. The only
				 * difference is the `rx_add_to_rotation=1` flag appended
				 * to that URL, which rx_theme_bundle_builder_add_to_cart_redirect()
				 * (inc/bundle-builder.php) reads to send the shopper to
				 * /build-a-bundle/ instead of the cart after a successful
				 * add — see that function for the full redirect rule.
				 */
				add_action(
					'woocommerce_before_add_to_cart_button',
					static function () use ( $product, $rx_theme_three_pack ): void {
						printf(
							'<button type="submit" name="add-to-cart" value="%1$s" formaction="%2$s" class="rx-rotation-box__bundle-button">%3$s</button>',
							esc_attr( $product->get_id() ),
							esc_url( add_query_arg( 'rx_add_to_rotation', '1', $product->get_permalink() ) ),
							esc_html(
								sprintf(
									/* translators: %s: top bundle discount percentage. */
									__( '+ Add to rotation bundle — save up to %s%%', 'rx-theme' ),
									rx_theme_format_percent( $rx_theme_three_pack['discount_percent'] )
								)
							)
						); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr()/esc_url()/esc_html()-escaped values only.
					}
				);
				?>

				<div class="single_variation_wrap">
					<?php
						do_action( 'woocommerce_before_single_variation' );
						do_action( 'woocommerce_single_variation' );
						do_action( 'woocommerce_after_single_variation' );
					?>
				</div>
			</div>
		<?php else : ?>
			<div class="single_variation_wrap">
				<?php
					/**
					 * Hook: woocommerce_before_single_variation.
					 */
					do_action( 'woocommerce_before_single_variation' );

					/**
					 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
					 *
					 * @since 2.4.0
					 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
					 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
					 */
					do_action( 'woocommerce_single_variation' );

					/**
					 * Hook: woocommerce_after_single_variation.
					 */
					do_action( 'woocommerce_after_single_variation' );
				?>
			</div>
		<?php endif; ?>
	<?php endif; ?>

	<?php do_action( 'woocommerce_after_variations_form' ); ?>
</form>

<?php
do_action( 'woocommerce_after_add_to_cart_form' );
