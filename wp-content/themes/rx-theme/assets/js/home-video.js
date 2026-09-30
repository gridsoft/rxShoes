/**
 * Homepage video facade (template-parts/front-page/video.php): swaps the
 * cover image + play button for the real YouTube/Vimeo player on click,
 * so none of the player's weight loads with the page. On first hover or
 * focus it also warms up the connection to the player's host, so the
 * click starts faster.
 */
( function () {
	'use strict';

	document.querySelectorAll( '[data-rx-video-embed]' ).forEach( function ( button ) {
		var src = button.getAttribute( 'data-rx-video-embed' );
		var warmed = false;

		function warm() {
			if ( warmed ) {
				return;
			}
			warmed = true;

			var link = document.createElement( 'link' );
			link.rel = 'preconnect';
			link.href = new URL( src ).origin;
			document.head.appendChild( link );
		}

		button.addEventListener( 'pointerover', warm, { once: true } );
		button.addEventListener( 'focus', warm, { once: true } );

		button.addEventListener( 'click', function () {
			var iframe = document.createElement( 'iframe' );
			iframe.className = 'rx-video__iframe';
			iframe.src = src;
			iframe.title = button.getAttribute( 'data-rx-video-title' ) || '';
			iframe.allow = 'autoplay; fullscreen; picture-in-picture; encrypted-media';
			iframe.allowFullscreen = true;

			button.replaceWith( iframe );
			iframe.focus();
		} );
	} );
}() );
