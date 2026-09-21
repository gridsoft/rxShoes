<?php
/**
 * Plugin bootstrap and service registration.
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Central bootstrap. Registers feature services as they're built —
 * Bundles, Pricing, Cart, Checkout, etc. are added here once each one's
 * requirements are settled (see PROJECT.md §15, currently open).
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Registered feature services, added to the array via a future
	 * `add_service()` as each one is built.
	 *
	 * @var Service[]
	 */
	private array $services = array();

	/**
	 * Get the singleton instance, creating it on first call.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Private constructor — use instance() instead.
	 */
	private function __construct() {}

	/**
	 * Register services and fire their hooks. Called on `plugins_loaded`
	 * after WooCommerce is confirmed active.
	 */
	public function boot(): void {
		foreach ( $this->services as $service ) {
			$service->register();
		}

		/**
		 * Fires after RX Core has registered its own services, so
		 * integrations can hook in with a guaranteed-loaded plugin.
		 *
		 * @param Plugin $plugin The booted plugin instance.
		 */
		do_action( 'rx_core_plugin_booted', $this );
	}

	/**
	 * Registered feature services.
	 *
	 * @return Service[]
	 */
	public function services(): array {
		return $this->services;
	}
}
