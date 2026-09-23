<?php
/**
 * A single blog post: category eyebrow, title, featured image, body.
 * Deliberately minimal — the blog isn't designed in Figma yet; this just
 * makes the posts behind the homepage "Different session" cards readable.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<main id="primary" class="rx-post">
		<article <?php post_class( 'rx-post__article' ); ?>>
			<header class="rx-post__header">
				<?php $rx_theme_categories = get_the_category(); ?>
				<?php if ( $rx_theme_categories ) : ?>
					<p class="rx-eyebrow"><?php echo esc_html( $rx_theme_categories[0]->name ); ?></p>
				<?php endif; ?>
				<h1 class="rx-section-heading"><?php the_title(); ?></h1>
			</header>

			<?php if ( has_post_thumbnail() ) : ?>
				<div class="rx-post__image"><?php the_post_thumbnail( 'large' ); ?></div>
			<?php endif; ?>

			<div class="rx-post__content">
				<?php the_content(); ?>
			</div>
		</article>
	</main>
	<?php
endwhile;

get_footer();
