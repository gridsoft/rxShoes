<?php
/**
 * Plugin Name: RX Core
 * Description: Site-specific business logic for the RX WooCommerce build — bundles, pricing rules, custom cart/checkout flows, orders, accounts, emails, integrations. Presentation stays in rx-theme; this plugin never touches markup beyond overridable templates.
 * Version: 0.1.0
 * Requires at least: 6.4
 * Requires PHP: 8.2
 * WC requires at least: 8.0
 * WC tested up to: 9.0
 * Author:
 * Text Domain: rx-core
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RX_CORE_VERSION', '0.1.0' );
define( 'RX_CORE_FILE', __FILE__ );
define( 'RX_CORE_DIR', __DIR__ );
define( 'RX_CORE_URL', plugin_dir_url( __FILE__ ) );

// Composer autoload (PSR-4: RX\Core\ => src/).
$rx_core_vendor_autoload = RX_CORE_DIR . '/vendor/autoload.php';
if ( file_exists( $rx_core_vendor_autoload ) ) {
	require_once $rx_core_vendor_autoload;
} else {
	add_action(
		'admin_notices',
		function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p>';
			esc_html_e( 'RX Core: run "composer install" in wp-content/plugins/rx-core before activating.', 'rx-core' );
			echo '</p></div>';
		}
	);
	return;
}

/**
 * Declare HPOS (High-Performance Order Storage) compatibility.
 * This plugin never reads/writes order data via post meta or raw wp_posts
 * queries — only via wc_get_order() and CRUD getters/setters.
 */
add_action(
	'before_woocommerce_init',
	function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				RX_CORE_FILE,
				true
			);
		}
	}
);

/**
 * Bootstrap once WooCommerce is confirmed active. Bails with an admin
 * notice otherwise — this plugin has no reason to exist without WC.
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
					esc_html_e( 'RX Core requires WooCommerce to be active.', 'rx-core' );
					echo '</p></div>';
				}
			);
			return;
		}

		Plugin::instance()->boot();
	}
);
