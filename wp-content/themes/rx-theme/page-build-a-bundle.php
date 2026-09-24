<?php
/**
 * "Build Your Rotation" page — WordPress picks this up automatically for
 * the page at /build-a-bundle/ (slug `build-a-bundle`) via the
 * page-{slug}.php template hierarchy; see inc/bundle-builder.php for
 * where that page comes from and the redirect that lands shoppers here.
 *
 * Structure follows the Figma reference (file 0Q5iFcBVniwb0HnBJRXbKc,
 * node 1-3744, cached at design-reference/bundle-builder.png) for layout
 * only. Its copy is NOT reproduced as-is: several lines there are
 * unconfirmed/fabricated claims this site doesn't make elsewhere either
 * (a "39% injury reduction" / "60% longer shoe life" stat with no source,
 * a cited "Sydney Biomechanics Study" that doesn't exist, a live-chat
 * "certified running coach" feature that isn't built, an "Includes
 * Australian GST" line — this store has tax disabled — see
 * PROJECT.md's running "real data only" rule). The tier percentages,
 * progress state, and every price on this page are real, from the
 * shopper's actual WooCommerce cart and the Customizer's configured
 * discount tiers (rx_theme_bundle_two_pack_discount_percent() /
 * rx_theme_bundle_max_discount_percent()) — the same numbers used
 * everywhere else on the site.
 *
 * A completed 2- or 3-pair rotation is genuinely discounted at checkout
 * (rx_theme_bundle_builder_apply_tier_discount(), inc/bundle-builder.php)
 * — a real WC_Cart fee, not just a number shown here. The sidebar's
 * "Rotation total" for the shopper's actual committed pairs is that real,
 * applied total. The one number that's still a preview is the "if
 * completed" figure for a slot that isn't filled yet ($rx_theme_show_projected
 * below): nothing to discount exists in the cart until that product is
 * actually added, so that figure stays a forecast, not a claim about
 * what checkout charges right now.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A dedicated body class for this page's own background — the
 * page-{slug}.php template hierarchy doesn't add one on its own (that
 * only happens for a page using an explicitly named "Template Name:"
 * template), and relying on page-id-2130 instead would break if this
 * page is ever recreated.
 */
add_filter(
	'body_class',
	static function ( array $classes ): array {
		$classes[] = 'rx-bundle-builder-page';
		return $classes;
	}
);

get_header();

$rx_theme_buckets  = rx_theme_bundle_builder_cart_buckets();
$rx_theme_eligible = $rx_theme_buckets['eligible'];
$rx_theme_other    = $rx_theme_buckets['other'];
$rx_theme_totals   = rx_theme_bundle_builder_totals( $rx_theme_eligible );
$rx_theme_two_pack = rx_theme_bundle_two_pack_discount_percent();
$rx_theme_max_pack = rx_theme_bundle_max_discount_percent();
/**
 * Only ever ONE empty slot rendered at a time — the next one to fill —
 * not every remaining slot at once. With 1 pair in the rotation, that's
 * Pair 2 only; Pair 3 doesn't exist yet, not even as a placeholder.
 * Filling Pair 2 makes Pair 3 appear, with its own real tier/recommendation
 * (see rx_theme_bundle_builder_tier() / the recommendation lookup below,
 * both already keyed by slot position, not by this count).
 *
 * Separate from $rx_theme_target_slots (the progress bar's denominator,
 * always the real max — 3, or more if someone genuinely has 4+ eligible
 * items in cart): that number doesn't change just because fewer cards
 * are on screen, or "1 of 2 shoes locked" would wrongly imply 2 pairs is
 * the finish line instead of 3.
 */
$rx_theme_target_slots = max( 3, count( $rx_theme_eligible ) );
$rx_theme_slots        = min( $rx_theme_target_slots, count( $rx_theme_eligible ) + 1 );
$rx_theme_anchor       = $rx_theme_eligible ? end( $rx_theme_eligible )['parent'] : null;
$rx_theme_in_cart      = wp_list_pluck( wp_list_pluck( $rx_theme_eligible, 'parent' ), 'id' );
$rx_theme_recommend    = rx_theme_bundle_builder_recommendation( $rx_theme_anchor, $rx_theme_in_cart );

switch ( $rx_theme_totals['pairs_count'] ) {
	case 0:
		$rx_theme_progress_note = __( 'Add your first bundle-eligible shoe to start your rotation.', 'rx-theme' );
		break;
	case 1:
		$rx_theme_progress_note = sprintf(
			/* translators: %s: 2-pack discount percentage. */
			__( 'Add one more pair to unlock %s%% off both.', 'rx-theme' ),
			rx_theme_format_percent( $rx_theme_two_pack )
		);
		break;
	case 2:
		$rx_theme_progress_note = sprintf(
			/* translators: 1: 2-pack discount percentage, 2: 3-pack discount percentage. */
			__( 'Add one more pair to boost your discount from %1$s%% to %2$s%%.', 'rx-theme' ),
			rx_theme_format_percent( $rx_theme_two_pack ),
			rx_theme_format_percent( $rx_theme_max_pack )
		);
		break;
	default:
		$rx_theme_progress_note = sprintf(
			/* translators: %s: 3-pack discount percentage. */
			__( 'Maximum %s%% rotation discount unlocked.', 'rx-theme' ),
			rx_theme_format_percent( $rx_theme_max_pack )
		);
		break;
}

/**
 * "Next unlock: Save $X" on the progress bar's right side — real dollar
 * savings for whichever slot is actually next (2 or 3), off the same
 * recommendation and per-slot tier maths the open slot's own status bar
 * already uses (rx_theme_bundle_builder_recommendation_pricing()). Blank
 * when there's no next slot (already at 3) or no recommendation to base
 * a number on, rather than showing a made-up figure.
 */
$rx_theme_next_unlock_save = null;
if ( $rx_theme_recommend instanceof WC_Product && count( $rx_theme_eligible ) < $rx_theme_target_slots ) {
	$rx_theme_next_pair_number = count( $rx_theme_eligible ) + 1;
	$rx_theme_next_pricing     = rx_theme_bundle_builder_recommendation_pricing( $rx_theme_eligible, $rx_theme_recommend, $rx_theme_next_pair_number );

	if ( $rx_theme_next_pricing['tier_percent'] > 0 ) {
		$rx_theme_next_unlock_save = $rx_theme_next_pricing['combined_savings'];
	}
}

/**
 * The sidebar's order-breakdown box: when there's a real next-slot
 * recommendation to preview (same one shown in the open slot itself),
 * the box previews the bundle AS IF it were added — real product, real
 * price, real tier maths, just not committed yet ("Projected" in the
 * line-item list below). Otherwise (rotation complete, or nothing to
 * recommend) it falls back to only what's actually in the cart.
 */
$rx_theme_show_projected = null !== $rx_theme_next_unlock_save;

if ( $rx_theme_show_projected ) {
	$rx_theme_box_regular = $rx_theme_next_pricing['combined_regular_total'];
	$rx_theme_box_preview = $rx_theme_next_pricing['combined_preview_total'];
	$rx_theme_box_savings = $rx_theme_next_pricing['combined_savings'];
	$rx_theme_box_pairs   = $rx_theme_next_pair_number;
	$rx_theme_box_percent = $rx_theme_next_pricing['tier_percent'];
} else {
	$rx_theme_box_regular = $rx_theme_totals['regular_total'];
	$rx_theme_box_preview = $rx_theme_totals['preview_total'];
	$rx_theme_box_savings = $rx_theme_totals['savings'];
	$rx_theme_box_pairs   = $rx_theme_totals['pairs_count'];
	$rx_theme_box_percent = $rx_theme_totals['tier']['percent'];
}
?>
<main id="primary" class="rx-bundle-builder">
	<div class="rx-bundle-builder__hero">
		<header class="rx-bundle-builder__intro">
			<p class="rx-eyebrow"><?php esc_html_e( 'Rotation builder', 'rx-theme' ); ?></p>
			<h1 class="rx-bundle-builder__heading"><?php esc_html_e( 'Build your rotation', 'rx-theme' ); ?><span class="rx-bundle-builder__heading-dot" aria-hidden="true"></span></h1>
			<p class="rx-bundle-builder__description"><?php esc_html_e( 'Choose your shoes, pick your colours, and select your sizes — rotating pairs unlock automatic tier discounts as you go.', 'rx-theme' ); ?></p>
		</header>

		<div class="rx-bundle-builder__tiers" role="group" aria-label="<?php esc_attr_e( 'Rotation discount tiers', 'rx-theme' ); ?>">
			<div class="rx-bundle-tier<?php echo 1 === $rx_theme_totals['pairs_count'] ? ' rx-bundle-tier--active' : ''; ?>">
				<span class="rx-bundle-tier__title"><?php esc_html_e( '1 pair', 'rx-theme' ); ?></span>
				<span class="rx-bundle-tier__note"><?php esc_html_e( 'Standard', 'rx-theme' ); ?></span>
			</div>
			<div class="rx-bundle-tier<?php echo 2 === $rx_theme_totals['pairs_count'] ? ' rx-bundle-tier--active' : ''; ?>">
				<span class="rx-bundle-tier__title"><?php esc_html_e( '2 pairs', 'rx-theme' ); ?></span>
				<span class="rx-bundle-tier__note"><?php echo esc_html( sprintf( /* translators: %s: discount percentage. */ __( 'Save %s%%', 'rx-theme' ), rx_theme_format_percent( $rx_theme_two_pack ) ) ); ?></span>
			</div>
			<div class="rx-bundle-tier rx-bundle-tier--best<?php echo $rx_theme_totals['pairs_count'] >= 3 ? ' rx-bundle-tier--active' : ''; ?>">
				<span class="rx-bundle-tier__badge"><?php esc_html_e( 'Best value', 'rx-theme' ); ?></span>
				<span class="rx-bundle-tier__title"><?php esc_html_e( '3 pairs', 'rx-theme' ); ?></span>
				<span class="rx-bundle-tier__note"><?php echo esc_html( sprintf( /* translators: %s: discount percentage. */ __( 'Save %s%%', 'rx-theme' ), rx_theme_format_percent( $rx_theme_max_pack ) ) ); ?></span>
			</div>
		</div>
	</div>

	<div class="rx-bundle-builder__progress">
		<div class="rx-bundle-builder__progress-top">
			<p class="rx-bundle-builder__progress-label">
				<?php
				printf(
					/* translators: 1: pairs added, 2: total pairs for the max tier. */
					esc_html__( '%1$d of %2$d shoes locked', 'rx-theme' ),
					(int) min( $rx_theme_totals['pairs_count'], $rx_theme_target_slots ),
					(int) $rx_theme_target_slots
				);
				?>
				<span class="rx-bundle-builder__progress-note"><?php echo esc_html( $rx_theme_progress_note ); ?></span>
			</p>
			<?php if ( null !== $rx_theme_next_unlock_save ) : ?>
				<p class="rx-bundle-builder__progress-next">
					<?php
					printf(
						/* translators: %s: real dollar savings the next slot would unlock. */
						esc_html__( 'Next unlock: Save %s', 'rx-theme' ),
						esc_html( rx_theme_format_money( $rx_theme_next_unlock_save, true ) )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
		<span class="rx-bundle-builder__progress-bar"><span style="width:<?php echo esc_attr( min( 100, round( 100 * $rx_theme_totals['pairs_count'] / $rx_theme_target_slots ) ) ); ?>%"></span></span>
	</div>

	<div class="rx-bundle-builder__layout">
		<div class="rx-bundle-builder__pairs">
			<?php for ( $rx_theme_i = 0; $rx_theme_i < $rx_theme_slots; $rx_theme_i++ ) : ?>
				<?php if ( isset( $rx_theme_eligible[ $rx_theme_i ] ) ) : ?>
					<?php
					get_template_part(
						'template-parts/bundle-builder/pair-card',
						null,
						array(
							'entry'       => $rx_theme_eligible[ $rx_theme_i ],
							'pair_number' => $rx_theme_i + 1,
						)
					);
					?>
				<?php else : ?>
					<?php
					get_template_part(
						'template-parts/bundle-builder/empty-slot',
						null,
						array(
							'pair_number'      => $rx_theme_i + 1,
							'recommendation'   => count( $rx_theme_eligible ) === $rx_theme_i ? $rx_theme_recommend : null,
							'eligible_entries' => $rx_theme_eligible,
						)
					);
					?>
				<?php endif; ?>
			<?php endfor; ?>

			<div class="rx-bundle-builder__info">
				<h2><?php esc_html_e( 'Why rotate?', 'rx-theme' ); ?></h2>
				<p><?php esc_html_e( 'Modern athletes rotate shoes to preserve joints and elevate performance — each pair gets time to decompress between sessions instead of breaking down under every run.', 'rx-theme' ); ?></p>
			</div>
		</div>

		<aside class="rx-bundle-builder__summary">
			<p class="rx-bundle-builder__summary-eyebrow"><?php esc_html_e( 'Order breakdown', 'rx-theme' ); ?></p>
			<h2 class="rx-bundle-builder__summary-heading"><?php esc_html_e( 'Your rotation', 'rx-theme' ); ?></h2>

			<?php if ( $rx_theme_eligible || $rx_theme_show_projected ) : ?>
				<ul class="rx-bundle-summary-list">
					<?php foreach ( $rx_theme_eligible as $rx_theme_index => $rx_theme_entry ) : ?>
						<?php
						$rx_theme_line_total = rx_theme_product_price( $rx_theme_entry['product'] ) * (int) $rx_theme_entry['cart_item']['quantity'];
						$rx_theme_colour     = rx_theme_bundle_builder_configured_colour( $rx_theme_entry['cart_item'] );
						$rx_theme_size       = rx_theme_bundle_builder_configured_size( $rx_theme_entry['cart_item'] );
						?>
						<li class="rx-bundle-summary-list__item">
							<div class="rx-bundle-summary-list__row">
								<span class="rx-bundle-summary-list__name">
									<?php echo esc_html( sprintf( /* translators: 1: pair number, 2: product name. */ __( '%1$d. %2$s', 'rx-theme' ), $rx_theme_index + 1, $rx_theme_entry['parent']->get_name() ) ); ?>
								</span>
								<span class="rx-bundle-summary-list__price"><?php echo esc_html( rx_theme_format_money( $rx_theme_line_total, true ) ); ?></span>
							</div>
							<?php if ( $rx_theme_colour || $rx_theme_size ) : ?>
								<p class="rx-bundle-summary-list__variant">
									<?php echo esc_html( implode( ' • ', array_filter( array( $rx_theme_colour ? $rx_theme_colour->name : '', $rx_theme_size ) ) ) ); ?>
								</p>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>

					<?php if ( $rx_theme_show_projected ) : ?>
						<?php $rx_theme_type = rx_theme_product_type_label( $rx_theme_recommend ); ?>
						<li class="rx-bundle-summary-list__item rx-bundle-summary-list__item--projected">
							<div class="rx-bundle-summary-list__row">
								<span class="rx-bundle-summary-list__name">
									<span class="rx-bundle-summary-list__projected-dot" aria-hidden="true"></span>
									<?php
									echo esc_html(
										sprintf(
											/* translators: 1: pair number, 2: product name. */
											__( '%1$d. %2$s (Projected)', 'rx-theme' ),
											$rx_theme_box_pairs,
											$rx_theme_recommend->get_name()
										)
									);
									?>
								</span>
								<span class="rx-bundle-summary-list__price"><?php echo esc_html( rx_theme_format_money( $rx_theme_next_pricing['item_regular_price'], true ) ); ?></span>
							</div>
							<p class="rx-bundle-summary-list__variant">
								<?php
								echo esc_html(
									implode(
										' • ',
										array_filter(
											array(
												$rx_theme_type,
												sprintf(
													/* translators: %d: pair number. */
													__( 'Selected to unlock Tier %d', 'rx-theme' ),
													$rx_theme_box_pairs
												),
											)
										)
									)
								);
								?>
							</p>
						</li>
					<?php endif; ?>
				</ul>
			<?php else : ?>
				<p class="rx-bundle-summary-empty"><?php esc_html_e( 'No rotation-eligible shoes in your cart yet.', 'rx-theme' ); ?></p>
			<?php endif; ?>

			<?php if ( $rx_theme_box_percent > 0 ) : ?>
				<div class="rx-bundle-summary-box">
					<p class="rx-bundle-summary-box__row">
						<span><?php esc_html_e( 'Standard combined retail', 'rx-theme' ); ?></span>
						<span><?php echo esc_html( rx_theme_format_money( $rx_theme_box_regular, true ) ); ?></span>
					</p>
					<p class="rx-bundle-summary-box__row">
						<span>
							<?php esc_html_e( 'Bundle tier incentive', 'rx-theme' ); ?>
							<span class="rx-bundle-summary-box__tier">
								<?php
								echo esc_html(
									sprintf(
										/* translators: 1: pair number, 2: discount percentage. */
										__( 'Tier %1$d (-%2$s%%)', 'rx-theme' ),
										$rx_theme_box_pairs,
										rx_theme_format_percent( $rx_theme_box_percent )
									)
								);
								?>
							</span>
						</span>
						<span class="rx-bundle-summary-box__savings">&minus;<?php echo esc_html( rx_theme_format_money( $rx_theme_box_savings, true ) ); ?></span>
					</p>
					<div class="rx-bundle-summary-box__total">
						<span class="rx-bundle-summary-box__total-label">
							<?php echo esc_html( $rx_theme_show_projected ? __( 'Rotation total if completed', 'rx-theme' ) : __( 'Rotation total (applied at checkout)', 'rx-theme' ) ); ?>
						</span>
						<strong class="rx-bundle-summary-box__total-amount"><?php echo esc_html( rx_theme_format_money( $rx_theme_box_preview, true ) ); ?></strong>
						<span class="rx-bundle-summary-box__total-savings">
							<?php echo esc_html( sprintf( /* translators: %s: real dollar savings. */ __( 'Total savings: %s', 'rx-theme' ), rx_theme_format_money( $rx_theme_box_savings, true ) ) ); ?>
						</span>
					</div>
				</div>
			<?php else : ?>
				<p class="rx-bundle-summary-totals__row">
					<span><?php esc_html_e( 'Cart subtotal today', 'rx-theme' ); ?></span>
					<strong><?php echo wp_kses_post( WC()->cart ? WC()->cart->get_cart_subtotal() : rx_theme_format_money( 0.0 ) ); ?></strong>
				</p>
			<?php endif; ?>

			<?php if ( $rx_theme_other ) : ?>
				<h3 class="rx-bundle-builder__summary-subheading"><?php esc_html_e( 'Other items in your cart', 'rx-theme' ); ?></h3>
				<p class="rx-bundle-summary-note"><?php esc_html_e( 'Not part of the rotation discount — charged at full price as usual.', 'rx-theme' ); ?></p>
				<ul class="rx-bundle-summary-list rx-bundle-summary-list--muted">
					<?php foreach ( $rx_theme_other as $rx_theme_entry ) : ?>
						<?php $rx_theme_line_total = rx_theme_product_price( $rx_theme_entry['product'] ) * (int) $rx_theme_entry['cart_item']['quantity']; ?>
						<li class="rx-bundle-summary-list__item">
							<div class="rx-bundle-summary-list__row">
								<span class="rx-bundle-summary-list__name"><?php echo esc_html( $rx_theme_entry['parent']->get_name() ); ?></span>
								<span class="rx-bundle-summary-list__price"><?php echo esc_html( rx_theme_format_money( $rx_theme_line_total, true ) ); ?></span>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php
			/**
			 * Only shown when there's something outside the rotation
			 * itself to combine it with — with no "other items", this
			 * would just repeat the order-breakdown box's own real
			 * (now genuinely discount-applied) total above it.
			 */
			?>
			<?php if ( $rx_theme_other && WC()->cart ) : ?>
				<div class="rx-bundle-summary-box rx-bundle-summary-box--real-total">
					<p class="rx-bundle-summary-box__total-label"><?php esc_html_e( 'Real cart total (everything above)', 'rx-theme' ); ?></p>
					<strong class="rx-bundle-summary-box__total-amount"><?php echo wp_kses_post( WC()->cart->get_total() ); ?></strong>
				</div>
			<?php endif; ?>

			<?php
			/**
			 * The primary action button, moved below "Other items" and
			 * the real cart total (client request, 2026-09-23): a final
			 * action reads more trustworthy once the shopper has seen
			 * everything they're actually paying for, not before it —
			 * the same order a real checkout review uses (summary, then
			 * total, then pay).
			 */
			?>
			<?php if ( $rx_theme_totals['tier']['percent'] > 0 && ! $rx_theme_show_projected ) : ?>
				<?php
				/**
				 * Real tier active and nothing left to project — every
				 * slot is genuinely filled (3 real pairs; capped at 3, so
				 * there's never a 4th to recommend). The rotation is
				 * actually done, so "continue" now means checkout, not a
				 * nudge toward one more pair.
				 */
				?>
				<a class="rx-btn rx-btn--bundle rx-bundle-builder__cta" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Continue to checkout', 'rx-theme' ); ?></a>
			<?php elseif ( $rx_theme_totals['tier']['percent'] > 0 ) : ?>
				<?php
				/**
				 * Same box-preview numbers as the order-breakdown box
				 * above ($rx_theme_box_*): with 2 real pairs committed AND
				 * a real recommendation for the 3rd, this reads "Continue
				 * with 3-pair bundle" / the real 45% total savings — the
				 * tier the shopper is actually one click away from, not
				 * just what's already confirmed. Links to the same
				 * #rx-pair-N in-page scroll the "unlock tier 2" button
				 * uses, since $rx_theme_show_projected is true in this
				 * branch (the sibling branch above handles the "nothing
				 * left to project" case).
				 */
				?>
				<a class="rx-btn rx-btn--bundle rx-bundle-builder__cta" href="#rx-pair-<?php echo esc_attr( $rx_theme_box_pairs ); ?>">
					<span>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: the pair count once the projected next slot is filled. */
								__( 'Continue with %d-pair bundle', 'rx-theme' ),
								$rx_theme_box_pairs
							)
						);
						?>
					</span>
					<span class="rx-bundle-builder__cta-badge">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: discount percentage, 2: real dollar savings. */
								__( 'Save %1$s%% (%2$s)', 'rx-theme' ),
								rx_theme_format_percent( $rx_theme_box_percent ),
								rx_theme_format_money( $rx_theme_box_savings, true )
							)
						);
						?>
					</span>
				</a>
				<?php
				/**
				 * A real 2-pair rotation already earns a genuine 35%
				 * discount (see rx_theme_bundle_builder_apply_tier_discount(),
				 * inc/bundle-builder.php) — a shopper shouldn't be forced
				 * toward a 3rd pair just to check out. Real checkout link,
				 * styled as the secondary action (white/blue-outline)
				 * next to the primary "complete the rotation" nudge above.
				 */
				?>
				<a class="rx-btn rx-btn--outline-blue rx-bundle-builder__cta" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Continue to checkout', 'rx-theme' ); ?></a>
			<?php elseif ( null !== $rx_theme_next_unlock_save ) : ?>
				<?php
				/**
				 * Exactly one real pair so far, no tier earned yet — real
				 * "continue to cart" would send the shopper away from a
				 * rotation that's one pair short of any discount at all.
				 * A real, working in-page link to the next open slot
				 * (#rx-pair-N, added to that slot's own root element)
				 * instead, with the real tier/savings it would unlock.
				 */
				?>
				<a class="rx-btn rx-btn--bundle rx-bundle-builder__cta" href="#rx-pair-<?php echo esc_attr( $rx_theme_next_pair_number ); ?>">
					<span>
						<?php
						echo esc_html(
							sprintf(
								/* translators: %d: the next pair number. */
								__( 'Continue to unlock tier %d', 'rx-theme' ),
								$rx_theme_next_pair_number
							)
						);
						?>
					</span>
					<span class="rx-bundle-builder__cta-badge">
						<?php
						echo esc_html(
							sprintf(
								/* translators: 1: discount percentage, 2: real dollar savings. */
								__( 'Save %1$s%% (%2$s)', 'rx-theme' ),
								rx_theme_format_percent( $rx_theme_next_pricing['tier_percent'] ),
								rx_theme_format_money( $rx_theme_next_unlock_save, true )
							)
						);
						?>
					</span>
				</a>
			<?php else : ?>
				<a class="rx-btn rx-btn--bundle rx-bundle-builder__cta" href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Continue to cart', 'rx-theme' ); ?></a>
			<?php endif; ?>

			<ul class="rx-bundle-summary-trust">
				<li><?php esc_html_e( 'Hassle-free sizing exchanges', 'rx-theme' ); ?></li>
				<li><?php esc_html_e( 'Manufacturer warranty', 'rx-theme' ); ?></li>
			</ul>
		</aside>
	</div>
</main>
<?php
get_footer();
