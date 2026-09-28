/**
 * PayID payment instructions: copy buttons, and a status poll that flips
 * the block to "Payment received" once AzuPay confirms the payment.
 *
 * Markup: templates/payid-instructions.php ([data-rx-payid]).
 * Status comes from rx-azupay/v1/orders/{id}/status (src/RestController.php).
 */
( function () {
	'use strict';

	var root = document.querySelector( '[data-rx-payid]' );
	if ( ! root ) {
		return;
	}

	// Copy buttons.
	root.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-rx-payid-copy]' );
		if ( ! button || ! navigator.clipboard ) {
			return;
		}
		var label = button.textContent;
		navigator.clipboard.writeText( button.getAttribute( 'data-rx-payid-copy' ) ).then( function () {
			button.textContent = 'Copied';
			button.classList.add( 'is-copied' );
			setTimeout( function () {
				button.textContent = label;
				button.classList.remove( 'is-copied' );
			}, 2000 );
		} );
	} );

	// Simulator mode buttons (RX_AZUPAY_SIMULATE): fake the payment or the
	// expiry, then reload so the server renders the new state.
	root.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '[data-rx-payid-simulate-outcome]' );
		if ( ! button ) {
			return;
		}
		var box = button.closest( '[data-rx-payid-simulate]' );
		var target = box.getAttribute( 'data-rx-payid-simulate' ) + button.getAttribute( 'data-rx-payid-simulate-outcome' ) +
			'?key=' + encodeURIComponent( box.getAttribute( 'data-rx-payid-key' ) );
		box.querySelectorAll( 'button' ).forEach( function ( b ) {
			b.disabled = true;
		} );
		fetch( target, { method: 'POST', credentials: 'omit' } ).then( function () {
			window.location.reload();
		} );
	} );

	if ( 'waiting' !== root.getAttribute( 'data-rx-payid-state' ) ) {
		return;
	}

	// Status poll: every 5s while the tab is visible, backing off to 15s
	// after 10 minutes. Stops once paid or the order leaves on-hold.
	var url = root.getAttribute( 'data-rx-payid-status-url' );
	var started = Date.now();
	var timer = null;

	function showPaid() {
		root.classList.remove( 'rx-payid--waiting' );
		root.classList.add( 'rx-payid--paid' );
		root.setAttribute( 'data-rx-payid-state', 'paid' );
		var waiting = root.querySelector( '.rx-payid__waiting' );
		if ( waiting ) {
			waiting.hidden = true;
		}
		root.querySelector( '.rx-payid__paid' ).hidden = false;
	}

	function schedule() {
		var delay = Date.now() - started > 10 * 60 * 1000 ? 15000 : 5000;
		timer = setTimeout( poll, delay );
	}

	function poll() {
		timer = null;
		if ( document.hidden ) {
			return; // Resumed by visibilitychange.
		}
		fetch( url, { credentials: 'same-origin', cache: 'no-store' } )
			.then( function ( response ) {
				return response.ok ? response.json() : null;
			} )
			.then( function ( data ) {
				if ( data && data.paid ) {
					showPaid();
				} else if ( data && 'on-hold' !== data.status ) {
					window.location.reload(); // Cancelled/expired: let the server render it.
				} else {
					schedule();
				}
			} )
			.catch( schedule );
	}

	document.addEventListener( 'visibilitychange', function () {
		if ( ! document.hidden && null === timer && 'waiting' === root.getAttribute( 'data-rx-payid-state' ) ) {
			poll();
		}
	} );

	schedule();
}() );
