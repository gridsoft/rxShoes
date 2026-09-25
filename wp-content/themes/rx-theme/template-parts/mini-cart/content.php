<?php
/**
 * Rotation drawer content (inside #rx-mini-cart) — the progress box, the
 * pairs, the next open slot, other items, totals and the actions. Also
 * served as a cart fragment, so the root element must stay
 * div.rx-mini-cart__content. Data comes from rx_theme_mini_cart_data()
 * (inc/mini-cart.php).
 *
 * Copy deliberately differs from the reference mockup where the mockup
 * shows numbers or claims this store doesn't have: percentages and
 * savings are the real Customizer tiers (not the mockup's 30/45/55),
 * shipping reads "Calculated at checkout" (no shipping zones are set up
 * yet, so there's no real "free express" to promise), and the trust
 * line reuses the bundle builder's "Hassle-free sizing exchanges"
 * rather than an unconfirmed "30-day free sizing swap".
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_d        = $args;
$rx_theme_pairs    = $rx_theme_d['pairs_count'];
$rx_theme_percent  = $rx_theme_d['percent'];
$rx_theme_next     = $rx_theme_d['next_pair'];
$rx_theme_pricing  = $rx_theme_d['next_pricing'];
$rx_theme_two_pack = rx_theme_bundle_two_pack_discount_percent();
$rx_theme_max_pack = rx_theme_bundle_max_discount_percent();

/* Progress headline and note — same four states as the bundle builder. */
if ( 0 === $rx_theme_pairs ) {
	$rx_theme_headline = __( 'Start your rotation', 'rx-theme' );
} elseif ( $rx_theme_percent > 0 ) {
	$rx_theme_headline = sprintf(
		/* translators: 1: pairs in the rotation, 2: discount percentage unlocked. */
		_n( '%1$d pair complete — %2$s%% off unlocked!', '%1$d pairs complete — %2$s%% off unlocked!', $rx_theme_pairs, 'rx-theme' ),
		$rx_theme_pairs,
		rx_theme_format_percent( $rx_theme_percent )
	);
} else {
	$rx_theme_headline = __( '1 pair in your rotation', 'rx-theme' );
}

/*
 * The note's savings figure: the extra dollars the next slot would add
 * on top of what's already saved (the builder's "Next unlock" maths).
 */
$rx_theme_extra = $rx_theme_pricing ? max( 0.0, $rx_theme_pricing['combined_savings'] - $rx_theme_d['savings'] ) : 0.0;

$rx_theme_segments = min( 3, $rx_theme_pairs );
?>
<div class="rx-mini-cart__content">
	<?php if ( $rx_theme_d['bundles'] ) : ?>
	<div class="rx-mini-cart__progress">
		<div class="rx-mini-cart__progress-top">
			<p class="rx-mini-cart__progress-title"><?php echo esc_html( $rx_theme_headline ); ?></p>
			<p class="rx-mini-cart__progress-count">
				<?php
				printf(
					/* translators: 1: pairs in the rotation, 2: pairs for the max tier. */
					esc_html__( '%1$d / %2$d', 'rx-theme' ),
					(int) min( $rx_theme_pairs, $rx_theme_d['target_slots'] ),
					(int) $rx_theme_d['target_slots']
				);
				?>
			</p>
		</div>
		<div class="rx-mini-cart__segments" aria-hidden="true">
			<?php for ( $rx_theme_s = 1; $rx_theme_s <= 3; $rx_theme_s++ ) : ?>
				<span class="rx-mini-cart__segment<?php echo $rx_theme_s <= $rx_theme_segments ? ' is-filled' : ''; ?>"></span>
			<?php endfor; ?>
		</div>
		<p class="rx-mini-cart__progress-note">
			<?php if ( $rx_theme_pairs >= 3 ) : ?>
				<?php
				printf(
					/* translators: %s: max discount percentage, wrapped in a highlight. */
					esc_html__( 'Maximum %s rotation discount unlocked.', 'rx-theme' ),
					'<strong>' . esc_html( rx_theme_format_percent( $rx_theme_max_pack ) . '% OFF' ) . '</strong>'
				);
				?>
			<?php elseif ( 0 === $rx_theme_pairs ) : ?>
				<?php
				printf(
					/* translators: %s: 2-pack discount percentage, wrapped in a highlight. */
					esc_html__( 'Add 2 bundle-eligible pairs to unlock %s.', 'rx-theme' ),
					'<strong>' . esc_html( rx_theme_format_percent( $rx_theme_two_pack ) . '% OFF' ) . '</strong>'
				);
				?>
			<?php else : ?>
				<?php
				printf(
					/* translators: %s: next tier's discount percentage, wrapped in a highlight. */
					esc_html( 1 === $rx_theme_pairs ? __( 'Add 1 more pair to unlock %s', 'rx-theme' ) : __( 'Add 1 more pair to upgrade to %s', 'rx-theme' ) ),
					'<strong>' . esc_html( rx_theme_format_percent( $rx_theme_d['next_percent'] ) . '% OFF' ) . '</strong>'
				);
				if ( $rx_theme_extra > 0 ) {
					echo ' ';
					echo esc_html(
						sprintf(
							/* translators: %s: extra dollar savings. */
							1 === $rx_theme_pairs ? __( '(Save %s!)', 'rx-theme' ) : __( '(Save %s extra!)', 'rx-theme' ),
							rx_theme_format_money( $rx_theme_extra, true, false )
						)
					);
				}
				?>
			<?php endif; ?>
		</p>
	</div>
	<?php endif; ?>

	<div class="rx-mini-cart__body">
		<?php if ( $rx_theme_d['is_empty'] ) : ?>
			<div class="rx-mini-cart__empty">
				<p class="rx-mini-cart__empty-title"><?php esc_html_e( 'Your cart is empty', 'rx-theme' ); ?></p>
				<p class="rx-mini-cart__empty-text"><?php echo esc_html( $rx_theme_d['bundles'] ? __( 'Pick your first pair to start a rotation — the more pairs, the bigger the discount.', 'rx-theme' ) : __( 'Browse the shoe vault to find your next pair.', 'rx-theme' ) ); ?></p>
				<a class="rx-btn rx-btn--dark" href="<?php echo esc_url( $rx_theme_d['vault_url'] ); ?>"><?php esc_html_e( 'Browse shoe vault', 'rx-theme' ); ?></a>
			</div>
		<?php else : ?>
			<ul class="rx-mini-cart__items">
				<?php foreach ( $rx_theme_d['eligible'] as $rx_theme_index => $rx_theme_entry ) : ?>
					<?php
					get_template_part(
						'template-parts/mini-cart/item',
						null,
						array(
							'entry'   => $rx_theme_entry,
							'label'   => rx_theme_bundle_pair_label( $rx_theme_entry['parent'], $rx_theme_index + 1 ),
							'percent' => $rx_theme_percent,
						)
					);
					?>
				<?php endforeach; ?>
			</ul>

			<?php if ( $rx_theme_next ) : ?>
				<div class="rx-mini-cart__slot">
					<a class="rx-mini-cart__slot-icon" href="<?php echo esc_url( $rx_theme_d['vault_url'] ); ?>" aria-hidden="true" tabindex="-1">
						<svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
					</a>
					<p class="rx-mini-cart__slot-title">
						<?php
						printf(
							/* translators: %d: slot number. */
							esc_html__( 'Slot %d: Open rotation slot', 'rx-theme' ),
							(int) $rx_theme_next
						);
						?>
					</p>
					<p class="rx-mini-cart__slot-text">
						<?php
						if ( 1 === $rx_theme_next ) {
							esc_html_e( 'Choose any bundle-eligible shoe to start your rotation.', 'rx-theme' );
						} elseif ( 2 === $rx_theme_next ) {
							echo esc_html(
								sprintf(
									/* translators: %s: 2-pack discount percentage. */
									__( 'Choose any second shoe to unlock %s%% off both pairs.', 'rx-theme' ),
									rx_theme_format_percent( $rx_theme_two_pack )
								)
							);
						} else {
							echo esc_html(
								sprintf(
									/* translators: 1: ordinal ("third"), 2: current discount percentage, 3: next discount percentage. */
									__( 'Choose any %1$s shoe to jump from %2$s%% to %3$s%% off the entire rotation.', 'rx-theme' ),
									rx_theme_mini_cart_ordinal( $rx_theme_next ),
									rx_theme_format_percent( $rx_theme_percent ),
									rx_theme_format_percent( $rx_theme_d['next_percent'] )
								)
							);
						}
						?>
					</p>
					<a class="rx-mini-cart__slot-btn" href="<?php echo esc_url( $rx_theme_d['vault_url'] ); ?>">
						<?php
						echo esc_html(
							sprintf(
								/* translators: %s: ordinal ("third"). */
								__( '+ Select %s pair', 'rx-theme' ),
								rx_theme_mini_cart_ordinal( $rx_theme_next )
							)
						);
						?>
					</a>
				</div>
			<?php endif; ?>

			<?php if ( $rx_theme_d['other'] ) : ?>
				<?php if ( $rx_theme_d['bundles'] ) : ?>
					<p class="rx-mini-cart__subheading"><?php esc_html_e( 'Other items', 'rx-theme' ); ?></p>
					<p class="rx-mini-cart__subnote"><?php esc_html_e( 'Not part of the rotation discount — charged at full price.', 'rx-theme' ); ?></p>
				<?php endif; ?>
				<ul class="rx-mini-cart__items">
					<?php foreach ( $rx_theme_d['other'] as $rx_theme_entry ) : ?>
						<?php
						get_template_part(
							'template-parts/mini-cart/item',
							null,
							array(
								'entry'   => $rx_theme_entry,
								'label'   => '',
								'percent' => 0.0,
							)
						);
						?>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<div class="rx-mini-cart__totals">
				<p class="rx-mini-cart__totals-row">
					<span><?php echo esc_html( $rx_theme_d['bundles'] ? __( 'Standard retail value', 'rx-theme' ) : __( 'Subtotal', 'rx-theme' ) ); ?></span>
					<span<?php echo $rx_theme_d['savings'] > 0 ? ' class="rx-mini-cart__strike"' : ''; ?>><?php echo esc_html( rx_theme_format_money( $rx_theme_d['regular_total'] ) ); ?></span>
				</p>
				<?php if ( $rx_theme_d['savings'] > 0 ) : ?>
					<p class="rx-mini-cart__totals-row rx-mini-cart__totals-row--discount">
						<span>
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: tier number, 2: discount percentage. */
									__( 'Tier %1$d bundle discount (%2$s%%)', 'rx-theme' ),
									min( 3, $rx_theme_pairs ),
									rx_theme_format_percent( $rx_theme_percent )
								)
							);
							?>
						</span>
						<span>&minus;<?php echo esc_html( rx_theme_format_money( $rx_theme_d['savings'] ) ); ?></span>
					</p>
				<?php endif; ?>
				<p class="rx-mini-cart__totals-row">
					<span><?php esc_html_e( 'Shipping', 'rx-theme' ); ?></span>
					<span><?php esc_html_e( 'Calculated at checkout', 'rx-theme' ); ?></span>
				</p>
				<p class="rx-mini-cart__totals-row rx-mini-cart__totals-row--total">
					<span><?php esc_html_e( 'Current total', 'rx-theme' ); ?></span>
					<span><?php echo esc_html( rx_theme_format_money( $rx_theme_d['total'] ) ); ?></span>
				</p>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( ! $rx_theme_d['is_empty'] ) : ?>
		<div class="rx-mini-cart__foot">
			<?php if ( $rx_theme_next && $rx_theme_d['eligible'] ) : ?>
				<a class="rx-mini-cart__cta rx-mini-cart__cta--dark" href="<?php echo esc_url( home_url( '/build-a-bundle/#rx-pair-' . $rx_theme_next ) ); ?>">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: ordinal ("third"), 2: discount percentage. */
							__( 'Choose my %1$s pair (save %2$s%%)', 'rx-theme' ),
							rx_theme_mini_cart_ordinal( $rx_theme_next ),
							rx_theme_format_percent( $rx_theme_d['next_percent'] )
						)
					);
					?>
					<span aria-hidden="true">&rarr;</span>
				</a>
			<?php endif; ?>
			<a class="rx-mini-cart__cta rx-mini-cart__cta--blue" href="<?php echo esc_url( wc_get_checkout_url() ); ?>">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: cart total. */
						__( 'Proceed to checkout — %s', 'rx-theme' ),
						rx_theme_format_money( $rx_theme_d['total'] )
					)
				);
				?>
			</a>
			<p class="rx-mini-cart__trust">
				<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" d="M7 11V8a5 5 0 0 1 10 0v3M5 11h14v10H5z"/></svg>
				<?php esc_html_e( 'Secure checkout • Hassle-free sizing exchanges', 'rx-theme' ); ?>
			</p>
		</div>
	<?php endif; ?>
</div>
