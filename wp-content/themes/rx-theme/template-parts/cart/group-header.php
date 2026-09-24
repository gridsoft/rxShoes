<?php
/**
 * Group header row for the cart page and checkout order-review tables:
 * "Your rotation" (dark bar, lime note) or "Other items" (plain). See
 * rx_theme_cart_grouping() in inc/bundle-builder.php.
 *
 * Args: group ('rotation'|'other'), note (rotation header's note),
 * colspan (the table's column count).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_group   = 'rotation' === ( $args['group'] ?? '' ) ? 'rotation' : 'other';
$rx_theme_note    = (string) ( $args['note'] ?? '' );
$rx_theme_colspan = (int) ( $args['colspan'] ?? 1 );
?>
<tr class="rx-review-group rx-review-group--<?php echo esc_attr( $rx_theme_group ); ?>">
	<th colspan="<?php echo esc_attr( (string) $rx_theme_colspan ); ?>">
		<?php if ( 'rotation' === $rx_theme_group ) : ?>
			<span class="rx-review-group__title"><span class="rx-review-group__dot" aria-hidden="true"></span><?php esc_html_e( 'Your rotation', 'rx-theme' ); ?></span>
			<span class="rx-review-group__note"><?php echo esc_html( $rx_theme_note ); ?></span>
		<?php else : ?>
			<span class="rx-review-group__title"><?php esc_html_e( 'Other items', 'rx-theme' ); ?></span>
			<span class="rx-review-group__note"><?php esc_html_e( 'Full price — not in the rotation discount', 'rx-theme' ); ?></span>
		<?php endif; ?>
	</th>
</tr>
