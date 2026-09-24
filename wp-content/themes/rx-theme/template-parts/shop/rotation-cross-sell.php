<?php
/**
 * "Complete Your Rotation" cross-sell, below the main product layout on
 * bundle-eligible single product pages. Real WooCommerce related
 * products (shared categories/tags — WooCommerce's own algorithm, not a
 * curated "pairs well with" recommendation, since no such data exists
 * here) with real prices, discounted at the same Customizer-configured
 * tiers the rotation box above uses. See inc/single-product.php for the
 * maths (rx_theme_rotation_related_products(), rx_theme_rotation_pair_data()).
 *
 * "+ Add as Pair N" links straight to that product's own page — there's
 * no bundle-basket engine yet (see the rotation box above), so this is
 * real, working navigation to somewhere a shopper can actually buy it,
 * rather than a button that looks like it adds a bundle but doesn't.
 *
 * Expects $args['rx_theme_current_product'] (WC_Product) and
 * $args['rx_theme_related'] (WC_Product[], already limited to what's
 * available) from the including template — get_template_part()'s $args
 * arrives as that one array, not extracted into individually-named
 * variables (that's a common assumption, but wrong: load_template()
 * only extracts $wp_query->query_vars, never the $args it's given).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_current_product = $args['rx_theme_current_product'] ?? null;
$rx_theme_related         = $args['rx_theme_related'] ?? array();

if ( empty( $rx_theme_related ) || ! $rx_theme_current_product instanceof WC_Product ) {
	return;
}

$rx_theme_tier_discounts = array(
	2 => rx_theme_bundle_two_pack_discount_percent(),
	3 => rx_theme_bundle_max_discount_percent(),
);
?>
<section class="rx-cross-sell">
	<div class="rx-cross-sell__header">
		<div>
			<p class="rx-cross-sell__eyebrow">
				<svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/></svg>
				<?php esc_html_e( 'Intelligent synergy system', 'rx-theme' ); ?>
			</p>
			<h2 class="rx-cross-sell__heading"><?php esc_html_e( 'Complete your rotation', 'rx-theme' ); ?></h2>
			<p class="rx-cross-sell__description">
				<?php
				printf(
					/* translators: %s: the current product's title. */
					esc_html__( 'Pair your %s with complementary shoes below to build a complete rotation.', 'rx-theme' ),
					esc_html( $rx_theme_current_product->get_name() )
				);
				?>
			</p>
		</div>

		<?php
		/**
		 * Illustrative only — "Pair 1" is always the product being
		 * viewed; there's no real cart/slot tracking yet (same "don't
		 * build fake interactivity against fake numbers" rule the
		 * homepage rotation calculator already follows).
		 */
		?>
		<div class="rx-cross-sell__slots">
			<p class="rx-cross-sell__slots-label"><?php esc_html_e( 'Your bundle calculation', 'rx-theme' ); ?></p>
			<p class="rx-cross-sell__slots-count">
				<?php
				printf(
					/* translators: %d: total rotation slots (1 + number of related products shown). */
					esc_html__( '1 of %d slots selected', 'rx-theme' ),
					1 + count( $rx_theme_related )
				);
				?>
			</p>
			<span class="rx-cross-sell__slots-bar"><span style="width:<?php echo esc_attr( round( 100 / ( 1 + count( $rx_theme_related ) ) ) ); ?>%"></span></span>
		</div>
	</div>

	<div class="rx-cross-sell__grid">
		<?php foreach ( $rx_theme_related as $rx_theme_index => $rx_theme_related_product ) : ?>
			<?php
			$rx_theme_pair_number = $rx_theme_index + 2; // Pair 1 is the current product.
			$rx_theme_discount    = $rx_theme_tier_discounts[ $rx_theme_pair_number ] ?? rx_theme_bundle_max_discount_percent();
			$rx_theme_pair        = rx_theme_rotation_pair_data( $rx_theme_current_product, $rx_theme_related, $rx_theme_pair_number, $rx_theme_discount );
			$rx_theme_type_label  = rx_theme_product_type_label( $rx_theme_related_product );
			$rx_theme_permalink   = get_permalink( $rx_theme_related_product->get_id() );
			// The top (3-pack) tier gets the "best value" colour scheme —
			// same distinction the rotation box above already makes
			// between its 2-pack and 3-pack tiles.
			$rx_theme_is_best = 3 === $rx_theme_pair_number;
			?>
			<div class="rx-cross-sell-card<?php echo $rx_theme_is_best ? ' rx-cross-sell-card--best' : ''; ?>">
				<div class="rx-cross-sell-card__top">
					<span class="rx-badge<?php echo $rx_theme_is_best ? ' rx-badge--blue' : ' rx-badge--dark'; ?>"><?php echo esc_html( sprintf( /* translators: %d: pair number. */ __( 'Pair %d recommendation', 'rx-theme' ), $rx_theme_pair_number ) ); ?></span>
					<span class="rx-badge<?php echo $rx_theme_is_best ? ' rx-badge--outline-red' : ' rx-badge--lime'; ?>">
						<?php if ( 2 === $rx_theme_pair_number ) : ?>
							<?php
							printf(
								/* translators: %s: discount percentage. */
								esc_html__( 'Unlock %s%% on both', 'rx-theme' ),
								esc_html( rx_theme_format_percent( $rx_theme_discount ) )
							);
							?>
						<?php else : ?>
							<?php
							printf(
								/* translators: 1: discount percentage, 2: pair number. */
								esc_html__( 'Unlock %1$s%% on all %2$d', 'rx-theme' ),
								esc_html( rx_theme_format_percent( $rx_theme_discount ) ),
								(int) $rx_theme_pair_number
							);
							?>
						<?php endif; ?>
					</span>
				</div>

				<div class="rx-cross-sell-card__body">
					<a class="rx-cross-sell-card__image" href="<?php echo esc_url( $rx_theme_permalink ); ?>">
						<?php echo wp_kses_post( $rx_theme_related_product->get_image( 'woocommerce_thumbnail' ) ); ?>
					</a>
					<div class="rx-cross-sell-card__info">
						<?php if ( $rx_theme_type_label ) : ?>
							<p class="rx-cross-sell-card__type"><?php echo esc_html( $rx_theme_type_label ); ?></p>
						<?php endif; ?>
						<h3 class="rx-cross-sell-card__title">
							<a href="<?php echo esc_url( $rx_theme_permalink ); ?>"><?php echo esc_html( $rx_theme_related_product->get_name() ); ?></a>
						</h3>
						<?php if ( $rx_theme_related_product->get_short_description() ) : ?>
							<p class="rx-cross-sell-card__excerpt"><?php echo wp_kses_post( wp_trim_words( $rx_theme_related_product->get_short_description(), 18 ) ); ?></p>
						<?php endif; ?>
						<p class="rx-cross-sell-card__price">
							<span class="rx-cross-sell-card__price-now"><?php echo esc_html( rx_theme_format_money( $rx_theme_pair['item_price'], true ) ); ?></span>
							<span class="rx-cross-sell-card__price-was"><?php echo esc_html( rx_theme_format_money( $rx_theme_pair['item_regular_price'], true, false ) ); ?></span>
							<span class="rx-cross-sell-card__price-save">
								<?php
								printf(
									/* translators: %s: dollar amount saved. */
									esc_html__( 'Save %s', 'rx-theme' ),
									esc_html( rx_theme_format_money( $rx_theme_pair['item_savings'], true, false ) )
								);
								?>
							</span>
						</p>
					</div>
				</div>

				<div class="rx-cross-sell-card__footer">
					<p class="rx-cross-sell-card__combined">
						<?php
						printf(
							/* translators: 1: label ("Combined"/"Full rotation"), 2: discounted total, 3: full-price total. */
							esc_html__( '%1$s: %2$s (was %3$s)', 'rx-theme' ),
							2 === $rx_theme_pair_number ? esc_html__( 'Combined', 'rx-theme' ) : esc_html__( 'Full rotation', 'rx-theme' ),
							esc_html( rx_theme_format_money( $rx_theme_pair['combined_total'], true ) ),
							esc_html( rx_theme_format_money( $rx_theme_pair['combined_regular_total'], true ) )
						);
						?>
					</p>
					<a class="rx-cross-sell-card__button<?php echo $rx_theme_is_best ? ' rx-cross-sell-card__button--best' : ''; ?>" href="<?php echo esc_url( $rx_theme_permalink ); ?>">
						<?php if ( $rx_theme_is_best ) : ?>
							<svg class="rx-cross-sell-card__button-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/></svg>
						<?php else : ?>
							<svg class="rx-cross-sell-card__button-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="2"/><path d="M12 8v8M8 12h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
						<?php endif; ?>
						<span>
							<?php
							printf(
								/* translators: 1: pair number, 2: discount percentage. */
								esc_html__( 'Add as pair %1$d (save %2$s%%)', 'rx-theme' ),
								(int) $rx_theme_pair_number,
								esc_html( rx_theme_format_percent( $rx_theme_discount ) )
							);
							?>
						</span>
					</a>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>
