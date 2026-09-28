<?php
/**
 * Plugin Name: RX AzuPay Payments
 * Description: PayID payments for WooCommerce through AzuPay. Registers a unique PayID per order, confirms payment automatically by webhook, cancels and restocks unpaid orders when the PayID expires, and refunds through AzuPay.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 11.1
 * Author:
 * Text Domain: rx-azupay
 *
 * Credentials live in wp-config.php, never the database:
 *
 *     define( 'RX_AZUPAY_CLIENT_ID', 'CLIENT1TEST' );  // Per environment.
 *     define( 'RX_AZUPAY_SECRET_KEY', '...' );
 *     define( 'RX_AZUPAY_SANDBOX', true );             // false on production only.
 *
 * @package RX\AzuPay
 */

declare(strict_types=1);

namespace RX\AzuPay;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RX_AZUPAY_VERSION', '0.1.0' );
define( 'RX_AZUPAY_FILE', __FILE__ );
define( 'RX_AZUPAY_DIR', __DIR__ );
define( 'RX_AZUPAY_URL', plugin_dir_url( __FILE__ ) );

/*
 * PSR-4 autoload (RX\AzuPay\ => src/). No Composer at runtime, so the
 * plugin can be dropped onto any site as-is.
 */
spl_autoload_register(
	function ( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';
		if ( ! str_starts_with( $class_name, $prefix ) ) {
			return;
		}

		$file = RX_AZUPAY_DIR . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_readable( $file ) ) {
			require $file;
		}
	}
);

/**
 * Declare HPOS compatibility: order data is only touched through
 * wc_get_order() and CRUD methods.
 */
add_action(
	'before_woocommerce_init',
	function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', RX_AZUPAY_FILE, true );
		}
	}
);

/**
 * Boot once WooCommerce is confirmed active.
 */
add_action(
	'plugins_loaded',
	function (): void {
		if ( ! class_exists( \WooCommerce::class ) ) {
			add_action(
				'admin_notices',
				function (): void {
					if ( ! current_user_can( 'activate_plugins' ) ) {
						return;
					}
					echo '<div class="notice notice-error"><p>';
					esc_html_e( 'RX AzuPay Payments requires WooCommerce to be active.', 'rx-azupay' );
					echo '</p></div>';
				}
			);
			return;
		}

		( new Plugin() )->boot();
	}
);
