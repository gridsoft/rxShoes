<?php
/**
 * Custom fields on the product_cat taxonomy admin screens.
 *
 * WooCommerce already gives categories a native "Thumbnail" field (term
 * meta `thumbnail_id`, set via Products > Categories) — no custom code
 * needed for that, see rx_theme_category_thumbnail_id().
 *
 * What core doesn't give us: a category's plain name ("Men") doesn't
 * reliably produce correct possessive grammar for card copy ("Shop
 * Men's Shoes" vs "Shop Unisex Shoes" — Unisex takes no apostrophe-s).
 * Rather than hardcoding that exception in the template, this adds one
 * small editable field ("Shop label") to the category edit screen, so
 * it's real admin-editable content like everything else, not inferred
 * string manipulation.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Add the "Shop label" field to the "Add category" screen.
 */
function rx_theme_product_cat_add_field(): void {
	?>
	<div class="form-field">
		<label for="rx_shop_label"><?php esc_html_e( 'Shop label', 'rx-theme' ); ?></label>
		<input type="text" name="rx_shop_label" id="rx_shop_label" value="">
		<p class="description"><?php esc_html_e( 'Used in "Shop [label] Shoes" card copy on the homepage — e.g. "Men\'s", "Women\'s", "Unisex". Falls back to the category name if left blank.', 'rx-theme' ); ?></p>
	</div>
	<?php
}
add_action( 'product_cat_add_form_fields', 'rx_theme_product_cat_add_field' );

/**
 * Add the "Shop label" field to the "Edit category" screen.
 *
 * @param WP_Term $term Category term being edited.
 */
function rx_theme_product_cat_edit_field( WP_Term $term ): void {
	$value = get_term_meta( $term->term_id, 'rx_shop_label', true );
	?>
	<tr class="form-field">
		<th scope="row"><label for="rx_shop_label"><?php esc_html_e( 'Shop label', 'rx-theme' ); ?></label></th>
		<td>
			<input type="text" name="rx_shop_label" id="rx_shop_label" value="<?php echo esc_attr( $value ); ?>">
			<p class="description"><?php esc_html_e( 'Used in "Shop [label] Shoes" card copy on the homepage — e.g. "Men\'s", "Women\'s", "Unisex". Falls back to the category name if left blank.', 'rx-theme' ); ?></p>
		</td>
	</tr>
	<?php
}
add_action( 'product_cat_edit_form_fields', 'rx_theme_product_cat_edit_field' );

/**
 * Save the "Shop label" field when a new category is created. Core's
 * "Add category" form nonce action is fixed ('add-tag'), unlike the
 * edit form's (which is per-term-id) — these need separate handlers.
 *
 * @param int $term_id Newly created term ID.
 */
function rx_theme_save_product_cat_field_on_create( int $term_id ): void {
	if ( ! isset( $_POST['rx_shop_label'] ) ) {
		return;
	}

	check_admin_referer( 'add-tag' );

	update_term_meta( $term_id, 'rx_shop_label', sanitize_text_field( wp_unslash( $_POST['rx_shop_label'] ) ) );
}
add_action( 'created_product_cat', 'rx_theme_save_product_cat_field_on_create' );

/**
 * Save the "Shop label" field when an existing category is edited.
 *
 * @param int $term_id Edited term ID.
 */
function rx_theme_save_product_cat_field_on_edit( int $term_id ): void {
	if ( ! isset( $_POST['rx_shop_label'] ) ) {
		return;
	}

	check_admin_referer( 'update-tag_' . $term_id );

	update_term_meta( $term_id, 'rx_shop_label', sanitize_text_field( wp_unslash( $_POST['rx_shop_label'] ) ) );
}
add_action( 'edited_product_cat', 'rx_theme_save_product_cat_field_on_edit' );

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
