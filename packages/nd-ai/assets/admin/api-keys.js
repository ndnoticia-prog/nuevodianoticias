( function () {
	'use strict';

	const root = document.getElementById( 'nd-ai-keys' );

	if ( ! root ) {
		return;
	}

	const restBase = root.dataset.restBase;
	const nonce    = root.dataset.nonce;

	function apiUrl( path ) {
		const url = new URL( restBase );

		if ( url.searchParams.has( 'rest_route' ) ) {
			url.searchParams.set( 'rest_route', url.searchParams.get( 'rest_route' ) + path );
		} else {
			url.pathname = url.pathname.replace( /\/$/, '' ) + path;
		}

		return url.toString();
	}

	function apiFetch( path, body, options ) {
		options = options || {};

		return fetch( apiUrl( path ), {
			method: options.method || 'GET',
			headers: Object.assign( { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce }, options.headers || {} ),
			body: body ? JSON.stringify( body ) : undefined,
		} ).then( function ( response ) {
			if ( ! response.ok ) {
				return response.json().then(
					function ( error ) {
						throw new Error( error.message || 'HTTP ' + response.status );
					},
					function () {
						throw new Error( 'HTTP ' + response.status );
					}
				);
			}

			return response.status === 204 ? null : response.json();
		} );
	}

	function escapeHtml( value ) {
		const div = document.createElement( 'div' );
		div.textContent = String( value );
		return div.innerHTML;
	}

	function renderRow( entry ) {
		const status = entry.has_key
			? '<span class="nd-ai-keys__badge nd-ai-keys__badge--set">' + escapeHtml( entry.key_preview ) + '</span>'
			: '<span class="nd-ai-keys__badge nd-ai-keys__badge--unset">' + escapeHtml( ndAiKeysL10n.noKey ) + '</span>';

		return (
			'<tr data-provider="' + escapeHtml( entry.provider ) + '">' +
			'<td>' + escapeHtml( entry.label ) + '</td>' +
			'<td>' + status + '</td>' +
			'<td><input type="password" class="nd-ai-keys__input" placeholder="' + escapeHtml( ndAiKeysL10n.placeholder ) + '" /></td>' +
			'<td class="nd-ai-keys__actions">' +
			'<button type="button" class="button button-primary nd-ai-keys__save">' + escapeHtml( ndAiKeysL10n.save ) + '</button> ' +
			'<button type="button" class="button nd-ai-keys__clear"' +
			( entry.has_key ? '' : ' disabled' ) +
			'>' +
			escapeHtml( ndAiKeysL10n.clear ) +
			'</button>' +
			'</td>' +
			'</tr>'
		);
	}

	function render( entries ) {
		root.innerHTML =
			'<table class="nd-ai-keys__table"><thead><tr>' +
			'<th>' + escapeHtml( ndAiKeysL10n.provider ) + '</th>' +
			'<th>' + escapeHtml( ndAiKeysL10n.status ) + '</th>' +
			'<th>' + escapeHtml( ndAiKeysL10n.newKey ) + '</th>' +
			'<th>' + escapeHtml( ndAiKeysL10n.actions ) + '</th>' +
			'</tr></thead><tbody>' +
			entries.map( renderRow ).join( '' ) +
			'</tbody></table>' +
			'<p class="nd-ai-keys__error" hidden></p>';

		root.querySelectorAll( '.nd-ai-keys__save' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				const row      = button.closest( 'tr' );
				const provider = row.dataset.provider;
				const input    = row.querySelector( '.nd-ai-keys__input' );
				const apiKey   = input.value.trim();

				if ( apiKey === '' ) {
					return;
				}

				apiFetch( '/keys/' + provider, { api_key: apiKey }, { method: 'PUT' } )
					.then( load )
					.catch( showError );
			} );
		} );

		root.querySelectorAll( '.nd-ai-keys__clear' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				const row      = button.closest( 'tr' );
				const provider = row.dataset.provider;

				if ( ! window.confirm( ndAiKeysL10n.confirmClear ) ) {
					return;
				}

				apiFetch( '/keys/' + provider, null, { method: 'DELETE' } )
					.then( load )
					.catch( showError );
			} );
		} );
	}

	function showError( error ) {
		const errorEl = root.querySelector( '.nd-ai-keys__error' );

		if ( errorEl ) {
			errorEl.textContent = error.message;
			errorEl.hidden = false;
		} else {
			window.alert( error.message );
		}
	}

	function load() {
		apiFetch( '/keys' )
			.then( function ( response ) {
				render( response.data );
			} )
			.catch( showError );
	}

	load();
} )();
