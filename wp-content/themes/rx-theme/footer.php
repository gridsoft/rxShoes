<?php
/**
 * Global site footer (Figma: the footer shared by the Home / Shop / PDP
 * frames): four trust items, the brand block with three link columns, and
 * the copyright + payment-method row.
 *
 * ALL TEXT HERE IS HARDCODED for now, as test content taken from the
 * Figma frame — to be made editable (Customizer / menus) later. Links to
 * pages that don't exist yet are "#".
 *
 * The "Your rotation" bar at the very bottom is static markup for now (no
 * behaviour): JavaScript will drive it later — each pair chip carries its
 * number and a state class (--added / --next / --locked) for that. Only
 * the discount percentages in it come from Appearance > Customize > Bundle
 * Discount; everything else is fixed text. In Figma it is a sticky bar;
 * here it simply sits at the end of the footer.
 *
 * Payment badges deliberately do NOT match the Figma mockup (which shows
 * Stripe/Apple Pay/Afterpay/Zip/Visa/MC) — the client's confirmed launch
 * payment methods are PayID + PayTo (AzuPay) and Card (Stripe), and the
 * footer shouldn't advertise methods the store won't accept. See
 * PROJECT.md §8. Change $rx_theme_payments only when gateways change.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rx_theme_account    = wc_get_page_permalink( 'myaccount' );
$rx_theme_two_pack   = rx_theme_format_percent( rx_theme_bundle_two_pack_discount_percent() );
$rx_theme_three_pack = rx_theme_format_percent( rx_theme_bundle_max_discount_percent() );

// Icon paths (24px grid, stroked): truck, returns (arrows + tick), badge-check, warehouse.
$rx_theme_footer_trust = array(
	array(
		'icon'  => '<path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2M15 18H9M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="17" cy="18" r="2"/><circle cx="7" cy="18" r="2"/>',
		'title' => __( 'Free Australian Shipping', 'rx-theme' ),
		'text'  => __( 'Free express dispatch across Australia on all orders over $150 AUD.', 'rx-theme' ),
	),
	array(
		'icon'  => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8M21 3v5h-5M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16M8 16H3v5"/><path d="m9.5 12 1.8 1.8 3.4-3.6"/>',
		'title' => __( 'Easy Returns', 'rx-theme' ),
		'text'  => __( 'Hassle-free sizing exchanges and trial returns with local drop off.', 'rx-theme' ),
	),
	array(
		'icon'  => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>',
		'title' => __( '100% Authentic Performance', 'rx-theme' ),
		'text'  => __( 'Direct partner supply for elite performance road, track, and trail footwear.', 'rx-theme' ),
	),
	array(
		'icon'  => '<path d="M22 8.35V20a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V8.35A2 2 0 0 1 3.26 6.5l8-3.2a2 2 0 0 1 1.48 0l8 3.2A2 2 0 0 1 22 8.35Z"/><path d="M6 18h12M6 14h12"/><rect width="12" height="12" x="6" y="10"/>',
		'title' => __( 'Sydney Dispatch & Support', 'rx-theme' ),
		'text'  => __( 'Local athlete customer care center and warehouse in New South Wales.', 'rx-theme' ),
	),
);

// Link columns: label => URL ('#' where the page isn't built yet); optional style modifier.
$rx_theme_footer_columns = array(
	__( 'Shop', 'rx-theme' )     => array(
		array( __( 'Men\'s Footwear', 'rx-theme' ), home_url( '/product-category/men/' ), '' ),
		array( __( 'Women\'s Footwear', 'rx-theme' ), home_url( '/product-category/women/' ), '' ),
		array( __( 'Unisex Series', 'rx-theme' ), home_url( '/product-category/unisex/' ), '' ),
		array( __( 'Performance Brands', 'rx-theme' ), wc_get_page_permalink( 'shop' ), '' ),
		array( __( 'Sale Clearance', 'rx-theme' ), home_url( '/sale/' ), 'sale' ),
		/* translators: %s: top-tier bundle discount percentage, e.g. "45". */
		array( sprintf( __( 'Build a Bundle (-%s%%)', 'rx-theme' ), $rx_theme_three_pack ), home_url( '/build-a-bundle/' ), 'bundle' ),
	),
	__( 'Guidance', 'rx-theme' ) => array(
		array( __( 'Rotation Calculator', 'rx-theme' ), '#', '' ),
		array( __( 'Shoe Finder Quiz', 'rx-theme' ), '#', '' ),
		array( __( 'Fit & Sizing Guide', 'rx-theme' ), '#', '' ),
		array( __( 'Marathon Pacing Shoes', 'rx-theme' ), '#', '' ),
		array( __( 'Track WooCommerce Order', 'rx-theme' ), wc_get_endpoint_url( 'orders', '', $rx_theme_account ), '' ),
	),
	__( 'Support', 'rx-theme' )  => array(
		array( __( 'Australian Shipping', 'rx-theme' ), '#', '' ),
		array( __( '30-Day Returns Policy', 'rx-theme' ), '#', '' ),
		array( __( 'Manufacturer Warranty', 'rx-theme' ), '#', '' ),
		array( __( 'Contact Athlete Team', 'rx-theme' ), '#', '' ),
		array( __( 'My WooCommerce Account', 'rx-theme' ), $rx_theme_account, '' ),
	),
);

$rx_theme_payments = array( 'PayID', 'PayTo', 'Card (Stripe)' );
?>
<footer id="site-footer" class="rx-footer">
	<div class="rx-footer__inner">
		<ul class="rx-footer-trust">
			<?php foreach ( $rx_theme_footer_trust as $rx_theme_item ) : ?>
				<li class="rx-footer-trust__item">
					<span class="rx-footer-trust__icon">
						<svg viewBox="0 0 24 24" width="28" height="28" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><?php echo $rx_theme_item['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static inline SVG. ?></svg>
					</span>
					<h3 class="rx-footer-trust__title"><?php echo esc_html( $rx_theme_item['title'] ); ?></h3>
					<p class="rx-footer-trust__text"><?php echo esc_html( $rx_theme_item['text'] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>

		<div class="rx-footer__main">
			<div class="rx-footer__brand">
				<a class="rx-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="rx-footer__logo-mark" aria-hidden="true">RX</span>
					<span class="rx-footer__logo-word"><?php esc_html_e( 'RX Shoe Global', 'rx-theme' ); ?></span>
				</a>
				<p class="rx-footer__about"><?php esc_html_e( 'Engineered athletic rotation engine for runners and hybrid athletes. Modern technical footwear paired with precision bundle incentives.', 'rx-theme' ); ?></p>
				<p class="rx-footer__secure">
					<svg viewBox="0 0 24 24" width="12" height="12" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
					<?php esc_html_e( 'Powered by secure WooCommerce Core with instant 256-bit encryption', 'rx-theme' ); ?>
				</p>
			</div>

			<?php foreach ( $rx_theme_footer_columns as $rx_theme_heading => $rx_theme_links ) : ?>
				<nav class="rx-footer__nav" aria-label="<?php echo esc_attr( $rx_theme_heading ); ?>">
					<h2 class="rx-footer__nav-title"><?php echo esc_html( $rx_theme_heading ); ?></h2>
					<ul class="rx-footer__nav-list">
						<?php foreach ( $rx_theme_links as $rx_theme_link ) : ?>
							<li><a<?php echo $rx_theme_link[2] ? ' class="rx-footer__link--' . esc_attr( $rx_theme_link[2] ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- modifier is escaped above. ?> href="<?php echo esc_url( $rx_theme_link[1] ); ?>"><?php echo esc_html( $rx_theme_link[0] ); ?></a></li>
						<?php endforeach; ?>
					</ul>
				</nav>
			<?php endforeach; ?>
		</div>

		<div class="rx-footer__bottom">
			<p class="rx-footer__copyright">
				&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php esc_html_e( 'RX Shoe Australia Pty Ltd. All rights reserved. Trademarks belong to their respective running brands.', 'rx-theme' ); ?>
			</p>
			<ul class="rx-footer__payments" aria-label="<?php esc_attr_e( 'Accepted payment methods', 'rx-theme' ); ?>">
				<?php foreach ( $rx_theme_payments as $rx_theme_payment ) : ?>
					<li><?php echo esc_html( $rx_theme_payment ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>

	<div class="rx-rotation-bar" role="region" aria-label="<?php esc_attr_e( 'Your rotation', 'rx-theme' ); ?>">
		<div class="rx-rotation-bar__lead">
			<span class="rx-rotation-bar__dot" aria-hidden="true"></span>
			<strong class="rx-rotation-bar__title"><?php esc_html_e( 'Your Rotation', 'rx-theme' ); ?></strong>
			<?php /* translators: %s: 2-pack discount percentage, e.g. "35". */ ?>
			<span class="rx-rotation-bar__badge"><?php echo esc_html( sprintf( __( '%s%% Unlocked', 'rx-theme' ), $rx_theme_two_pack ) ); ?></span>
		</div>

		<ul class="rx-rotation-bar__pairs">
			<li class="rx-rotation-bar__pair rx-rotation-bar__pair--added" data-pair="1">
				<svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m8 12.5 2.8 2.8L16 9.5"/></svg>
				<?php esc_html_e( 'Pair 1: Tempo / Speed [Added]', 'rx-theme' ); ?>
			</li>
			<li class="rx-rotation-bar__pair rx-rotation-bar__pair--next" data-pair="2">
				<svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v8M8 12h8"/></svg>
				<?php /* translators: %s: 2-pack discount percentage, e.g. "35". */ ?>
				<?php echo esc_html( sprintf( __( 'Pair 2: Daily Mileage [Add for %s%%]', 'rx-theme' ), $rx_theme_two_pack ) ); ?>
			</li>
			<li class="rx-rotation-bar__pair rx-rotation-bar__pair--locked" data-pair="3">
				<svg viewBox="0 0 24 24" width="13" height="13" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><rect width="16" height="10" x="4" y="11" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
				<?php /* translators: %s: 3-pack discount percentage, e.g. "45". */ ?>
				<?php echo esc_html( sprintf( __( 'Pair 3: Race Day [Unlock %s%%]', 'rx-theme' ), $rx_theme_three_pack ) ); ?>
			</li>
		</ul>

		<a class="rx-rotation-bar__cta" href="<?php echo esc_url( home_url( '/build-a-bundle/' ) ); ?>">
			<?php esc_html_e( 'Build Now', 'rx-theme' ); ?>
			<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
		</a>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
