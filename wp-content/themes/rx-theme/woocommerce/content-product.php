<?php
/**
 * The template for displaying product content within loops
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/content-product.php,
 * restructured into the RX product card (Figma reference, measured
 * 2026-09-21). Based on core version 9.4.0 — when WooCommerce bumps
 * this template's @version, diff it against the core copy and port any
 * relevant change (WooCommerce > Status flags outdated overrides).
 *
 * The default loop hooks (woocommerce_before_shop_loop_item,
 * woocommerce_shop_loop_item_title, etc.) are deliberately not fired:
 * their callbacks (link wrapper, title, rating, price, add-to-cart)
 * would duplicate the markup below. Nothing in this project hooks them.
 *
 * One button per card, always WooCommerce's standard add-to-cart.
 * Bundle-eligible products (the "Eligible for bundle" checkbox, owned by
 * rx-core, read via rx_theme_product_is_bundle_eligible() in
 * inc/woocommerce.php) get the bundle badge, "as low as" pricing,
 * rotation row and the blue "Add to bundle" button. Everything else
 * gets the black "Add to basket". Both are the same add-to-cart action
 * under a different label — the bundle discount is calculated later
 * from the cart contents, not from which button was pressed.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Check if the product is a valid WooCommerce product and ensure its visibility before proceeding.
if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$rx_theme_permalink    = get_permalink( $product->get_id() );
$rx_theme_category     = rx_theme_product_primary_category( $product );
$rx_theme_colours      = rx_theme_product_colour_count( $product );
$rx_theme_type_label   = rx_theme_product_type_label( $product );
$rx_theme_rating       = rx_theme_product_placeholder_rating( $product );
$rx_theme_rating_label = sprintf(
	/* translators: 1: rating out of 5, 2: number of reviews. */
	__( 'Rated %1$s out of 5 from %2$d reviews', 'rx-theme' ),
	$rx_theme_rating['rating'],
	$rx_theme_rating['count']
);
$rx_theme_best_for  = rx_theme_product_best_for( $product );
$rx_theme_price     = rx_theme_product_price( $product );
$rx_theme_is_bundle = rx_theme_product_is_bundle_eligible( $product );
$rx_theme_percent   = rx_theme_bundle_max_discount_percent();

// "Add to basket" and "Add to bundle" are the SAME action — WooCommerce's
// standard add-to-cart (AJAX for simple products, a link to the product
// page for variable ones, which need a size chosen first) — under
// different labels. The bundle discount is worked out later from what's
// in the cart, not by which button was pressed, so there's deliberately
// nothing bundle-specific in the link itself.
$rx_theme_cart_href = $product->add_to_cart_url();
$rx_theme_cart_ajax = ( $product->supports( 'ajax_add_to_cart' ) && $product->is_purchasable() && $product->is_in_stock() )
	? ' add_to_cart_button ajax_add_to_cart'
	: '';
$rx_theme_cart_attr = sprintf(
	' data-product_id="%1$d" data-product_sku="%2$s" rel="nofollow"',
	$product->get_id(),
	esc_attr( $product->get_sku() )
);
?>
<li <?php wc_product_class( 'rx-product-card', $product ); ?>>
	<div class="rx-product-card__media">
		<a class="rx-product-card__image-link" href="<?php echo esc_url( $rx_theme_permalink ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo wp_kses_post( $product->get_image( 'woocommerce_single', array( 'class' => 'rx-product-card__image' ) ) ); ?>
		</a>

		<div class="rx-product-card__badges">
			<?php if ( $rx_theme_category ) : ?>
				<span class="rx-product-card__badge rx-product-card__badge--category"><?php echo esc_html( $rx_theme_category->name ); ?></span>
			<?php endif; ?>
			<?php if ( $rx_theme_is_bundle ) : ?>
				<span class="rx-product-card__badge rx-product-card__badge--bundle"><?php esc_html_e( 'Bundle eligible', 'rx-theme' ); ?></span>
			<?php endif; ?>
		</div>

		<?php if ( $rx_theme_colours > 0 ) : ?>
			<span class="rx-product-card__colours">
				<?php
				printf(
					/* translators: %d: number of colour options. */
					esc_html( _n( '%d Colour', '%d Colours', $rx_theme_colours, 'rx-theme' ) ),
					(int) $rx_theme_colours
				);
				?>
			</span>
		<?php endif; ?>
	</div>

	<div class="rx-product-card__body">
		<div class="rx-product-card__meta">
			<span class="rx-product-card__type"><?php echo esc_html( $rx_theme_type_label ); ?></span>
			<span class="rx-product-card__rating" aria-label="<?php echo esc_attr( $rx_theme_rating_label ); ?>">
				<svg class="rx-product-card__star" viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path fill="currentColor" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
				<?php echo esc_html( $rx_theme_rating['rating'] ); ?> (<?php echo (int) $rx_theme_rating['count']; ?>)
			</span>
		</div>

		<h3 class="rx-product-card__title">
			<a href="<?php echo esc_url( $rx_theme_permalink ); ?>"><?php echo esc_html( $product->get_name() ); ?></a>
		</h3>

		<?php if ( $rx_theme_best_for ) : ?>
			<p class="rx-product-card__best-for">
				<?php
				printf(
					/* translators: %s: the product's "Best for" field, e.g. "Functional Training • Strength". */
					esc_html__( 'Best for: %s', 'rx-theme' ),
					esc_html( $rx_theme_best_for )
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( $rx_theme_price > 0 ) : ?>
			<p class="rx-product-card__pricing">
				<span class="rx-product-card__price"><?php echo esc_html( rx_theme_format_money( $rx_theme_price, true ) ); ?></span>
				<?php if ( $rx_theme_is_bundle ) : ?>
					<span class="rx-product-card__as-low-as">
						<?php
						printf(
							/* translators: %s: discounted 3-pack price, e.g. "$109.45". */
							esc_html__( 'As low as %s in 3-pack', 'rx-theme' ),
							esc_html( rx_theme_format_money( rx_theme_product_bundle_price( $product ), false, false ) )
						);
						?>
					</span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<div class="rx-product-card__actions">
			<?php if ( $rx_theme_is_bundle ) : ?>
				<?php // Static until the bundle builder tracks the rotation: the pair slot this would fill depends on what's already in it. ?>
				<p class="rx-product-card__rotation">
					<span><?php esc_html_e( 'Adds into Rotation:', 'rx-theme' ); ?></span>
					<strong>
						<?php
						printf(
							/* translators: %s: top-tier discount percentage, e.g. "45". */
							esc_html__( 'Pair 3 (+%s%% tier)', 'rx-theme' ),
							esc_html( rx_theme_format_percent( $rx_theme_percent ) )
						);
						?>
					</strong>
				</p>
			<?php endif; ?>

			<?php if ( $rx_theme_is_bundle ) : ?>
				<a class="rx-product-card__button rx-product-card__button--bundle<?php echo esc_attr( $rx_theme_cart_ajax ); ?>" href="<?php echo esc_url( $rx_theme_cart_href ); ?>"<?php echo $rx_theme_cart_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr()-escaped values only. ?> aria-label="<?php echo esc_attr( sprintf( /* translators: %s: product name. */ __( 'Add “%s” to your bundle', 'rx-theme' ), $product->get_name() ) ); ?>">
					<svg class="rx-product-card__button-icon" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 8v8M8 12h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					<span><?php esc_html_e( 'Add to bundle', 'rx-theme' ); ?></span>
				</a>
			<?php else : ?>
				<a class="rx-product-card__button rx-product-card__button--cart<?php echo esc_attr( $rx_theme_cart_ajax ); ?>" href="<?php echo esc_url( $rx_theme_cart_href ); ?>"<?php echo $rx_theme_cart_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from esc_attr()-escaped values only. ?> aria-label="<?php echo esc_attr( $product->add_to_cart_description() ); ?>">
					<span><?php esc_html_e( 'Add to basket', 'rx-theme' ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</li>
