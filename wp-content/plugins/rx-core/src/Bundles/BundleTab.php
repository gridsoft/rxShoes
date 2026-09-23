<?php
/**
 * The "Bundle" tab in the product data box.
 *
 * Holds everything about this product's rotation bundle: whether it's
 * eligible at all (RX\Core\Bundles\BundleEligibility) and, once it is,
 * which two other products fill Pair 2 and Pair 3
 * (RX\Core\Bundles\BundleRotationPairs). Split out from the "Shop card"
 * tab (RX\Core\Admin\ProductCardTab) — that tab is about shop-card
 * *copy* (type label, best-for), this one is a business rule with
 * product-to-product relationships, a different enough concern to earn
 * its own tab rather than growing Shop card indefinitely.
 *
 * Same reasoning as Shop card for why this needs its own tab at all: the
 * obvious home, the General tab, is hidden by WooCommerce for variable
 * products, and every product here is variable.
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Bundles;

use RX\Core\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the tab and renders its (initially empty) panel.
 */
final class BundleTab implements Service {

	/**
	 * Tab key in WooCommerce's product data tabs.
	 */
	public const TAB = 'rx_bundle';

	/**
	 * Action fired inside the panel; hook it to add fields.
	 */
	public const FIELDS_ACTION = 'rx_core_bundle_fields';

	/**
	 * Add the tab and its panel.
	 */
	public function register(): void {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_panel' ) );
	}

	/**
	 * Add the tab. No hide_if_* / show_if_* classes, so it shows for
	 * every product type. Priority 66 — right after Shop card (65).
	 *
	 * @param array<string,array<string,mixed>> $tabs Existing tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public function add_tab( array $tabs ): array {
		$tabs[ self::TAB ] = array(
			'label'    => __( 'Bundle', 'rx-core' ),
			'target'   => self::TAB . '_product_data',
			'class'    => array(),
			'priority' => 66,
		);

		return $tabs;
	}

	/**
	 * Render the panel wrapper and let fields add themselves.
	 */
	public function render_panel(): void {
		echo '<div id="' . esc_attr( self::TAB . '_product_data' ) . '" class="panel woocommerce_options_panel hidden"><div class="options_group">';

		/**
		 * Fires inside the Bundle tab. Output product fields here.
		 */
		do_action( 'rx_core_bundle_fields' ); // Must stay equal to self::FIELDS_ACTION (PHPCS needs the literal).

		echo '</div></div>';
	}
}
