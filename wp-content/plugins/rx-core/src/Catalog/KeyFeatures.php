<?php
/**
 * Product "Key features" (the three icon tiles under the PDP gallery in
 * the client's reference design: "Olympic Stable — Solid TPU heel
 * wedge" etc.).
 *
 * Entered per product on a "Key features" tab of the product data box:
 * up to three rows of title + short line + icon. The imported
 * descriptions have bullet lists but almost never in a "title + line"
 * shape (sampled 2026-09-24: 1 in 10), so features are not generated
 * automatically — the tab lists the description's usable bullets as
 * suggestions (retailer shipping notes filtered out, icon guessed from
 * the wording) with a "Use" button that copies one into the next empty
 * row for the editor to finish. Nothing shows on the site until saved.
 *
 * Stored as one meta array (META_KEY) of rows {title, text, icon}; the
 * theme reads it via get_rows() and owns the icon artwork.
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Catalog;

use RX\Core\Service;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Key features tab, storage and description suggestions.
 */
final class KeyFeatures implements Service {

	/**
	 * Product meta key holding the rows.
	 */
	public const META_KEY = '_rx_key_features';

	/**
	 * Product data tab id.
	 */
	private const TAB = 'rx_features';

	/**
	 * How many tiles a product can have.
	 */
	public const MAX_ROWS = 3;

	/**
	 * Hook the tab, its panel, the save and the admin script.
	 */
	public function register(): void {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'add_tab' ) );
		add_action( 'woocommerce_product_data_panels', array( $this, 'render_panel' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_script' ) );
	}

	/**
	 * Icon slugs the theme draws, with their admin labels.
	 *
	 * @return array<string,string>
	 */
	public static function icons(): array {
		return array(
			'stability'   => __( 'Stability / lifting', 'rx-core' ),
			'cushioning'  => __( 'Cushioning / foam', 'rx-core' ),
			'durability'  => __( 'Durability / protection', 'rx-core' ),
			'grip'        => __( 'Grip / outsole', 'rx-core' ),
			'fit'         => __( 'Fit / toe box', 'rx-core' ),
			'breathable'  => __( 'Breathable', 'rx-core' ),
			'flex'        => __( 'Flexibility', 'rx-core' ),
			'lightweight' => __( 'Lightweight', 'rx-core' ),
			'feature'     => __( 'General', 'rx-core' ),
		);
	}

	/**
	 * The saved rows for a product: only rows with a title, icon
	 * validated, at most MAX_ROWS.
	 *
	 * @param WC_Product $product Product.
	 * @return array<int,array{title:string,text:string,icon:string}>
	 */
	public static function get_rows( WC_Product $product ): array {
		$raw  = $product->get_meta( self::META_KEY );
		$rows = array();

		foreach ( is_array( $raw ) ? $raw : array() as $row ) {
			$title = isset( $row['title'] ) ? trim( (string) $row['title'] ) : '';

			if ( '' === $title ) {
				continue;
			}

			$icon   = isset( $row['icon'] ) ? (string) $row['icon'] : 'feature';
			$rows[] = array(
				'title' => $title,
				'text'  => isset( $row['text'] ) ? trim( (string) $row['text'] ) : '',
				'icon'  => array_key_exists( $icon, self::icons() ) ? $icon : 'feature',
			);
		}

		return array_slice( $rows, 0, self::MAX_ROWS );
	}

	/**
	 * Candidate features from the product description's bullet points:
	 * short bullets become a title, longer ones the line under it (the
	 * editor writes the other half). "Title: line" bullets split cleanly.
	 * Retailer shipping/offer notes some imported descriptions carry are
	 * skipped.
	 *
	 * @param WC_Product $product Product.
	 * @return array<int,array{title:string,text:string,icon:string}>
	 */
	public static function suggestions( WC_Product $product ): array {
		if ( ! preg_match_all( '#<li[^>]*>(.*?)</li>#is', $product->get_description(), $matches ) ) {
			return array();
		}

		$suggestions = array();

		foreach ( $matches[1] as $item ) {
			$text = trim( (string) preg_replace( '/\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $item ), ENT_QUOTES, 'UTF-8' ) ) );

			if ( '' === $text || preg_match( '/marketplace partner|excluded from|arrive separately|free gift|\bTWL\b|app offers|shipping|returns?\b|delivery|despatch|dispatch|warehouse|business days|sent to customers|outside of australia/i', $text ) ) {
				continue;
			}

			if ( preg_match( '/^([^:.]{3,40}):\s+(.+)$/u', $text, $parts ) ) {
				$title = $parts[1];
				$line  = $parts[2];
			} elseif ( mb_strlen( $text ) <= 32 ) {
				$title = $text;
				$line  = '';
			} else {
				$title = '';
				$line  = $text;
			}

			$suggestions[] = array(
				'title' => $title,
				'text'  => mb_strlen( $line ) > 90 ? mb_substr( $line, 0, 87 ) . '…' : $line,
				'icon'  => self::guess_icon( $text ),
			);
		}

		return $suggestions;
	}

	/**
	 * A best-guess icon from a feature's wording (the editor can change it).
	 *
	 * @param string $text Feature text.
	 */
	public static function guess_icon( string $text ): string {
		$map = array(
			'cushioning'  => '/foam|cushion|rebound|responsive|nrg|phylon|midsole/i',
			'stability'   => '/stab|heel|wedge|flat|wide base|lift|support|torsional|rigid|platform/i',
			'durability'  => '/durab|abrasion|guard|superfabric|rope|protect|wrap|sidewall|ripstop/i',
			'grip'        => '/grip|outsole|traction|rubber|lug/i',
			'fit'         => '/toe[- ]?box|fit|anatomical|strap|collar|lac(e|ing)|sockliner/i',
			'breathable'  => '/mesh|breath|airflow|vent/i',
			'flex'        => '/flex/i',
			'lightweight' => '/light|weigh/i',
		);

		foreach ( $map as $icon => $pattern ) {
			if ( preg_match( $pattern, $text ) ) {
				return $icon;
			}
		}

		return 'feature';
	}

	/**
	 * Add the "Key features" tab to the product data box (all types).
	 *
	 * @param array<string,array<string,mixed>> $tabs Product data tabs.
	 * @return array<string,array<string,mixed>>
	 */
	public function add_tab( array $tabs ): array {
		$tabs[ self::TAB ] = array(
			'label'    => __( 'Key features', 'rx-core' ),
			'target'   => self::TAB . '_product_data',
			'class'    => array(),
			'priority' => 66,
		);

		return $tabs;
	}

	/**
	 * Render the tab: three editable rows, then the suggestions.
	 */
	public function render_panel(): void {
		global $product_object;

		$product = $product_object instanceof WC_Product ? $product_object : null;
		$saved   = $product ? $product->get_meta( self::META_KEY ) : array();
		$saved   = is_array( $saved ) ? array_values( $saved ) : array();
		?>
		<div id="<?php echo esc_attr( self::TAB . '_product_data' ); ?>" class="panel woocommerce_options_panel hidden rx-key-features">
			<div class="options_group">
				<p class="form-field"><?php esc_html_e( 'Up to three tiles shown under the product gallery. A row without a title is not shown.', 'rx-core' ); ?></p>
				<?php for ( $i = 0; $i < self::MAX_ROWS; $i++ ) : ?>
					<?php $row = $saved[ $i ] ?? array(); ?>
					<p class="form-field rx-key-features__row" data-rx-feature-row="<?php echo esc_attr( (string) $i ); ?>">
						<label for="rx_feature_title_<?php echo esc_attr( (string) $i ); ?>">
							<?php
							/* translators: %d: tile number. */
							echo esc_html( sprintf( __( 'Tile %d', 'rx-core' ), $i + 1 ) );
							?>
						</label>
						<input type="text" id="rx_feature_title_<?php echo esc_attr( (string) $i ); ?>" name="rx_feature_title[<?php echo esc_attr( (string) $i ); ?>]" value="<?php echo esc_attr( (string) ( $row['title'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Title, e.g. Olympic stable', 'rx-core' ); ?>" maxlength="40" style="width:30%;">
						<input type="text" name="rx_feature_text[<?php echo esc_attr( (string) $i ); ?>]" value="<?php echo esc_attr( (string) ( $row['text'] ?? '' ) ); ?>" placeholder="<?php esc_attr_e( 'Short line, e.g. Solid TPU heel wedge', 'rx-core' ); ?>" maxlength="90" style="width:40%;" aria-label="<?php esc_attr_e( 'Short line', 'rx-core' ); ?>">
						<select name="rx_feature_icon[<?php echo esc_attr( (string) $i ); ?>]" aria-label="<?php esc_attr_e( 'Icon', 'rx-core' ); ?>">
							<?php foreach ( self::icons() as $slug => $label ) : ?>
								<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $row['icon'] ?? 'feature', $slug ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</p>
				<?php endfor; ?>
			</div>

			<?php $suggestions = $product ? self::suggestions( $product ) : array(); ?>
			<?php if ( $suggestions ) : ?>
				<div class="options_group">
					<p class="form-field"><strong><?php esc_html_e( 'Suggestions from the description', 'rx-core' ); ?></strong><br><?php esc_html_e( '"Use" copies one into the next empty tile — then shorten or write the missing title/line yourself.', 'rx-core' ); ?></p>
					<ul class="rx-key-features__suggestions" style="margin:0 12px 12px 162px;">
						<?php foreach ( $suggestions as $suggestion ) : ?>
							<li style="display:flex;gap:8px;align-items:baseline;margin-bottom:6px;">
								<button type="button" class="button button-small" data-rx-feature-use data-title="<?php echo esc_attr( $suggestion['title'] ); ?>" data-text="<?php echo esc_attr( $suggestion['text'] ); ?>" data-icon="<?php echo esc_attr( $suggestion['icon'] ); ?>"><?php esc_html_e( 'Use', 'rx-core' ); ?></button>
								<span><?php echo esc_html( trim( $suggestion['title'] . ( $suggestion['title'] && $suggestion['text'] ? ' — ' : '' ) . $suggestion['text'] ) ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Save the rows through the product CRUD object. WooCommerce has
	 * already verified the product-save nonce and the user's capability
	 * before this action fires.
	 *
	 * @param WC_Product $product Product being saved.
	 */
	public function save( WC_Product $product ): void {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified by WC_Meta_Box_Product_Data::save() before this action fires.
		if ( ! isset( $_POST['rx_feature_title'] ) || ! is_array( $_POST['rx_feature_title'] ) ) {
			return;
		}

		$titles = array_map( 'sanitize_text_field', wp_unslash( $_POST['rx_feature_title'] ) );
		$texts  = isset( $_POST['rx_feature_text'] ) && is_array( $_POST['rx_feature_text'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['rx_feature_text'] ) ) : array();
		$icons  = isset( $_POST['rx_feature_icon'] ) && is_array( $_POST['rx_feature_icon'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['rx_feature_icon'] ) ) : array();
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$rows = array();

		for ( $i = 0; $i < self::MAX_ROWS; $i++ ) {
			$title = trim( (string) ( $titles[ $i ] ?? '' ) );

			if ( '' === $title ) {
				continue;
			}

			$icon   = (string) ( $icons[ $i ] ?? 'feature' );
			$rows[] = array(
				'title' => mb_substr( $title, 0, 40 ),
				'text'  => mb_substr( trim( (string) ( $texts[ $i ] ?? '' ) ), 0, 90 ),
				'icon'  => array_key_exists( $icon, self::icons() ) ? $icon : 'feature',
			);
		}

		if ( $rows ) {
			$product->update_meta_data( self::META_KEY, $rows );
		} else {
			$product->delete_meta_data( self::META_KEY );
		}
	}

	/**
	 * The "Use" buttons: copy a suggestion into the first tile with an
	 * empty title (or, for a line-only suggestion, an empty line).
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_script( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'product' !== get_post_type() ) {
			return;
		}

		wp_add_inline_script(
			'jquery',
			"jQuery( function ( $ ) {
				$( document ).on( 'click', '[data-rx-feature-use]', function () {
					var \$b = $( this ), title = \$b.data( 'title' ) || '', text = \$b.data( 'text' ) || '';
					var \$rows = $( '.rx-key-features__row' ), \$target = null;
					\$rows.each( function () {
						var \$t = $( this ).find( 'input[name^=\"rx_feature_title\"]' ), \$l = $( this ).find( 'input[name^=\"rx_feature_text\"]' );
						if ( ! \$target && ( title ? ! \$t.val() : ! \$l.val() ) ) { \$target = $( this ); }
					} );
					if ( ! \$target ) { return; }
					if ( title ) { \$target.find( 'input[name^=\"rx_feature_title\"]' ).val( title ); }
					if ( text ) { \$target.find( 'input[name^=\"rx_feature_text\"]' ).val( text ); }
					\$target.find( 'select' ).val( \$b.data( 'icon' ) );
					( title ? \$target.find( 'input[name^=\"rx_feature_text\"]' ) : \$target.find( 'input[name^=\"rx_feature_title\"]' ) ).trigger( 'focus' );
				} );
			} );"
		);
	}
}
