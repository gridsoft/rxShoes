<?php
/**
 * The template for displaying product content in the single-product.php
 * template.
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/content-single-product.php,
 * based on core version 3.6.0, restructured to the client's single-
 * product reference (2026-09-22, first pass: gallery + summary only —
 * the "Build Your Rotation" bundle box, "Complete Your Rotation"
 * cross-sell and the specs/shipping accordion are explicitly deferred
 * to a later pass, per the client's own scoping).
 *
 * Like content-product.php (the shop-loop card), the default
 * woocommerce_single_product_summary sub-hooks (title/rating/price/
 * excerpt/meta/sharing) are deliberately NOT fired — their callbacks
 * would duplicate the custom markup below. woocommerce_before_single_product_summary
 * (sale flash + gallery) and the add-to-cart hooks ARE kept, since the
 * gallery and the variation form (colour swatches / size pills — see
 * woocommerce/single-product/add-to-cart/variable.php) are genuinely
 * reused, not rebuilt.
 *
 * Deliberately NOT shown here, because no real data/feature backs them
 * (see inc/single-product.php and PROJECT.md's existing "don't fabricate"
 * calls on the shop card — reviews, Sydney-specific stock, Afterpay):
 * a "N athletes viewing now" counter, "IN STOCK • SYDNEY" (Sydney
 * fulfilment copy was already built and removed once before, per
 * PROJECT.md, for being unconfirmed), Afterpay/Zip instalments (no such
 * plugin is active), "Fit Guide"/"Compare" links, a "Fit advice: true to
 * size" claim, and the per-product feature-icon tiles ("Olympic Stable"
 * etc. in the reference) — there's no field for those yet.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- template override: fires WooCommerce's own hooks by their core names.

global $product;

/**
 * Hook: woocommerce_before_single_product.
 *
 * @hooked woocommerce_output_all_notices - 10
 */
do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-generated form markup, as in WooCommerce's own template.
	return;
}

$rx_theme_gender       = rx_theme_product_gender_label( $product );
$rx_theme_is_bundle    = rx_theme_product_is_bundle_eligible( $product );
$rx_theme_percent      = rx_theme_bundle_max_discount_percent();
$rx_theme_brand        = wp_get_post_terms( $product->get_id(), 'product_brand', array( 'fields' => 'names' ) );
$rx_theme_rating       = rx_theme_product_placeholder_rating( $product );
$rx_theme_rating_label = sprintf(
	/* translators: 1: rating out of 5, 2: number of reviews. */
	__( 'Rated %1$s out of 5 from %2$d reviews', 'rx-theme' ),
	$rx_theme_rating['rating'],
	$rx_theme_rating['count']
);
$rx_theme_best_for   = rx_theme_product_best_for( $product );
$rx_theme_size_equiv = rx_theme_product_size_equivalent_line( $product );
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'rx-single-product', $product ); ?>>

	<div class="rx-single-product__layout">
		<div class="rx-single-product__gallery">
			<div class="rx-single-product__gallery-badges">
				<?php if ( $rx_theme_gender ) : ?>
					<span class="rx-badge rx-badge--dark"><?php echo esc_html( strtoupper( $rx_theme_gender ) ); ?> SPEC</span>
				<?php endif; ?>
				<?php if ( $rx_theme_is_bundle ) : ?>
					<span class="rx-badge rx-badge--lime">
						<?php
						printf(
							/* translators: %s: top bundle discount percentage, e.g. "45". */
							esc_html__( 'Rotation eligible (-%s%%)', 'rx-theme' ),
							esc_html( rx_theme_format_percent( $rx_theme_percent ) )
						);
						?>
					</span>
				<?php endif; ?>
			</div>

			<?php
			/**
			 * Hook: woocommerce_before_single_product_summary.
			 *
			 * @hooked woocommerce_show_product_sale_flash - 10
			 * @hooked woocommerce_show_product_images - 20
			 */
			do_action( 'woocommerce_before_single_product_summary' );
			?>
		</div>

		<div class="rx-single-product__summary summary entry-summary">
			<?php if ( $rx_theme_brand ) : ?>
				<p class="rx-single-product__brand"><?php echo esc_html( $rx_theme_brand[0] ); ?></p>
			<?php endif; ?>

			<h1 class="rx-single-product__title">
				<?php list( $rx_theme_title_lead, $rx_theme_title_tail ) = rx_theme_product_title_parts( $product ); ?>
				<span class="rx-single-product__title-lead"><?php echo esc_html( $rx_theme_title_lead ); ?></span>
				<?php if ( $rx_theme_title_tail ) : ?>
					<span class="rx-single-product__title-tail">— <?php echo esc_html( $rx_theme_title_tail ); ?></span>
				<?php endif; ?>
			</h1>

			<?php if ( $rx_theme_gender || $rx_theme_size_equiv ) : ?>
				<p class="rx-single-product__model-line">
					<?php if ( $rx_theme_gender ) : ?>
						<span class="rx-badge rx-badge--dark"><?php echo esc_html( strtoupper( $rx_theme_gender ) ); ?> MODEL</span>
					<?php endif; ?>
					<?php if ( $rx_theme_size_equiv ) : ?>
						<span class="rx-badge"><?php echo esc_html( $rx_theme_size_equiv ); ?></span>
					<?php endif; ?>
				</p>
			<?php endif; ?>

			<div class="rx-single-product__meta-row">
				<p class="rx-single-product__rating" aria-label="<?php echo esc_attr( $rx_theme_rating_label ); ?>">
					<svg class="rx-single-product__star" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
					<strong><?php echo esc_html( $rx_theme_rating['rating'] ); ?></strong>
					<span class="rx-single-product__rating-count">(<?php echo (int) $rx_theme_rating['count']; ?> <?php esc_html_e( 'reviews', 'rx-theme' ); ?>)</span>
				</p>

				<p class="rx-single-product__stock <?php echo esc_attr( $product->is_in_stock() ? 'is-in-stock' : 'is-out-of-stock' ); ?>">
					<?php echo esc_html( $product->is_in_stock() ? __( 'In stock', 'rx-theme' ) : __( 'Out of stock', 'rx-theme' ) ); ?>
				</p>
			</div>

			<p class="rx-single-product__price">
				<span class="rx-single-product__price-amount"><?php echo esc_html( rx_theme_format_money( rx_theme_product_price( $product ), true, false ) ); ?></span>
				<span class="rx-single-product__price-currency"><?php echo esc_html( get_woocommerce_currency() ); ?></span>
			</p>

			<?php if ( $rx_theme_best_for ) : ?>
				<p class="rx-single-product__best-for">
					<span class="rx-single-product__best-for-label"><?php esc_html_e( 'Best for', 'rx-theme' ); ?></span>
					<span><?php echo esc_html( $rx_theme_best_for ); ?></span>
				</p>
			<?php endif; ?>

			<?php
			/**
			 * Just the add-to-cart form (colour swatches, size pills,
			 * quantity and the button — see the variable.php override).
			 * Called directly rather than through the usual
			 * woocommerce_single_product_summary hook cascade, since the
			 * rest of that cascade (title/rating/price/excerpt/meta) is
			 * built custom above and firing it would duplicate it.
			 */
			if ( function_exists( 'woocommerce_template_single_add_to_cart' ) ) {
				woocommerce_template_single_add_to_cart();
			}
			?>
		</div>

		<?php
		/*
		 * The product's own description (client request, 2026-09-24):
		 * under the gallery on desktop (grid, see style.css), after the
		 * summary on phones so it never pushes size/add-to-cart down.
		 * Formatted the way WooCommerce's description tab does
		 * (wc_format_content), minus the stray leading &nbsp; some
		 * imported descriptions start with.
		 */
		$rx_theme_description = preg_replace( '/^(\s|&nbsp;|\xc2\xa0)+/u', '', (string) $product->get_description() );
		?>
		<?php if ( '' !== trim( $rx_theme_description ) ) : ?>
			<section class="rx-single-product__description" aria-labelledby="rx-product-details-title">
				<h2 id="rx-product-details-title" class="rx-single-product__description-title"><?php esc_html_e( 'Product details', 'rx-theme' ); ?></h2>
				<div class="rx-single-product__description-body">
					<?php echo wc_format_content( wp_kses_post( $rx_theme_description ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- kses'd above; wc_format_content() only adds paragraphs/shortcodes, as in WooCommerce's description tab. ?>
				</div>
			</section>
		<?php endif; ?>
	</div>

	<?php if ( $rx_theme_is_bundle ) : ?>
		<?php
		$rx_theme_current_product = $product;
		$rx_theme_related         = rx_theme_rotation_related_products( $product, 2 );
		get_template_part( 'template-parts/shop/rotation-cross-sell', null, compact( 'rx_theme_current_product', 'rx_theme_related' ) );
		?>
	<?php endif; ?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
