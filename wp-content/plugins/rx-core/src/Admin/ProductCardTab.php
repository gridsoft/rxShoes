<?php
/**
 * The "Shop card" tab in the product data box.
 *
 * Holds the per-product settings that drive the shop-card design (type
 * label, "best for" text, bundle eligibility). It's a dedicated tab
 * because the obvious home — the General tab — is HIDDEN by WooCommerce
 * for variable products (everything in it is simple-product pricing),
 * and every shoe here is variable, so fields placed there were invisible
 * on every real product.
 *
 * This class only owns the tab and its panel. Fields plug in through the
 * rx_core_product_card_fields action.
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Admin;

use RX\Core\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the tab and renders its (initially empty) panel.
 */
final class ProductCardTab implements Service {

	/**
	 * Tab key in WooCommerce's product data tabs.
	 */
	public const TAB = 'rx_card';

	/**
	 * Action fired inside the panel; hook it to add fields.
	 */
	public const FIELDS_ACTION = 'rx_core_product_card_fields';

	/**
	 * Add the tab and its panel.
	 */
	public function register(): void {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_panel' ) );
	}

	/**
	 * Add the tab. No hide_if_* / show_if_* classes, so it shows for
	 * every product type. Priority 65 places it after Variations,
	 * before Advanced — and, importantly, not first, so it doesn't
	 * become the tab WooCommerce opens by default on variable products.
	 *
	 * @param array<string,array<string,mixed>> $tabs Existing tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public function add_tab( array $tabs ): array {
		$tabs[ self::TAB ] = array(
			'label'    => __( 'Shop card', 'rx-core' ),
			'target'   => self::TAB . '_product_data',
			'class'    => array(),
			'priority' => 65,
		);

		return $tabs;
	}

	/**
	 * Render the panel wrapper and let fields add themselves.
	 */
	public function render_panel(): void {
		echo '<div id="' . esc_attr( self::TAB . '_product_data' ) . '" class="panel woocommerce_options_panel hidden"><div class="options_group">';

		/**
		 * Fires inside the Shop card tab. Output product fields here.
		 */
		do_action( 'rx_core_product_card_fields' ); // Must stay equal to self::FIELDS_ACTION (PHPCS needs the literal).

		echo '</div></div>';
	}
}
