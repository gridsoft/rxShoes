<?php
/**
 * The bundle offer's run window: when the whole bundle feature is live.
 *
 * Set under Appearance > Customize > Bundle Discount ("Offer starts" /
 * "Offer ends", see inc/customizer-bundle.php). Outside that window
 * rx_theme_bundle_offer_is_active() is false and the site runs as a
 * plain WooCommerce store: no product is bundle-eligible
 * (rx_theme_product_is_bundle_eligible()), so the real cart discount,
 * the rotation grouping in the mini cart / cart / checkout, the shop
 * card and PDP bundle treatment and the "Bundle eligible" shop filter
 * all switch off together — and the purely promotional pieces (header
 * CTA, announcement promo, footer rotation bar, homepage Power Rotation,
 * shop rotation strip) check the same function directly. The mini cart,
 * cart and checkout keep their own design; only the bundle parts drop
 * out. /build-a-bundle/ redirects to the shop.
 *
 * Both dates are optional: no start means "already running", no end
 * means "runs until switched off". Stored as options (like the discount
 * percentages next to them) in the site's own timezone (Settings >
 * General), as "2026-10-01T09:00".
 *
 * Nothing is scheduled: every request compares the current time against
 * the window, so the offer stops at the configured minute without a
 * cron job. A full-page cache in production must still be purged (or
 * expire) at that moment for cached pages to follow.
 *
 * @package RX_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stored format of the two dates — what an <input type="datetime-local">
 * submits.
 */
const RX_THEME_BUNDLE_OFFER_DATE_FORMAT = 'Y-m-d\TH:i';

/**
 * One of the window's dates (option `rx_bundle_offer_start` or
 * `rx_bundle_offer_end`), in the site's timezone, or null when unset.
 *
 * @param string $option_name Option name.
 */
function rx_theme_bundle_offer_date( string $option_name ): ?DateTimeImmutable {
	$stored = (string) get_option( $option_name, '' );

	if ( '' === $stored ) {
		return null;
	}

	$date = DateTimeImmutable::createFromFormat( RX_THEME_BUNDLE_OFFER_DATE_FORMAT, $stored, wp_timezone() );

	return $date ? $date : null;
}

/**
 * Where "now" sits against the offer window: 'scheduled' (before the
 * start), 'running' or 'ended' (at or after the end).
 */
function rx_theme_bundle_offer_state(): string {
	$now   = new DateTimeImmutable( 'now', wp_timezone() );
	$start = rx_theme_bundle_offer_date( 'rx_bundle_offer_start' );
	$end   = rx_theme_bundle_offer_date( 'rx_bundle_offer_end' );

	if ( $start && $now < $start ) {
		return 'scheduled';
	}

	if ( $end && $now >= $end ) {
		return 'ended';
	}

	return 'running';
}

/**
 * Whether the bundle offer (and with it every bundle feature on the
 * site) is live right now.
 */
function rx_theme_bundle_offer_is_active(): bool {
	/**
	 * Filters whether the bundle offer is live.
	 *
	 * @param bool $active True inside the configured window.
	 */
	return (bool) apply_filters( 'rx_theme_bundle_offer_is_active', 'running' === rx_theme_bundle_offer_state() );
}

/**
 * Sanitize a window date from the Customizer: a datetime-local value
 * normalised to RX_THEME_BUNDLE_OFFER_DATE_FORMAT (seconds dropped), or
 * '' when empty or unparseable.
 *
 * @param mixed $value Raw submitted value.
 */
function rx_theme_sanitize_bundle_offer_date( $value ): string {
	$value = trim( (string) $value );

	foreach ( array( RX_THEME_BUNDLE_OFFER_DATE_FORMAT, 'Y-m-d\TH:i:s' ) as $format ) {
		$date = DateTimeImmutable::createFromFormat( '!' . $format, $value, wp_timezone() );

		if ( $date && $date->format( $format ) === $value ) {
			return $date->format( RX_THEME_BUNDLE_OFFER_DATE_FORMAT );
		}
	}

	return '';
}

/**
 * "Status: running — ends 31 Oct 2026, 11:59 pm." for the Customizer
 * section, so an admin can see at a glance what the saved dates mean.
 */
function rx_theme_bundle_offer_status_text(): string {
	$format = get_option( 'date_format' ) . ', ' . get_option( 'time_format' );
	$start  = rx_theme_bundle_offer_date( 'rx_bundle_offer_start' );
	$end    = rx_theme_bundle_offer_date( 'rx_bundle_offer_end' );

	switch ( rx_theme_bundle_offer_state() ) {
		case 'scheduled':
			/* translators: %s: start date and time. */
			return sprintf( __( 'Status: scheduled — bundles switch on %s.', 'rx-theme' ), wp_date( $format, $start->getTimestamp() ) );
		case 'ended':
			/* translators: %s: end date and time. */
			return sprintf( __( 'Status: ended %s — bundles are off and the store runs without them.', 'rx-theme' ), wp_date( $format, $end->getTimestamp() ) );
		default:
			return $end
				/* translators: %s: end date and time. */
				? sprintf( __( 'Status: running — ends %s.', 'rx-theme' ), wp_date( $format, $end->getTimestamp() ) )
				: __( 'Status: running, with no end date.', 'rx-theme' );
	}
}

/**
 * Send /build-a-bundle/ to the shop while the offer is off — the page
 * has nothing to build without eligible products.
 */
function rx_theme_bundle_offer_redirect_builder(): void {
	if ( rx_theme_bundle_offer_is_active() || ! is_page( 'build-a-bundle' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
		return;
	}

	wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
	exit;
}
add_action( 'template_redirect', 'rx_theme_bundle_offer_redirect_builder' );
