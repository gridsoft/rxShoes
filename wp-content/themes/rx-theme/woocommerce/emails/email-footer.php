<?php
/**
 * Email Footer
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/emails/email-footer.php,
 * based on core version 10.4.0. Changes from core: a blue trust line
 * (the site announcement bar's promises, rx_theme_email_trust_points())
 * and a dark footer with Shop / My account links above the footer text
 * from WooCommerce → Settings → Emails (still filtered as in core).
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 10.4.0
 */

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- template override: WooCommerce's own hooks and variables, kept by their core names.

$email = $email ?? null;

$email_footer_text = get_option( 'woocommerce_email_footer_text' );
/** This filter is documented in woocommerce/templates/emails/email-styles.php */
if ( apply_filters( 'woocommerce_is_email_preview', false ) ) {
	$text_transient    = get_transient( 'woocommerce_email_footer_text' );
	$email_footer_text = false !== $text_transient ? $text_transient : $email_footer_text;
}

$rx_theme_shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' );
$rx_theme_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/' );
?>
																		</div>
																	</td>
																</tr>
															</table>
															<!-- End Content -->
														</td>
													</tr>
												</table>
												<!-- End Body -->
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr>
								<td align="center" valign="top">
									<!-- Footer -->
									<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_footer" role="presentation">
										<tr>
											<td class="rx-email-trust">
												<?php echo wp_kses( implode( ' <span>&bull;</span> ', array_map( 'esc_html', rx_theme_email_trust_points() ) ), array( 'span' => array() ) ); ?>
											</td>
										</tr>
										<tr>
											<td valign="top" id="credit" class="rx-email-footer">
												<p class="rx-email-footer__links">
													<a href="<?php echo esc_url( $rx_theme_shop_url ); ?>"><?php esc_html_e( 'Shop', 'rx-theme' ); ?></a>
													<a href="<?php echo esc_url( home_url( '/build-a-bundle/' ) ); ?>"><?php esc_html_e( 'Build a bundle', 'rx-theme' ); ?></a>
													<a href="<?php echo esc_url( $rx_theme_account_url ); ?>"><?php esc_html_e( 'My account', 'rx-theme' ); ?></a>
												</p>
												<?php
												echo wp_kses_post(
													wpautop(
														wptexturize(
															/** This filter is documented in woocommerce/templates/emails/email-footer.php */
															apply_filters( 'woocommerce_email_footer_text', $email_footer_text, $email )
														)
													)
												);
												?>
											</td>
										</tr>
									</table>
									<!-- End Footer -->
								</td>
							</tr>
						</table>
					</div>
				</td>
				<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
			</tr>
		</table>
	</body>
</html>
