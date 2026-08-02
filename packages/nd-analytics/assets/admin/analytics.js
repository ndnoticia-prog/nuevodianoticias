( function () {
	'use strict';

	const root = document.getElementById( 'nd-analytics-panel' );

	if ( ! root ) {
		return;
	}

	const restBase  = root.dataset.restBase;
	const postsBase = root.dataset.postsBase;
	const usersBase = root.dataset.usersBase;
	const nonce     = root.dataset.nonce;

	let days = 7;

	function apiUrl( base, path, params ) {
		const url = new URL( base );

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

	function apiFetch( url ) {
		return fetch( url, { headers: { 'X-WP-Nonce': nonce } } ).then( function ( response ) {
			if ( ! response.ok ) {
				throw new Error( 'HTTP ' + response.status );
			}

			return response.json();
		} );
	}

	function lookupNames( base, ids, field ) {
		const unique = Array.from( new Set( ids ) ).filter( function ( id ) {
			return id > 0;
		} );

		if ( unique.length === 0 ) {
			return Promise.resolve( {} );
		}

		const url = new URL( base );
		url.searchParams.set( 'include', unique.join( ',' ) );
		url.searchParams.set( 'per_page', String( unique.length ) );
		url.searchParams.set( '_fields', 'id,' + field );

		return apiFetch( url.toString() ).then( function ( items ) {
			const map = {};

			items.forEach( function ( item ) {
				map[ item.id ] = field === 'title' ? item.title.rendered : item.name;
			} );

			return map;
		} );
	}

	function escapeHtml( value ) {
		const div = document.createElement( 'div' );
		div.textContent = String( value );
		return div.innerHTML;
	}

	function renderPostsTable( rows, titles ) {
		if ( rows.length === 0 ) {
			return '<p class="nd-analytics-panel__empty">' + escapeHtml( ndAnalyticsPanelL10n.noData ) + '</p>';
		}

		const items = rows
			.map( function ( row ) {
				const title = titles[ row.post_id ] || '#' + row.post_id;
				return '<tr><td>' + escapeHtml( title ) + '</td><td>' + row.views + '</td></tr>';
			} )
			.join( '' );

		return (
			'<table class="nd-analytics-panel__table"><thead><tr><th>' +
			escapeHtml( ndAnalyticsPanelL10n.post ) +
			'</th><th>' +
			escapeHtml( ndAnalyticsPanelL10n.views ) +
			'</th></tr></thead><tbody>' +
			items +
			'</tbody></table>'
		);
	}

	function renderAuthorsTable( rows, names ) {
		if ( rows.length === 0 ) {
			return '<p class="nd-analytics-panel__empty">' + escapeHtml( ndAnalyticsPanelL10n.noData ) + '</p>';
		}

		const items = rows
			.map( function ( row ) {
				const name = names[ row.author_id ] || '#' + row.author_id;
				return '<tr><td>' + escapeHtml( name ) + '</td><td>' + row.views + '</td></tr>';
			} )
			.join( '' );

		return (
			'<table class="nd-analytics-panel__table"><thead><tr><th>' +
			escapeHtml( ndAnalyticsPanelL10n.author ) +
			'</th><th>' +
			escapeHtml( ndAnalyticsPanelL10n.views ) +
			'</th></tr></thead><tbody>' +
			items +
			'</tbody></table>'
		);
	}

	function renderCategoriesTable( rows ) {
		if ( rows.length === 0 ) {
			return '<p class="nd-analytics-panel__empty">' + escapeHtml( ndAnalyticsPanelL10n.noData ) + '</p>';
		}

		const items = rows
			.map( function ( row ) {
				return '<tr><td>' + escapeHtml( row.name ) + '</td><td>' + row.views + '</td></tr>';
			} )
			.join( '' );

		return (
			'<table class="nd-analytics-panel__table"><thead><tr><th>' +
			escapeHtml( ndAnalyticsPanelL10n.category ) +
			'</th><th>' +
			escapeHtml( ndAnalyticsPanelL10n.views ) +
			'</th></tr></thead><tbody>' +
			items +
			'</tbody></table>'
		);
	}

	function render( state ) {
		root.innerHTML =
			'<div class="nd-analytics-panel__toolbar">' +
			'<label for="nd-analytics-panel-days">' + escapeHtml( ndAnalyticsPanelL10n.rangeLabel ) + '</label> ' +
			'<select id="nd-analytics-panel-days">' +
			[ 7, 30, 90 ]
				.map( function ( value ) {
					return (
						'<option value="' + value + '"' + ( value === days ? ' selected' : '' ) + '>' +
						escapeHtml( ndAnalyticsPanelL10n.days.replace( '%d', String( value ) ) ) +
						'</option>'
					);
				} )
				.join( '' ) +
			'</select></div>' +
			'<div class="nd-analytics-panel__grid">' +
			'<div class="nd-analytics-panel__card"><h2>' +
			escapeHtml( ndAnalyticsPanelL10n.activeNow ) +
			'</h2><p class="nd-analytics-panel__big-number">' +
			state.activeNow.visitors +
			'</p>' +
			renderPostsTable( state.activeNow.posts, state.titles ) +
			'</div>' +
			'<div class="nd-analytics-panel__card"><h2>' +
			escapeHtml( ndAnalyticsPanelL10n.topPosts ) +
			'</h2>' +
			renderPostsTable( state.topPosts, state.titles ) +
			'</div>' +
			'<div class="nd-analytics-panel__card"><h2>' +
			escapeHtml( ndAnalyticsPanelL10n.topAuthors ) +
			'</h2>' +
			renderAuthorsTable( state.topAuthors, state.authorNames ) +
			'</div>' +
			'<div class="nd-analytics-panel__card"><h2>' +
			escapeHtml( ndAnalyticsPanelL10n.topCategories ) +
			'</h2>' +
			renderCategoriesTable( state.topCategories ) +
			'</div>' +
			'</div>';

		document.getElementById( 'nd-analytics-panel-days' ).addEventListener( 'change', function ( event ) {
			days = parseInt( event.target.value, 10 );
			load();
		} );
	}

	function load() {
		root.innerHTML = '<p>' + escapeHtml( ndAnalyticsPanelL10n.loading ) + '</p>';

		Promise.all( [
			apiFetch( apiUrl( restBase, '/top-posts', { days: days, limit: 10 } ) ),
			apiFetch( apiUrl( restBase, '/active-now', { minutes: 5 } ) ),
			apiFetch( apiUrl( restBase, '/top-authors', { days: days } ) ),
			apiFetch( apiUrl( restBase, '/top-categories', { days: days } ) ),
		] )
			.then( function ( results ) {
				const topPosts      = results[ 0 ].data;
				const activeNow     = results[ 1 ].data;
				const topAuthors    = results[ 2 ].data;
				const topCategories = results[ 3 ].data;

				const postIds = topPosts
					.map( function ( row ) {
						return row.post_id;
					} )
					.concat(
						activeNow.posts.map( function ( row ) {
							return row.post_id;
						} )
					);

				const authorIds = topAuthors.map( function ( row ) {
					return row.author_id;
				} );

				return Promise.all( [ lookupNames( postsBase, postIds, 'title' ), lookupNames( usersBase, authorIds, 'name' ) ] ).then(
					function ( lookups ) {
						render( {
							topPosts: topPosts,
							activeNow: activeNow,
							topAuthors: topAuthors,
							topCategories: topCategories,
							titles: lookups[ 0 ],
							authorNames: lookups[ 1 ],
						} );
					}
				);
			} )
			.catch( function ( error ) {
				root.innerHTML = '<p class="nd-analytics-panel__error">' + escapeHtml( error.message ) + '</p>';
			} );
	}

	load();
} )();
