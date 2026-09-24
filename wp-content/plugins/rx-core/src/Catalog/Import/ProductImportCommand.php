<?php
/**
 * WP-CLI command that imports variable products (and their variations)
 * from the normalized shoe-catalogue JSON produced by the offline
 * spreadsheet parser (see wp-content/themes/rx-theme/import/README.md).
 *
 * Idempotent: re-running the same file updates existing products/variations
 * instead of duplicating them. A product is matched by the `_rx_import_handle`
 * meta this command writes on first import; a variation is matched by SKU.
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Catalog\Import;

use RX\Core\Service;
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Product_Variation;
use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers `wp rx import-products` when WP-CLI is present. A no-op on
 * ordinary web requests.
 */
final class ProductImportCommand implements Service {

	/**
	 * Postmeta key linking an imported product back to its source Handle,
	 * so re-running the import updates rather than duplicates it.
	 */
	public const HANDLE_META_KEY = '_rx_import_handle';

	/**
	 * Register the WP-CLI command, if WP-CLI is running.
	 */
	public function register(): void {
		if ( ! defined( 'WP_CLI' ) || ! \WP_CLI ) {
			return;
		}

		WP_CLI::add_command( 'rx import-products', array( $this, 'run' ) );
	}

	/**
	 * Import products from a normalized JSON file.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : Path to the products.json produced by normalize.py.
	 *
	 * [--dry-run]
	 * : Report what would happen without writing anything.
	 *
	 * [--handle=<handle>]
	 * : Only import the single product with this Handle (for testing).
	 *
	 * ## EXAMPLES
	 *
	 *     wp rx import-products wp-content/themes/rx-theme/import/products.json --dry-run
	 *
	 * @param string[]             $args       Positional args.
	 * @param array<string,string> $assoc_args Flags.
	 */
	public function run( array $args, array $assoc_args ): void {
		$file = $args[0] ?? '';

		if ( '' === $file || ! is_readable( $file ) ) {
			WP_CLI::error( "Cannot read file: {$file}" );
			return;
		}

		$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file path passed to a CLI command, not a URL.

		if ( ! is_array( $data ) || ! isset( $data['products'] ) ) {
			WP_CLI::error( 'File is not valid products.json (missing "products" key).' );
			return;
		}

		$dry_run     = isset( $assoc_args['dry-run'] );
		$only_handle = $assoc_args['handle'] ?? null;

		$stats = array(
			'products_created'   => 0,
			'products_updated'   => 0,
			'products_skipped'   => 0,
			'variations_created' => 0,
			'variations_updated' => 0,
			'variations_skipped' => 0,
		);

		foreach ( $data['products'] as $product_row ) {
			if ( $only_handle && $product_row['handle'] !== $only_handle ) {
				continue;
			}

			if ( empty( $product_row['variants'] ) ) {
				++$stats['products_skipped'];
				WP_CLI::log( "SKIP {$product_row['handle']} — no importable variants (unresolved duplicate SKUs)." );
				continue;
			}

			$this->import_product( $product_row, $dry_run, $stats );
		}

		WP_CLI::log( '' );
		WP_CLI::log( ( $dry_run ? '[DRY RUN] ' : '' ) . 'Done.' );
		foreach ( $stats as $key => $value ) {
			WP_CLI::log( "  {$key}: {$value}" );
		}
	}

	/**
	 * Create or update one product and its variations.
	 *
	 * @param array<string,mixed> $row   Normalized product row.
	 * @param bool                $dry_run Whether to skip writes.
	 * @param array<string,int>   $stats Running counters, by reference.
	 */
	private function import_product( array $row, bool $dry_run, array &$stats ): void {
		$handle      = $row['handle'];
		$existing_id = $this->find_product_id_by_handle( $handle );
		$is_new      = null === $existing_id;

		WP_CLI::log( ( $is_new ? 'CREATE' : 'UPDATE' ) . " {$handle} — {$row['title']} ({$row['variant_attr_mode']}, " . count( $row['variants'] ) . ' variants)' );

		if ( $dry_run ) {
			if ( $is_new ) {
				++$stats['products_created'];
			} else {
				++$stats['products_updated'];
			}
			$stats['variations_created'] += count( $row['variants'] );
			return;
		}

		$product = $is_new ? new WC_Product_Variable() : wc_get_product( $existing_id );

		if ( ! $product instanceof WC_Product_Variable ) {
			WP_CLI::warning( "  {$handle}: existing post is not a variable product, skipping." );
			++$stats['products_skipped'];
			return;
		}

		$product->set_name( (string) ( $row['title'] ?? $handle ) );
		$product->set_description( (string) ( $row['body_html'] ?? '' ) );
		$product->set_status( 'publish' );
		$product->update_meta_data( self::HANDLE_META_KEY, $handle );

		if ( ! empty( $row['google_category'] ) ) {
			$product->update_meta_data( '_rx_import_google_category', $row['google_category'] );
		}

		// Collect the distinct colour/size term slugs this product needs,
		// creating any missing terms in the global pa_colour / pa_size
		// attribute taxonomies.
		$colour_slugs         = array();
		$size_slugs           = array();
		$colour_slug_by_value = array();
		$size_slug_by_value   = array();

		foreach ( $row['variants'] as $variant ) {
			if ( ! empty( $variant['colour'] ) ) {
				$slug                                       = $this->get_or_create_attribute_term( 'pa_colour', $variant['colour'] );
				$colour_slug_by_value[ $variant['colour'] ] = $slug;
				$colour_slugs[ $slug ]                      = true;
			}
			if ( ! empty( $variant['size'] ) ) {
				$slug                                   = $this->get_or_create_attribute_term( 'pa_size', $variant['size'] );
				$size_slug_by_value[ $variant['size'] ] = $slug;
				$size_slugs[ $slug ]                    = true;
			}
		}

		// Filter-only facets: Men's size / Women's size, derived from the
		// same variant size strings but assigned at the product level only
		// (never variation=true) — see SizeFilterAttributes for why these
		// exist alongside, not instead of, the purchasable pa_size.
		$mens_size_slugs   = array();
		$womens_size_slugs = array();

		foreach ( $row['variants'] as $variant ) {
			if ( empty( $variant['size'] ) ) {
				continue;
			}
			list( $mens_value, $womens_value ) = $this->parse_size_filter_values( $variant['size'], $row['gender'] ?? 'unisex' );

			if ( null !== $mens_value ) {
				$mens_size_slugs[ $this->get_or_create_attribute_term( 'pa_mens-size', $mens_value ) ] = true;
			}
			if ( null !== $womens_value ) {
				$womens_size_slugs[ $this->get_or_create_attribute_term( 'pa_womens-size', $womens_value ) ] = true;
			}
		}

		$attributes = array();

		if ( ! empty( $colour_slugs ) ) {
			$attributes[] = $this->build_variation_attribute( 'pa_colour', array_keys( $colour_slugs ) );
		}
		if ( ! empty( $size_slugs ) ) {
			$attributes[] = $this->build_variation_attribute( 'pa_size', array_keys( $size_slugs ) );
		}
		if ( ! empty( $mens_size_slugs ) ) {
			$attributes[] = $this->build_filter_attribute( 'pa_mens-size', array_keys( $mens_size_slugs ) );
		}
		if ( ! empty( $womens_size_slugs ) ) {
			$attributes[] = $this->build_filter_attribute( 'pa_womens-size', array_keys( $womens_size_slugs ) );
		}

		$product->set_attributes( $attributes );
		$product_id = $product->save();

		// Category/brand terms are assigned via wp_set_object_terms(), which
		// needs a real post ID — must run after save(), not before (a new
		// product's ID is 0 until saved, and wp_set_object_terms(0, ...)
		// silently does nothing, leaving WordPress to fall back to the
		// default "Uncategorized" term).
		$this->assign_brand( $product_id, $row['brand'] ?? null );
		$this->assign_categories( $product_id, $row['collection'] ?? null, $row['gender'] ?? 'unisex' );

		if ( $is_new ) {
			++$stats['products_created'];
		} else {
			++$stats['products_updated'];
		}

		foreach ( $row['variants'] as $variant ) {
			$this->import_variation(
				$product_id,
				$variant,
				$colour_slug_by_value[ $variant['colour'] ] ?? '',
				$size_slug_by_value[ $variant['size'] ] ?? '',
				$stats
			);
		}

		WC_Product_Variable::sync( $product_id );
		wc_delete_product_transients( $product_id );
	}

	/**
	 * Create or update a single variation, matched by SKU.
	 *
	 * @param int                 $product_id  Parent product ID.
	 * @param array<string,mixed> $variant     Variant row.
	 * @param string              $colour_slug Resolved pa_colour term slug, or ''.
	 * @param string              $size_slug   Resolved pa_size term slug, or ''.
	 * @param array<string,int>   $stats       Running counters, by reference.
	 */
	private function import_variation( int $product_id, array $variant, string $colour_slug, string $size_slug, array &$stats ): void {
		$sku          = (string) $variant['sku'];
		$variation_id = wc_get_product_id_by_sku( $sku );

		if ( $variation_id && (int) wp_get_post_parent_id( $variation_id ) !== $product_id ) {
			WP_CLI::warning( "  SKU {$sku} already belongs to a different product (#{$variation_id}), skipping." );
			++$stats['variations_skipped'];
			return;
		}

		$variation = $variation_id ? wc_get_product( $variation_id ) : new WC_Product_Variation();

		if ( ! $variation instanceof WC_Product_Variation ) {
			++$stats['variations_skipped'];
			return;
		}

		$variation->set_parent_id( $product_id );
		$variation->set_sku( $sku );

		// Taxonomy-based variation attributes must be keyed by the full
		// taxonomy name (e.g. 'pa_size'), not the bare attribute name —
		// see WC_Product_Variation::set_attributes() doc comment.
		$attributes = array();
		if ( $colour_slug ) {
			$attributes['pa_colour'] = $colour_slug;
		}
		if ( $size_slug ) {
			$attributes['pa_size'] = $size_slug;
		}
		$variation->set_attributes( $attributes );

		$price         = $this->to_price( $variant['price'] ?? null );
		$compare_price = $this->to_price( $variant['compare_at_price'] ?? null );

		if ( null !== $compare_price && null !== $price && $compare_price > $price ) {
			$variation->set_regular_price( (string) $compare_price );
			$variation->set_sale_price( (string) $price );
		} elseif ( null !== $price ) {
			$variation->set_regular_price( (string) $price );
			$variation->set_sale_price( '' );
		}

		$variation->set_manage_stock( false );
		$variation->set_stock_status( 'instock' );
		$variation->set_status( 'publish' );

		$variation->save();

		if ( $variation_id ) {
			++$stats['variations_updated'];
		} else {
			++$stats['variations_created'];
		}
	}

	/**
	 * Build a variation-enabled global attribute for the parent product.
	 *
	 * @param string   $taxonomy   e.g. 'pa_colour'.
	 * @param string[] $term_slugs Term slugs to allow on this product.
	 */
	private function build_variation_attribute( string $taxonomy, array $term_slugs ): WC_Product_Attribute {
		$term_ids = array();
		foreach ( $term_slugs as $slug ) {
			$term = get_term_by( 'slug', $slug, $taxonomy );
			if ( $term ) {
				$term_ids[] = $term->term_id;
			}
		}

		$attribute = new WC_Product_Attribute();
		$attribute->set_id( wc_attribute_taxonomy_id_by_name( str_replace( 'pa_', '', $taxonomy ) ) );
		$attribute->set_name( $taxonomy );
		$attribute->set_options( $term_ids );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		return $attribute;
	}

	/**
	 * Build a non-variation (filter-only) global attribute for the parent
	 * product — same shape as build_variation_attribute() but never
	 * selectable as a purchase option, since pa_mens-size / pa_womens-size
	 * exist purely for shop-page filtering (see SizeFilterAttributes).
	 *
	 * @param string   $taxonomy   e.g. 'pa_mens-size'.
	 * @param string[] $term_slugs Term slugs to allow on this product.
	 */
	private function build_filter_attribute( string $taxonomy, array $term_slugs ): WC_Product_Attribute {
		$attribute = $this->build_variation_attribute( $taxonomy, $term_slugs );
		$attribute->set_variation( false );

		return $attribute;
	}

	/**
	 * Derive the Men's-size / Women's-size filter values from one
	 * variant's size string, or [null, null] when it doesn't map to
	 * either (e.g. an EU size — a different scale we have no reliable
	 * conversion for, so it's left out of these filters rather than
	 * guessed).
	 *
	 * Two shapes carry a value here:
	 *  - Paired "M4 W5.5": the same physical pair, both facets get a value.
	 *  - A bare number ("7") on a product whose title marks it as single-
	 *    gender: that number is this gender's size. Plain numbers on a
	 *    unisex-titled product are always the paired-write-up's Men's
	 *    half elsewhere, so appear as pairs, not bare — see the importer's
	 *    Option1/Option2 pairing in normalize.py.
	 *
	 * @param string $size   One variant's size string from the sheet.
	 * @param string $gender Product gender slug: 'men', 'women' or 'unisex'.
	 * @return array{0:?string,1:?string} [mens_value, womens_value].
	 */
	private function parse_size_filter_values( string $size, string $gender ): array {
		if ( preg_match( '/^M(\d+(?:\.\d+)?)\s*W(\d+(?:\.\d+)?)$/i', $size, $m ) ) {
			return array( $m[1], $m[2] );
		}

		if ( preg_match( '/^\d+(?:\.\d+)?$/', $size ) ) {
			if ( 'men' === $gender ) {
				return array( $size, null );
			}
			if ( 'women' === $gender ) {
				return array( null, $size );
			}
		}

		return array( null, null );
	}

	/**
	 * Find or create a term in a global product-attribute taxonomy
	 * (pa_colour, pa_size), returning its slug.
	 *
	 * @param string $taxonomy Attribute taxonomy, e.g. 'pa_colour'.
	 * @param string $value    Term name.
	 * @return string Term slug, or '' if it couldn't be created.
	 */
	private function get_or_create_attribute_term( string $taxonomy, string $value ): string {
		$term = get_term_by( 'name', $value, $taxonomy );

		if ( $term ) {
			return $term->slug;
		}

		$result = wp_insert_term( $value, $taxonomy );

		if ( is_wp_error( $result ) ) {
			WP_CLI::warning( "  Could not create {$taxonomy} term '{$value}': " . $result->get_error_message() );
			return '';
		}

		$term = get_term( $result['term_id'], $taxonomy );

		return $term ? $term->slug : '';
	}

	/**
	 * Assign the WooCommerce Brands term for a Vendor name, creating it if
	 * it doesn't already exist.
	 *
	 * @param int         $product_id Product to assign the brand to.
	 * @param string|null $brand      Vendor name from the sheet.
	 */
	private function assign_brand( int $product_id, ?string $brand ): void {
		if ( ! $brand || ! taxonomy_exists( 'product_brand' ) ) {
			return;
		}

		$term = get_term_by( 'name', $brand, 'product_brand' );

		if ( ! $term ) {
			$result = wp_insert_term( $brand, 'product_brand' );
			$term   = is_wp_error( $result ) ? null : get_term( $result['term_id'], 'product_brand' );
		}

		if ( $term ) {
			wp_set_object_terms( $product_id, array( (int) $term->term_id ), 'product_brand', false );
		}
	}

	/**
	 * Assign product categories: the Collection (model line) plus the
	 * Men/Women/Unisex gender term, both in product_cat, both created on
	 * demand except gender which always pre-exists on this site.
	 *
	 * @param int         $product_id Product to categorise.
	 * @param string|null $collection Collection (model line) name.
	 * @param string      $gender     Gender category slug.
	 */
	private function assign_categories( int $product_id, ?string $collection, string $gender ): void {
		$term_ids = array();

		$gender_term = get_term_by( 'slug', $gender, 'product_cat' );
		if ( $gender_term ) {
			$term_ids[] = (int) $gender_term->term_id;
		}

		if ( $collection ) {
			$collection_term = get_term_by( 'name', $collection, 'product_cat' );
			if ( ! $collection_term ) {
				$result          = wp_insert_term( $collection, 'product_cat' );
				$collection_term = is_wp_error( $result ) ? null : get_term( $result['term_id'], 'product_cat' );
			}
			if ( $collection_term ) {
				$term_ids[] = (int) $collection_term->term_id;
			}
		}

		if ( $term_ids ) {
			wp_set_object_terms( $product_id, $term_ids, 'product_cat', false );
		}
	}

	/**
	 * Look up an existing product by the import Handle we stamped on it
	 * last time this command ran.
	 *
	 * @param string $handle Import Handle from the sheet.
	 * @return int|null Product ID, or null if not imported before.
	 */
	private function find_product_id_by_handle( string $handle ): ?int {
		$posts = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::HANDLE_META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => $handle, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return $posts ? (int) $posts[0] : null;
	}

	/**
	 * Parse a price string from the spreadsheet into a float, or null.
	 *
	 * @param mixed $raw Price cell value.
	 */
	private function to_price( $raw ): ?float {
		if ( null === $raw || '' === $raw ) {
			return null;
		}

		return (float) $raw;
	}
}
