<?php
/**
 * Which two other products fill Pair 2 and Pair 3 of a bundle-eligible
 * product's rotation — the single product page's "Complete Your
 * Rotation" section shows exactly these two, curated by hand, instead
 * of WooCommerce's generic related-products guess (same-category shoes)
 * once they're set.
 *
 * Two searchable product pickers in the product edit screen's "Bundle"
 * tab (RX\Core\Bundles\BundleTab), shown only once "Eligible for
 * bundle" (RX\Core\Bundles\BundleEligibility) is ticked — reuses
 * WooCommerce's own product-search widget (the same one behind
 * Upsells/Cross-sells), not a custom search built from scratch.
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Bundles;

use RX\Core\Service;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the two pickers, saves them, and answers the theme's
 * "what are this product's curated rotation pairs?" question.
 */
final class BundleRotationPairs implements Service {

	/**
	 * Product meta keys — each holds a single product ID (0 = unset).
	 */
	public const PAIR_2_KEY = '_rx_bundle_pair_2_id';
	public const PAIR_3_KEY = '_rx_bundle_pair_3_id';

	/**
	 * Add the fields, their save handler, the admin toggle script, and
	 * the theme-facing filter.
	 */
	public function register(): void {
		add_action( BundleTab::FIELDS_ACTION, array( $this, 'render_fields' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_toggle_script' ) );
		add_filter( 'rx_theme_product_bundle_pair_ids', array( $this, 'filter_pair_ids' ), 10, 2 );
	}

	/**
	 * Render both pickers, wrapped in a container the toggle script
	 * shows/hides based on the eligibility checkbox above it.
	 */
	public function render_fields(): void {
		global $product_object;

		$current_id = $product_object instanceof WC_Product ? $product_object->get_id() : 0;
		$pair_2_id  = $product_object instanceof WC_Product ? (int) $product_object->get_meta( self::PAIR_2_KEY ) : 0;
		$pair_3_id  = $product_object instanceof WC_Product ? (int) $product_object->get_meta( self::PAIR_3_KEY ) : 0;

		echo '<div id="rx_bundle_rotation_pairs">';

		$this->render_picker(
			self::PAIR_2_KEY,
			__( 'Pair 2 product', 'rx-core' ),
			__( 'The product recommended alongside this one to complete a 2-pair rotation.', 'rx-core' ),
			$pair_2_id,
			array( $current_id )
		);

		$this->render_picker(
			self::PAIR_3_KEY,
			__( 'Pair 3 product', 'rx-core' ),
			__( 'The extra product recommended to complete a 3-pair rotation, alongside Pair 2.', 'rx-core' ),
			$pair_3_id,
			// Also excludes whatever Pair 2 currently is, so the same
			// product can't be picked for both — this is the friendly
			// UI half of that rule; save_fields() is the actual guard.
			array_filter( array( $current_id, $pair_2_id ) )
		);

		echo '</div>';
	}

	/**
	 * One WooCommerce product-search field — the same AJAX search widget
	 * behind Upsells/Cross-sells and Grouped products
	 * (class="wc-product-search"), reused rather than rebuilt.
	 * Single-select: only one product per pair. Uses
	 * data-action="woocommerce_json_search_products" (whole products
	 * only) — same widget as Upsells/Cross-sells, but deliberately not
	 * their "_and_variations" search action: a bundle pair is a product
	 * ("pair this shoe with that shoe"), and every one of these products
	 * has many size/colour variations, so the variations-included search
	 * buried the actual product options under dozens of near-duplicate
	 * results per product.
	 *
	 * @param string $meta_key    Meta key this field saves to.
	 * @param string $label       Field label.
	 * @param string $description Help text (desc_tip style).
	 * @param int    $selected_id Currently saved product ID, or 0.
	 * @param int[]  $exclude_ids Product IDs this picker shouldn't offer (itself, and — for Pair 3 — whatever Pair 2 already is).
	 */
	private function render_picker( string $meta_key, string $label, string $description, int $selected_id, array $exclude_ids ): void {
		$selected_product = $selected_id ? wc_get_product( $selected_id ) : null;
		?>
		<p class="form-field">
			<label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $label ); ?></label>
			<select
				class="wc-product-search"
				style="width: 50%;"
				id="<?php echo esc_attr( $meta_key ); ?>"
				name="<?php echo esc_attr( $meta_key ); ?>"
				data-placeholder="<?php esc_attr_e( 'Search for a product&hellip;', 'rx-core' ); ?>"
				data-action="woocommerce_json_search_products"
				data-exclude="<?php echo esc_attr( implode( ',', $exclude_ids ) ); ?>"
			>
				<?php if ( $selected_product ) : ?>
					<option value="<?php echo esc_attr( (string) $selected_id ); ?>" selected="selected">
						<?php echo esc_html( wp_strip_all_tags( $selected_product->get_formatted_name() ) ); ?>
					</option>
				<?php endif; ?>
			</select>
			<?php echo wc_help_tip( $description ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_help_tip() escapes internally. ?>
		</p>
		<?php
	}

	/**
	 * The small script that shows the pickers only while "Eligible for
	 * bundle" is ticked — pure UI convenience (the fields still save
	 * even if briefly visible then hidden), not a security boundary.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_toggle_script( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$file = RX_CORE_DIR . '/assets/js/bundle-rotation-admin.js';

		wp_enqueue_script(
			'rx-core-bundle-rotation-admin',
			RX_CORE_URL . 'assets/js/bundle-rotation-admin.js',
			array( 'jquery' ),
			file_exists( $file ) ? (string) filemtime( $file ) : RX_CORE_VERSION,
			true
		);
	}

	/**
	 * Save both pickers through the product CRUD object. Guards against
	 * picking the product itself, and against picking the same product
	 * for both pairs — either would make the rotation section show this
	 * product recommending itself, or duplicate a pair.
	 *
	 * @param WC_Product $product Product being saved.
	 */
	public function save_fields( WC_Product $product ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by WC_Meta_Box_Product_Data::save() before this action fires.
		$pair_2_id = isset( $_POST[ self::PAIR_2_KEY ] ) ? absint( $_POST[ self::PAIR_2_KEY ] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.
		$pair_3_id = isset( $_POST[ self::PAIR_3_KEY ] ) ? absint( $_POST[ self::PAIR_3_KEY ] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.

		$pair_2_id = $this->sanitize_pair_id( $pair_2_id );
		$pair_3_id = $this->sanitize_pair_id( $pair_3_id );

		if ( $pair_2_id === $product->get_id() ) {
			$pair_2_id = 0;
		}
		if ( $pair_3_id === $product->get_id() || ( $pair_3_id && $pair_3_id === $pair_2_id ) ) {
			$pair_3_id = 0;
		}

		// Read straight from the DB, not $product->get_meta() — at this
		// point in the request that would already reflect the new value
		// on a re-entrant call, and this specific comparison needs the
		// value as it was before this save, to know what changed.
		$old_ids = array_filter(
			array(
				(int) get_post_meta( $product->get_id(), self::PAIR_2_KEY, true ),
				(int) get_post_meta( $product->get_id(), self::PAIR_3_KEY, true ),
			)
		);
		$new_ids = array_filter( array( $pair_2_id, $pair_3_id ) );

		$product->update_meta_data( self::PAIR_2_KEY, $pair_2_id );
		$product->update_meta_data( self::PAIR_3_KEY, $pair_3_id );

		$this->sync_reciprocal_links( $product->get_id(), $old_ids, $new_ids );
	}

	/**
	 * Keeps the relationship two-way: if product A picks product B as a
	 * pair, product B's own edit screen should show A back — a shopper
	 * viewing either product's page should see the other recommended,
	 * not just whichever one was edited first. Concretely: for every
	 * newly-added partner, add this product into that partner's first
	 * empty pair slot (skipped if both of the partner's slots are
	 * already taken by something else — never overwrites another
	 * product's own curated choice); for every partner just removed,
	 * clear this product back out of wherever it was on that partner,
	 * but only if it's still exactly this product sitting there.
	 *
	 * @param int   $product_id This product's ID (the one just saved).
	 * @param int[] $old_ids    Its pair IDs before this save.
	 * @param int[] $new_ids    Its pair IDs after this save.
	 */
	private function sync_reciprocal_links( int $product_id, array $old_ids, array $new_ids ): void {
		foreach ( array_diff( $new_ids, $old_ids ) as $added_id ) {
			$partner = wc_get_product( $added_id );

			if ( ! $partner ) {
				continue;
			}

			$partner_pair_2 = (int) $partner->get_meta( self::PAIR_2_KEY );
			$partner_pair_3 = (int) $partner->get_meta( self::PAIR_3_KEY );

			if ( $product_id === $partner_pair_2 || $product_id === $partner_pair_3 ) {
				continue; // Already linked back.
			}

			if ( ! $partner_pair_2 ) {
				$partner->update_meta_data( self::PAIR_2_KEY, $product_id );
			} elseif ( ! $partner_pair_3 ) {
				$partner->update_meta_data( self::PAIR_3_KEY, $product_id );
			} else {
				continue; // Both of the partner's slots are already someone else's choice.
			}

			$partner->save();
		}

		foreach ( array_diff( $old_ids, $new_ids ) as $removed_id ) {
			$partner = wc_get_product( $removed_id );

			if ( ! $partner ) {
				continue;
			}

			$changed = false;

			if ( $product_id === (int) $partner->get_meta( self::PAIR_2_KEY ) ) {
				$partner->update_meta_data( self::PAIR_2_KEY, 0 );
				$changed = true;
			}
			if ( $product_id === (int) $partner->get_meta( self::PAIR_3_KEY ) ) {
				$partner->update_meta_data( self::PAIR_3_KEY, 0 );
				$changed = true;
			}

			if ( $changed ) {
				$partner->save();
			}
		}
	}

	/**
	 * This product's curated pair IDs, in order, skipping any that no
	 * longer resolve to a real, published product (e.g. one that was
	 * later deleted or trashed) rather than surfacing a broken link.
	 *
	 * @param WC_Product $product Product to check.
	 * @return int[]
	 */
	public static function get_pair_ids( WC_Product $product ): array {
		$ids = array();

		foreach ( array( self::PAIR_2_KEY, self::PAIR_3_KEY ) as $meta_key ) {
			$id = (int) $product->get_meta( $meta_key );

			// 'product' only, not 'product_variation' — belt-and-braces
			// alongside sanitize_pair_id() on the write side, in case a
			// variation ID ever ends up stored some other way (it did
			// once already, from picking a search result before the
			// picker was restricted to whole products only).
			if ( $id && 'publish' === get_post_status( $id ) && 'product' === get_post_type( $id ) ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * A submitted pair ID, or 0 if it doesn't resolve to a real,
	 * standalone product — specifically rejects a product_variation ID
	 * (a shoe's own size/colour option, not a separate product to
	 * recommend), which the search field used to be able to return
	 * before it was restricted to whole-product results only.
	 *
	 * @param int $id Submitted product ID.
	 */
	private function sanitize_pair_id( int $id ): int {
		return $id && 'product' === get_post_type( $id ) ? $id : 0;
	}

	/**
	 * Answer the theme's "what are this product's curated rotation
	 * pairs?" question.
	 *
	 * @param int[]      $ids     Value so far (the theme's default: empty).
	 * @param WC_Product $product Product being rendered.
	 * @return int[]
	 */
	public function filter_pair_ids( array $ids, WC_Product $product ): array {
		unset( $ids ); // The meta is authoritative; the theme default only applies when this plugin is inactive.

		return self::get_pair_ids( $product );
	}
}
