/**
 * Shop sidebar price slider: two overlapping range inputs (min / max).
 * Keeps them from crossing, updates the labels and the filled track while
 * dragging, and submits the form shortly after the customer stops.
 *
 * Without this script the form still works — the Apply button submits it.
 * A slider left at its full range sends no price at all, so the URL stays
 * clean and the archive isn't filtered.
 */
( function () {
	'use strict';

	document.querySelectorAll( '.rx-price' ).forEach( function ( form ) {
		var min = form.querySelector( 'input[name="min_price"]' );
		var max = form.querySelector( 'input[name="max_price"]' );
		var minLabel = form.querySelector( '[data-rx-price-min]' );
		var maxLabel = form.querySelector( '[data-rx-price-max]' );
		var apply = form.querySelector( '.rx-price__apply' );
		var timer;

		if ( ! min || ! max ) {
			return;
		}

		var lo = Number( min.min );
		var hi = Number( min.max );

		function format( value ) {
			return ( form.dataset.format || '%s' ).replace( '%s', value.toLocaleString() );
		}

		function sync( changed ) {
			var a = Number( min.value );
			var b = Number( max.value );

			if ( a > b ) {
				if ( changed === min ) {
					min.value = b;
					a = b;
				} else {
					max.value = a;
					b = a;
				}
			}

			minLabel.textContent = format( a );
			maxLabel.textContent = format( b );
			form.style.setProperty( '--rx-lo', ( ( a - lo ) / ( hi - lo ) ) * 100 + '%' );
			form.style.setProperty( '--rx-hi', ( ( b - lo ) / ( hi - lo ) ) * 100 + '%' );
		}

		function submit() {
			// Untouched ends aren't a filter; leave them out of the URL.
			min.disabled = Number( min.value ) <= lo;
			max.disabled = Number( max.value ) >= hi;
			form.submit();
		}

		[ min, max ].forEach( function ( input ) {
			input.addEventListener( 'input', function () {
				sync( input );
			} );
			input.addEventListener( 'change', function () {
				sync( input );
				clearTimeout( timer );
				timer = setTimeout( submit, 400 );
			} );
		} );

		// Coming back with the browser's back button can restore the page
		// with the inputs still disabled from the last submit.
		window.addEventListener( 'pageshow', function () {
			min.disabled = false;
			max.disabled = false;
		} );

		if ( apply ) {
			apply.hidden = true;
		}

		sync();
	} );
}() );
