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
 * — a real WC_Cart fee, not just a number shown here. The sidebar shows
 * exactly that: the real cart subtotal, the real tier discount and the
 * resulting total, never a projected pair that isn't in the cart.
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
$rx_theme_max_pack = rx_theme_bundle_max_discount_percent();
/**
 * Every slot up to the real max is always on screen: filled pairs, then
 * the next one to fill ("Choose your shoe"), then any later ones shown
 * locked — so the shopper sees the whole 3-pair finish line at once.
 * The max is 3, or more if someone genuinely has 4+ eligible items in
 * cart, so "1 of 2 pairs" never wrongly implies 2 is the finish line.
 */
$rx_theme_target_slots = max( 3, count( $rx_theme_eligible ) );
$rx_theme_anchor       = $rx_theme_eligible ? end( $rx_theme_eligible )['parent'] : null;
$rx_theme_in_cart      = array_map( static fn( WC_Product $p ): int => $p->get_id(), wp_list_pluck( $rx_theme_eligible, 'parent' ) );

/**
 * Sidebar summary — only what's really in the cart, no projected pairs
 * (client correction, 2026-09-29: a previewed "2. <shoe> (Projected)"
 * line read as a pair they'd added). Subtotal is the whole cart; the
 * bundle discount is the same tier saving
 * rx_theme_bundle_builder_apply_tier_discount() adds as a real fee.
 */
$rx_theme_cart_count = WC()->cart ? (int) WC()->cart->get_cart_contents_count() : 0;
$rx_theme_subtotal   = WC()->cart ? (float) WC()->cart->get_subtotal() : 0.0;
$rx_theme_discount   = $rx_theme_totals['savings'];
$rx_theme_pairs      = $rx_theme_totals['pairs_count'];

// Next tier to aim for: 2 pairs, then 3; none once 3+ are in.
$rx_theme_next_tier_pairs = $rx_theme_pairs < 2 ? 2 : ( $rx_theme_pairs < 3 ? 3 : 0 );
$rx_theme_nudge_percent   = $rx_theme_next_tier_pairs ? rx_theme_bundle_builder_tier( $rx_theme_next_tier_pairs )['percent'] : $rx_theme_max_pack;
$rx_theme_nudge_progress  = $rx_theme_next_tier_pairs ? min( 100, round( 100 * $rx_theme_pairs / $rx_theme_next_tier_pairs ) ) : 100;

/**
 * "Complete your rotation" picks under the slots: up to two real
 * bundle-eligible products related to the latest pair, not already in
 * the rotation, strongest match first.
 * Empty (section hidden) once the rotation is full or the cart is empty.
 */
$rx_theme_recs = array();
if ( $rx_theme_anchor && count( $rx_theme_eligible ) < $rx_theme_target_slots ) {
	foreach ( rx_theme_rotation_related_products( $rx_theme_anchor, 6 ) as $rx_theme_candidate ) {
		if ( ! in_array( $rx_theme_candidate->get_id(), $rx_theme_in_cart, true ) ) {
			$rx_theme_recs[] = $rx_theme_candidate;
		}
		if ( count( $rx_theme_recs ) >= 2 ) {
			break;
		}
	}
}

?>
<main id="primary" class="rx-bundle-builder">
	<div class="rx-bundle-builder__layout">
		<div class="rx-bundle-builder__pairs">
			<header class="rx-bundle-builder__intro">
				<h1 class="rx-bundle-builder__heading"><?php esc_html_e( 'Build your rotation.', 'rx-theme' ); ?></h1>
				<p class="rx-bundle-builder__description"><?php esc_html_e( 'Choose your shoes. Save more when you bundle.', 'rx-theme' ); ?></p>
			</header>

			<div class="rx-bundle-steps">
				<ol class="rx-bundle-steps__list">
					<?php for ( $rx_theme_i = 1; $rx_theme_i <= $rx_theme_target_slots; $rx_theme_i++ ) : ?>
						<li class="rx-bundle-steps__step<?php echo $rx_theme_i <= $rx_theme_totals['pairs_count'] ? ' rx-bundle-steps__step--done' : ''; ?>">
							<?php echo esc_html( sprintf( /* translators: %d: step number. */ __( 'Step %d', 'rx-theme' ), $rx_theme_i ) ); ?>
						</li>
					<?php endfor; ?>
				</ol>
				<p class="rx-bundle-steps__note">
					<?php
					printf(
						/* translators: 1: pairs added, 2: total pairs for the max tier. */
						esc_html__( '%1$d of %2$d Pairs Complete', 'rx-theme' ),
						(int) min( $rx_theme_totals['pairs_count'], $rx_theme_target_slots ),
						(int) $rx_theme_target_slots
					);
					?>
				</p>
			</div>

			<div class="rx-bundle-builder__slots">
				<?php for ( $rx_theme_i = 0; $rx_theme_i < $rx_theme_target_slots; $rx_theme_i++ ) : ?>
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
								'pair_number' => $rx_theme_i + 1,
								'locked'      => count( $rx_theme_eligible ) !== $rx_theme_i,
							)
						);
						?>
					<?php endif; ?>
				<?php endfor; ?>
			</div>

			<?php if ( $rx_theme_recs ) : ?>
				<section class="rx-bundle-recs" aria-labelledby="rx-bundle-recs-heading">
					<header class="rx-bundle-recs__header">
						<h2 class="rx-bundle-recs__heading" id="rx-bundle-recs-heading"><?php esc_html_e( 'Complete your rotation', 'rx-theme' ); ?></h2>
						<p class="rx-bundle-recs__note"><?php echo esc_html( sprintf( /* translators: %s: product name of the latest pair. */ __( 'Picked to pair with your %s.', 'rx-theme' ), $rx_theme_anchor->get_name() ) ); ?></p>
					</header>
					<div class="rx-bundle-recs__grid">
						<?php foreach ( $rx_theme_recs as $rx_theme_rec ) : ?>
							<?php
							get_template_part(
								'template-parts/bundle-builder/recommendation-card',
								null,
								array(
									'product'     => $rx_theme_rec,
									'pair_number' => count( $rx_theme_eligible ) + 1,
								)
							);
							?>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>
		</div>

		<aside class="rx-bundle-builder__summary">
			<h2 class="rx-bundle-builder__summary-heading"><?php esc_html_e( 'Summary', 'rx-theme' ); ?></h2>

			<?php
			/*
			 * Bundle pairs and everything else listed separately, as before
			 * the redesign: only the rotation earns the tier discount, other
			 * items are charged at full price (both are in the subtotal).
			 */
			$rx_theme_summary_groups = array(
				array(
					'title'   => __( 'Your rotation', 'rx-theme' ),
					'note'    => '',
					'entries' => $rx_theme_eligible,
					'bundle'  => true,
				),
				array(
					'title'   => __( 'Other items', 'rx-theme' ),
					'note'    => __( 'Not part of the rotation discount — charged at full price.', 'rx-theme' ),
					'entries' => $rx_theme_other,
					'bundle'  => false,
				),
			);
			?>
			<?php foreach ( $rx_theme_summary_groups as $rx_theme_group ) : ?>
				<?php
				if ( ! $rx_theme_group['entries'] && ! $rx_theme_group['bundle'] ) {
					continue;
				}
				?>
				<div class="rx-bundle-summary__group<?php echo $rx_theme_group['bundle'] ? '' : ' rx-bundle-summary__group--other'; ?>">
					<p class="rx-bundle-summary__group-title"><?php echo esc_html( $rx_theme_group['title'] ); ?></p>
					<?php if ( $rx_theme_group['note'] ) : ?>
						<p class="rx-bundle-summary__group-note"><?php echo esc_html( $rx_theme_group['note'] ); ?></p>
					<?php endif; ?>
					<?php if ( $rx_theme_group['entries'] ) : ?>
					<ul class="rx-bundle-summary__list">
						<?php foreach ( $rx_theme_group['entries'] as $rx_theme_index => $rx_theme_entry ) : ?>
							<?php
							$rx_theme_line_total = rx_theme_product_price( $rx_theme_entry['product'] ) * (int) $rx_theme_entry['cart_item']['quantity'];
							$rx_theme_colour     = rx_theme_bundle_builder_configured_colour( $rx_theme_entry['cart_item'] );
							$rx_theme_size       = rx_theme_bundle_builder_configured_size( $rx_theme_entry['cart_item'] );
							$rx_theme_name       = $rx_theme_entry['parent']->get_name();
							?>
							<li class="rx-bundle-summary__item">
								<span class="rx-bundle-summary__item-name">
									<?php echo esc_html( $rx_theme_group['bundle'] ? sprintf( /* translators: 1: pair number, 2: product name. */ __( '%1$d. %2$s', 'rx-theme' ), $rx_theme_index + 1, $rx_theme_name ) : $rx_theme_name ); ?>
									<?php if ( $rx_theme_colour || $rx_theme_size ) : ?>
										<small><?php echo esc_html( implode( ' · ', array_filter( array( $rx_theme_colour ? $rx_theme_colour->name : '', $rx_theme_size ) ) ) ); ?></small>
									<?php endif; ?>
								</span>
								<span class="rx-bundle-summary__item-price"><?php echo wp_kses_post( wc_price( $rx_theme_line_total ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
					<?php endif; ?>
					<?php if ( $rx_theme_group['bundle'] ) : ?>
					<div class="rx-bundle-summary__nudge">
						<p class="rx-bundle-summary__nudge-text">
							<?php
							$rx_theme_percent_html = '<strong>' . esc_html( sprintf( /* translators: %s: discount percentage. */ __( '%s%% OFF', 'rx-theme' ), rx_theme_format_percent( $rx_theme_nudge_percent ) ) ) . '</strong>';

							if ( ! $rx_theme_next_tier_pairs ) {
								/* translators: %s: discount, e.g. "45% OFF" (bold). */
								printf( esc_html__( 'Maximum %s unlocked.', 'rx-theme' ), $rx_theme_percent_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
							} elseif ( 0 === $rx_theme_pairs ) {
								/* translators: %s: discount, e.g. "35% OFF" (bold). */
								printf( esc_html__( 'Add two pairs to unlock %s.', 'rx-theme' ), $rx_theme_percent_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
							} else {
								/* translators: %s: discount, e.g. "35% OFF" (bold). */
								printf( esc_html__( 'Add one more pair and unlock %s.', 'rx-theme' ), $rx_theme_percent_html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
							}
							?>
						</p>
						<span class="rx-bundle-summary__nudge-bar"><span style="width:<?php echo esc_attr( $rx_theme_nudge_progress ); ?>%"></span></span>
						<p class="rx-bundle-summary__nudge-tier">
							<?php
							printf(
								/* translators: 1: "Next tier" or "Top tier", 2: pairs in that tier, 3: its discount percentage. */
								esc_html__( '%1$s: %2$d pairs (%3$s%% off)', 'rx-theme' ),
								esc_html( $rx_theme_next_tier_pairs ? __( 'Next tier', 'rx-theme' ) : __( 'Top tier', 'rx-theme' ) ),
								(int) ( $rx_theme_next_tier_pairs ? $rx_theme_next_tier_pairs : 3 ),
								esc_html( rx_theme_format_percent( $rx_theme_nudge_percent ) )
							);
							?>
						</p>
					</div>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>

			<p class="rx-bundle-summary__row">
				<span>
					<?php
					printf(
						/* translators: %d: number of items in the cart. */
						esc_html( _n( 'Subtotal (%d item)', 'Subtotal (%d items)', $rx_theme_cart_count, 'rx-theme' ) ),
						(int) $rx_theme_cart_count
					);
					?>
				</span>
				<span><?php echo wp_kses_post( wc_price( $rx_theme_subtotal ) ); ?></span>
			</p>
			<p class="rx-bundle-summary__row<?php echo $rx_theme_discount > 0 ? ' rx-bundle-summary__row--discount' : ' rx-bundle-summary__row--muted'; ?>">
				<span><?php esc_html_e( 'Bundle Discount', 'rx-theme' ); ?></span>
				<span>-<?php echo wp_kses_post( wc_price( $rx_theme_discount ) ); ?></span>
			</p>
			<p class="rx-bundle-summary__total">
				<span><?php esc_html_e( 'Total', 'rx-theme' ); ?></span>
				<span><?php echo wp_kses_post( wc_price( max( 0, $rx_theme_subtotal - $rx_theme_discount ) ) ); ?></span>
			</p>

			<?php if ( $rx_theme_cart_count ) : ?>
				<a class="rx-bundle-summary__checkout<?php echo $rx_theme_discount > 0 ? ' rx-bundle-summary__checkout--active' : ''; ?>" href="<?php echo esc_url( wc_get_checkout_url() ); ?>"><?php esc_html_e( 'Proceed to checkout', 'rx-theme' ); ?></a>
			<?php else : ?>
				<a class="rx-bundle-summary__checkout" href="<?php echo esc_url( add_query_arg( 'rx_bundle', '1', wc_get_page_permalink( 'shop' ) ) ); ?>"><?php esc_html_e( 'Shop bundle shoes', 'rx-theme' ); ?></a>
			<?php endif; ?>
			<p class="rx-bundle-summary__fineprint"><?php esc_html_e( 'Taxes and shipping calculated at checkout.', 'rx-theme' ); ?></p>
		</aside>
	</div>
</main>
<?php
get_footer();
