<?php
/**
 * Global site footer.
 *
 * Structure confirmed against Figma (Home/Bundle Builder frames share this
 * footer). Payment badges deliberately do NOT match the Figma mockup
 * (which shows Stripe/Apple Pay/Afterpay/Zip/Visa/MC) — the client's
 * confirmed launch payment methods are PayID + PayTo (AzuPay) and Card
 * (Stripe). See PROJECT.md §8. Update this list only when gateways change.
 *
 * @package RX_Theme
 */

?>
<footer id="site-footer" class="rx-footer">
	<div class="rx-footer__trust">
		<p><?php esc_html_e( 'Free Australian Shipping', 'rx-theme' ); ?></p>
		<p><?php esc_html_e( 'Easy 30-Day Returns', 'rx-theme' ); ?></p>
		<p><?php esc_html_e( '100% Authentic Performance', 'rx-theme' ); ?></p>
	</div>

	<div class="rx-footer__columns">
		<div class="rx-footer__brand">
			<span class="rx-logo__mark">RX<span class="rx-logo__slash">/</span></span>
			<p><?php esc_html_e( 'Engineered athletic rotation engine for runners and hybrid athletes. Modern technical footwear paired with precision bundle incentives.', 'rx-theme' ); ?></p>
		</div>

		<nav class="rx-footer__nav" aria-label="<?php esc_attr_e( 'Shop', 'rx-theme' ); ?>">
			<h2><?php esc_html_e( 'Shop', 'rx-theme' ); ?></h2>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'footer',
					'container'      => false,
					'menu_class'     => 'rx-footer__nav-list',
					'fallback_cb'    => false,
				)
			);
			?>
		</nav>
	</div>

	<div class="rx-footer__payments" aria-label="<?php esc_attr_e( 'Accepted payment methods', 'rx-theme' ); ?>">
		<span>PayID</span>
		<span>PayTo</span>
		<span><?php esc_html_e( 'Card (Stripe)', 'rx-theme' ); ?></span>
	</div>

	<p class="rx-footer__copyright">
		&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php esc_html_e( 'All rights reserved.', 'rx-theme' ); ?>
	</p>
</footer>

<?php wp_footer(); ?>
</body>
</html>
