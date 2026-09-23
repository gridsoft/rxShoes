/**
 * Shows the Pair 2 / Pair 3 product pickers only while "Eligible for
 * bundle" is ticked, in the product edit screen's Bundle tab. Pure UI
 * convenience — the fields still save correctly even if left filled in
 * while hidden; the actual eligibility rule is the checkbox's own saved
 * value (see RX\Core\Bundles\BundleEligibility), not this script.
 *
 * @package RX\Core
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $checkbox = $( '#_rx_bundle_eligible' );
		var $pairs = $( '#rx_bundle_rotation_pairs' );

		if ( ! $checkbox.length || ! $pairs.length ) {
			return;
		}

		function sync() {
			$pairs.toggle( $checkbox.is( ':checked' ) );
		}

		$checkbox.on( 'change', sync );
		sync();
	} );
} )( jQuery );
