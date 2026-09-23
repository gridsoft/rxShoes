<?php
/**
 * One open rotation slot on /build-a-bundle/. Matches the client's Figma
 * reference card design (2026-09-22 screenshot) layout-for-layout: a
 * full-width status bar, an "empty slot" card, and (when one exists) a
 * "recommended pairing" card next to it.
 *
 * The reference's copy is real data here, not reproduced as-is:
 * - The top bar's "Unlock N% off bundle" and its real dollar savings are
 *   computed from this store's real Customizer-configured tiers
 *   (rx_theme_bundle_builder_tier() / rx_theme_bundle_builder_recommendation_pricing()),
 *   keyed by THIS SLOT's own pair number — Pair 2 always reads as the
 *   two-pack tier and Pair 3 always reads as the max tier, regardless of
 *   how many are actually filled yet, matching the reference's own
 *   Pair-N-specific framing (see rx_theme_bundle_builder_recommendation_pricing()'s
 *   own comment for why "current count + 1" was wrong here). Never the
 *   reference's own "$270+ Avg Across Entire Order" — an unconfirmed,
 *   unsourced stat this site doesn't otherwise make.
 * - "Speed / Road / Hybrid... recommended to complement Lifters &
 *   Functional trainers" becomes rx_theme_bundle_builder_complement_text():
 *   the real _rx_type_label of whatever's actually already in the
 *   rotation.
 * - "Matches Pair 1 + 2 profile" is real (rx_theme_bundle_builder_matches_label()),
 *   from how many slots are actually filled.
 *
 * The recommendation's CTA does NOT navigate to that product's own page
 * (client correction, 2026-09-23): it's a pure-CSS checkbox toggle
 * (.rx-bundle-pair__add-toggle, same mechanism as the filled pair card's
 * "Edit size / colour") that swaps this slot's empty-grid for the real
 * inline variation-picker form (template-parts/bundle-builder/pair-edit-panel.php,
 * "adding" mode — $args['product'] instead of $args['entry']), so a
 * shopper chooses size/colour and confirms without ever leaving this
 * page; that form's own real add-to-cart redirects straight back here.
 *
 * Expects $args['pair_number'], $args['recommendation'] (?WC_Product) and
 * $args['eligible_entries'] (the cart's current eligible bucket, for the
 * real copy above).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_pair       = (int) ( $args['pair_number'] ?? 0 );
$rx_theme_recommend  = $args['recommendation'] ?? null;
$rx_theme_eligible   = $args['eligible_entries'] ?? array();
$rx_theme_slot_tier  = rx_theme_bundle_builder_tier( $rx_theme_pair );
$rx_theme_filled     = count( $rx_theme_eligible );
$rx_theme_add_toggle_id = 'rx-pair-add-toggle-' . $rx_theme_pair;
/**
 * "Browse shoe vault" has to land on bundle-eligible products only —
 * browsing to a shoe that can't actually earn a rotation slot would be a
 * dead end. Reuses the shop's own real "Bundle eligible" filter
 * (?rx_bundle=1, see rx_theme_shop_bundle_only() in inc/shop-filters.php),
 * the same toggle already on the shop filter bar, rather than a second
 * filtering mechanism.
 */
$rx_theme_vault_url = add_query_arg( 'rx_bundle', '1', wc_get_page_permalink( 'shop' ) );
?>
<div class="rx-bundle-pair rx-bundle-pair--empty" id="rx-pair-<?php echo esc_attr( $rx_theme_pair ); ?>">
	<?php if ( $rx_theme_recommend instanceof WC_Product ) : ?>
		<input type="checkbox" id="<?php echo esc_attr( $rx_theme_add_toggle_id ); ?>" class="rx-bundle-pair__add-toggle">
	<?php endif; ?>

	<div class="rx-bundle-pair__status-bar<?php echo $rx_theme_slot_tier['percent'] > 0 ? ' rx-bundle-pair__status-bar--urgent' : ''; ?>">
		<span class="rx-bundle-pair__status-title">
			<svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true" focusable="false"><path fill="currentColor" d="m12 2 2.9 6.3 6.9.7-5.2 4.7 1.5 6.8L12 17l-6.1 3.5 1.5-6.8-5.2-4.7 6.9-.7Z"/></svg>
			<?php
			if ( $rx_theme_slot_tier['percent'] > 0 ) {
				printf(
					/* translators: 1: pair number, 2: discount percentage this slot would unlock. */
					esc_html__( 'Pair %1$d • Unlock %2$s%% off bundle', 'rx-theme' ),
					$rx_theme_pair,
					esc_html( rx_theme_format_percent( $rx_theme_slot_tier['percent'] ) )
				);
			} else {
				printf(
					/* translators: %d: pair number. */
					esc_html__( 'Pair %d • Open slot', 'rx-theme' ),
					$rx_theme_pair
				);
			}
			?>
		</span>
		<?php if ( $rx_theme_recommend instanceof WC_Product && $rx_theme_slot_tier['percent'] > 0 ) : ?>
			<?php $rx_theme_pricing = rx_theme_bundle_builder_recommendation_pricing( $rx_theme_eligible, $rx_theme_recommend, $rx_theme_pair ); ?>
			<span class="rx-bundle-pair__status-note">
				<?php echo esc_html( sprintf( /* translators: %s: real dollar savings at that tier. */ __( 'Save %s if you fill this slot', 'rx-theme' ), rx_theme_format_money( $rx_theme_pricing['combined_savings'], true ) ) ); ?>
			</span>
		<?php endif; ?>
	</div>

	<div class="rx-bundle-pair__empty-grid">
		<div class="rx-bundle-pair__vault">
			<a class="rx-bundle-pair__vault-icon" href="<?php echo esc_url( $rx_theme_vault_url ); ?>" aria-label="<?php esc_attr_e( 'Browse shoe vault', 'rx-theme' ); ?>">
				<svg viewBox="0 0 24 24" width="20" height="20" focusable="false" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
			</a>
			<p class="rx-bundle-pair__vault-heading"><?php esc_html_e( 'Empty rotation slot', 'rx-theme' ); ?></p>
			<p class="rx-bundle-pair__vault-text"><?php echo esc_html( rx_theme_bundle_builder_complement_text( $rx_theme_eligible ) ); ?></p>
			<a class="rx-btn rx-btn--dark" href="<?php echo esc_url( $rx_theme_vault_url ); ?>"><?php esc_html_e( 'Browse shoe vault', 'rx-theme' ); ?></a>
		</div>

		<?php if ( $rx_theme_recommend instanceof WC_Product ) : ?>
			<?php
			$rx_theme_permalink = get_permalink( $rx_theme_recommend->get_id() );
			$rx_theme_pricing   = $rx_theme_pricing ?? rx_theme_bundle_builder_recommendation_pricing( $rx_theme_eligible, $rx_theme_recommend, $rx_theme_pair );
			$rx_theme_matches   = rx_theme_bundle_builder_matches_label( $rx_theme_filled );
			$rx_theme_excerpt   = $rx_theme_recommend->get_short_description();
			?>
			<div class="rx-bundle-pair__recommendation">
				<div class="rx-bundle-pair__recommendation-head">
					<p class="rx-bundle-pair__recommendation-label">
						<span class="rx-bundle-pair__recommendation-dot" aria-hidden="true"></span>
						<?php esc_html_e( 'Recommended rotation pairing', 'rx-theme' ); ?>
					</p>
					<?php if ( $rx_theme_matches ) : ?>
						<p class="rx-bundle-pair__recommendation-matches"><?php echo esc_html( $rx_theme_matches ); ?></p>
					<?php endif; ?>
				</div>

				<div class="rx-bundle-pair__recommendation-row">
					<a class="rx-bundle-pair__image" href="<?php echo esc_url( $rx_theme_permalink ); ?>">
						<?php echo wp_kses_post( $rx_theme_recommend->get_image( 'woocommerce_thumbnail' ) ); ?>
					</a>
					<div class="rx-bundle-pair__recommendation-text">
						<div class="rx-bundle-pair__recommendation-title-row">
							<h3 class="rx-bundle-pair__title">
								<a href="<?php echo esc_url( $rx_theme_permalink ); ?>"><?php echo esc_html( $rx_theme_recommend->get_name() ); ?></a>
							</h3>
							<span class="rx-bundle-pair__recommendation-price"><?php echo esc_html( rx_theme_format_money( $rx_theme_pricing['item_regular_price'], true ) ); ?></span>
						</div>
						<?php if ( $rx_theme_excerpt ) : ?>
							<p class="rx-bundle-pair__recommendation-excerpt"><?php echo wp_kses_post( wp_trim_words( $rx_theme_excerpt, 20 ) ); ?></p>
						<?php endif; ?>
						<?php if ( $rx_theme_pricing['tier_percent'] > 0 ) : ?>
							<p class="rx-bundle-pair__recommendation-deal">
								<?php
								printf(
									/* translators: 1: discounted item price, 2: total pairs this bundle would have. */
									esc_html__( 'Only %1$s when added to this %2$d-bundle!', 'rx-theme' ),
									esc_html( rx_theme_format_money( $rx_theme_pricing['item_price'], true ) ),
									$rx_theme_filled + 1
								);
								?>
							</p>
						<?php endif; ?>
					</div>
				</div>

				<label class="rx-btn rx-btn--lime rx-bundle-pair__recommendation-cta" for="<?php echo esc_attr( $rx_theme_add_toggle_id ); ?>">
					<svg viewBox="0 0 24 24" width="15" height="15" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/></svg>
					<?php
					if ( $rx_theme_pricing['tier_percent'] > 0 ) {
						printf(
							/* translators: 1: product name, 2: discount percentage, 3: real dollar savings. */
							esc_html__( '+ Add %1$s & unlock %2$s%% (%3$s saved)', 'rx-theme' ),
							esc_html( $rx_theme_recommend->get_name() ),
							esc_html( rx_theme_format_percent( $rx_theme_pricing['tier_percent'] ) ),
							esc_html( rx_theme_format_money( $rx_theme_pricing['combined_savings'], true ) )
						);
					} else {
						printf(
							/* translators: %s: product name. */
							esc_html__( '+ Add %s', 'rx-theme' ),
							esc_html( $rx_theme_recommend->get_name() )
						);
					}
					?>
				</label>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $rx_theme_recommend instanceof WC_Product ) : ?>
		<?php
		get_template_part(
			'template-parts/bundle-builder/pair-edit-panel',
			null,
			array(
				'product'     => $rx_theme_recommend,
				'pair_number' => $rx_theme_pair,
				'toggle_id'   => $rx_theme_add_toggle_id,
			)
		);
		?>
	<?php endif; ?>
</div>
