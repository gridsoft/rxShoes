<?php
/**
 * Custom fields on the product_cat taxonomy admin screens.
 *
 * WooCommerce already gives categories a native "Thumbnail" field (term
 * meta `thumbnail_id`, set via Products > Categories) — no custom code
 * needed for that, see rx_theme_category_thumbnail_id().
 *
 * What core doesn't give us: the homepage Category Cards section (Figma
 * "Section - 2. CATEGORY CARDS SECTION") uses two bits of per-category
 * copy that can't be reliably derived from the category's plain name —
 * - "Shop label": correct possessive grammar ("Shop Men's Shoes" vs
 *   "Shop Unisex Shoes" — Unisex takes no apostrophe-s).
 * - "Badge label": a distinct kicker shown on the card photo itself
 *   ("MEN'S PERFORMANCE", "UNISEX SERIES") that isn't just the shop
 *   label or the category name with a fixed suffix — Figma uses
 *   different suffixes per category ("Performance" vs "Series").
 * Both are small real admin-editable fields on the category edit
 * screen, not inferred string manipulation.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simple term-meta text fields this theme adds to the product_cat
 * screens: meta_key => [label, description]. Add more here rather than
 * duplicating the add/edit/save boilerplate below.
 *
 * @return array<string,array{label:string,description:string}>
 */
function rx_theme_category_text_fields(): array {
	return array(
		'rx_shop_label'  => array(
			'label'       => __( 'Shop label', 'rx-theme' ),
			'description' => __( 'Used in "Shop [label] Shoes" card copy — e.g. "Men\'s", "Women\'s", "Unisex". Falls back to the category name if left blank.', 'rx-theme' ),
		),
		'rx_badge_label' => array(
			'label'       => __( 'Badge label', 'rx-theme' ),
			'description' => __( 'Small kicker shown on the card photo itself — e.g. "Men\'s Performance", "Unisex Series". Falls back to the category name if left blank.', 'rx-theme' ),
		),
	);
}

/**
 * Add this theme's fields to the "Add category" screen.
 */
function rx_theme_product_cat_add_fields(): void {
	foreach ( rx_theme_category_text_fields() as $meta_key => $field ) {
		?>
		<div class="form-field">
			<label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $field['label'] ); ?></label>
			<input type="text" name="<?php echo esc_attr( $meta_key ); ?>" id="<?php echo esc_attr( $meta_key ); ?>" value="">
			<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
		</div>
		<?php
	}
}
add_action( 'product_cat_add_form_fields', 'rx_theme_product_cat_add_fields' );

/**
 * Add this theme's fields to the "Edit category" screen.
 *
 * @param WP_Term $term Category term being edited.
 */
function rx_theme_product_cat_edit_fields( WP_Term $term ): void {
	foreach ( rx_theme_category_text_fields() as $meta_key => $field ) {
		$value = get_term_meta( $term->term_id, $meta_key, true );
		?>
		<tr class="form-field">
			<th scope="row"><label for="<?php echo esc_attr( $meta_key ); ?>"><?php echo esc_html( $field['label'] ); ?></label></th>
			<td>
				<input type="text" name="<?php echo esc_attr( $meta_key ); ?>" id="<?php echo esc_attr( $meta_key ); ?>" value="<?php echo esc_attr( $value ); ?>">
				<p class="description"><?php echo esc_html( $field['description'] ); ?></p>
			</td>
		</tr>
		<?php
	}
}
add_action( 'product_cat_edit_form_fields', 'rx_theme_product_cat_edit_fields' );

/**
 * Save this theme's fields when a new category is created. Core's "Add
 * category" form nonce action is fixed ('add-tag'), unlike the edit
 * form's (which is per-term-id) — these need separate handlers.
 *
 * @param int $term_id Newly created term ID.
 */
function rx_theme_save_product_cat_fields_on_create( int $term_id ): void {
	check_admin_referer( 'add-tag' );

	foreach ( array_keys( rx_theme_category_text_fields() ) as $meta_key ) {
		if ( isset( $_POST[ $meta_key ] ) ) {
			update_term_meta( $term_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) ) );
		}
	}
}
add_action( 'created_product_cat', 'rx_theme_save_product_cat_fields_on_create' );

/**
 * Save this theme's fields when an existing category is edited.
 *
 * @param int $term_id Edited term ID.
 */
function rx_theme_save_product_cat_fields_on_edit( int $term_id ): void {
	check_admin_referer( 'update-tag_' . $term_id );

	foreach ( array_keys( rx_theme_category_text_fields() ) as $meta_key ) {
		if ( isset( $_POST[ $meta_key ] ) ) {
			update_term_meta( $term_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $meta_key ] ) ) );
		}
	}
}
add_action( 'edited_product_cat', 'rx_theme_save_product_cat_fields_on_edit' );

/**
 * Get a category's shop-copy label, falling back to its plain name.
 *
 * @param WP_Term $term Category term.
 */
function rx_theme_category_shop_label( WP_Term $term ): string {
	$label = get_term_meta( $term->term_id, 'rx_shop_label', true );
	return $label ? $label : $term->name;
}

/**
 * Get a category's card-badge label, falling back to its plain name.
 *
 * @param WP_Term $term Category term.
 */
function rx_theme_category_badge_label( WP_Term $term ): string {
	$label = get_term_meta( $term->term_id, 'rx_badge_label', true );
	return $label ? $label : $term->name;
}

/**
 * Get a category's thumbnail attachment ID — same term meta key
 * (`thumbnail_id`) WooCommerce core itself uses for the native
 * "Thumbnail" field on Products > Categories, and the same lookup
 * woocommerce_subcategory_thumbnail() uses internally. Assign images
 * there; nothing custom needed on the admin side for this part.
 *
 * @param WP_Term $term Category term.
 */
function rx_theme_category_thumbnail_id( WP_Term $term ): int {
	return absint( get_term_meta( $term->term_id, 'thumbnail_id', true ) );
}
