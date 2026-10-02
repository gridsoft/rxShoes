<?php
/**
 * Email Header
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/emails/email-header.php,
 * based on core version 10.7.0. Changes from core: the logo area is the
 * RX brand bar (black, "RX/ SHOE" wordmark like the site header, blue
 * accent line) — or the header image from WooCommerce → Settings →
 * Emails when one is set. Everything else (wrappers, ids, heading) is
 * core's, so WooCommerce's own CSS and the theme's (inc/emails.php)
 * still apply.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template override: WooCommerce's own hooks and variables, kept by their core names.

$store_name = $store_name ?? get_bloginfo( 'name', 'display' );

/** This filter is documented in woocommerce/templates/emails/email-header.php */
$header_image_url = apply_filters( 'woocommerce_email_header_image_url', home_url() );

$img = get_option( 'woocommerce_email_header_image' );
/** This filter is documented in woocommerce/templates/emails/email-styles.php */
if ( apply_filters( 'woocommerce_is_email_preview', false ) ) {
	$img_transient = get_transient( 'woocommerce_email_header_image' );
	$img           = false !== $img_transient ? $img_transient : $img;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<title><?php echo esc_html( $store_name ); ?></title>
		<?php // The site's fonts, for the email apps that load web fonts (Apple Mail, iOS); others fall back to system faces. ?>
		<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@700;800;900&amp;family=Inter:wght@400;600;700&amp;display=swap" rel="stylesheet">
	</head>
	<body <?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0" marginwidth="0" topmargin="0" marginheight="0" offset="0">
		<table width="100%" id="outer_wrapper" role="presentation">
			<tr>
				<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
				<td width="600">
					<div id="wrapper" dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
						<table border="0" cellpadding="0" cellspacing="0" height="100%" width="100%" id="inner_wrapper" role="presentation">
							<tr>
								<td align="center" valign="top">
									<table border="0" cellpadding="0" cellspacing="0" width="100%" role="presentation">
										<tr>
											<td id="template_header_image" class="rx-email-brandbar" align="left">
												<a href="<?php echo esc_url( $header_image_url ? $header_image_url : home_url() ); ?>" style="text-decoration:none;" target="_blank">
													<?php if ( $img ) : ?>
														<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $store_name ); ?>" />
													<?php else : ?>
														<span class="rx-email-logo">RX<span class="rx-email-logo__slash">/</span> SHOE</span>
														<span class="rx-email-logo__tag"><?php esc_html_e( 'PERFORMANCE ROTATION', 'rx-theme' ); ?></span>
													<?php endif; ?>
												</a>
											</td>
										</tr>
										<tr>
											<td class="rx-email-accent">&nbsp;</td>
										</tr>
									</table>
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container" role="presentation">
										<tr>
											<td align="center" valign="top">
												<!-- Header -->
												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header" role="presentation">
													<tr>
														<td id="header_wrapper">
															<h1><?php echo esc_html( $email_heading ); ?></h1>
														</td>
													</tr>
												</table>
												<!-- End Header -->
											</td>
										</tr>
										<tr>
											<td align="center" valign="top">
												<!-- Body -->
												<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body" role="presentation">
													<tr>
														<td valign="top" id="body_content">
															<!-- Content -->
															<table border="0" cellpadding="20" cellspacing="0" width="100%" role="presentation">
																<tr>
																	<td valign="top" id="body_content_inner_cell">
																		<div id="body_content_inner">
