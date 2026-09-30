<?php
/**
 * Checkout Form
 *
 * TEMPLATE OVERRIDE of woocommerce/templates/checkout/form-checkout.php,
 * based on core version 9.4.0. Laid out per the client's checkout mockup
 * (see inc/checkout.php): left, the summary column (heading, video,
 * PayID notice, and the order review — selection + saving box); right,
 * "1 Details" / "2 Delivery" (billing + shipping fields) / "3 Payment";
 * on phones, a bar fixed to the bottom with the total and order button.
 * The whole layout sits inside the real checkout <form>. Because the
 * review (left) and payment (right) are split across the columns, the
 * woocommerce_checkout_order_review action is replaced by direct calls
 * to its two core callbacks, woocommerce_order_review() and
 * woocommerce_checkout_payment(); anything else hooked to that action
 * would not run (nothing is, today). All other core hooks are kept.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- template override: WooCommerce's own hooks, kept by their core names.

do_action( 'woocommerce_before_checkout_form', $checkout );

// If checkout registration is disabled and not logged in, the user cannot checkout.
if ( ! $checkout->is_registration_enabled() && $checkout->is_registration_required() && ! is_user_logged_in() ) {
	echo esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'You must be logged in to checkout.', 'woocommerce' ) ) );
	return;
}

$rx_theme_rotation = rx_theme_checkout_rotation();
$rx_theme_video    = (string) get_theme_mod( 'rx_checkout_video', '' );
$rx_theme_poster   = (string) get_theme_mod( 'rx_checkout_video_poster', '' );
// The discount available with PayID: this column isn't re-rendered when the payment method changes.
$rx_theme_percent = rx_theme_format_percent( $rx_theme_rotation['available'] );
?>

<form name="checkout" method="post" class="checkout woocommerce-checkout rx-checkout" action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">

	<div class="rx-checkout__summary">
		<h1 class="rx-checkout__title">
			<?php echo esc_html( $rx_theme_rotation['pairs'] > 1 ? __( 'Your rotation is ready.', 'rx-theme' ) : __( 'Checkout', 'rx-theme' ) ); ?>
		</h1>

		<?php if ( '' !== $rx_theme_video ) : ?>
			<figure class="rx-checkout-video">
				<video class="rx-checkout-video__media" src="<?php echo esc_url( $rx_theme_video ); ?>" <?php echo $rx_theme_poster ? 'poster="' . esc_url( $rx_theme_poster ) . '"' : ''; ?> controls playsinline preload="none"></video>
				<figcaption class="rx-checkout-video__caption">
					<strong><?php echo esc_html( get_theme_mod( 'rx_checkout_video_title', __( '60 seconds before you check out', 'rx-theme' ) ) ); ?></strong>
					<span><?php echo esc_html( get_theme_mod( 'rx_checkout_video_caption', __( 'Choose PayID below to receive the eligible promotional price', 'rx-theme' ) ) ); ?></span>
				</figcaption>
			</figure>
		<?php endif; ?>

		<?php
		// The homepage video (Customizer > RX Homepage > Video), same click-to-play embed.
		get_template_part( 'template-parts/front-page/video', null, array( 'modifier' => 'checkout' ) );
		?>

		<?php if ( $rx_theme_rotation['available'] > 0 && $rx_theme_rotation['pairs'] > 1 ) : ?>
			<p class="rx-checkout-unlocked">
				<?php
				/* translators: %s: discount percentage. */
				echo esc_html( sprintf( __( '%s%% off unlocked', 'rx-theme' ), $rx_theme_percent ) );
				?>
			</p>
			<div class="rx-checkout-notice" role="note">
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
				<div>
					<strong><?php esc_html_e( 'PayID exclusive', 'rx-theme' ); ?></strong>
					<p>
						<?php
						/* translators: %s: discount percentage. */
						echo esc_html( sprintf( __( 'Your %s%% bundle discount applies when you pay by PayID.', 'rx-theme' ), $rx_theme_percent ) );
						?>
					</p>
				</div>
			</div>
		<?php endif; ?>

		<?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>

		<h2 id="order_review_heading" class="rx-checkout__subtitle"><?php esc_html_e( 'Your selection', 'rx-theme' ); ?></h2>

		<?php do_action( 'woocommerce_checkout_before_order_review' ); ?>

		<div id="order_review" class="woocommerce-checkout-review-order">
			<?php woocommerce_order_review(); ?>
		</div>
	</div>

	<div class="rx-checkout__main">
		<?php if ( $checkout->get_checkout_fields() ) : ?>

			<?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

			<div id="customer_details">
				<?php do_action( 'woocommerce_checkout_billing' ); ?>
				<?php do_action( 'woocommerce_checkout_shipping' ); ?>
			</div>

			<?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

		<?php endif; ?>

		<h3 class="rx-checkout-step"><span class="rx-checkout-step__num">3</span><?php esc_html_e( 'Payment', 'rx-theme' ); ?></h3>

		<?php
		woocommerce_checkout_payment();

		do_action( 'woocommerce_checkout_after_order_review' );
		?>

		<ul class="rx-checkout-trust">
			<li>
				<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false" fill="currentColor"><path d="M12 2 4 5v6c0 5 3.4 9.7 8 11 4.6-1.3 8-6 8-11V5l-8-3z"/></svg>
				<?php esc_html_e( 'Secure payment', 'rx-theme' ); ?>
			</li>
			<li>
				<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 15.5-6.3L21 8M21 3v5h-5M21 12a9 9 0 0 1-15.5 6.3L3 16M3 21v-5h5"/></svg>
				<?php esc_html_e( '30-day returns', 'rx-theme' ); ?>
			</li>
			<li>
				<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1v-6h3zM3 19a2 2 0 0 0 2 2h1v-6H3z"/></svg>
				<?php esc_html_e( 'Australian support', 'rx-theme' ); ?>
			</li>
		</ul>
	</div>

	<div class="rx-checkout-bar" aria-hidden="true">
		<div class="rx-checkout-bar__total">
			<span class="rx-checkout-bar__label"><?php esc_html_e( 'Total', 'rx-theme' ); ?></span>
			<strong data-rx-checkout-bar-total><?php wc_cart_totals_order_total_html(); ?></strong>
		</div>
		<button type="button" class="rx-checkout-bar__button" data-rx-checkout-bar-submit tabindex="-1"><?php esc_html_e( 'Complete order', 'rx-theme' ); ?></button>
	</div>

</form>

<?php do_action( 'woocommerce_after_checkout_form', $checkout ); ?>
