/**
 * Rotation drawer (mini cart) — see inc/mini-cart.php.
 *
 * Opens on hover of the header cart icon on devices that can hover, and
 * on click/tap everywhere (most traffic is mobile, where there's no
 * hover). A hover-open closes again shortly after the pointer leaves both
 * the icon and the drawer; a click-open stays until closed (X, backdrop,
 * Escape). Without this script the icon is just a link to the cart page.
 *
 * Remove buttons use WooCommerce's remove_from_cart AJAX endpoint and
 * swap in the returned cart fragments (drawer content + count badge);
 * their href (a nonce'd remove URL) is the no-JS / error fallback.
 */
( function ( $ ) {
	'use strict';

	var drawer = document.getElementById( 'rx-mini-cart' );
	var trigger = document.querySelector( '.rx-icon-link--cart[aria-controls="rx-mini-cart"]' );
	var backdrop = document.querySelector( '.rx-mini-cart-backdrop' );

	if ( ! drawer || ! trigger ) {
		return;
	}

	var canHover = window.matchMedia( '(hover: hover) and (pointer: fine)' );
	var closeTimer = null;
	var pinned = false; // True when opened by click: ignore mouseleave.

	function isOpen() {
		return drawer.classList.contains( 'is-open' );
	}

	function open( byClick ) {
		clearTimeout( closeTimer );
		pinned = pinned || byClick;

		if ( byClick && backdrop ) {
			backdrop.hidden = false;
		}

		drawer.classList.add( 'is-open' );
		drawer.setAttribute( 'aria-hidden', 'false' );
		trigger.setAttribute( 'aria-expanded', 'true' );

		if ( byClick ) {
			var close = drawer.querySelector( '.rx-mini-cart__close' );
			if ( close ) {
				close.focus( { preventScroll: true } );
			}
		}
	}

	function close( returnFocus ) {
		clearTimeout( closeTimer );
		pinned = false;

		if ( backdrop ) {
			backdrop.hidden = true;
		}

		drawer.classList.remove( 'is-open' );
		drawer.setAttribute( 'aria-hidden', 'true' );
		trigger.setAttribute( 'aria-expanded', 'false' );

		if ( returnFocus ) {
			trigger.focus( { preventScroll: true } );
		}
	}

	function scheduleClose() {
		if ( pinned ) {
			return;
		}
		clearTimeout( closeTimer );
		closeTimer = setTimeout( function () {
			close( false );
		}, 250 );
	}

	trigger.addEventListener( 'click', function ( event ) {
		event.preventDefault();

		if ( isOpen() && pinned ) {
			close( true );
		} else {
			open( true );
		}
	} );

	[ trigger, drawer ].forEach( function ( el ) {
		el.addEventListener( 'mouseenter', function () {
			if ( canHover.matches ) {
				open( false );
			}
		} );
		el.addEventListener( 'mouseleave', function () {
			if ( canHover.matches ) {
				scheduleClose();
			}
		} );
	} );

	/*
	 * A hover-opened drawer sits over the cart icon, so a click aimed at
	 * the icon lands in the drawer. Any interaction inside it counts as
	 * intent: pin it so drifting the mouse out doesn't close it.
	 */
	drawer.addEventListener( 'pointerdown', function () {
		if ( isOpen() ) {
			pinned = true;
			clearTimeout( closeTimer );
		}
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( event.target.closest( '[data-rx-mini-cart-close]' ) ) {
			close( true );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && isOpen() ) {
			close( drawer.contains( document.activeElement ) );
		}
	} );

	/* Keyboard users: tabbing out of an open drawer closes it. */
	drawer.addEventListener( 'focusout', function ( event ) {
		if ( event.relatedTarget && ! drawer.contains( event.relatedTarget ) && event.relatedTarget !== trigger ) {
			close( false );
		}
	} );

	/* Remove a line over AJAX. */
	drawer.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( '.rx-mini-cart__remove' );
		var params = window.wc_cart_fragments_params;

		if ( ! link || ! params ) {
			return;
		}

		event.preventDefault();
		pinned = true; // Keep the drawer open while the content is swapped.
		drawer.classList.add( 'is-loading' );

		var body = new URLSearchParams();
		body.append( 'cart_item_key', link.getAttribute( 'data-cart-item-key' ) );

		fetch( params.wc_ajax_url.toString().replace( '%%endpoint%%', 'remove_from_cart' ), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( ! data || ! data.fragments ) {
					throw new Error( 'No fragments' );
				}

				/*
				 * The bundle builder page renders the same cart in its own
				 * layout — reload it rather than leave it out of date.
				 */
				if ( document.body.classList.contains( 'rx-bundle-builder-page' ) ) {
					window.location.reload();
					return;
				}

				$.each( data.fragments, function ( selector, html ) {
					$( selector ).replaceWith( html );
				} );

				// Lets wc-cart-fragments refresh its stored copy.
				$( document.body ).trigger( 'removed_from_cart', [ data.fragments, data.cart_hash, $( link ) ] );
				drawer.classList.remove( 'is-loading' );
			} )
			.catch( function () {
				window.location.href = link.href;
			} );
	} );
} )( window.jQuery );
