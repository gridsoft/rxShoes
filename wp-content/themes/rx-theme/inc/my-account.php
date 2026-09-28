<?php
/**
 * My Account presentation (client request, 2026-09-25: "follow the
 * general site style"). No Figma frame exists for these pages, so they
 * reuse the cart / checkout look: gray page, white cards, condensed
 * uppercase headings, blue primary buttons. Layout is CSS over
 * WooCommerce's own markup plus two small template overrides
 * (woocommerce/myaccount/my-account.php for the page header,
 * dashboard.php for the quick-link tiles).
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Drop "Downloads" from the account menu: the store sells shoes, not
 * downloadable products, so the tab would only ever say "No downloads".
 *
 * @param array<string,string> $items Endpoint => label.
 * @return array<string,string>
 */
function rx_theme_account_menu_items( array $items ): array {
	unset( $items['downloads'] );

	return $items;
}
add_filter( 'woocommerce_account_menu_items', 'rx_theme_account_menu_items' );

/**
 * On the account's order view, move "Payment method" above the order
 * total, so the total is the table's last row (styled as the bold
 * closing line, like the cart / checkout totals). Scoped to the account
 * pages so emails keep WooCommerce's order.
 *
 * @param array<string,array<string,string>> $totals Order item totals, keyed.
 * @return array<string,array<string,string>>
 */
function rx_theme_account_order_totals( array $totals ): array {
	if ( ! is_account_page() || ! isset( $totals['order_total'] ) ) {
		return $totals;
	}

	$order_total = $totals['order_total'];
	unset( $totals['order_total'] );
	$totals['order_total'] = $order_total;

	return $totals;
}
add_filter( 'woocommerce_get_order_item_totals', 'rx_theme_account_order_totals', 20 );

/**
 * Page header shared by the logged-in and logged-out account views.
 *
 * @param string $title    Heading.
 * @param string $subtitle Line under the heading (already escaped HTML allowed: links).
 */
function rx_theme_account_header( string $title, string $subtitle = '' ): void {
	?>
	<header class="rx-account-header">
		<p class="rx-eyebrow"><?php esc_html_e( 'Your account', 'rx-theme' ); ?></p>
		<h1 class="rx-account-header__title">
			<span class="rx-account-header__dot" aria-hidden="true"></span>
			<?php echo esc_html( $title ); ?>
		</h1>
		<?php if ( '' !== $subtitle ) : ?>
			<p class="rx-account-header__subtitle"><?php echo wp_kses_post( $subtitle ); ?></p>
		<?php endif; ?>
	</header>
	<?php
}

/**
 * Header above the logged-out login / register forms.
 */
function rx_theme_account_login_header(): void {
	rx_theme_account_header(
		__( 'Sign in', 'rx-theme' ),
		esc_html__( 'Track orders, manage your addresses and check out faster.', 'rx-theme' )
	);
}
add_action( 'woocommerce_before_customer_login_form', 'rx_theme_account_login_header' );
