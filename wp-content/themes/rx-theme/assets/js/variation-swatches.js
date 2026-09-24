/**
 * Drives the colour/size swatch buttons on the single product page from
 * WooCommerce's own variation form — it does NOT reimplement stock
 * checking, price updates or add-to-cart. Each attribute's real <select>
 * (rendered by wc_dropdown_variation_attribute_options(), see
 * woocommerce/single-product/add-to-cart/variable.php) stays in the DOM,
 * visually hidden (see style.css .rx-variation-select) — clicking a
 * swatch just sets that select's value and fires a native 'change',
 * which is exactly what WooCommerce's own add-to-cart-variation.js
 * listens for. Availability (disabled options) is computed by that same
 * WooCommerce script; this file only reads the result back off the
 * <option> elements after each recompute and reflects it onto the
 * swatch buttons.
 *
 * @package RX_Theme
 */
( function ( $ ) {
	'use strict';

	function initForm( $form ) {
		var $swatchGroups = $form.find( '.rx-variation-swatches' );

		if ( ! $swatchGroups.length ) {
			return;
		}

		function syncGroup( $group ) {
			var selectId = $group.data( 'select-id' );
			var $select = $form.find( '#' + selectId );

			if ( ! $select.length ) {
				return;
			}

			var selected = $select.val();
			var $activeSwatch = null;

			$group.find( '.rx-swatch, .rx-swatch-card' ).each( function () {
				var $swatch = $( this );
				// .attr(), not .data() — jQuery's .data() auto-converts a
				// purely numeric attribute ("10") into an actual number,
				// which then never strictly-equals the select's value
				// (always a string) — broke every plain-digit size ("10",
				// "11"...) while paired sizes ("m10-w11-5") and colour
				// slugs ("grey") happened to work, since only they aren't
				// numeric-looking.
				var value = $swatch.attr( 'data-value' );
				var $option = $select.find( 'option[value="' + value + '"]' );
				var disabled = ! $option.length || $option.prop( 'disabled' );
				var isActive = value === selected && '' !== selected;

				$swatch.toggleClass( 'is-active', isActive );
				$swatch.toggleClass( 'is-disabled', disabled );
				$swatch.attr( 'aria-pressed', isActive ? 'true' : 'false' );

				if ( isActive ) {
					$activeSwatch = $swatch;
				}
			} );

			// "Colourway: <name>" in the row header, e.g. above the colour
			// swatches — kept in sync with whichever card is selected.
			$form.find( '.rx-variations__label-value[data-select-id="' + selectId + '"]' )
				.text( $activeSwatch ? $activeSwatch.data( 'name' ) : '' );
		}

		function syncAll() {
			$swatchGroups.each( function () {
				syncGroup( $( this ) );
			} );
		}

		$swatchGroups.each( function () {
			var $group = $( this );
			var $select = $form.find( '#' + $group.data( 'select-id' ) );

			$group.on( 'click', '.rx-swatch, .rx-swatch-card', function () {
				if ( $( this ).hasClass( 'is-disabled' ) ) {
					return;
				}

				var value = $( this ).attr( 'data-value' );

				// Toggling the same value off mirrors WooCommerce's own
				// "Clear" behaviour — a second click deselects.
				$select.val( $select.val() === value ? '' : value ).trigger( 'change' );
			} );
		} );

		// WooCommerce fires this after every availability recompute
		// (a selection changed, or was reset) — resync swatch state to it
		// rather than trying to duplicate its own compatibility logic.
		$form.on( 'woocommerce_update_variation_values reset_data found_variation', syncAll );

		syncAll();
	}

	$( function () {
		$( '.variations_form' ).each( function () {
			initForm( $( this ) );
		} );
	} );

	/*
	 * Cart page: applying/removing a coupon makes WooCommerce's cart.js
	 * replace the whole cart form (updated_wc_div), including the inline
	 * "Edit size / colour" pickers — start the new ones the same way.
	 */
	$( document.body ).on( 'updated_wc_div', function () {
		$( '.woocommerce-cart-form .variations_form' ).each( function () {
			var $form = $( this );

			if ( typeof $form.wc_variation_form === 'function' ) {
				$form.wc_variation_form();
			}
			initForm( $form );
		} );
	} );
} )( jQuery );
