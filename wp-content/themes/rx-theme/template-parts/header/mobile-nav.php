<?php
/**
 * Mobile / tablet menu (below 62em, where the header's inline nav and
 * search are hidden): a panel that slides in from the left, opened by the
 * header's ☰ button (.rx-menu-toggle). Contents: search, the primary menu
 * (Appearance > Menus — the same one as desktop), "Shop by brand", the
 * Build a Bundle button, account and cart. Behaviour: assets/js/mobile-nav.js.
 *
 * Args: bundles (bool) — whether the bundle offer is running.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_bundles = ! empty( $args['bundles'] );
$rx_theme_brands  = taxonomy_exists( 'product_brand' ) ? get_terms(
	array(
		'taxonomy'   => 'product_brand',
		'hide_empty' => true,
		'orderby'    => 'name',
	)
) : array();
$rx_theme_brands  = is_wp_error( $rx_theme_brands ) ? array() : $rx_theme_brands;
?>
<div class="rx-mobile-nav-backdrop" data-rx-mobile-nav-close hidden></div>
<div id="rx-mobile-nav" class="rx-mobile-nav" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'rx-theme' ); ?>" aria-hidden="true">
	<div class="rx-mobile-nav__head">
		<a class="rx-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'RX Shoe — home', 'rx-theme' ); ?>">
			<span class="rx-logo__mark">RX<span class="rx-logo__slash">/</span></span>
			<span class="rx-logo__word"><span class="rx-logo__shoe">SHOE</span></span>
		</a>
		<button type="button" class="rx-mobile-nav__close" data-rx-mobile-nav-close aria-label="<?php esc_attr_e( 'Close menu', 'rx-theme' ); ?>">
			<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
		</button>
	</div>

	<div class="rx-mobile-nav__body">
		<form class="rx-mobile-nav__search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<label class="screen-reader-text" for="rx-mobile-search-input"><?php esc_html_e( 'Search shoes, brands', 'rx-theme' ); ?></label>
			<input type="search" id="rx-mobile-search-input" name="s" placeholder="<?php esc_attr_e( 'Search shoes, brands…', 'rx-theme' ); ?>">
			<input type="hidden" name="post_type" value="product">
		</form>

		<?php
		wp_nav_menu(
			array(
				'theme_location'  => 'primary',
				'container'       => 'nav',
				'container_class' => 'rx-mobile-nav__menu',
				'menu_class'      => 'rx-mobile-nav__list',
				'fallback_cb'     => 'rx_theme_primary_nav_fallback',
			)
		);
		?>

		<?php if ( $rx_theme_bundles ) : ?>
			<a class="rx-btn rx-btn--bundle rx-mobile-nav__bundle" href="<?php echo esc_url( home_url( '/build-a-bundle/' ) ); ?>">
				<?php esc_html_e( 'Build a Bundle', 'rx-theme' ); ?>
				<span class="rx-btn--bundle__badge"><?php echo esc_html( sprintf( /* translators: %s: top bundle discount percentage. */ __( 'Save %s%%', 'rx-theme' ), rx_theme_format_percent( rx_theme_bundle_max_discount_percent() ) ) ); ?></span>
			</a>
		<?php endif; ?>

		<?php if ( $rx_theme_brands ) : ?>
			<p class="rx-mobile-nav__heading"><?php esc_html_e( 'Shop by brand', 'rx-theme' ); ?></p>
			<ul class="rx-mobile-nav__brands">
				<?php foreach ( $rx_theme_brands as $rx_theme_brand ) : ?>
					<?php
					$rx_theme_link = get_term_link( $rx_theme_brand );
					if ( is_wp_error( $rx_theme_link ) ) {
						continue;
					}
					?>
					<li>
						<a href="<?php echo esc_url( $rx_theme_link ); ?>">
							<?php echo esc_html( $rx_theme_brand->name ); ?>
							<span><?php echo esc_html( number_format_i18n( $rx_theme_brand->count ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( class_exists( 'WooCommerce' ) ) : ?>
			<ul class="rx-mobile-nav__account">
				<li><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php echo esc_html( is_user_logged_in() ? __( 'My account', 'rx-theme' ) : __( 'Sign in / Register', 'rx-theme' ) ); ?></a></li>
				<li><a href="<?php echo esc_url( wc_get_cart_url() ); ?>"><?php esc_html_e( 'Cart', 'rx-theme' ); ?></a></li>
			</ul>
		<?php endif; ?>
	</div>
</div>
