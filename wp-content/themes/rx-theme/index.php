<?php
/**
 * Fallback template. Real templates arrive with the Figma build (Milestone 3).
 *
 * @package RX_Theme
 */

get_header();
?>

<main id="primary" class="rx-content">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			the_content();
		}
	}
	?>
</main>

<?php
get_footer();
