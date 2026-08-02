( function () {
	'use strict';

	const root = document.getElementById( 'nd-search-index' );

	if ( ! root ) {
		return;
	}

	const restBase = root.dataset.restBase;
	const nonce    = root.dataset.nonce;

	function apiUrl( path, params ) {
		const url = new URL( restBase );

		if ( url.searchParams.has( 'rest_route' ) ) {
			url.searchParams.set( 'rest_route', url.searchParams.get( 'rest_route' ) + path );
		} else {
			url.pathname = url.pathname.replace( /\/$/, '' ) + path;
		}

		if ( params ) {
			Object.keys( params ).forEach( function ( key ) {
				url.searchParams.set( key, params[ key ] );
			} );
		}

		return url.toString();
	}

	function apiFetch( path, params, options ) {
		options = options || {};

		return fetch( apiUrl( path, params ), {
			method: options.method || 'GET',
			headers: { 'X-WP-Nonce': nonce },
		} ).then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( 'HTTP ' + response.status );
			}

			return response.json();
		} );
	}

	function escapeHtml( value ) {
		const div = document.createElement( 'div' );
		div.textContent = String( value );
		return div.innerHTML;
	}

	function renderRecent( rows ) {
		if ( rows.length === 0 ) {
			return '<p class="nd-search-index__empty">' + escapeHtml( ndSearchIndexL10n.noRecent ) + '</p>';
		}

		const items = rows
			.map( function ( row ) {
				return '<tr><td>' + escapeHtml( row.title ) + '</td><td>' + escapeHtml( row.updated_at ) + '</td></tr>';
			} )
			.join( '' );

		return (
			'<table class="nd-search-index__table"><thead><tr><th>' +
			escapeHtml( ndSearchIndexL10n.article ) +
			'</th><th>' +
			escapeHtml( ndSearchIndexL10n.updatedAt ) +
			'</th></tr></thead><tbody>' +
			items +
			'</tbody></table>'
		);
	}

	function render( stats, recent ) {
		root.innerHTML =
			'<div class="nd-search-index__card">' +
			'<p class="nd-search-index__big-number">' +
			stats.indexed +
			'</p>' +
			'<p>' +
			escapeHtml( ndSearchIndexL10n.indexed ) +
			'</p>' +
			'<button type="button" class="button button-primary" id="nd-search-index-reindex">' +
			escapeHtml( ndSearchIndexL10n.reindex ) +
			'</button>' +
			'<p id="nd-search-index-reindex-status"></p>' +
			'</div>' +
			'<div class="nd-search-index__card">' +
			'<h2>' +
			escapeHtml( ndSearchIndexL10n.recentTitle ) +
			'</h2>' +
			renderRecent( recent ) +
			'</div>' +
			'<div class="nd-search-index__card">' +
			'<h2>' +
			escapeHtml( ndSearchIndexL10n.testTitle ) +
			'</h2>' +
			'<input type="text" id="nd-search-index-query" placeholder="' +
			escapeHtml( ndSearchIndexL10n.testPlaceholder ) +
			'" />' +
			'<button type="button" class="button" id="nd-search-index-query-button">' +
			escapeHtml( ndSearchIndexL10n.testButton ) +
			'</button>' +
			'<div id="nd-search-index-query-results"></div>' +
			'</div>';

		document.getElementById( 'nd-search-index-reindex' ).addEventListener( 'click', function ( event ) {
			const button    = event.target;
			const statusEl  = document.getElementById( 'nd-search-index-reindex-status' );
			button.disabled = true;
			statusEl.textContent = ndSearchIndexL10n.reindexing;

			apiFetch( '/reindex', null, { method: 'POST' } )
				.then( function ( response ) {
					statusEl.textContent = ndSearchIndexL10n.reindexDone.replace( '%d', String( response.data.reindexed ) );
					return load();
				} )
				.catch( function ( error ) {
					statusEl.textContent = error.message;
				} )
				.finally( function () {
					button.disabled = false;
				} );
		} );

		document.getElementById( 'nd-search-index-query-button' ).addEventListener( 'click', function () {
			const query      = document.getElementById( 'nd-search-index-query' ).value.trim();
			const resultsEl  = document.getElementById( 'nd-search-index-query-results' );

			if ( query === '' ) {
				return;
			}

			apiFetch( '/query', { q: query, limit: 10 } )
				.then( function ( response ) {
					if ( response.data.length === 0 ) {
						resultsEl.innerHTML = '<p class="nd-search-index__empty">' + escapeHtml( ndSearchIndexL10n.noResults ) + '</p>';
						return;
					}

					resultsEl.innerHTML =
						'<ul>' +
						response.data
							.map( function ( row ) {
								return '<li>' + escapeHtml( row.title ) + '</li>';
							} )
							.join( '' ) +
						'</ul>';
				} )
				.catch( function ( error ) {
					resultsEl.innerHTML = '<p class="nd-search-index__error">' + escapeHtml( error.message ) + '</p>';
				} );
		} );
	}

	function load() {
		return Promise.all( [ apiFetch( '/stats' ), apiFetch( '/recent', { limit: 20 } ) ] ).then( function ( results ) {
			render( results[ 0 ].data, results[ 1 ].data );
		} );
	}

	root.innerHTML = '<p>' + escapeHtml( ndSearchIndexL10n.loading ) + '</p>';
	load().catch( function ( error ) {
		root.innerHTML = '<p class="nd-search-index__error">' + escapeHtml( error.message ) + '</p>';
	} );
} )();
