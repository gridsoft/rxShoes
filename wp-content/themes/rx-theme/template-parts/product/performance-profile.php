<?php
/**
 * PDP "Performance dispersion profile" box (client reference design):
 * 1–5 ratings as five dots + "N/5", then a row of specs (drop, weight,
 * forefoot, midsole). Values are entered per product on the "Shop card"
 * tab; only the ratings/specs actually entered are shown, and the box is
 * skipped entirely when there are none (rx_theme_product_performance()
 * returns null). The reference's "Lab verified metrics" link is left out:
 * the numbers are admin-entered, not lab-measured.
 *
 * Args: ratings, specs (see rx_theme_product_performance()).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_ratings = $args['ratings'] ?? array();
$rx_theme_specs   = $args['specs'] ?? array();

if ( ! $rx_theme_ratings && ! $rx_theme_specs ) {
	return;
}
?>
<section class="rx-perf" aria-labelledby="rx-perf-title">
	<h2 id="rx-perf-title" class="rx-perf__title"><?php esc_html_e( 'Performance dispersion profile', 'rx-theme' ); ?></h2>

	<?php if ( $rx_theme_ratings ) : ?>
		<dl class="rx-perf__ratings">
			<?php foreach ( $rx_theme_ratings as $rx_theme_rating ) : ?>
				<div class="rx-perf__rating">
					<dt class="rx-perf__rating-label"><?php echo esc_html( $rx_theme_rating['label'] ); ?></dt>
					<dd class="rx-perf__rating-value">
						<span class="rx-perf__dots" role="img" aria-label="<?php echo esc_attr( sprintf( /* translators: %d: rating out of 5. */ __( '%d out of 5', 'rx-theme' ), $rx_theme_rating['value'] ) ); ?>">
							<?php for ( $rx_theme_i = 1; $rx_theme_i <= 5; $rx_theme_i++ ) : ?>
								<span class="rx-perf__dot<?php echo $rx_theme_i <= $rx_theme_rating['value'] ? ' is-on' : ''; ?>"></span>
							<?php endfor; ?>
						</span>
						<span class="rx-perf__score" aria-hidden="true"><?php echo esc_html( $rx_theme_rating['value'] . '/5' ); ?></span>
					</dd>
				</div>
			<?php endforeach; ?>
		</dl>
	<?php endif; ?>

	<?php if ( $rx_theme_specs ) : ?>
		<ul class="rx-perf__specs">
			<?php foreach ( $rx_theme_specs as $rx_theme_spec ) : ?>
				<li class="rx-perf__spec">
					<strong class="rx-perf__spec-value"><?php echo esc_html( $rx_theme_spec['value'] ); ?></strong>
					<span class="rx-perf__spec-label"><?php echo esc_html( $rx_theme_spec['label'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
