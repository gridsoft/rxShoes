<?php
/**
 * Contract for a registerable feature service (Bundles, Pricing, Cart, ...).
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface Service {

	/**
	 * Add the service's hooks (actions/filters). Called once, from
	 * Plugin::boot(). Must not do any work itself — only register hooks.
	 */
	public function register(): void;
}
