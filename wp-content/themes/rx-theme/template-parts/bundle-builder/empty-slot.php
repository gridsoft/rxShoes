<?php
/**
 * One open rotation slot on /build-a-bundle/, per the client's Figma
 * redesign (2026-09-29). Two states:
 *
 * - Next slot to fill: "+" icon, "Choose your shoe", and a "Select shoe"
 *   button to the shop filtered to bundle-eligible products (?rx_bundle=1,
 *   the shop filter bar's own "Bundle eligible" toggle).
 * - Later slot ($args['locked']): greyed out with a lock icon and the real
 *   tier this slot unlocks (rx_theme_bundle_builder_tier(), by slot
 *   position — Pair 2 is always the two-pack tier, Pair 3+ the max tier).
 *
 * Expects $args['pair_number'] and $args['locked'].
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_pair    = (int) ( $args['pair_number'] ?? 0 );
$rx_theme_locked  = ! empty( $args['locked'] );
$rx_theme_percent = rx_theme_bundle_builder_tier( $rx_theme_pair )['percent'];
$rx_theme_is_max  = $rx_theme_percent > 0 && $rx_theme_percent >= rx_theme_bundle_max_discount_percent();
$rx_theme_ordinal = array(
	1 => __( 'first', 'rx-theme' ),
	2 => __( 'second', 'rx-theme' ),
	3 => __( 'third', 'rx-theme' ),
)[ $rx_theme_pair ] ?? __( 'another', 'rx-theme' );

if ( 1 === $rx_theme_pair ) {
	$rx_theme_text = __( 'Select your first pair to start your rotation.', 'rx-theme' );
} elseif ( $rx_theme_is_max ) {
	$rx_theme_text = $rx_theme_locked
		? __( 'Complete the rotation for maximum savings.', 'rx-theme' )
		/* translators: 1: ordinal ("third"), 2: discount percentage. */
		: sprintf( __( 'Select a %1$s pair to unlock %2$s%% off.', 'rx-theme' ), $rx_theme_ordinal, rx_theme_format_percent( $rx_theme_percent ) );
} else {
	/* translators: %s: ordinal ("second"). */
	$rx_theme_text = sprintf( __( 'Select a %s pair to activate bundle pricing.', 'rx-theme' ), $rx_theme_ordinal );
}
?>
<div class="rx-bundle-slot rx-bundle-slot--<?php echo $rx_theme_locked ? 'locked' : 'open'; ?>" id="rx-pair-<?php echo esc_attr( $rx_theme_pair ); ?>">
	<?php if ( $rx_theme_locked ) : ?>
		<span class="rx-bundle-slot__icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" width="18" height="18" focusable="false"><rect x="5" y="11" width="14" height="10" rx="2" fill="none" stroke="currentColor" stroke-width="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3" fill="none" stroke="currentColor" stroke-width="2"/></svg>
		</span>
		<p class="rx-bundle-slot__heading">
			<?php echo esc_html( sprintf( /* translators: %s: ordinal ("third"). */ __( 'Add %s pair', 'rx-theme' ), $rx_theme_ordinal ) ); ?>
		</p>
		<?php if ( $rx_theme_percent > 0 ) : ?>
			<span class="rx-bundle-slot__unlock">
				<svg viewBox="0 0 24 24" width="10" height="10" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/></svg>
				<?php echo esc_html( sprintf( /* translators: %s: discount percentage. */ __( 'Unlock %s%% off', 'rx-theme' ), rx_theme_format_percent( $rx_theme_percent ) ) ); ?>
			</span>
		<?php endif; ?>
		<p class="rx-bundle-slot__text"><?php echo esc_html( $rx_theme_text ); ?></p>
	<?php else : ?>
		<?php $rx_theme_vault_url = add_query_arg( 'rx_bundle', '1', wc_get_page_permalink( 'shop' ) ); ?>
		<a class="rx-bundle-slot__icon" href="<?php echo esc_url( $rx_theme_vault_url ); ?>" aria-label="<?php esc_attr_e( 'Select shoe', 'rx-theme' ); ?>">
			<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
		</a>
		<p class="rx-bundle-slot__heading"><?php esc_html_e( 'Choose your shoe', 'rx-theme' ); ?></p>
		<p class="rx-bundle-slot__text"><?php echo esc_html( $rx_theme_text ); ?></p>
		<a class="rx-bundle-slot__select" href="<?php echo esc_url( $rx_theme_vault_url ); ?>"><?php esc_html_e( 'Select shoe', 'rx-theme' ); ?></a>
	<?php endif; ?>
</div>
