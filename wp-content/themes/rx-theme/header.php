<?php
/**
 * Global site header.
 *
 * Structure confirmed against the Figma file's Home and Bundle Builder
 * frames, which share this header exactly (logo, primary nav, search,
 * bundle CTA, account/wishlist/cart). Copy is kept deliberately generic
 * where the Figma mockup's specifics (currency switcher, exact promo %)
 * are known to be stale against the client's actual confirmed scope —
 * see PROJECT.md §8 dev log, 2026-09-21.
 *
 * @package RX_Theme
 */

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'rx-theme' ); ?></a>

<div class="rx-announcement-bar">
	<p><?php esc_html_e( 'Free Express Shipping on orders over $150 AUD · 30-Day Hassle-Free Returns', 'rx-theme' ); ?></p>
</div>

<header id="site-header" class="rx-header">
	<div class="rx-header__row">
		<a class="rx-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'RX Shoe — home', 'rx-theme' ); ?>">
			<span class="rx-logo__mark">RX<span class="rx-logo__slash">/</span></span>
			<span class="rx-logo__word">
				<span class="rx-logo__shoe">SHOE</span>
				<span class="rx-logo__tagline"><?php esc_html_e( 'PERFORMANCE ROTATION', 'rx-theme' ); ?></span>
			</span>
		</a>

		<?php
		wp_nav_menu(
			array(
				'theme_location'  => 'primary',
				'container'       => 'nav',
				'container_class' => 'rx-nav',
				'menu_class'      => 'rx-nav__list',
				'fallback_cb'     => 'rx_theme_primary_nav_fallback',
			)
		);
		?>

		<form class="rx-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="rx-search-input"><?php esc_html_e( 'Search shoes, brands', 'rx-theme' ); ?></label>
			<input type="search" id="rx-search-input" name="s" placeholder="<?php esc_attr_e( 'Search shoes, brands…', 'rx-theme' ); ?>">
		</form>

		<div class="rx-header__actions">
			<a class="rx-btn rx-btn--bundle" href="<?php echo esc_url( home_url( '/build-a-bundle/' ) ); ?>">
				<?php esc_html_e( 'Build a Bundle', 'rx-theme' ); ?>
			</a>

			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="rx-icon-link rx-icon-link--account" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php esc_attr_e( 'My account', 'rx-theme' ); ?>"></a>
				<a class="rx-icon-link rx-icon-link--cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'rx-theme' ); ?>">
					<span class="rx-icon-link__count"><?php echo esc_html( WC()->cart ? WC()->cart->get_cart_contents_count() : 0 ); ?></span>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>
