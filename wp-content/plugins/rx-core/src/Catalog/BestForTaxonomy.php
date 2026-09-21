<?php
/**
 * The "Best for" product taxonomy (e.g. "Functional Training",
 * "Strength", "Road Running").
 *
 * It behaves like Product categories in the admin: the terms are managed
 * under Products > Best for, and on each product's edit screen they're a
 * tick-box list in the side panel (the box shows for every product type,
 * variable ones included). That tick-box UI is what `hierarchical =>
 * true` gives — the terms themselves are a flat list; nothing here
 * relies on parents. As a real taxonomy it also leaves the door open for
 * "filter shop by Best for" later.
 *
 * It's registered here, not in the theme, because it's catalogue data:
 * switching themes must not orphan the terms already assigned to
 * products. The theme only reads it (rx_theme_product_best_for()).
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Catalog;

use RX\Core\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the taxonomy on the product post type.
 */
final class BestForTaxonomy implements Service {

	/**
	 * Taxonomy key. The theme reads terms by this name.
	 */
	public const TAXONOMY = 'rx_best_for';

	/**
	 * Hook registration.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Register the taxonomy. Not public: there are no "Best for" archive
	 * pages or URLs (yet) — it's a label on the card — but the admin UI
	 * and the REST API (used by the block-editor-era tooling and any
	 * future import) work as normal. Permissions reuse WooCommerce's
	 * product-term capabilities so shop managers can manage it the same
	 * way they manage categories.
	 */
	public function register_taxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			'product',
			array(
				'labels'             => array(
					'name'          => _x( 'Best for', 'taxonomy general name', 'rx-core' ),
					'singular_name' => _x( 'Best for', 'taxonomy singular name', 'rx-core' ),
					'menu_name'     => __( 'Best for', 'rx-core' ),
					'all_items'     => __( 'All Best for', 'rx-core' ),
					'edit_item'     => __( 'Edit Best for', 'rx-core' ),
					'update_item'   => __( 'Update Best for', 'rx-core' ),
					'add_new_item'  => __( 'Add new Best for', 'rx-core' ),
					'new_item_name' => __( 'New Best for name', 'rx-core' ),
					'search_items'  => __( 'Search Best for', 'rx-core' ),
					'not_found'     => __( 'No Best for found.', 'rx-core' ),
					'back_to_items' => __( '← Back to Best for', 'rx-core' ),
				),
				'hierarchical'       => true,
				'public'             => false,
				'publicly_queryable' => false,
				'show_ui'            => true,
				'show_in_menu'       => true,
				'show_in_nav_menus'  => false,
				'show_in_quick_edit' => true,
				'show_admin_column'  => true,
				'show_in_rest'       => true,
				'rewrite'            => false,
				'query_var'          => false,
				'capabilities'       => array(
					'manage_terms' => 'manage_product_terms',
					'edit_terms'   => 'edit_product_terms',
					'delete_terms' => 'delete_product_terms',
					'assign_terms' => 'assign_product_terms',
				),
			)
		);
	}
}
