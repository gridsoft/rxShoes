<?php
/**
 * Homepage "Different session. Different shoe." section (Figma "Section
 * - 5", the biomechanical-principle band with three discipline cards).
 * Built ahead of section 4 (Best Sellers) at the client's request.
 *
 * Three standard blog posts from the "Biomechanics" category — see
 * inc/biomechanics.php for how a post maps onto a card. Eyebrow, heading
 * and description are Customizer fields (rx_theme_biomech_fields()).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_biomech_posts = rx_theme_biomech_posts();

if ( ! $rx_theme_biomech_posts ) {
	return;
}
?>
<section class="rx-biomech" aria-labelledby="rx-biomech-heading">
	<header class="rx-biomech__header">
		<p class="rx-eyebrow rx-eyebrow--red" data-customize-partial="rx_biomech_eyebrow"><?php echo esc_html( rx_theme_get_mod( 'rx_biomech_eyebrow' ) ); ?></p>
		<h2 class="rx-section-heading" id="rx-biomech-heading" data-customize-partial="rx_biomech_heading"><?php echo esc_html( rx_theme_get_mod( 'rx_biomech_heading' ) ); ?></h2>
		<p class="rx-biomech__description" data-customize-partial="rx_biomech_description"><?php echo esc_html( rx_theme_get_mod( 'rx_biomech_description' ) ); ?></p>
	</header>

	<div class="rx-biomech__grid">
		<?php foreach ( $rx_theme_biomech_posts as $rx_theme_index => $rx_theme_post ) : ?>
			<?php
			$rx_theme_position = $rx_theme_index + 1;
			$rx_theme_link     = get_permalink( $rx_theme_post );
			$rx_theme_points   = rx_theme_post_bullets( $rx_theme_post );
			?>
			<article class="rx-biomech-card">
				<?php if ( has_post_thumbnail( $rx_theme_post ) ) : ?>
					<a class="rx-biomech-card__image" href="<?php echo esc_url( $rx_theme_link ); ?>" tabindex="-1" aria-hidden="true">
						<?php echo get_the_post_thumbnail( $rx_theme_post, 'large', array( 'class' => 'rx-biomech-card__photo' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-generated <img>. ?>
					</a>
				<?php endif; ?>

				<p class="rx-biomech-card__label">
					<?php echo rx_theme_biomech_icon( $rx_theme_position ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?>
					<?php
					printf(
						/* translators: %s: two-digit card number, e.g. "01". */
						esc_html__( 'Discipline %s', 'rx-theme' ),
						esc_html( str_pad( (string) $rx_theme_position, 2, '0', STR_PAD_LEFT ) )
					);
					?>
				</p>

				<h3 class="rx-biomech-card__title">
					<a href="<?php echo esc_url( $rx_theme_link ); ?>"><?php echo esc_html( get_the_title( $rx_theme_post ) ); ?></a>
				</h3>

				<p class="rx-biomech-card__text"><?php echo esc_html( get_the_excerpt( $rx_theme_post ) ); ?></p>

				<?php if ( $rx_theme_points ) : ?>
					<ul class="rx-biomech-card__points">
						<?php foreach ( $rx_theme_points as $rx_theme_point ) : ?>
							<li><?php echo esc_html( $rx_theme_point ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</div>
</section>
