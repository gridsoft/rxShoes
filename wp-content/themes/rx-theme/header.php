<?php
/**
 * Global site header.
 *
 * Structure confirmed against the Figma file's Home and Bundle Builder
 * frames, which share this header exactly (logo, primary nav, search,
 * bundle CTA, account/wishlist/cart).
 *
 * Two things from that reference are still deliberately left out
 * (2026-09-24 client styling pass), same reasoning as this file's
 * original note about dropping stale specifics: a currency switcher
 * ("AUD $") and a wishlist icon would both be real, functional
 * additions this store doesn't have — no multi-currency plugin, no
 * wishlist feature anywhere else on the site. Adding the icons without
 * the feature behind them would be exactly the kind of decorative,
 * non-working UI this build has avoided everywhere else. The
 * announcement bar's discount percentages, by contrast, ARE real now
 * (rx_theme_bundle_two_pack_discount_percent() /
 * rx_theme_bundle_max_discount_percent() didn't exist yet at the
 * original pass) — not the reference's own hardcoded 30%/45%.
 *
 * @package RX_Theme
 */

$rx_theme_header_two_pack = rx_theme_format_percent( rx_theme_bundle_two_pack_discount_percent() );
$rx_theme_header_max_pack = rx_theme_format_percent( rx_theme_bundle_max_discount_percent() );

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
	<div class="rx-announcement-bar__inner">
		<p class="rx-announcement-bar__brand">
			<svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13 2 3 14h7l-1 8 10-12h-7l1-8z"/></svg>
			<?php esc_html_e( 'Australian Athlete Performance Hub', 'rx-theme' ); ?>
		</p>
		<p class="rx-announcement-bar__promo">
			<a class="rx-announcement-bar__promo-link" href="<?php echo esc_url( home_url( '/build-a-bundle/' ) ); ?>"><?php esc_html_e( 'Build Your Rotation', 'rx-theme' ); ?></a>
			<?php
			printf(
				/* translators: 1: 2-pack discount percentage, 2: 3-pack discount percentage. */
				esc_html__( '— 2 Pairs Save %1$s%% | 3 Pairs Save %2$s%%', 'rx-theme' ),
				esc_html( $rx_theme_header_two_pack ),
				esc_html( $rx_theme_header_max_pack )
			);
			?>
			<span class="rx-announcement-bar__sep" aria-hidden="true">•</span>
			<?php esc_html_e( 'Free Express Shipping Over $150', 'rx-theme' ); ?>
			<span class="rx-announcement-bar__sep" aria-hidden="true">•</span>
			<?php esc_html_e( '30-Day Hassle-Free Returns', 'rx-theme' ); ?>
		</p>
		<p class="rx-announcement-bar__status">
			<span class="rx-announcement-bar__status-dot" aria-hidden="true"></span>
			<?php esc_html_e( 'Sydney Dispatch Active', 'rx-theme' ); ?>
		</p>
	</div>
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
				<span class="rx-btn--bundle__badge"><?php echo esc_html( sprintf( /* translators: %s: top bundle discount percentage. */ __( 'Save %s%%', 'rx-theme' ), $rx_theme_header_max_pack ) ); ?></span>
			</a>

			<?php if ( class_exists( 'WooCommerce' ) ) : ?>
				<a class="rx-icon-link rx-icon-link--account" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" aria-label="<?php esc_attr_e( 'My account', 'rx-theme' ); ?>">
					<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
				</a>
				<a class="rx-icon-link rx-icon-link--cart" href="<?php echo esc_url( wc_get_cart_url() ); ?>" aria-label="<?php esc_attr_e( 'Cart', 'rx-theme' ); ?>"<?php echo rx_theme_mini_cart_enabled() ? ' aria-controls="rx-mini-cart" aria-expanded="false"' : ''; ?>>
					<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8V6a6 6 0 0 1 12 0v2"/><rect width="18" height="13" x="3" y="8" rx="2"/></svg>
					<?php echo rx_theme_mini_cart_count_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside the helper. ?>
				</a>
			<?php endif; ?>
		</div>
	</div>
</header>
