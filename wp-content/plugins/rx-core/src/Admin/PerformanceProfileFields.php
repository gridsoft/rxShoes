<?php
/**
 * "Performance dispersion profile" values for the single product page
 * (client reference design, 2026-09-24): five 1–5 ratings (training,
 * lifting, stability, running, cushioning) plus four specs (drop in mm,
 * weight in grams for a men's US 9, forefoot and midsole as short text).
 *
 * Entered by an admin on the product's "Shop card" tab (after the type
 * label), not derived from the imported descriptions — those contain no
 * ratings at all, and inventing scores from marketing copy was ruled out
 * (PROJECT.md, "real data only"). Every field is optional; the theme
 * shows the box only when at least one value is set, and only the rows /
 * specs that are set.
 *
 * Stored as one meta array (META_KEY); read through get_values().
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Admin;

use RX\Core\Service;
use WC_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Performance profile fields on the Shop card tab.
 */
final class PerformanceProfileFields implements Service {

	/**
	 * Product meta key holding the values.
	 */
	public const META_KEY = '_rx_performance';

	/**
	 * Hook the fields into the Shop card tab and the product save.
	 */
	public function register(): void {
		add_action( ProductCardTab::FIELDS_ACTION, array( $this, 'render_fields' ), 20 );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_style' ) );
	}

	/**
	 * The 1–5 ratings, in display order, with their labels.
	 *
	 * @return array<string,string>
	 */
	public static function ratings(): array {
		return array(
			'training'   => __( 'Training', 'rx-core' ),
			'lifting'    => __( 'Lifting', 'rx-core' ),
			'stability'  => __( 'Stability', 'rx-core' ),
			'running'    => __( 'Running', 'rx-core' ),
			'cushioning' => __( 'Cushioning', 'rx-core' ),
		);
	}

	/**
	 * The saved values for a product, cleaned: ratings as ints 1–5,
	 * drop/weight as positive numbers, forefoot/midsole as text. Unset
	 * fields are left out entirely.
	 *
	 * @param WC_Product $product Product.
	 * @return array{ratings: array<string,int>, drop?: float, weight?: int, forefoot?: string, midsole?: string}
	 */
	public static function get_values( WC_Product $product ): array {
		$raw    = $product->get_meta( self::META_KEY );
		$raw    = is_array( $raw ) ? $raw : array();
		$values = array( 'ratings' => array() );

		foreach ( array_keys( self::ratings() ) as $key ) {
			$rating = isset( $raw[ $key ] ) ? (int) $raw[ $key ] : 0;

			if ( $rating >= 1 && $rating <= 5 ) {
				$values['ratings'][ $key ] = $rating;
			}
		}

		if ( isset( $raw['drop'] ) && is_numeric( $raw['drop'] ) && (float) $raw['drop'] >= 0 ) {
			$values['drop'] = (float) $raw['drop'];
		}
		if ( isset( $raw['weight'] ) && is_numeric( $raw['weight'] ) && (int) $raw['weight'] > 0 ) {
			$values['weight'] = (int) $raw['weight'];
		}
		foreach ( array( 'forefoot', 'midsole' ) as $key ) {
			if ( isset( $raw[ $key ] ) && '' !== trim( (string) $raw[ $key ] ) ) {
				$values[ $key ] = trim( (string) $raw[ $key ] );
			}
		}

		return $values;
	}

	/**
	 * Render the fields (after the Shop card tab's copy fields) as two
	 * compact groups — the five one-digit ratings side by side, then the
	 * four specs on one line — instead of nine full-width rows.
	 */
	public function render_fields(): void {
		global $product_object;

		$raw = $product_object instanceof WC_Product ? $product_object->get_meta( self::META_KEY ) : array();
		$raw = is_array( $raw ) ? $raw : array();
		?>
		</div>
		<div class="options_group rx-perf-admin">
			<p class="form-field rx-perf-admin__intro">
				<strong><?php esc_html_e( 'Performance dispersion profile', 'rx-core' ); ?></strong><br>
				<?php esc_html_e( 'Shown on the product page. Leave everything blank to hide the box; blank fields are left out.', 'rx-core' ); ?>
			</p>

			<fieldset class="form-field rx-perf-admin__group">
				<legend><?php esc_html_e( 'Ratings (1–5)', 'rx-core' ); ?></legend>
				<span class="rx-perf-admin__fields">
					<?php foreach ( self::ratings() as $key => $label ) : ?>
						<?php
						$this->render_mini_field(
							$key,
							$label,
							$raw,
							array(
								'inputmode' => 'numeric',
								'pattern'   => '[1-5]',
								'maxlength' => '1',
								'title'     => __( 'A whole number from 1 to 5', 'rx-core' ),
								'size'      => 'xs',
							)
						);
						?>
					<?php endforeach; ?>
				</span>
			</fieldset>

			<fieldset class="form-field rx-perf-admin__group">
				<legend><?php esc_html_e( 'Specs', 'rx-core' ); ?></legend>
				<span class="rx-perf-admin__fields">
					<?php
					$this->render_mini_field(
						'drop',
						__( 'Drop (mm)', 'rx-core' ),
						$raw,
						array(
							'inputmode'   => 'decimal',
							'pattern'     => '\d{1,2}([.,]\d)?',
							'maxlength'   => '4',
							'size'        => 's',
							'placeholder' => '6',
							'title'       => __( 'Heel-to-toe drop in millimetres, e.g. 6 or 6.5 (0 for zero-drop).', 'rx-core' ),
						)
					);
					$this->render_mini_field(
						'weight',
						__( 'Weight (g, M9)', 'rx-core' ),
						$raw,
						array(
							'inputmode'   => 'numeric',
							'pattern'     => '\d{1,4}',
							'maxlength'   => '4',
							'size'        => 's',
							'placeholder' => '340',
							'title'       => __( "Weight in grams of a men's US size 9 shoe.", 'rx-core' ),
						)
					);
					$this->render_mini_field(
						'forefoot',
						__( 'Forefoot', 'rx-core' ),
						$raw,
						array(
							'size'        => 'm',
							'placeholder' => __( 'Wide toe', 'rx-core' ),
							'maxlength'   => '30',
						)
					);
					$this->render_mini_field(
						'midsole',
						__( 'Midsole', 'rx-core' ),
						$raw,
						array(
							'size'        => 'm',
							'placeholder' => __( 'Swell', 'rx-core' ),
							'maxlength'   => '30',
						)
					);
					?>
				</span>
			</fieldset>
		<?php
	}

	/**
	 * One compact labelled input (label above, width by size: xs = one
	 * digit, s = a short number, m = a word or two).
	 *
	 * @param string               $key   Value key inside META_KEY.
	 * @param string               $label Visible label.
	 * @param array<string,mixed>  $raw   Saved values.
	 * @param array<string,string> $attrs type / min / max / step / size / placeholder / title / maxlength.
	 */
	private function render_mini_field( string $key, string $label, array $raw, array $attrs ): void {
		$id   = self::META_KEY . '_' . $key;
		$size = $attrs['size'] ?? 'm';
		unset( $attrs['size'] );
		$attrs['type'] = $attrs['type'] ?? 'text';
		?>
		<label class="rx-perf-admin__field rx-perf-admin__field--<?php echo esc_attr( $size ); ?>" for="<?php echo esc_attr( $id ); ?>">
			<span><?php echo esc_html( $label ); ?></span>
			<input id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( self::META_KEY . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( isset( $raw[ $key ] ) ? (string) $raw[ $key ] : '' ); ?>"
				<?php
				foreach ( $attrs as $attr => $attr_value ) {
					echo ' ' . esc_attr( $attr ) . '="' . esc_attr( $attr_value ) . '"';
				}
				?>
			>
		</label>
		<?php
	}

	/**
	 * Compact layout for the two groups, on the product edit screen only.
	 *
	 * @param string $hook Current admin page.
	 */
	public function enqueue_admin_style( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || 'product' !== get_post_type() ) {
			return;
		}

		// Own (file-less) handle, so the rules never depend on another plugin's stylesheet being registered first.
		wp_register_style( 'rx-core-performance-admin', false, array(), '1' );
		wp_enqueue_style( 'rx-core-performance-admin' );
		wp_add_inline_style(
			'rx-core-performance-admin',
			// Scoped under #woocommerce-product-data so these beat WooCommerce's
			// own ".woocommerce_options_panel label / input" widths and floats.
			'#woocommerce-product-data .rx-perf-admin__group { margin: 0; padding: 5px 20px 12px 162px; border: 0; }
			#woocommerce-product-data .rx-perf-admin__group legend { float: left; width: 150px; margin: 0 0 0 -150px; padding: 24px 0 0; font-size: 12px; }
			#woocommerce-product-data .rx-perf-admin__fields { display: flex; flex-wrap: wrap; gap: 10px 16px; }
			#woocommerce-product-data label.rx-perf-admin__field { float: none; display: flex; flex-direction: column; gap: 4px; width: auto; margin: 0; padding: 0; }
			#woocommerce-product-data label.rx-perf-admin__field span { font-size: 12px; color: #50575e; white-space: nowrap; }
			#woocommerce-product-data label.rx-perf-admin__field input[type="text"] { float: none; margin: 0; }
			#woocommerce-product-data label.rx-perf-admin__field--xs input[type="text"] { width: 3.25em; text-align: center; }
			#woocommerce-product-data label.rx-perf-admin__field--s input[type="text"] { width: 5.5em; }
			#woocommerce-product-data label.rx-perf-admin__field--m input[type="text"] { width: 9em; }'
		);
	}

	/**
	 * Save through the product CRUD object. WooCommerce has already
	 * verified the product-save nonce and the user's capability before
	 * this action fires. Out-of-range ratings and non-positive numbers are
	 * dropped rather than clamped, so a typo never shows as a real score.
	 *
	 * @param WC_Product $product Product being saved.
	 */
	public function save_fields( WC_Product $product ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified by WC_Meta_Box_Product_Data::save() before this action fires.
		if ( ! isset( $_POST[ self::META_KEY ] ) || ! is_array( $_POST[ self::META_KEY ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- as above.
		$input  = array_map( 'sanitize_text_field', wp_unslash( $_POST[ self::META_KEY ] ) );
		$values = array();

		foreach ( array_keys( self::ratings() ) as $key ) {
			$rating = isset( $input[ $key ] ) && '' !== $input[ $key ] ? (int) $input[ $key ] : 0;

			if ( $rating >= 1 && $rating <= 5 ) {
				$values[ $key ] = $rating;
			}
		}

		if ( isset( $input['drop'] ) ) {
			$input['drop'] = str_replace( ',', '.', $input['drop'] ); // "6,5" as well as "6.5".
		}
		if ( isset( $input['drop'] ) && is_numeric( $input['drop'] ) && (float) $input['drop'] >= 0 ) {
			$values['drop'] = round( (float) $input['drop'], 1 );
		}
		if ( isset( $input['weight'] ) && is_numeric( $input['weight'] ) && (int) $input['weight'] > 0 ) {
			$values['weight'] = (int) $input['weight'];
		}
		foreach ( array( 'forefoot', 'midsole' ) as $key ) {
			if ( isset( $input[ $key ] ) && '' !== trim( $input[ $key ] ) ) {
				$values[ $key ] = mb_substr( trim( $input[ $key ] ), 0, 30 );
			}
		}

		if ( $values ) {
			$product->update_meta_data( self::META_KEY, $values );
		} else {
			$product->delete_meta_data( self::META_KEY );
		}
	}
}
