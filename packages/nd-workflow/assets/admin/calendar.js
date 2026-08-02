( function () {
	'use strict';

	var container = document.getElementById( 'nd-workflow-calendar' );

	if ( ! container ) {
		return;
	}

	var restBase = container.dataset.restBase;
	var nonce = container.dataset.nonce;
	var today = new Date();
	var state = {
		year: today.getFullYear(),
		month: today.getMonth() + 1,
	};

	var WEEKDAYS = [ 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom' ];
	var MONTH_NAMES = [
		'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
		'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre',
	];

	function pad( n ) {
		return n < 10 ? '0' + n : String( n );
	}

	function isoDate( year, month, day ) {
		return year + '-' + pad( month ) + '-' + pad( day );
	}

	function daysInMonth( year, month ) {
		return new Date( year, month, 0 ).getDate();
	}

	/**
	 * Día de la semana del 1 del mes, normalizado a lunes=0 (JS usa
	 * domingo=0 por defecto).
	 */
	function firstWeekdayOffset( year, month ) {
		var jsDay = new Date( year, month - 1, 1 ).getDay();
		return jsDay === 0 ? 6 : jsDay - 1;
	}

	/**
	 * Construye la URL de un endpoint a partir de restBase, que puede venir
	 * en dos formas según los enlaces permanentes del sitio: "bonita"
	 * (".../wp-json/nd/v1/workflow") o "simple"
	 * (".../index.php?rest_route=/nd/v1/workflow", la que usa WordPress por
	 * defecto en una instalación nueva). Concatenar texto a pelo rompe la
	 * segunda forma: el "?" de una query string propia colisiona con el de
	 * rest_route= y produce una URL inválida.
	 */
	function apiUrl( subPath, queryParams ) {
		var url = new URL( restBase, window.location.href );
		var restRoute = url.searchParams.get( 'rest_route' );

		if ( restRoute !== null ) {
			url.searchParams.set( 'rest_route', restRoute + subPath );
		} else {
			url.pathname = url.pathname.replace( /\/?$/, '' ) + subPath;
		}

		Object.keys( queryParams || {} ).forEach( function ( key ) {
			url.searchParams.set( key, queryParams[ key ] );
		} );

		return url.toString();
	}

	function apiFetch( subPath, queryParams, options ) {
		options = options || {};
		options.headers = Object.assign( {}, options.headers, { 'X-WP-Nonce': nonce } );
		options.credentials = 'same-origin';

		return fetch( apiUrl( subPath, queryParams ), options ).then( function ( response ) {
			if ( ! response.ok ) {
				return response.json().then(
					function ( body ) {
						throw new Error( ( body && body.message ) || 'Error de red (' + response.status + ')' );
					},
					function () {
						throw new Error( 'Error de red (' + response.status + ')' );
					}
				);
			}

			return response.status === 204 ? null : response.json();
		} );
	}

	function loadMonth() {
		container.innerHTML = '<p>Cargando calendario…</p>';

		apiFetch( '/calendar', { year: state.year, month: state.month } )
			.then( function ( body ) {
				render( body.data || {} );
			} )
			.catch( function ( error ) {
				container.innerHTML = '<p class="nd-workflow-calendar__error">' + escapeHtml( error.message ) + '</p>';
			} );
	}

	function render( postsByDate ) {
		container.innerHTML = '';
		container.appendChild( buildToolbar() );
		container.appendChild( buildGrid( postsByDate ) );
	}

	function buildToolbar() {
		var toolbar = document.createElement( 'div' );
		toolbar.className = 'nd-workflow-calendar__toolbar';

		var prev = document.createElement( 'button' );
		prev.type = 'button';
		prev.className = 'button';
		prev.textContent = '‹ Anterior';
		prev.addEventListener( 'click', function () {
			state.month -= 1;
			if ( state.month < 1 ) {
				state.month = 12;
				state.year -= 1;
			}
			loadMonth();
		} );

		var label = document.createElement( 'h2' );
		label.textContent = MONTH_NAMES[ state.month - 1 ] + ' ' + state.year;

		var next = document.createElement( 'button' );
		next.type = 'button';
		next.className = 'button';
		next.textContent = 'Siguiente ›';
		next.addEventListener( 'click', function () {
			state.month += 1;
			if ( state.month > 12 ) {
				state.month = 1;
				state.year += 1;
			}
			loadMonth();
		} );

		toolbar.appendChild( prev );
		toolbar.appendChild( label );
		toolbar.appendChild( next );

		return toolbar;
	}

	function buildGrid( postsByDate ) {
		var grid = document.createElement( 'div' );
		grid.className = 'nd-workflow-calendar__grid';

		WEEKDAYS.forEach( function ( weekday ) {
			var head = document.createElement( 'div' );
			head.className = 'nd-workflow-calendar__weekday';
			head.textContent = weekday;
			grid.appendChild( head );
		} );

		var offset = firstWeekdayOffset( state.year, state.month );
		var totalDays = daysInMonth( state.year, state.month );

		for ( var i = 0; i < offset; i++ ) {
			var blank = document.createElement( 'div' );
			blank.className = 'nd-workflow-calendar__day nd-workflow-calendar__day--outside';
			grid.appendChild( blank );
		}

		for ( var day = 1; day <= totalDays; day++ ) {
			grid.appendChild( buildDayCell( day, postsByDate[ isoDate( state.year, state.month, day ) ] || [] ) );
		}

		return grid;
	}

	function buildDayCell( day, posts ) {
		var cell = document.createElement( 'div' );
		cell.className = 'nd-workflow-calendar__day';
		cell.dataset.date = isoDate( state.year, state.month, day );

		var number = document.createElement( 'div' );
		number.className = 'nd-workflow-calendar__day-number';
		number.textContent = String( day );
		cell.appendChild( number );

		posts.forEach( function ( post ) {
			cell.appendChild( buildItem( post ) );
		} );

		cell.addEventListener( 'dragover', function ( event ) {
			event.preventDefault();
			cell.classList.add( 'nd-workflow-calendar__day--dragover' );
		} );

		cell.addEventListener( 'dragleave', function () {
			cell.classList.remove( 'nd-workflow-calendar__day--dragover' );
		} );

		cell.addEventListener( 'drop', function ( event ) {
			event.preventDefault();
			cell.classList.remove( 'nd-workflow-calendar__day--dragover' );

			var postId = event.dataTransfer.getData( 'text/plain' );

			if ( ! postId ) {
				return;
			}

			reschedule( postId, cell.dataset.date );
		} );

		return cell;
	}

	function buildItem( post ) {
		var item = document.createElement( 'div' );
		item.className = 'nd-workflow-calendar__item';
		item.draggable = true;
		item.dataset.status = post.status;

		var link = document.createElement( 'a' );
		link.href = post.edit_link;
		link.textContent = post.title || '(sin título)';
		link.target = '_blank';
		link.rel = 'noopener noreferrer';
		item.appendChild( link );

		item.addEventListener( 'dragstart', function ( event ) {
			event.dataTransfer.setData( 'text/plain', String( post.id ) );
			event.dataTransfer.effectAllowed = 'move';
			item.classList.add( 'nd-workflow-calendar__item--dragging' );
		} );

		item.addEventListener( 'dragend', function () {
			item.classList.remove( 'nd-workflow-calendar__item--dragging' );
		} );

		return item;
	}

	function reschedule( postId, date ) {
		apiFetch( '/posts/' + postId + '/schedule', {}, {
			method: 'PATCH',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify( { date: date } ),
		} )
			.then( loadMonth )
			.catch( function ( error ) {
				window.alert( 'No se pudo reprogramar: ' + error.message ); // eslint-disable-line no-alert
			} );
	}

	function escapeHtml( text ) {
		var div = document.createElement( 'div' );
		div.textContent = text;
		return div.innerHTML;
	}

	loadMonth();
} )();
