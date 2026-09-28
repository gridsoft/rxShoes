<?php
/**
 * Free gift with purchase: one pair of socks in every order (client,
 * 2026-09-21; PROJECT.md §5).
 *
 * The gift is a real product (its own SKU, stock and order line, so
 * fulfilment and exports see it), picked under WooCommerce → Settings →
 * Products → Free gift. While the cart holds anything else, exactly one
 * of it is kept in the cart: added automatically, quantity forced to 1,
 * price forced to 0, and it can't be removed (a removal just re-adds
 * it). An emptied cart drops it. If the gift product is missing, not
 * purchasable or out of stock, nothing is added.
 *
 * The theme asks "is this line the free gift?" through the
 * rx_theme_cart_item_is_free_gift / rx_theme_order_item_is_free_gift
 * filters and handles the display ("Free" instead of a price, no
 * quantity or remove controls).
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Cart;

use RX\Core\Service;
use WC_Cart;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Keeps the free gift in the cart and marks it on orders.
 */
final class FreeGift implements Service {

	/** Option: the gift product's id. */
	public const OPTION_PRODUCT = 'rx_free_gift_product_id';

	/** Option: 'yes' / 'no' — whether the gift is given at all. */
	public const OPTION_ENABLED = 'rx_free_gift_enabled';

	/** Cart item data flag on the gift line. */
	public const CART_FLAG = 'rx_free_gift';

	/** Order item meta flag on the gift line (hidden). */
	public const ORDER_META = '_rx_free_gift';

	/**
	 * Re-entrancy guard: adding, removing and re-quantifying cart lines
	 * fire the very hooks sync() listens to.
	 *
	 * @var bool
	 */
	private static bool $syncing = false;

	/**
	 * Add hooks.
	 */
	public function register(): void {
		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'sync' ), 20 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'sync' ), 20, 0 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'sync' ), 20, 0 );
		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'sync' ), 20, 0 );
		add_action( 'woocommerce_cart_item_restored', array( $this, 'sync' ), 20, 0 );
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'zero_price' ), 5 );

		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'block_manual_add' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_remove_link', array( $this, 'remove_link' ), 10, 2 );
		add_filter( 'woocommerce_cart_item_quantity', array( $this, 'quantity_html' ), 10, 3 );
		add_filter( 'woocommerce_cart_contents_count', array( $this, 'contents_count' ) );

		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'mark_order_item' ), 10, 3 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_order_meta' ) );

		add_filter( 'rx_theme_cart_item_is_free_gift', array( $this, 'filter_cart_item_is_gift' ), 10, 2 );
		add_filter( 'rx_theme_order_item_is_free_gift', array( $this, 'filter_order_item_is_gift' ), 10, 2 );

		add_action( 'woocommerce_product_options_general_product_data', array( $this, 'render_product_field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product_field' ) );
		add_filter( 'display_post_states', array( $this, 'post_state' ), 10, 2 );

		add_filter( 'woocommerce_get_sections_products', array( $this, 'settings_section' ) );
		add_filter( 'woocommerce_get_settings_products', array( $this, 'settings' ), 10, 2 );
	}

	/**
	 * The gift product, when the gift is on and the product can be given.
	 */
	public static function gift_product(): ?WC_Product {
		if ( 'yes' !== get_option( self::OPTION_ENABLED, 'yes' ) ) {
			return null;
		}

		$product = wc_get_product( (int) get_option( self::OPTION_PRODUCT, 0 ) );

		if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return null;
		}

		return $product;
	}

	/**
	 * Whether a cart line is the free gift: flagged by sync(), or the gift
	 * product however it got there (an "order again", say).
	 *
	 * @param array<string,mixed> $cart_item Cart line.
	 */
	public static function is_gift_line( array $cart_item ): bool {
		if ( ! empty( $cart_item[ self::CART_FLAG ] ) ) {
			return true;
		}

		$gift_id = (int) get_option( self::OPTION_PRODUCT, 0 );

		return $gift_id > 0 && (int) ( $cart_item['product_id'] ?? 0 ) === $gift_id;
	}

	/**
	 * Bring the cart in line with the rule: exactly one gift, quantity 1,
	 * while the cart has anything else; none otherwise.
	 */
	public function sync(): void {
		// Only runs from cart hooks, so the cart exists.
		$cart = WC()->cart;
		if ( self::$syncing ) {
			return;
		}
		self::$syncing = true;

		try {
			$gift      = self::gift_product();
			$gift_keys = array();
			$has_paid  = false;

			foreach ( $cart->get_cart() as $key => $item ) {
				if ( self::is_gift_line( $item ) ) {
					$gift_keys[] = $key;
				} else {
					$has_paid = true;
				}
			}

			if ( ! $gift || ! $has_paid ) {
				foreach ( $gift_keys as $key ) {
					$cart->remove_cart_item( $key );
				}
				return;
			}

			// Keep the first proper gift line; drop duplicates and stale ones.
			$keep = null;
			foreach ( $gift_keys as $key ) {
				$item = $cart->get_cart_item( $key );
				if ( null === $keep && ! empty( $item[ self::CART_FLAG ] ) && (int) $item['product_id'] === $gift->get_id() ) {
					$keep = $key;
					continue;
				}
				$cart->remove_cart_item( $key );
			}

			if ( null === $keep ) {
				$cart->add_to_cart( $gift->get_id(), 1, 0, array(), array( self::CART_FLAG => 1 ) );
			} elseif ( 1 !== (int) $cart->get_cart_item( $keep )['quantity'] ) {
				$cart->set_quantity( $keep, 1, false );
			}
		} finally {
			self::$syncing = false;
		}
	}

	/**
	 * The gift costs nothing, whatever its catalogue price.
	 *
	 * @param WC_Cart $cart Cart being totalled.
	 */
	public function zero_price( WC_Cart $cart ): void {
		foreach ( $cart->get_cart() as $item ) {
			if ( self::is_gift_line( $item ) && $item['data'] instanceof WC_Product ) {
				$item['data']->set_price( '0' );
			}
		}
	}

	/**
	 * Customers can't add the gift themselves; it comes with the order.
	 *
	 * @param bool $passed     Validation so far.
	 * @param int  $product_id Product being added.
	 */
	public function block_manual_add( $passed, $product_id ): bool {
		if ( $passed && 0 < (int) $product_id && (int) get_option( self::OPTION_PRODUCT, 0 ) === (int) $product_id ) {
			wc_add_notice( __( 'This is our free gift — it\'s added to every order automatically.', 'rx-core' ), 'notice' );
			return false;
		}

		return (bool) $passed;
	}

	/**
	 * No remove link on the gift line.
	 *
	 * @param string $link          Remove link HTML.
	 * @param string $cart_item_key Cart line key.
	 */
	public function remove_link( $link, $cart_item_key ): string {
		$item = WC()->cart->get_cart_item( (string) $cart_item_key );

		return $item && self::is_gift_line( $item ) ? '' : (string) $link;
	}

	/**
	 * A fixed "1" instead of a quantity field on the gift line.
	 *
	 * @param string              $html          Quantity input HTML.
	 * @param string              $cart_item_key Cart line key.
	 * @param array<string,mixed> $cart_item     Cart line.
	 */
	public function quantity_html( $html, $cart_item_key, $cart_item = array() ): string {
		unset( $cart_item_key );

		return self::is_gift_line( (array) $cart_item ) ? '1' : (string) $html;
	}

	/**
	 * The header's cart count leaves the gift out: it's not something the
	 * customer added.
	 *
	 * @param int $count Item count.
	 */
	public function contents_count( $count ): int {
		$count = (int) $count;

		// Filtered by WC_Cart itself, so the cart exists.
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( self::is_gift_line( $item ) ) {
				$count -= (int) $item['quantity'];
			}
		}

		return max( 0, $count );
	}

	/**
	 * Flag the gift's order line, so order views and exports can tell it
	 * apart from a paid item.
	 *
	 * @param WC_Order_Item_Product $item          Order line being created.
	 * @param string                $cart_item_key Its cart line key.
	 * @param array<string,mixed>   $values        The cart line.
	 */
	public function mark_order_item( $item, $cart_item_key, $values ): void {
		unset( $cart_item_key );

		if ( self::is_gift_line( (array) $values ) ) {
			$item->add_meta_data( self::ORDER_META, 'yes', true );
		}
	}

	/**
	 * Keep the flag out of the admin order screen's meta list.
	 *
	 * @param string[] $hidden Hidden meta keys.
	 * @return string[]
	 */
	public function hide_order_meta( array $hidden ): array {
		$hidden[] = self::ORDER_META;

		return $hidden;
	}

	/**
	 * Answer the theme's cart-line question.
	 *
	 * @param bool                $is_gift   Answer so far.
	 * @param array<string,mixed> $cart_item Cart line.
	 */
	public function filter_cart_item_is_gift( $is_gift, $cart_item ): bool {
		return (bool) $is_gift || self::is_gift_line( (array) $cart_item );
	}

	/**
	 * Answer the theme's order-line question.
	 *
	 * @param bool  $is_gift Answer so far.
	 * @param mixed $item    Order item.
	 */
	public function filter_order_item_is_gift( $is_gift, $item ): bool {
		return (bool) $is_gift || ( $item instanceof WC_Order_Item_Product && 'yes' === $item->get_meta( self::ORDER_META ) );
	}

	/**
	 * "Free gift with every order" checkbox on the product edit screen
	 * (General tab, simple products) — the same setting as WooCommerce →
	 * Settings → Products → Free gift, shown where people look for it.
	 */
	public function render_product_field(): void {
		global $product_object;

		if ( ! $product_object instanceof WC_Product ) {
			return;
		}

		$is_gift     = (int) get_option( self::OPTION_PRODUCT, 0 ) === $product_object->get_id();
		$settings    = admin_url( 'admin.php?page=wc-settings&tab=products&section=rx_free_gift' );
		$description = $is_gift && 'yes' !== get_option( self::OPTION_ENABLED, 'yes' )
			? __( 'This is the gift product, but the free gift is switched off in the settings.', 'rx-core' )
			: __( 'One of this product is added free to every order. Only one product can be the gift — ticking this replaces the current one.', 'rx-core' );

		echo '<div class="options_group show_if_simple">';
		woocommerce_wp_checkbox(
			array(
				'id'          => '_rx_is_free_gift',
				'label'       => __( 'Free gift with every order', 'rx-core' ),
				'value'       => $is_gift ? 'yes' : 'no',
				'cbvalue'     => 'yes',
				'description' => $description . ' <a href="' . esc_url( $settings ) . '">' . esc_html__( 'Free gift settings', 'rx-core' ) . '</a>',
			)
		);
		echo '</div>';
	}

	/**
	 * Save the checkbox: ticked makes this the gift product (and switches
	 * the gift on); unticked on the current gift product clears it.
	 *
	 * @param WC_Product $product Product being saved.
	 */
	public function save_product_field( WC_Product $product ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce verifies the product edit nonce before this hook.
		$ticked  = isset( $_POST['_rx_is_free_gift'] ) && 'yes' === sanitize_text_field( wp_unslash( $_POST['_rx_is_free_gift'] ) );
		$current = (int) get_option( self::OPTION_PRODUCT, 0 );

		if ( $ticked && $product->is_type( 'simple' ) ) {
			update_option( self::OPTION_PRODUCT, (string) $product->get_id() );
			update_option( self::OPTION_ENABLED, 'yes' );
		} elseif ( ! $ticked && $current === $product->get_id() ) {
			update_option( self::OPTION_PRODUCT, '' );
		}
	}

	/**
	 * "Free gift" label beside the gift product's name in the Products list.
	 *
	 * @param array<string,string> $states Post states.
	 * @param \WP_Post             $post   Post being listed.
	 * @return array<string,string>
	 */
	public function post_state( $states, $post ): array {
		$states = (array) $states;

		if ( 'product' === $post->post_type && (int) get_option( self::OPTION_PRODUCT, 0 ) === $post->ID ) {
			$states['rx_free_gift'] = 'yes' === get_option( self::OPTION_ENABLED, 'yes' )
				? __( 'Free gift', 'rx-core' )
				: __( 'Free gift (switched off)', 'rx-core' );
		}

		return $states;
	}

	/**
	 * "Free gift" section under WooCommerce → Settings → Products.
	 *
	 * @param array<string,string> $sections Sections.
	 * @return array<string,string>
	 */
	public function settings_section( array $sections ): array {
		$sections['rx_free_gift'] = __( 'Free gift', 'rx-core' );

		return $sections;
	}

	/**
	 * The section's fields.
	 *
	 * @param array<int,array<string,mixed>> $settings Settings of the current section.
	 * @param string                         $section  Current section id.
	 * @return array<int,array<string,mixed>>
	 */
	public function settings( array $settings, $section ): array {
		if ( 'rx_free_gift' !== $section ) {
			return $settings;
		}

		$options = array( '' => __( '— None —', 'rx-core' ) );
		foreach ( wc_get_products(
			array(
				'type'    => 'simple',
				'status'  => array( 'publish', 'private' ),
				'limit'   => -1,
				'orderby' => 'title',
				'order'   => 'ASC',
			)
		) as $product ) {
			$options[ (string) $product->get_id() ] = $product->get_name() . ( $product->get_sku() ? ' (' . $product->get_sku() . ')' : '' );
		}

		return array(
			array(
				'title' => __( 'Free gift with every order', 'rx-core' ),
				'type'  => 'title',
				'desc'  => __( 'One of the chosen product is added to every cart that has anything else in it, free, quantity 1, and it can\'t be removed. It keeps its own order line and stock. Pick a simple product (hide it from the catalogue under Catalog visibility). If it\'s out of stock, carts simply don\'t get it.', 'rx-core' ),
				'id'    => 'rx_free_gift_title',
			),
			array(
				'title'   => __( 'Enable', 'rx-core' ),
				'desc'    => __( 'Add the free gift to every order', 'rx-core' ),
				'id'      => self::OPTION_ENABLED,
				'type'    => 'checkbox',
				'default' => 'yes',
			),
			array(
				'title'   => __( 'Gift product', 'rx-core' ),
				'id'      => self::OPTION_PRODUCT,
				'type'    => 'select',
				'class'   => 'wc-enhanced-select',
				'options' => $options,
				'default' => '',
			),
			array(
				'type' => 'sectionend',
				'id'   => 'rx_free_gift_title',
			),
		);
	}
}
