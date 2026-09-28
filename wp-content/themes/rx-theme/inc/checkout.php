<?php
/**
 * Checkout layout per the client's checkout mockup (2026-09-25; Figma
 * "PayID Exclusive Checkout" / "Mobile VSL Checkout"), minus the express
 * checkout buttons (not in scope, PROJECT.md §8).
 *
 * Desktop: two columns — left, on gray: "Your rotation is ready."
 * heading, the checkout video, the PayID notice, the selection and the
 * saving box (woocommerce/checkout/review-order.php, which WooCommerce
 * re-renders on every AJAX update); right, on white: numbered 1 Details
 * / 2 Delivery / 3 Payment sections and "Complete my order". Phones:
 * the same blocks stacked, plus a bar fixed to the bottom with the total
 * and the order button (mobile frame).
 *
 * Templates: woocommerce/checkout/{form-checkout,form-billing,
 * review-order,payment-method}.php. Still WooCommerce's real classic
 * checkout underneath — validation, AJAX totals, gateways untouched.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Billing field keys shown under "1 Details"; every other billing field
 * goes under "2 Delivery" (form-billing.php).
 *
 * @return string[]
 */
function rx_theme_checkout_detail_field_keys(): array {
	return array( 'billing_email', 'billing_first_name', 'billing_last_name', 'billing_phone' );
}

/**
 * Field order within the two sections: email first, as in the mockup.
 *
 * @param array<string,array<string,mixed>> $fields Billing fields.
 * @return array<string,array<string,mixed>>
 */
function rx_theme_checkout_billing_fields( array $fields ): array {
	$priorities = array(
		'billing_email'      => 1,
		'billing_first_name' => 2,
		'billing_last_name'  => 3,
		'billing_phone'      => 4,
	);

	foreach ( $priorities as $key => $priority ) {
		if ( isset( $fields[ $key ] ) ) {
			$fields[ $key ]['priority'] = $priority;
		}
	}
	if ( isset( $fields['billing_email'] ) ) {
		$fields['billing_email']['class'] = array( 'form-row-wide' );
	}
	if ( isset( $fields['billing_phone'] ) ) {
		$fields['billing_phone']['class'] = array( 'form-row-wide' );
	}

	return $fields;
}
add_filter( 'woocommerce_billing_fields', 'rx_theme_checkout_billing_fields', 20 );

/**
 * "Complete my order" on the order button (mockup). A gateway with its
 * own button text (data-order_button_text) still overrides it, as core
 * does; the lock icon is CSS, so the checkout script's text swaps can't
 * drop it.
 */
function rx_theme_checkout_order_button_text(): string {
	return __( 'Complete my order', 'rx-theme' );
}
add_filter( 'woocommerce_order_button_text', 'rx_theme_checkout_order_button_text' );

/**
 * The cart's rotation state for the checkout's copy: pairs in the
 * rotation, the discount percentage the cart actually gets right now
 * ("percent"; 0 when the offer is closed, there's one pair, or the
 * chosen payment method doesn't qualify), and the percentage it would get
 * with PayID ("available"), for the "switch to PayID" prompts.
 *
 * @return array{pairs:int, percent:float, available:float}
 */
function rx_theme_checkout_rotation(): array {
	if ( ! WC()->cart ) {
		return array(
			'pairs'     => 0,
			'percent'   => 0.0,
			'available' => 0.0,
		);
	}

	$grouping = rx_theme_cart_grouping();
	$pairs    = 0;
	foreach ( array_keys( $grouping['pair_numbers'] ) as $key ) {
		$pairs += (int) ( WC()->cart->get_cart()[ $key ]['quantity'] ?? 0 );
	}

	$percent = 0.0;
	foreach ( WC()->cart->get_fees() as $fee ) {
		if ( (float) $fee->amount < 0 && preg_match( '/\(-([\d.]+)%\)/', $fee->name, $matches ) ) {
			$percent = (float) $matches[1];
		}
	}

	return array(
		'pairs'     => $pairs,
		'percent'   => $percent,
		'available' => rx_theme_bundle_offer_is_active() ? (float) $grouping['tier_percent'] : 0.0,
	);
}

/**
 * Checkout video (Appearance > Customize > Checkout): an MP4 from the
 * media library or any URL, with an optional poster and caption. The
 * block only renders once a video is set.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function rx_theme_customize_checkout_register( WP_Customize_Manager $wp_customize ): void {
	$wp_customize->add_section(
		'rx_checkout',
		array(
			'title'       => __( 'Checkout', 'rx-theme' ),
			'description' => __( 'The short video shown above the order summary at checkout ("60 seconds before you check out"). Hidden until a video is set.', 'rx-theme' ),
			'priority'    => 32,
		)
	);

	$wp_customize->add_setting(
		'rx_checkout_video',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		'rx_checkout_video',
		array(
			'section'     => 'rx_checkout',
			'type'        => 'url',
			'label'       => __( 'Video file URL (MP4)', 'rx-theme' ),
			'description' => __( 'Upload the MP4 under Media, then paste its file URL here. Keep it short and small (under ~10 MB) — most shoppers are on phones.', 'rx-theme' ),
		)
	);

	$wp_customize->add_setting(
		'rx_checkout_video_poster',
		array(
			'default'           => '',
			'sanitize_callback' => 'esc_url_raw',
		)
	);
	$wp_customize->add_control(
		new WP_Customize_Image_Control(
			$wp_customize,
			'rx_checkout_video_poster',
			array(
				'section' => 'rx_checkout',
				'label'   => __( 'Video poster image', 'rx-theme' ),
			)
		)
	);

	$captions = array(
		'rx_checkout_video_title'   => array(
			'label'   => __( 'Video title', 'rx-theme' ),
			'default' => __( '60 seconds before you check out', 'rx-theme' ),
		),
		'rx_checkout_video_caption' => array(
			'label'   => __( 'Video caption', 'rx-theme' ),
			'default' => __( 'Choose PayID below to receive the eligible promotional price', 'rx-theme' ),
		),
	);
	foreach ( $captions as $id => $caption ) {
		$wp_customize->add_setting(
			$id,
			array(
				'default'           => $caption['default'],
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
		$wp_customize->add_control(
			$id,
			array(
				'section' => 'rx_checkout',
				'type'    => 'text',
				'label'   => $caption['label'],
			)
		);
	}
}
add_action( 'customize_register', 'rx_theme_customize_checkout_register' );

/**
 * Checkout script: keeps the phone bar's total in step with the order
 * summary after each AJAX refresh, and its button submits the real
 * checkout form.
 */
function rx_theme_enqueue_checkout_script(): void {
	if ( ! is_checkout() || is_wc_endpoint_url( 'order-received' ) || is_wc_endpoint_url( 'order-pay' ) ) {
		return;
	}

	$file = RX_THEME_DIR . '/assets/js/checkout.js';
	wp_enqueue_script(
		'rx-theme-checkout',
		RX_THEME_URI . '/assets/js/checkout.js',
		array( 'jquery', 'wc-checkout' ),
		file_exists( $file ) ? (string) filemtime( $file ) : RX_THEME_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);
}
add_action( 'wp_enqueue_scripts', 'rx_theme_enqueue_checkout_script' );
