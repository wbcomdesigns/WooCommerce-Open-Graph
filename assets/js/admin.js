/**
 * Open Graph for WooCommerce - settings screen behaviour.
 *
 * Sitemap Generate/Test buttons and the media-library image picker.
 * Strings, the AJAX URL and the nonce come from the localized `wogAdmin`.
 *
 * @since 2.1.0
 */
( function () {
	'use strict';

	var config = window.wogAdmin || {};
	var i18n   = config.i18n || {};

	function showResult( success, message ) {
		var results = document.getElementById( 'wog-sitemap-results' );
		if ( ! results ) {
			return;
		}
		var notice = document.createElement( 'div' );
		var text   = document.createElement( 'p' );
		notice.className = 'notice inline ' + ( success ? 'notice-success' : 'notice-error' );
		text.textContent = message;
		notice.appendChild( text );
		results.replaceChildren( notice );
	}

	function runSitemapAction( button ) {
		var action = button.getAttribute( 'data-wog-sitemap' );
		var idle   = 'generate' === action ? i18n.generate : i18n.test;
		var body   = new URLSearchParams( {
			action: 'generate' === action ? 'wog_generate_sitemap' : 'wog_test_sitemap',
			nonce: config.nonce || ''
		} );

		button.disabled    = true;
		button.textContent = 'generate' === action ? i18n.generating : i18n.testing;

		fetch( config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				var message = data && data.data && data.data.message ? data.data.message : i18n.failed;
				showResult( !! ( data && data.success ), message );
			} )
			.catch( function () {
				showResult( false, i18n.failed );
			} )
			.finally( function () {
				button.disabled    = false;
				button.textContent = idle;
			} );
	}

	function selectImage( button ) {
		var field   = button.closest( '.wog-image-field' );
		var input   = field.querySelector( 'input[type="url"]' );
		var preview = field.querySelector( '.wog-image-preview' );

		// wp_enqueue_media() runs on this screen; without it, typing a URL still works.
		if ( ! window.wp || ! window.wp.media ) {
			input.focus();
			return;
		}

		var frame = window.wp.media( {
			title: i18n.chooseImage,
			button: { text: i18n.useImage },
			multiple: false,
			library: { type: 'image' }
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			input.value    = attachment.url;
			preview.src    = attachment.url;
			preview.hidden = false;
		} );

		frame.open();
	}

	document.addEventListener( 'click', function ( event ) {
		var sitemapButton = event.target.closest( '[data-wog-sitemap]' );
		if ( sitemapButton ) {
			event.preventDefault();
			runSitemapAction( sitemapButton );
			return;
		}

		var imageButton = event.target.closest( '[data-wog-select-image]' );
		if ( imageButton ) {
			event.preventDefault();
			selectImage( imageButton );
		}
	} );
} )();
