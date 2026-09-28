/**
 * Checkout phone bar (woocommerce/checkout/form-checkout.php): keeps its
 * total in step with the order summary after each WooCommerce AJAX
 * refresh, and its button clicks the real #place_order, so validation
 * and gateways run exactly as they do for the main button.
 */
( function ( $ ) {
	'use strict';

	var $bar = $( '.rx-checkout-bar' );
	if ( ! $bar.length ) {
		return;
	}

	function syncTotal() {
		var $total = $( '[data-rx-checkout-total]' ).first();
		if ( $total.length ) {
			$bar.find( '[data-rx-checkout-bar-total]' ).html( $total.html() );
		}
	}

	$( document.body ).on( 'updated_checkout', syncTotal );

	// The rotation discount depends on the payment method (PayID only,
	// enforced server-side), so re-total the order whenever it changes.
	// WooCommerce doesn't do this by default.
	$( 'form.checkout' ).on( 'change', 'input[name="payment_method"]', function () {
		$( document.body ).trigger( 'update_checkout' );
	} );

	// "Switch to PayID" in the removed-discount warning (review-order.php).
	// A real click, not just checked + change: WooCommerce's checkout script
	// tracks the chosen method through click events and re-selects its
	// remembered choice after each refresh, so a bare change would leave the
	// old method ticked while the order was re-totalled for PayID.
	$( document.body ).on( 'click', '[data-rx-switch-payid]', function () {
		var $payid = $( '#payment_method_azupay_payid' );
		if ( $payid.length ) {
			$payid.trigger( 'click' );
			$( 'html, body' ).animate( { scrollTop: $payid.closest( '.wc_payment_method' ).offset().top - 80 }, 250 );
		}
	} );

	$bar.on( 'click', '[data-rx-checkout-bar-submit]', function () {
		$( '#place_order' ).trigger( 'click' );
	} );

	// Hide the bar while the real button is on screen (no two identical
	// buttons at once). #place_order is re-rendered on every refresh, so
	// the observer is re-attached each time.
	if ( 'IntersectionObserver' in window ) {
		var observer = new IntersectionObserver( function ( entries ) {
			$bar.toggleClass( 'is-hidden', entries[ 0 ].isIntersecting );
		} );
		var observe = function () {
			var button = document.getElementById( 'place_order' );
			observer.disconnect();
			if ( button ) {
				observer.observe( button );
			}
		};
		$( document.body ).on( 'updated_checkout', observe );
		observe();
	}
}( jQuery ) );
