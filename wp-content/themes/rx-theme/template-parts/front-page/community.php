<?php
/**
 * Homepage "Community Rotations" section (Figma section 8): customer
 * testimonials. The cards are entries of the "Community" post type
 * (rx-core; see inc/community.php); eyebrow, heading and the note on the
 * right are Customizer fields (rx_theme_community_fields()). Hidden until
 * there is at least one published testimonial.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_community_posts = rx_theme_community_posts();

if ( ! $rx_theme_community_posts ) {
	return;
}
?>
<section class="rx-community" aria-labelledby="rx-community-heading">
	<header class="rx-community__header">
		<div class="rx-community__title">
			<p class="rx-eyebrow" data-customize-partial="rx_community_eyebrow"><?php echo esc_html( rx_theme_get_mod( 'rx_community_eyebrow' ) ); ?></p>
			<h2 class="rx-section-heading" id="rx-community-heading" data-customize-partial="rx_community_heading"><?php echo esc_html( rx_theme_get_mod( 'rx_community_heading' ) ); ?></h2>
		</div>

		<p class="rx-community__note">
			<svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="12" fill="currentColor"/><path d="M7 12.5l3.2 3.2L17 9" fill="none" stroke="#111" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span data-customize-partial="rx_community_note"><?php echo esc_html( rx_theme_get_mod( 'rx_community_note' ) ); ?></span>
		</p>
	</header>

	<ul class="rx-community__grid">
		<?php foreach ( $rx_theme_community_posts as $rx_theme_post ) : ?>
			<?php $rx_theme_entry = rx_theme_community_entry( $rx_theme_post ); ?>
			<li>
				<article class="rx-community-card">
					<?php echo rx_theme_star_rating_html( $rx_theme_entry['rating'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper. ?>

					<blockquote class="rx-community-card__quote">
						<p>&ldquo;<?php echo esc_html( $rx_theme_entry['quote'] ); ?>&rdquo;</p>
					</blockquote>

					<footer class="rx-community-card__person">
						<span class="rx-community-card__avatar" aria-hidden="true"><?php echo esc_html( $rx_theme_entry['initials'] ); ?></span>
						<span class="rx-community-card__who">
							<strong class="rx-community-card__name"><?php echo esc_html( $rx_theme_entry['name'] ); ?></strong>
							<?php if ( $rx_theme_entry['details'] ) : ?>
								<span class="rx-community-card__role"><?php echo esc_html( implode( ' • ', $rx_theme_entry['details'] ) ); ?></span>
							<?php endif; ?>
						</span>
					</footer>
				</article>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
