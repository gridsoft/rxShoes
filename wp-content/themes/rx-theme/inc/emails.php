<?php
/**
 * WooCommerce emails in the theme's style (order confirmations, status
 * updates, invoices, admin new-order, account emails — everything that
 * goes through WooCommerce's mailer):
 *
 * - Look: RX wordmark bar, condensed uppercase headings, ink / blue /
 *   lime palette, the site's trust line in the footer. CSS is appended to
 *   WooCommerce's own email CSS (woocommerce_email_styles) rather than
 *   forking email-styles.php; header/footer are template overrides
 *   (woocommerce/emails/email-header.php, email-footer.php).
 * - Order items: grouped exactly like the website's order view —
 *   "Your rotation" (pair labels, discount note), "Other items" and
 *   "Free with your order" — with product photos and "Size • Colour"
 *   lines (woocommerce/emails/email-order-items.php and its plain-text
 *   twin), from rx_theme_order_grouping() (inc/order-details.php).
 *
 * Email photos are small JPEG copies: the media library stores product
 * images as WebP (rx-core's WebpSubsizes), which desktop Outlook can't
 * display. rx_theme_email_image_url() makes the JPEG once per image and
 * reuses it.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Product photo size in emails, in CSS pixels (the file is 2x for sharp screens). */
const RX_THEME_EMAIL_IMAGE_SIZE = 72;

/**
 * Show product photos in every order email (customer and admin) at the
 * theme's size.
 *
 * @param array<string,mixed> $args wc_get_email_order_items() args.
 * @return array<string,mixed>
 */
function rx_theme_email_order_items_args( array $args ): array {
	$args['show_image'] = true;
	$args['image_size'] = array( RX_THEME_EMAIL_IMAGE_SIZE, RX_THEME_EMAIL_IMAGE_SIZE );

	return $args;
}
add_filter( 'woocommerce_email_order_items_args', 'rx_theme_email_order_items_args' );

/**
 * URL of an email-safe (JPEG, square, 2x) copy of an image, created next
 * to the original the first time it's needed. Falls back to the
 * thumbnail URL if the copy can't be made.
 *
 * @param int $attachment_id Image attachment ID.
 */
function rx_theme_email_image_url( int $attachment_id ): string {
	$file = get_attached_file( $attachment_id );

	if ( ! $file || ! file_exists( $file ) ) {
		return (string) wp_get_attachment_image_url( $attachment_id, 'woocommerce_gallery_thumbnail' );
	}

	$size       = RX_THEME_EMAIL_IMAGE_SIZE * 2;
	$email_file = preg_replace( '/\.[^.]+$/', '', $file ) . "-email-{$size}.jpg";
	$email_url  = str_replace( wp_basename( (string) wp_get_attachment_url( $attachment_id ) ), wp_basename( $email_file ), (string) wp_get_attachment_url( $attachment_id ) );

	if ( file_exists( $email_file ) ) {
		return $email_url;
	}

	$editor = wp_get_image_editor( $file );

	if ( is_wp_error( $editor ) ) {
		return (string) wp_get_attachment_image_url( $attachment_id, 'woocommerce_gallery_thumbnail' );
	}

	// rx-core maps JPEG output to WebP site-wide; this one file must stay JPEG.
	$keep_jpeg = static fn(): array => array();
	add_filter( 'image_editor_output_format', $keep_jpeg, 999 );

	$editor->resize( $size, $size, true );
	$editor->set_quality( 82 );
	$saved = $editor->save( $email_file, 'image/jpeg' );

	remove_filter( 'image_editor_output_format', $keep_jpeg, 999 );

	return is_wp_error( $saved ) ? (string) wp_get_attachment_image_url( $attachment_id, 'woocommerce_gallery_thumbnail' ) : $email_url;
}

/**
 * <img> for an order item's product in an email.
 *
 * @param WC_Product|null $product Product (variation) of the line.
 */
function rx_theme_email_item_image( ?WC_Product $product ): string {
	$image_id = $product ? (int) $product->get_image_id() : 0;

	if ( ! $image_id && $product && $product->get_parent_id() ) {
		$parent   = wc_get_product( $product->get_parent_id() );
		$image_id = $parent ? (int) $parent->get_image_id() : 0;
	}

	$src = $image_id ? rx_theme_email_image_url( $image_id ) : wc_placeholder_img_src( 'woocommerce_gallery_thumbnail' );

	return sprintf(
		'<img src="%1$s" alt="%2$s" width="%3$d" height="%3$d" class="rx-email-item__img" style="display:block;width:%3$dpx;height:%3$dpx;border-radius:4px;border:1px solid #e8e8e8;background:#f5f5f5;">',
		esc_url( $src ),
		esc_attr( $product ? $product->get_name() : '' ),
		RX_THEME_EMAIL_IMAGE_SIZE
	);
}

/**
 * The trust line under every email — the same promises as the site's
 * announcement bar.
 *
 * @return string[]
 */
function rx_theme_email_trust_points(): array {
	return array(
		__( 'Free express shipping over $150', 'rx-theme' ),
		__( '30-day hassle-free returns', 'rx-theme' ),
		__( 'Australian owned', 'rx-theme' ),
	);
}

/**
 * Theme email CSS, appended after WooCommerce's own (and inlined by its
 * mailer). Colours are the theme tokens (assets/css/tokens.css) written
 * out, since email clients don't support CSS variables. Headings use the
 * site's Barlow Condensed where the client allows web fonts, else a
 * condensed system face.
 *
 * @param string $css WooCommerce email CSS.
 */
function rx_theme_email_styles( string $css ): string {
	$display = "'Barlow Condensed', 'Arial Narrow', 'Helvetica Neue', Arial, sans-serif";
	$body    = "'Inter', 'Helvetica Neue', Helvetica, Arial, sans-serif";

	return $css . "
body, #outer_wrapper { background-color: #f3f3f3 !important; }
#wrapper { padding: 24px 0 !important; max-width: 600px; }
#inner_wrapper { background-color: #ffffff; border-radius: 6px; overflow: hidden; border: 1px solid #e8e8e8; }
#template_container, #template_body, #body_content { background-color: #ffffff !important; border: 0 !important; box-shadow: none !important; }
#body_content_inner, #body_content_inner_cell, .font-family, td, th, p { font-family: {$body}; color: #111111; }
#body_content_inner { font-size: 15px; line-height: 1.55; }
#body_content_inner_cell { padding: 8px 32px 32px !important; }

.rx-email-brandbar { background-color: #111111; padding: 18px 32px; }
.rx-email-logo { font-family: {$display}; font-weight: 900; font-size: 26px; line-height: 1; letter-spacing: 0.01em; color: #ffffff; text-decoration: none; }
.rx-email-logo__slash { color: #0066ff; }
.rx-email-logo__tag { display: block; margin-top: 4px; font-family: {$body}; font-weight: 600; font-size: 9px; letter-spacing: 0.14em; color: #aafd30; }
.rx-email-brandbar img { max-height: 40px; width: auto; }
.rx-email-accent { height: 4px; line-height: 4px; font-size: 0; background-color: #0066ff; }

#template_header { background-color: #ffffff !important; border: 0 !important; }
#header_wrapper { padding: 28px 32px 8px !important; }
#template_header h1, h1 { font-family: {$display}; font-weight: 800; font-size: 34px; line-height: 1.05; text-transform: uppercase; letter-spacing: 0.01em; color: #111111 !important; text-align: left; margin: 0; text-shadow: none; }
h2, .email-order-detail-heading { font-family: {$display}; font-weight: 800; font-size: 20px; line-height: 1.2; text-transform: uppercase; letter-spacing: 0.02em; color: #111111 !important; margin: 28px 0 12px; }
.email-order-detail-heading span { display: block; font-family: {$body}; font-size: 13px; font-weight: 400; text-transform: none; letter-spacing: 0; color: #545454; }
h3 { font-family: {$body}; font-size: 15px; font-weight: 700; color: #111111; }
a, .link { color: #0066ff !important; font-weight: 600; }

.td, table.td, .email-order-details td, .email-order-details th { border-color: #e8e8e8 !important; }
.email-order-details .order-totals th, .email-order-details .order-totals td { font-weight: 400; color: #545454; padding: 6px 0 !important; border: 0 !important; }
.email-order-details .order-totals-total th, .email-order-details .order-totals-total td { font-family: {$display}; font-size: 22px !important; font-weight: 800 !important; text-transform: uppercase; color: #111111 !important; padding-top: 12px !important; border-top: 2px solid #111111 !important; }
.order-totals-fee td { color: #e53935 !important; font-weight: 700 !important; }

.rx-email-group th { padding: 10px 12px !important; text-align: left; border: 0 !important; }
.rx-email-group--rotation th { background-color: #111111; color: #ffffff !important; }
.rx-email-group--other th { background-color: #f2f2f2; color: #111111 !important; }
.rx-email-group--gift th { background-color: #aafd30; color: #111111 !important; }
.rx-email-group__title { font-family: {$display}; font-size: 15px; font-weight: 800; letter-spacing: 0.04em; text-transform: uppercase; }
.rx-email-group__note { float: right; font-family: {$body}; font-size: 12px; font-weight: 600; }
.rx-email-group--rotation .rx-email-group__note { color: #aafd30 !important; }
.rx-email-group--other .rx-email-group__note, .rx-email-group--gift .rx-email-group__note { color: #545454 !important; }

.rx-email-item td { border: 0 !important; border-bottom: 1px solid #e8e8e8 !important; padding: 14px 0 !important; vertical-align: middle; }
.rx-email-item__thumb { width: " . ( RX_THEME_EMAIL_IMAGE_SIZE + 16 ) . "px; padding-right: 16px !important; }
.rx-email-item__label { display: inline-block; margin-bottom: 4px; padding: 2px 8px; border-radius: 999px; background-color: #0066ff; color: #ffffff !important; font-size: 10px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
.rx-email-item--gift .rx-email-item__label { background-color: #aafd30; color: #111111 !important; }
.rx-email-item__name { margin: 0; font-family: {$display}; font-size: 17px; font-weight: 700; line-height: 1.2; text-transform: uppercase; color: #111111; }
.rx-email-item__meta { margin: 3px 0 0; font-size: 13px; color: #545454; }
.rx-email-item__qty { font-size: 13px; color: #545454; white-space: nowrap; }
.rx-email-item__price { font-weight: 700; white-space: nowrap; }
.rx-email-item__free { color: #2e7d32; font-weight: 800; text-transform: uppercase; }

.address, address { padding: 12px 14px !important; font-size: 14px; border: 1px solid #e8e8e8 !important; border-radius: 4px; color: #111111 !important; font-style: normal; }
.email-introduction p, #body_content_inner > p { margin: 0 0 14px; }
#addresses td { padding: 0 !important; }

.button, a.button, .email-button { display: inline-block; padding: 14px 24px !important; border-radius: 2px !important; background-color: #0066ff !important; color: #ffffff !important; font-family: {$display}; font-size: 16px !important; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; text-decoration: none !important; }

#template_footer { background-color: #111111 !important; }
#template_footer td { padding: 0; }
#template_footer td.rx-email-trust { padding: 16px 32px !important; background-color: #0066ff; color: #ffffff !important; font-size: 12px; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; text-align: center; }
.rx-email-trust span { color: #aafd30; }
#template_footer td.rx-email-footer { padding: 24px 32px 28px !important; color: #b5b5b5 !important; font-size: 12px; line-height: 1.6; text-align: center; }
.rx-email-footer p, #credit p { color: #b5b5b5 !important; margin: 0 0 6px; }
.rx-email-footer a { color: #ffffff !important; font-weight: 600; }
.rx-email-footer__links { margin-bottom: 12px !important; font-family: {$display}; font-size: 14px; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase; }
.rx-email-footer__links a { margin: 0 8px; text-decoration: none; }

@media screen and (max-width: 600px) {
	#body_content_inner_cell { padding: 8px 18px 24px !important; }
	#header_wrapper { padding: 22px 18px 6px !important; }
	.rx-email-brandbar, .rx-email-trust, .rx-email-footer { padding-left: 18px !important; padding-right: 18px !important; }
	#template_header h1, h1 { font-size: 28px; }
	.rx-email-group__note { float: none; display: block; margin-top: 2px; }
}
";
}
add_filter( 'woocommerce_email_styles', 'rx_theme_email_styles', 20 );
