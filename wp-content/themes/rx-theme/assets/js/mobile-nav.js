/**
 * Mobile / tablet menu (template-parts/header/mobile-nav.php): the
 * header's ☰ button opens the slide-in panel; ✕, the backdrop, Escape,
 * or widening the window to desktop close it. While open the page behind
 * doesn't scroll and focus moves into the panel (and back to the button
 * on close).
 */
( function () {
	'use strict';

	var toggle = document.querySelector( '.rx-menu-toggle' );
	var panel = document.getElementById( 'rx-mobile-nav' );
	var backdrop = document.querySelector( '.rx-mobile-nav-backdrop' );

	if ( ! toggle || ! panel ) {
		return;
	}

	var desktop = window.matchMedia( '(min-width: 62em)' );

	function isOpen() {
		return panel.classList.contains( 'is-open' );
	}

	function open() {
		panel.classList.add( 'is-open' );
		panel.setAttribute( 'aria-hidden', 'false' );
		toggle.setAttribute( 'aria-expanded', 'true' );
		if ( backdrop ) {
			backdrop.hidden = false;
		}
		document.documentElement.classList.add( 'rx-no-scroll' );

		var close = panel.querySelector( '.rx-mobile-nav__close' );
		if ( close ) {
			close.focus( { preventScroll: true } );
		}
	}

	function close( returnFocus ) {
		panel.classList.remove( 'is-open' );
		panel.setAttribute( 'aria-hidden', 'true' );
		toggle.setAttribute( 'aria-expanded', 'false' );
		if ( backdrop ) {
			backdrop.hidden = true;
		}
		document.documentElement.classList.remove( 'rx-no-scroll' );

		if ( returnFocus ) {
			toggle.focus( { preventScroll: true } );
		}
	}

	toggle.addEventListener( 'click', function () {
		if ( isOpen() ) {
			close( true );
		} else {
			open();
		}
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( isOpen() && event.target.closest( '[data-rx-mobile-nav-close]' ) ) {
			close( true );
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key && isOpen() ) {
			close( true );
		}
	} );

	// Keep keyboard focus inside the open panel.
	panel.addEventListener( 'keydown', function ( event ) {
		if ( 'Tab' !== event.key ) {
			return;
		}
		var focusable = panel.querySelectorAll( 'a[href], button, input:not([type="hidden"])' );
		var first = focusable[ 0 ];
		var last = focusable[ focusable.length - 1 ];
		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	} );

	function onWiden( query ) {
		if ( query.matches && isOpen() ) {
			close( false );
		}
	}
	if ( desktop.addEventListener ) {
		desktop.addEventListener( 'change', onWiden );
	}
}() );
