<?php
/**
 * Homepage "Best Sellers" section (Figma section 4, "Performance Best
 * Sellers"): four product cards. Each card is the theme's shop card —
 * woocommerce/content-product.php via wc_get_template_part(), the same
 * template the shop and archive pages use — inside the same
 * ul.products markup WooCommerce prints there. Which products: see
 * inc/best-sellers.php. Eyebrow, heading and the note on the right are
 * Customizer fields (rx_theme_bestsellers_fields()).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_best_sellers = rx_theme_best_sellers_query();

if ( ! $rx_theme_best_sellers->have_posts() ) {
	wp_reset_postdata();
	return;
}
?>
<section class="rx-best-sellers" aria-labelledby="rx-best-sellers-heading">
	<header class="rx-best-sellers__header">
		<div class="rx-best-sellers__title">
			<p class="rx-eyebrow" data-customize-partial="rx_bestsellers_eyebrow"><?php echo esc_html( rx_theme_get_mod( 'rx_bestsellers_eyebrow' ) ); ?></p>
			<h2 class="rx-section-heading" id="rx-best-sellers-heading" data-customize-partial="rx_bestsellers_heading"><?php echo esc_html( rx_theme_get_mod( 'rx_bestsellers_heading' ) ); ?></h2>
		</div>
		<p class="rx-best-sellers__note" data-customize-partial="rx_bestsellers_note"><?php echo esc_html( rx_theme_get_mod( 'rx_bestsellers_note' ) ); ?></p>
	</header>

	<?php
	wc_setup_loop(
		array(
			'name'    => 'rx_best_sellers',
			'columns' => 4,
			'total'   => $rx_theme_best_sellers->post_count,
		)
	);

	woocommerce_product_loop_start();

	while ( $rx_theme_best_sellers->have_posts() ) {
		$rx_theme_best_sellers->the_post();
		wc_get_template_part( 'content', 'product' );
	}

	woocommerce_product_loop_end();

	wp_reset_postdata();
	wc_reset_loop();
	?>
</section>
