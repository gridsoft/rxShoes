<?php
/**
 * Generate WordPress's image sub-sizes (thumbnails, medium, large, the
 * WooCommerce sizes) as WebP instead of JPEG/PNG — PROJECT.md §5/§10:
 * WebP with responsive srcset, for a ~90% mobile audience.
 *
 * Only the generated sizes change; the uploaded original is kept as-is
 * (WordPress never re-encodes originals), and srcset still offers it for
 * the largest screens. WebP keeps PNG transparency, so cut-out product
 * shots stay cut out. Applies to new uploads; existing images get their
 * sizes rebuilt with `wp media regenerate`.
 *
 * Lives in rx-core, not the theme: it's media processing, and the files
 * it produces must not depend on which theme is active.
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Media;

use RX\Core\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps JPEG/PNG sub-size output to WebP when the server can write it.
 */
final class WebpSubsizes implements Service {

	/**
	 * Add the output-format filter.
	 */
	public function register(): void {
		add_filter( 'image_editor_output_format', array( $this, 'output_format' ) );
	}

	/**
	 * JPEG and PNG sub-sizes are written as WebP — only if the image
	 * editor supports WebP on this server (otherwise nothing changes).
	 *
	 * @param array<string,string> $formats Source MIME => output MIME.
	 * @return array<string,string>
	 */
	public function output_format( $formats ): array {
		$formats = (array) $formats;

		if ( ! wp_image_editor_supports( array( 'mime_type' => 'image/webp' ) ) ) {
			return $formats;
		}

		$formats['image/jpeg'] = 'image/webp';
		$formats['image/png']  = 'image/webp';

		return $formats;
	}
}
