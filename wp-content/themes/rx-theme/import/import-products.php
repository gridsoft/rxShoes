<?php
/**
 * Import the Shopify-format product CSV (twl_com_au__edited_with_links_v2.csv)
 * into WooCommerce: one variable product per model, colour + size
 * variations, images downloaded from the "Edited Variant Image Link"
 * Google Drive links. See client-questions.md for the decisions behind the
 * rules below (skipped duplicates, colour names, TWL text removal).
 *
 * Re-runnable: products are matched by their Shopify handle
 * (_rx_import_handle), variations by SKU, images by Drive file ID
 * (_rx_drive_id), so a second run updates instead of duplicating.
 *
 * Usage (from this folder):
 *   php import-products.php [--skip-images] [--only=<handle>] [--dry-run]
 *
 * Lives in the theme's import/ folder, which the deploy workflow excludes.
 *
 * @package RX_Theme
 */

// phpcs:disable -- one-off CLI tool, not theme runtime code.

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

define( 'WP_USE_THEMES', false );
require __DIR__ . '/../../../../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

set_time_limit( 0 );
ini_set( 'memory_limit', '1024M' );
wp_suspend_cache_invalidation( false );

$opts = getopt( '', array( 'skip-images', 'only:', 'dry-run', 'file:' ) );
$file = $opts['file'] ?? __DIR__ . '/twl_com_au__edited_with_links_v2.csv';

/* ------------------------------------------------------------------ */
/* Decisions (see client-questions.md)                                 */
/* ------------------------------------------------------------------ */

// Duplicates: the combined Nano X5 Edge repeats the Men's + Women's
// products; the two TYR L-2 handles are identical (keep one).
const RX_IMPORT_SKIP = array( 'reebok-nano-x5-edge-black-white', 'tyr-l-2-lifter-white-gum' );

// Colourways the CSV has no colour name for, keyed by their Drive image ID.
const RX_IMPORT_DRIVE_COLOURS = array(
	'1bNrO1mvmkeRF4aZPsQlqN1dZH8k61oWO' => 'Arctic Wolf',
	'1rdABDEfo29p0SOAMr8ufjJ6eIX55RhPD' => 'Olive',
	'1YxdzALGkTT5OGXXcfGKVxiuMi5T2e1E6' => 'Eclipse',
	'1XnDBh7gVW0HN2KE22WfF7zFAx8xXCv0x' => 'Bright White/Pink',
	'1s2bIiay2fHTRRpwROdxTvhEBu_onpCaV' => 'Midnight',
	'1MP2-zpr0XxYzsFfK7zjpHFuek2zvvuUy' => 'Pink',
	'1ZwIFiIEoCbl9Xddp8Gv_lxQ64AfGP1JM' => 'Obsidian',
	'1Dd2aYNsHKznEMVxRjIK4LglP37Ik9-E7' => 'Mineral',
	'18TNCfDLv_rZcN5RL-2WAuutQEAWB8GZN' => 'Midnight',
	'1qJQaGX7tUNL-jiXPtEVCtxMsAIvUZpwJ' => 'Green',
	'1yfGKjlXJbGagE8nfUGGf5nNIkqHHargA' => 'White',
	'1IMqDtFC9NTqQ3BsBg-1dmPpEZ__5oKTf' => 'Grey',
	'1Topt0cT98oo2d00OzaNA1qPFVf6h0PjB' => 'Black & White',
	'1WJpdjAwzOi9fQRE-2osEUW34aXWEvOM9' => 'White/Gum',
);

const RX_IMPORT_VENDORS = array(
	'LUXIAOJUN Australia' => 'LUXIAOJUN',
);

// Swatch colours: the last matching word in each "/"-separated part wins.
const RX_IMPORT_COLOUR_WORDS = array(
	'white' => '#f5f5f5', 'ftwr white' => '#f5f5f5', 'ftwwht' => '#f5f5f5', 'cloud white' => '#f5f5f5', 'bright white' => '#fafafa', 'porcelain' => '#efeae2', 'arctic wolf' => '#e7e1d4',
	'black' => '#111111', 'cblack' => '#111111', 'core black' => '#111111', 'obsidian' => '#1f2126', 'eclipse' => '#1c1c1c', 'anthracite' => '#383838',
	'grey' => '#8c8c8c', 'gray' => '#8c8c8c', 'silver' => '#c0c0c0', 'platinum' => '#d9d9d6', 'wolf grey' => '#9ea3a8', 'iron grey' => '#56585c', 'pumice' => '#cfc7b8', 'smoke' => '#6d6e70', 'slate' => '#5b6770', 'pencil point' => '#5e5e60', 'mineral' => '#d8dbd7', 'fog' => '#c9cbcc', 'dshgry' => '#595b5d', 'ntgrey' => '#7a7c7e', 'clear' => '#e8e8e8', 'metallic' => '#b8b8b8',
	'red' => '#d0021b', 'cherry' => '#e3264b', 'crimson' => '#dc143c', 'flame' => '#ff5a1f', 'lucred' => '#e10600', 'sport red' => '#c8102e', 'coral' => '#ff6f61', 'lava' => '#e24a33',
	'pink' => '#f4a6c0', 'peony' => '#f7a8b8', 'lucpnk' => '#ff5fa2', 'laser pink' => '#ff4fa3', 'atomic pink' => '#ff4f93', 'lavender' => '#b9a7d9',
	'orange' => '#ff7a00', 'flash orange' => '#ff6a13', 'smash orange' => '#ff6a13', 'solar red' => '#ff3b30', 'energy' => '#ff8a00',
	'yellow' => '#ffd400', 'lime' => '#b5e61d', 'acid' => '#c6f000', 'acid green' => '#b4f000', 'flame green' => '#5fd43a', 'vivid green' => '#3ccf4e', 'lilac' => '#c8a2c8', 'digital lime' => '#c6ff00', 'neon cherry' => '#ff2d55', 'light lavender' => '#c9b8e8', 'thistle' => '#d8bfd8', 'chalk' => '#e9e4d8', 'grey3' => '#8c8c8c', 'leopard' => '#c19a6b', 'solar acid yellow' => '#e8f000', 'soltur' => '#1cc6c6', 'volt' => '#d7ff00', 'gold' => '#c9a13b', 'olympic gold' => '#c9a13b',
	'green' => '#2e8b57', 'mint' => '#98e2c6', 'intense mint' => '#7fe0c0', 'ranger green' => '#4b5320', 'pukie green' => '#9acd32', 'olive' => '#6b6b3a', 'aqua' => '#27c3c9', 'atomic aqua' => '#14d1d1',
	'blue' => '#1f5fbf', 'sport blue' => '#1e6fd9', 'navy' => '#1b2a4a', 'midnight' => '#1d2742', 'sapphire' => '#0f52ba', 'tint' => '#cfe3f3', 'blue tint' => '#cfe3f3', 'armory navy' => '#243447', 'inky' => '#1d2b3a', 'sea' => '#5f9ea0', 'soothing sea' => '#8fbcbb', 'cyan' => '#00bcd4',
	'purple' => '#6b3fa0', 'brown' => '#7b4a2d', 'gum' => '#b5793f', 'sesame' => '#d8b98a', 'tan' => '#c9a27e', 'beige' => '#d9c9a8', 'cream' => '#efe6d2', 'sand' => '#d8c3a0', 'dark shadow' => '#2c2c2c', 'shadow' => '#3a3a3a',
);

/* ------------------------------------------------------------------ */
/* Helpers                                                             */
/* ------------------------------------------------------------------ */

function rx_imp_log( string $msg ): void {
	echo $msg . "\n";
}

function rx_imp_drive_id( string $url ): string {
	return preg_match( '~/d/([A-Za-z0-9_-]+)~', $url, $m ) ? $m[1] : '';
}

function rx_imp_is_measure( string $v ): bool {
	return (bool) preg_match( '~^US(-(Men|Women)(/Women)?)?$~i', trim( $v ) );
}

function rx_imp_is_size( string $v ): bool {
	$v = trim( $v );
	return (bool) preg_match( '~^(M\s*\d+(\.\d+)?\s*/?\s*W\s*\d+(\.\d+)?|\d+(\.\d+)?(\s*eu)?|\d+(\.\d+)?\s*EU|\d+(\.\d+)?Y?\s*(US\s*)?\(.*EU\))$~i', $v );
}

/** One consistent size label: "M10 W11.5", "7", "40eu", "7 US (40 EU)", "5Y (37.5 EU)". */
function rx_imp_size( string $v ): string {
	$v = preg_replace( '/\s+/', ' ', trim( $v ) );
	if ( preg_match( '~^M\s*(\d+(?:\.\d+)?)\s*/?\s*W\s*(\d+(?:\.\d+)?)$~i', $v, $m ) ) {
		return "M{$m[1]} W{$m[2]}";
	}
	if ( preg_match( '~^(\d+(?:\.\d+)?)\s*eu$~i', $v, $m ) ) {
		return "{$m[1]}eu";
	}
	if ( preg_match( '~^(\d+(?:\.\d+)?)\s*US\s*\(\s*(\d+(?:\.\d+)?)\s*EU\s*\)$~i', $v, $m ) ) {
		return "{$m[1]} US ({$m[2]} EU)";
	}
	if ( preg_match( '~^(\d+(?:\.\d+)?)Y\s*\(\s*(\d+(?:\.\d+)?)\s*EU\s*\)$~i', $v, $m ) ) {
		return "{$m[1]}Y ({$m[2]} EU)";
	}
	return $v;
}

/** Sort key for a size label, so size swatches list in order. */
function rx_imp_size_order( string $size ): int {
	if ( preg_match( '~^(\d+(?:\.\d+)?)Y~', $size, $m ) ) {
		return (int) ( (float) $m[1] * 10 );
	}
	if ( preg_match( '~(\d+(?:\.\d+)?)eu$~', $size, $m ) ) {
		return 2000 + (int) ( (float) $m[1] * 10 );
	}
	return preg_match( '~(\d+(?:\.\d+)?)~', $size, $m ) ? 1000 + (int) ( (float) $m[1] * 10 ) : 9999;
}

/** [mens, womens] filter values for one size, or nulls. */
function rx_imp_filter_sizes( string $size, string $measure, string $gender ): array {
	if ( preg_match( '~^M(\S+) W(\S+)$~', $size, $m ) ) {
		return array( $m[1], $m[2] );
	}
	if ( preg_match( '~^(\d+(?:\.\d+)?) US \(~', $size, $m ) ) {
		return array( $m[1], null );
	}
	if ( preg_match( '~^\d+(\.\d+)?$~', $size ) ) {
		$who = stripos( $measure, 'women' ) !== false && stripos( $measure, 'men/' ) === false ? 'women'
			: ( preg_match( '~^US-Men$~i', $measure ) ? 'men' : $gender );
		return 'women' === $who ? array( null, $size ) : ( 'men' === $who ? array( $size, null ) : array( null, null ) );
	}
	return array( null, null );
}

function rx_imp_title_colour( string $title ): string {
	if ( preg_match( '~\(([^)]+)\)\s*$~', $title, $m ) ) {
		return trim( $m[1] );
	}
	$parts = array_map( 'trim', explode( ' - ', $title ) );
	return count( $parts ) > 1 ? end( $parts ) : '';
}

function rx_imp_title_without_colour( string $title ): string {
	if ( preg_match( '~^(.*?)\s*\([^)]+\)\s*$~', $title, $m ) ) {
		return trim( $m[1] );
	}
	$parts = array_map( 'trim', explode( ' - ', $title ) );
	if ( count( $parts ) > 2 ) {
		array_pop( $parts );
	}
	return implode( ' - ', $parts );
}

function rx_imp_gender( string $title, array $measures, bool $paired ): string {
	if ( preg_match( "~\bwomen'?s\b~i", $title ) ) {
		return 'women';
	}
	if ( preg_match( "~\bmen'?s\b~i", $title ) ) {
		return 'men';
	}
	if ( ! $paired && $measures ) {
		$u = array_unique( array_map( 'strtolower', $measures ) );
		if ( array( 'us-women' ) === $u ) {
			return 'women';
		}
		if ( array( 'us-men' ) === $u ) {
			return 'men';
		}
	}
	return 'unisex';
}

/** [type label, best-for terms] from the title and tags. */
function rx_imp_best_for( string $title, string $tags ): array {
	$t = strtolower( $title . ' ' . $tags );
	$best = array();
	if ( preg_match( '~lifter|weightlifting|romaleos|savaleos|powerpro|legacy lifter~', strtolower( $title ) ) ) {
		$best[] = 'Weightlifting';
	}
	if ( preg_match( '~runner|running|deviate|rnr|trail~', strtolower( $title ) ) ) {
		$best[] = 'Running';
	}
	if ( preg_match( '~vivobarefoot|barefoot|dropzero|nature trainers|primus|motus~', $t ) ) {
		$best[] = 'Barefoot';
	}
	if ( preg_match( '~recovery|slides|zenglide~', $t ) ) {
		$best[] = 'Recovery';
	}
	if ( strpos( $t, 'hyrox' ) !== false ) {
		$best[] = 'HYROX';
	}
	if ( ! $best || preg_match( '~trainer|metcon|nano|cxt|training|x-load|xt-motion|gym|one v2|r-1|synth|zero|pro~', strtolower( $title ) ) ) {
		$best[] = 'Training';
	}
	$best = array_values( array_unique( $best ) );
	return array( $best[0], $best );
}

/** Remove TWL-only paragraphs, hotlinked images and Shopify editor attributes. */
function rx_imp_clean_body( string $html ): string {
	$html = preg_replace( '~<img[^>]*>~i', '', $html );
	$html = preg_replace( '~\s(data-mce-[a-z-]+|data-start|data-end)="[^"]*"~i', '', $html );
	// Drop any block element mentioning TWL, plus "BUYING … IN AUSTRALIA" headings.
	$html = preg_replace( '~<(p|li|div|h[1-6]|span)\b[^>]*>(?:(?!</\1>).)*?(TWL|twl\.com\.au|marketplace partner|BUYING [^<]* IN AUSTRALIA)(?:(?!</\1>).)*?</\1>~is', '', $html );
	// Any leftover bare mentions.
	$html = preg_replace( '~[^.<>]*\bTWL\b[^.<>]*\.?~', '', $html );
	$html = preg_replace( '~<(p|li|strong|span)[^>]*>\s*(&nbsp;|<br\s*/?>|\s)*</\1>~i', '', $html );
	return trim( $html );
}

function rx_imp_ensure_attribute( string $slug, string $label ): void {
	if ( ! wc_attribute_taxonomy_id_by_name( $slug ) ) {
		wc_create_attribute( array( 'name' => $label, 'slug' => $slug, 'type' => 'select', 'order_by' => 'menu_order', 'has_archives' => false ) );
		delete_transient( 'wc_attribute_taxonomies' );
		WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
	}
	$tax = 'pa_' . $slug;
	if ( ! taxonomy_exists( $tax ) ) {
		register_taxonomy( $tax, array( 'product', 'product_variation' ), array( 'hierarchical' => false, 'show_ui' => false, 'query_var' => true, 'rewrite' => false ) );
	}
}

function rx_imp_term( string $taxonomy, string $name, array $meta = array() ): int {
	$term = get_term_by( 'name', $name, $taxonomy );
	if ( ! $term ) {
		$res = wp_insert_term( $name, $taxonomy );
		if ( is_wp_error( $res ) ) {
			$existing = $res->get_error_data( 'term_exists' );
			if ( ! $existing ) {
				rx_imp_log( "  ! term {$taxonomy} '{$name}': " . $res->get_error_message() );
				return 0;
			}
			$term_id = (int) $existing;
		} else {
			$term_id = (int) $res['term_id'];
		}
		foreach ( $meta as $k => $v ) {
			update_term_meta( $term_id, $k, $v );
		}
		return $term_id;
	}
	// Fill meta (e.g. a swatch colour added to the word list later) only where still empty,
	// so anything set by hand in the admin is kept.
	foreach ( $meta as $k => $v ) {
		if ( '' === (string) get_term_meta( $term->term_id, $k, true ) ) {
			update_term_meta( $term->term_id, $k, $v );
		}
	}
	return (int) $term->term_id;
}

/** Up to two swatch colours: the first two distinct colour words, in order, longest match first. */
function rx_imp_swatch( string $colour ): array {
	static $pattern = null;
	if ( null === $pattern ) {
		$words = array_keys( RX_IMPORT_COLOUR_WORDS );
		usort( $words, fn( $a, $b ) => strlen( $b ) <=> strlen( $a ) );
		$pattern = '~\b(' . implode( '|', array_map( fn( $w ) => preg_quote( $w, '~' ), $words ) ) . ')\b~';
	}
	$text = preg_replace( '~[/&\-]+~', ' ', strtolower( $colour ) );
	preg_match_all( $pattern, $text, $m );
	$hex = array();
	foreach ( $m[1] as $word ) {
		$h = RX_IMPORT_COLOUR_WORDS[ $word ];
		if ( ! in_array( $h, $hex, true ) ) {
			$hex[] = $h;
		}
		if ( count( $hex ) >= 2 ) {
			break;
		}
	}
	return $hex;
}

/** Attachment ID for a Drive image, downloading it once. */
function rx_imp_image( string $drive_id, string $filename, string $alt, int $parent, bool $skip ): int {
	if ( '' === $drive_id ) {
		return 0;
	}
	$found = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_rx_drive_id', 'meta_value' => $drive_id ) );
	if ( $found ) {
		return (int) $found[0];
	}
	if ( $skip ) {
		return 0;
	}
	$tmp = download_url( 'https://drive.usercontent.google.com/download?id=' . rawurlencode( $drive_id ) . '&export=download', 60 );
	if ( is_wp_error( $tmp ) ) {
		rx_imp_log( "  ! image {$drive_id}: " . $tmp->get_error_message() );
		return 0;
	}
	$mime = wp_get_image_mime( $tmp );
	$ext  = array( 'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp' )[ $mime ] ?? '';
	if ( ! $ext ) {
		@unlink( $tmp );
		rx_imp_log( "  ! image {$drive_id}: not an image ({$mime})" );
		return 0;
	}
	$name = sanitize_file_name( pathinfo( $filename, PATHINFO_FILENAME ) ?: $drive_id ) . '.' . $ext;
	$id   = media_handle_sideload( array( 'name' => $name, 'tmp_name' => $tmp ), $parent, $alt );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp );
		rx_imp_log( "  ! image {$drive_id}: " . $id->get_error_message() );
		return 0;
	}
	update_post_meta( $id, '_rx_drive_id', $drive_id );
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return (int) $id;
}

/* ------------------------------------------------------------------ */
/* Read the CSV                                                        */
/* ------------------------------------------------------------------ */

$fh     = fopen( $file, 'r' );
$header = fgetcsv( $fh, 0, ',', '"', '' );
$groups = array();
while ( ( $r = fgetcsv( $fh, 0, ',', '"', '' ) ) !== false ) {
	$r                         = array_combine( $header, $r );
	$groups[ $r['Handle'] ][] = $r;
}
fclose( $fh );

rx_imp_ensure_attribute( 'colour', 'Colour' );
rx_imp_ensure_attribute( 'size', 'Size' );
rx_imp_ensure_attribute( 'mens-size', "Men's size" );
rx_imp_ensure_attribute( 'womens-size', "Women's size" );

$cats = array();
foreach ( array( 'men' => 'Men', 'women' => 'Women', 'unisex' => 'Unisex' ) as $slug => $name ) {
	$t = get_term_by( 'slug', $slug, 'product_cat' );
	$cats[ $slug ] = $t ? (int) $t->term_id : (int) ( wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) )['term_id'] ?? 0 );
}

$stats = array( 'created' => 0, 'updated' => 0, 'skipped' => 0, 'variations' => 0, 'dupes' => 0 );

foreach ( $groups as $handle => $rows ) {
	if ( isset( $opts['only'] ) && $opts['only'] !== $handle ) {
		continue;
	}
	if ( in_array( $handle, RX_IMPORT_SKIP, true ) ) {
		++$stats['skipped'];
		rx_imp_log( "SKIP {$handle} (duplicate)" );
		continue;
	}

	$head        = $rows[0];
	$title       = trim( preg_replace( '/[\s\x{00A0}]+/u', ' ', $head['Title'] ) );
	$title_col   = rx_imp_title_colour( $title );
	$variants    = array();
	$measures    = array();
	$paired      = false;
	$seen        = array();

	foreach ( $rows as $r ) {
		if ( '' === $r['Variant SKU'] ) {
			continue;
		}
		$vals    = array_values( array_filter( array_map( 'trim', array( $r['Option1 Value'], $r['Option2 Value'], $r['Option3 Value'] ) ), 'strlen' ) );
		$measure = '';
		$size    = '';
		$colour  = '';
		foreach ( $vals as $v ) {
			if ( ! $measure && rx_imp_is_measure( $v ) ) {
				$measure = $v;
			} elseif ( ! $size && rx_imp_is_size( $v ) ) {
				$size = rx_imp_size( $v );
			} elseif ( ! $colour ) {
				$colour = $v;
			}
		}
		$drive = rx_imp_drive_id( $r['Edited Variant Image Link'] );
		if ( ! $colour ) {
			$colour = RX_IMPORT_DRIVE_COLOURS[ $drive ] ?? $title_col;
		}
		$key = strtolower( $colour . '|' . $size );
		if ( isset( $seen[ $key ] ) ) {
			++$stats['dupes'];
			continue;
		}
		$seen[ $key ] = true;
		$paired       = $paired || (bool) preg_match( '~^M\S+ W~', $size );
		if ( $measure ) {
			$measures[] = $measure;
		}
		$variants[] = array(
			'sku'     => trim( $r['Variant SKU'] ),
			'colour'  => $colour,
			'size'    => $size,
			'measure' => $measure,
			'price'   => $r['Variant Price'],
			'compare' => $r['Variant Compare At Price'],
			'barcode' => trim( $r['Variant Barcode'] ),
			'grams'   => $r['Variant Grams'],
			'qty'     => max( 0, (int) $r['Variant Inventory Qty'] ), // Negative (oversold) counts as 0.
			'drive'   => $drive,
			'file'    => $r['Image File'],
		);
	}

	$colours     = array_values( array_unique( array_column( $variants, 'colour' ) ) );
	$gender      = rx_imp_gender( $title, $measures, $paired );
	$clean_title = count( $colours ) > 1 ? rx_imp_title_without_colour( $title ) : $title;
	$brand       = RX_IMPORT_VENDORS[ $head['Vendor'] ] ?? $head['Vendor'];
	list( $type_label, $best_for ) = rx_imp_best_for( $title, $head['Tags'] );

	$existing = get_posts( array( 'post_type' => 'product', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_rx_import_handle', 'meta_value' => $handle ) );
	rx_imp_log( ( $existing ? 'UPDATE' : 'CREATE' ) . " {$clean_title} — " . count( $variants ) . ' variants, ' . count( $colours ) . " colours, {$gender}, {$type_label}" );

	if ( isset( $opts['dry-run'] ) ) {
		foreach ( array_slice( $variants, 0, 3 ) as $v ) {
			rx_imp_log( "    {$v['sku']} | {$v['colour']} | {$v['size']} | " . implode( '/', array_map( fn( $x ) => $x ?? '-', rx_imp_filter_sizes( $v['size'], $v['measure'], $gender ) ) ) . ' | ' . implode( ' ', rx_imp_swatch( $v['colour'] ) ) );
		}
		continue;
	}

	$product = $existing ? wc_get_product( $existing[0] ) : new WC_Product_Variable();
	$product->set_name( $clean_title );
	if ( ! $existing ) {
		$product->set_slug( sanitize_title( $clean_title ) );
	}
	$product->set_description( rx_imp_clean_body( $head['Body (HTML)'] ) );
	$product->set_status( 'publish' );
	$product->set_catalog_visibility( 'visible' );
	$product->update_meta_data( '_rx_import_handle', $handle );
	$product->update_meta_data( '_rx_bundle_eligible', 'yes' );
	$product->update_meta_data( '_rx_type_label', $type_label );
	$product->update_meta_data( '_rx_seo_title', trim( preg_replace( '~\s*[–-]\s*TWL\s*$~i', '', $head['SEO Title'] ) ) );
	$product->update_meta_data( '_rx_seo_description', trim( preg_replace( '~\bTWL\b~', 'RX Shoes', $head['SEO Description'] ) ) );

	// Attribute terms.
	$colour_terms = array();
	$size_terms   = array();
	$mens         = array();
	$womens       = array();
	foreach ( $variants as $v ) {
		if ( ! isset( $colour_terms[ $v['colour'] ] ) ) {
			$hex  = rx_imp_swatch( $v['colour'] );
			$meta = array();
			if ( $hex ) {
				$meta['_rx_swatch_1'] = $hex[0];
				if ( isset( $hex[1] ) && $hex[1] !== $hex[0] ) {
					$meta['_rx_swatch_2'] = $hex[1];
				}
			}
			$colour_terms[ $v['colour'] ] = rx_imp_term( 'pa_colour', $v['colour'], $meta );
		}
		if ( $v['size'] && ! isset( $size_terms[ $v['size'] ] ) ) {
			$size_terms[ $v['size'] ] = rx_imp_term( 'pa_size', $v['size'], array( 'order' => rx_imp_size_order( $v['size'] ) ) );
		}
		list( $m, $w ) = rx_imp_filter_sizes( $v['size'], $v['measure'], $gender );
		if ( null !== $m ) {
			$mens[ $m ] = rx_imp_term( 'pa_mens-size', $m, array( 'order' => rx_imp_size_order( $m ) ) );
		}
		if ( null !== $w ) {
			$womens[ $w ] = rx_imp_term( 'pa_womens-size', $w, array( 'order' => rx_imp_size_order( $w ) ) );
		}
	}

	$attrs = array();
	$pos   = 0;
	foreach ( array( 'pa_colour' => array( $colour_terms, true ), 'pa_size' => array( $size_terms, true ), 'pa_mens-size' => array( $mens, false ), 'pa_womens-size' => array( $womens, false ) ) as $tax => $spec ) {
		if ( ! $spec[0] ) {
			continue;
		}
		$a = new WC_Product_Attribute();
		$a->set_id( wc_attribute_taxonomy_id_by_name( substr( $tax, 3 ) ) );
		$a->set_name( $tax );
		$a->set_options( array_values( array_filter( $spec[0] ) ) );
		$a->set_position( $pos++ );
		$a->set_visible( true );
		$a->set_variation( $spec[1] );
		$attrs[] = $a;
	}
	$product->set_attributes( $attrs );
	$product_id = $product->save();

	wp_set_object_terms( $product_id, array( $cats[ $gender ] ), 'product_cat' );
	wp_set_object_terms( $product_id, array( rx_imp_term( 'product_brand', $brand ) ), 'product_brand' );
	if ( taxonomy_exists( 'rx_best_for' ) ) {
		wp_set_object_terms( $product_id, array_map( fn( $b ) => rx_imp_term( 'rx_best_for', $b ), $best_for ), 'rx_best_for' );
	}

	// Images: gallery from the positioned rows, one image per colourway.
	$skip_images = isset( $opts['skip-images'] );
	$gallery     = array();
	$positioned  = array_filter( $rows, fn( $r ) => '' !== $r['Image Position'] );
	usort( $positioned, fn( $a, $b ) => (int) $a['Image Position'] <=> (int) $b['Image Position'] );
	foreach ( $positioned as $r ) {
		$id = rx_imp_image( rx_imp_drive_id( $r['Edited Variant Image Link'] ), $r['Image File'], $clean_title, $product_id, $skip_images );
		if ( $id ) {
			$gallery[] = $id;
		}
	}
	$colour_image = array();
	foreach ( $variants as $v ) {
		if ( ! isset( $colour_image[ $v['drive'] ] ) ) {
			$colour_image[ $v['drive'] ] = rx_imp_image( $v['drive'], $v['file'], $clean_title . ' – ' . $v['colour'], $product_id, $skip_images );
		}
	}
	$gallery = array_values( array_unique( array_filter( $gallery ) ) );
	$main    = $gallery ? array_shift( $gallery ) : (int) reset( $colour_image );
	$product = wc_get_product( $product_id );
	$product->set_image_id( $main );
	$product->set_gallery_image_ids( $gallery );
	$product->save();

	// Variations.
	$keep = array();
	foreach ( $variants as $v ) {
		$vid       = wc_get_product_id_by_sku( $v['sku'] );
		$variation = $vid ? wc_get_product( $vid ) : new WC_Product_Variation();
		if ( $vid && (int) $variation->get_parent_id() !== $product_id ) {
			rx_imp_log( "  ! SKU {$v['sku']} belongs to another product, skipped" );
			continue;
		}
		$variation->set_parent_id( $product_id );
		$variation->set_sku( $v['sku'] );
		$va = array( 'pa_colour' => get_term( $colour_terms[ $v['colour'] ] )->slug );
		if ( $v['size'] ) {
			$va['pa_size'] = get_term( $size_terms[ $v['size'] ] )->slug;
		}
		$variation->set_attributes( $va );
		$price   = '' !== $v['price'] ? (float) $v['price'] : null;
		$compare = '' !== $v['compare'] ? (float) $v['compare'] : null;
		if ( null !== $compare && null !== $price && $compare > $price ) {
			$variation->set_regular_price( (string) $compare );
			$variation->set_sale_price( (string) $price );
		} elseif ( null !== $price ) {
			$variation->set_regular_price( (string) $price );
			$variation->set_sale_price( '' );
		}
		if ( $v['barcode'] ) {
			$variation->update_meta_data( '_rx_barcode', $v['barcode'] );
		}
		if ( (float) $v['grams'] > 0 ) {
			$variation->set_weight( (string) round( (float) $v['grams'] / 1000, 3 ) );
		}
		// Real stock from the CSV; all rows use Shopify's "deny" policy, so no backorders.
		$variation->set_manage_stock( true );
		$variation->set_stock_quantity( $v['qty'] );
		$variation->set_backorders( 'no' );
		$variation->set_status( 'publish' );
		$variation->set_image_id( $colour_image[ $v['drive'] ] ?? 0 );
		$keep[] = $variation->save();
		++$stats['variations'];
	}
	// Open the page on the first colourway that has any size in stock.
	$default_colour = $variants[0]['colour'];
	foreach ( $variants as $v ) {
		if ( $v['qty'] > 0 ) {
			$default_colour = $v['colour'];
			break;
		}
	}
	$product = wc_get_product( $product_id );
	$product->set_default_attributes( array( 'pa_colour' => get_term( $colour_terms[ $default_colour ] )->slug ) );
	$product->save();
	WC_Product_Variable::sync( $product_id );
	wc_delete_product_transients( $product_id );

	++$stats[ $existing ? 'updated' : 'created' ];
	wp_cache_flush(); // Keep memory flat across 2,000+ variation saves.
}

wc_delete_product_transients();
delete_transient( 'wc_term_counts' );
rx_imp_log( "\nDone: " . wp_json_encode( $stats ) );
