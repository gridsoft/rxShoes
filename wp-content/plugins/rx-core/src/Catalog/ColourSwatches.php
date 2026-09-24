<?php
/**
 * Swatch colour(s) for the global Colour attribute (pa_colour), edited on
 * Products > Attributes > Colour > (term). Many terms here are genuine
 * two-tone colourways ("White / Red", "Black / Grey"), not a single
 * shade, so each term gets up to two hex values — the swatch renders as
 * a solid circle with one set, a 50/50 split with both.
 *
 * This is catalogue data (survives a theme switch, same reasoning as
 * BestForTaxonomy / SizeFilterAttributes), so it lives here; the theme
 * only reads it via rx_theme_colour_swatch_colours() in inc/woocommerce.php
 * and renders it.
 *
 * Deliberately does NOT try to guess every term's colour from its name —
 * many of these are brand marketing names or abbreviated codes ("RBKLE3",
 * "PUGRY6") that can't be read reliably. A one-off seed script sets the
 * confident, plain-colour-word cases; everything else starts unset and
 * shows a neutral placeholder swatch until picked here. Showing a wrong
 * guessed colour on a live store is worse than an honest "not set yet".
 *
 * @package RX\Core
 */

declare(strict_types=1);

namespace RX\Core\Catalog;

use RX\Core\Service;
use WP_Term;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the swatch colour fields on the pa_colour taxonomy's term
 * screens, and the public getter other code reads them through.
 */
final class ColourSwatches implements Service {

	/**
	 * Term meta keys. Hex strings including the '#', e.g. '#111111'.
	 * The second is optional — most terms are one colour.
	 */
	public const META_KEY_1 = '_rx_swatch_1';
	public const META_KEY_2 = '_rx_swatch_2';

	/**
	 * The attribute taxonomy this applies to. WooCommerce attribute
	 * taxonomies are ordinary taxonomies once registered, so the normal
	 * {$taxonomy}_add_form_fields / {$taxonomy}_edit_form_fields /
	 * created_{$taxonomy} / edited_{$taxonomy} hooks all apply.
	 */
	private const TAXONOMY = 'pa_colour';

	/**
	 * Hook the term meta registration, form fields and save handlers.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_term_meta' ) );
		add_action( self::TAXONOMY . '_add_form_fields', array( $this, 'render_add_fields' ) );
		add_action( self::TAXONOMY . '_edit_form_fields', array( $this, 'render_edit_fields' ) );
		add_action( 'created_' . self::TAXONOMY, array( $this, 'save_on_create' ) );
		add_action( 'edited_' . self::TAXONOMY, array( $this, 'save_on_edit' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_color_picker' ) );
	}

	/**
	 * Declare the term meta (REST-visible, so the block editor / future
	 * tooling can read it without reaching into raw meta).
	 */
	public function register_term_meta(): void {
		foreach ( array( self::META_KEY_1, self::META_KEY_2 ) as $key ) {
			register_term_meta(
				self::TAXONOMY,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => array( self::class, 'sanitize_hex' ),
				)
			);
		}
	}

	/**
	 * WordPress's own colour-picker script/styles, only on the pa_colour
	 * term screens where the fields below actually appear.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_color_picker( string $hook ): void {
		if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading, not acting on, the taxonomy query arg.
		if ( ! isset( $_GET['taxonomy'] ) || self::TAXONOMY !== $_GET['taxonomy'] ) {
			return;
		}

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_add_inline_script(
			'wp-color-picker',
			'jQuery(function($){ $(".rx-swatch-color-field").wpColorPicker(); });'
		);
	}

	/**
	 * Fields on the "Add new Colour" screen — no existing term yet, so
	 * every field starts blank.
	 */
	public function render_add_fields(): void {
		foreach ( $this->field_definitions() as $key => $field ) {
			?>
			<div class="form-field">
				<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
				<input type="text" class="rx-swatch-color-field" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="" data-default-color="">
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Fields on the "Edit Colour" screen — pre-filled from term meta.
	 *
	 * @param WP_Term $term Term being edited.
	 */
	public function render_edit_fields( WP_Term $term ): void {
		foreach ( $this->field_definitions() as $key => $field ) {
			$value = get_term_meta( $term->term_id, $key, true );
			?>
			<tr class="form-field">
				<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
				<td>
					<input type="text" class="rx-swatch-color-field" name="<?php echo esc_attr( $key ); ?>" id="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" data-default-color="">
					<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
				</td>
			</tr>
			<?php
		}
	}

	/**
	 * Field label/description, shared between the add and edit screens.
	 *
	 * @return array<string,array{label:string,description:string}>
	 */
	private function field_definitions(): array {
		return array(
			self::META_KEY_1 => array(
				'label'       => __( 'Swatch colour', 'rx-core' ),
				'description' => __( 'The colour shown on the product page swatch. Leave blank until you\'re sure — an unset swatch shows a neutral placeholder rather than a guess.', 'rx-core' ),
			),
			self::META_KEY_2 => array(
				'label'       => __( 'Second swatch colour (optional)', 'rx-core' ),
				'description' => __( 'For two-tone colourways ("White / Red") — the swatch splits 50/50 between the two. Leave blank for a single-colour swatch.', 'rx-core' ),
			),
		);
	}

	/**
	 * Save on "Add new Colour". See taxonomy-fields.php in the theme for
	 * why the nonce check is gated behind isset( $_POST['_wpnonce'] ):
	 * created_{$taxonomy} also fires for terms created programmatically
	 * (the product importer, REST API), where there's no admin form
	 * submission and so no nonce to check.
	 *
	 * @param int $term_id Newly created term ID.
	 */
	public function save_on_create( int $term_id ): void {
		if ( ! isset( $_POST['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified two lines below; this only gates against a non-admin-form call.
			return;
		}

		check_admin_referer( 'add-tag' );
		$this->save_fields( $term_id );
	}

	/**
	 * Save on "Edit Colour". Same non-admin-context guard as above.
	 *
	 * @param int $term_id Edited term ID.
	 */
	public function save_on_edit( int $term_id ): void {
		if ( ! isset( $_POST['_wpnonce'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified two lines below; this only gates against a non-admin-form call.
			return;
		}

		check_admin_referer( 'update-tag_' . $term_id );
		$this->save_fields( $term_id );
	}

	/**
	 * Save the swatch fields. Only called after check_admin_referer() in
	 * save_on_create() / save_on_edit().
	 *
	 * @param int $term_id Term to save meta on.
	 */
	private function save_fields( int $term_id ): void {
		foreach ( array_keys( $this->field_definitions() ) as $key ) {
			if ( ! isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by both callers.
				continue;
			}

			$value = self::sanitize_hex( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified by both callers; sanitized via sanitize_hex().

			if ( '' === $value ) {
				delete_term_meta( $term_id, $key );
			} else {
				update_term_meta( $term_id, $key, $value );
			}
		}
	}

	/**
	 * A valid 3/6-digit hex colour (with '#'), or ''. Anything else —
	 * free text, a half-finished picker value — is dropped rather than
	 * stored wrong.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_hex( $value ): string {
		$value = sanitize_text_field( (string) $value );

		return (bool) preg_match( '/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', $value ) ? $value : '';
	}

	/**
	 * The swatch colour(s) for a pa_colour term: [hex1, hex2] — either
	 * may be null. Both null means "not set yet".
	 *
	 * @param int $term_id pa_colour term ID.
	 * @return array{0:?string,1:?string}
	 */
	public static function get_colours( int $term_id ): array {
		$hex1 = get_term_meta( $term_id, self::META_KEY_1, true );
		$hex2 = get_term_meta( $term_id, self::META_KEY_2, true );

		return array( $hex1 ? $hex1 : null, $hex2 ? $hex2 : null );
	}
}
