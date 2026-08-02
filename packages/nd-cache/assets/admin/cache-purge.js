( function () {
	'use strict';

	const root = document.getElementById( 'nd-cache-purge' );

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

	const button = document.createElement( 'button' );
	button.type = 'button';
	button.className = 'button button-primary';
	button.textContent = ndCachePurgeL10n.purge;

	const status = document.createElement( 'p' );

	root.appendChild( button );
	root.appendChild( status );

	button.addEventListener( 'click', function () {
		button.disabled = true;
		status.textContent = ndCachePurgeL10n.purging;

		fetch( apiUrl( '/purge' ), {
			method: 'POST',
			headers: { 'X-WP-Nonce': nonce },
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}

				status.textContent = ndCachePurgeL10n.done;
			} )
			.catch( function ( error ) {
				status.textContent = error.message;
			} )
			.finally( function () {
				button.disabled = false;
			} );
	} );
} )();
