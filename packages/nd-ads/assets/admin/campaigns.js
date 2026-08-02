( function () {
	'use strict';

	var container = document.getElementById( 'nd-ads-campaigns' );

	if ( ! container ) {
		return;
	}

	var restBase = container.dataset.restBase;
	var nonce = container.dataset.nonce;
	var types = JSON.parse( container.dataset.types || '[]' );
	var editingId = null;

	var CREATIVE_FIELDS = {
		adsense: [
			{ key: 'adsense_client', label: 'Client ID de AdSense (ca-pub-...)' },
			{ key: 'adsense_slot', label: 'Slot de AdSense' },
		],
		gam: [
			{ key: 'gam_unit_path', label: 'Ruta del ad unit de Google Ad Manager' },
			{ key: 'gam_size', label: 'Tamaño (ancho x alto, p. ej. 300x250)' },
		],
		html: [ { key: 'html', label: 'HTML', type: 'textarea' } ],
		image: [
			{ key: 'image_url', label: 'URL de la imagen' },
			{ key: 'link_url', label: 'URL de destino al hacer clic (opcional)' },
			{ key: 'alt_text', label: 'Texto alternativo (opcional)' },
		],
		video: [ { key: 'video_url', label: 'URL del vídeo' } ],
		sponsored: [
			{ key: 'html', label: 'HTML', type: 'textarea' },
			{ key: 'sponsor_label', label: 'Etiqueta ("Contenido patrocinado" si se deja vacío)' },
		],
	};

	/**
	 * Igual razonamiento que en nd-workflow/assets/admin/calendar.js:
	 * restBase puede venir en forma "bonita" o "?rest_route=..." según los
	 * enlaces permanentes del sitio; concatenar texto a pelo rompe la
	 * segunda forma.
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

	function escapeHtml( text ) {
		var div = document.createElement( 'div' );
		div.textContent = text == null ? '' : String( text );
		return div.innerHTML;
	}

	function load() {
		container.innerHTML = '<p>Cargando campañas…</p>';

		apiFetch( '/campaigns', {} )
			.then( function ( body ) {
				render( body.data || [] );
			} )
			.catch( function ( error ) {
				container.innerHTML = '<p class="nd-ads-campaigns__error">' + escapeHtml( error.message ) + '</p>';
			} );
	}

	function render( campaigns ) {
		container.innerHTML = '';

		var toolbar = document.createElement( 'div' );
		toolbar.className = 'nd-ads-campaigns__toolbar';

		var addButton = document.createElement( 'button' );
		addButton.type = 'button';
		addButton.className = 'button button-primary';
		addButton.textContent = 'Nueva campaña';
		addButton.addEventListener( 'click', function () {
			showForm( null );
		} );
		toolbar.appendChild( addButton );
		container.appendChild( toolbar );

		container.appendChild( buildTable( campaigns ) );
	}

	function buildTable( campaigns ) {
		var table = document.createElement( 'table' );
		table.className = 'nd-ads-campaigns__table';

		var thead = document.createElement( 'thead' );
		thead.innerHTML =
			'<tr><th>Nombre</th><th>Anunciante</th><th>Tipo</th><th>Estado</th>' +
			'<th>Prioridad</th><th>Estadísticas</th><th>Acciones</th></tr>';
		table.appendChild( thead );

		var tbody = document.createElement( 'tbody' );

		if ( campaigns.length === 0 ) {
			var emptyRow = document.createElement( 'tr' );
			emptyRow.innerHTML = '<td colspan="7">Todavía no hay campañas.</td>';
			tbody.appendChild( emptyRow );
		}

		campaigns.forEach( function ( campaign ) {
			tbody.appendChild( buildRow( campaign ) );
		} );

		table.appendChild( tbody );

		return table;
	}

	function buildRow( campaign ) {
		var row = document.createElement( 'tr' );

		var stats = campaign.stats || { impressions: 0, clicks: 0, ctr: 0 };

		row.innerHTML =
			'<td>' + escapeHtml( campaign.name ) + '</td>' +
			'<td>' + escapeHtml( campaign.advertiser ) + '</td>' +
			'<td>' + escapeHtml( campaign.type ) + '</td>' +
			'<td><span class="nd-ads-campaigns__badge nd-ads-campaigns__badge--' +
			( campaign.active ? 'active' : 'inactive' ) + '">' +
			( campaign.active ? 'Activa' : 'Inactiva' ) + '</span></td>' +
			'<td>' + escapeHtml( campaign.priority ) + '</td>' +
			'<td class="nd-ads-campaigns__stats">' +
			escapeHtml( stats.impressions ) + ' impr. · ' +
			escapeHtml( stats.clicks ) + ' clics · ' +
			escapeHtml( stats.ctr ) + '% CTR</td>' +
			'<td></td>';

		var actionsCell = row.lastElementChild;
		var actions = document.createElement( 'div' );
		actions.className = 'nd-ads-campaigns__actions';

		var editButton = document.createElement( 'button' );
		editButton.type = 'button';
		editButton.className = 'button';
		editButton.textContent = 'Editar';
		editButton.addEventListener( 'click', function () {
			showForm( campaign );
		} );
		actions.appendChild( editButton );

		var toggleButton = document.createElement( 'button' );
		toggleButton.type = 'button';
		toggleButton.className = 'button';
		toggleButton.textContent = campaign.active ? 'Desactivar' : 'Activar';
		toggleButton.addEventListener( 'click', function () {
			apiFetch( '/campaigns/' + campaign.id + '/active', {}, {
				method: 'PATCH',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( { active: ! campaign.active } ),
			} ).then( load ).catch( function ( error ) {
				window.alert( 'No se pudo cambiar el estado: ' + error.message ); // eslint-disable-line no-alert
			} );
		} );
		actions.appendChild( toggleButton );

		var deleteButton = document.createElement( 'button' );
		deleteButton.type = 'button';
		deleteButton.className = 'button button-link-delete';
		deleteButton.textContent = 'Borrar';
		deleteButton.addEventListener( 'click', function () {
			if ( ! window.confirm( '¿Borrar la campaña "' + campaign.name + '"?' ) ) { // eslint-disable-line no-alert
				return;
			}

			apiFetch( '/campaigns/' + campaign.id, {}, { method: 'DELETE' } )
				.then( load )
				.catch( function ( error ) {
					window.alert( 'No se pudo borrar: ' + error.message ); // eslint-disable-line no-alert
				} );
		} );
		actions.appendChild( deleteButton );

		actionsCell.appendChild( actions );

		return row;
	}

	function showForm( campaign ) {
		editingId = campaign ? campaign.id : null;

		var existingForm = container.querySelector( '.nd-ads-campaigns__form' );

		if ( existingForm ) {
			existingForm.remove();
		}

		var form = document.createElement( 'form' );
		form.className = 'nd-ads-campaigns__form';

		var heading = document.createElement( 'h2' );
		heading.textContent = campaign ? 'Editar campaña' : 'Nueva campaña';
		form.appendChild( heading );

		form.appendChild( textField( 'name', 'Nombre', campaign ? campaign.name : '' ) );
		form.appendChild( textField( 'advertiser', 'Anunciante', campaign ? campaign.advertiser : '' ) );
		form.appendChild( selectField( 'type', 'Tipo', types, campaign ? campaign.type : types[ 0 ] ) );
		form.appendChild( checkboxField( 'active', 'Activa', campaign ? campaign.active : true ) );
		form.appendChild( numberField( 'priority', 'Prioridad (más alto = más preferencia)', campaign ? campaign.priority : 0 ) );
		form.appendChild( textField( 'zones', 'Zonas (separadas por coma, p. ej. header,in-article)', campaign ? campaign.zones.join( ',' ) : '' ) );
		form.appendChild( textField( 'category_slugs', 'Categorías (opcional, separadas por coma; vacío = todas)', campaign ? campaign.category_slugs.join( ',' ) : '' ) );
		form.appendChild( dateField( 'starts_at', 'Empieza (opcional)', campaign ? campaign.starts_at : '' ) );
		form.appendChild( dateField( 'ends_at', 'Termina (opcional)', campaign ? campaign.ends_at : '' ) );

		var creativeContainer = document.createElement( 'div' );
		creativeContainer.className = 'nd-ads-campaigns__creative-fields';
		form.appendChild( creativeContainer );

		function renderCreativeFields() {
			var selectedType = form.elements.type.value;
			creativeContainer.innerHTML = '';

			( CREATIVE_FIELDS[ selectedType ] || [] ).forEach( function ( field ) {
				var value = '';

				if ( campaign && campaign.creative ) {
					if ( field.key === 'gam_size' && Array.isArray( campaign.creative.gam_sizes ) && campaign.creative.gam_sizes[ 0 ] ) {
						value = campaign.creative.gam_sizes[ 0 ].join( 'x' );
					} else if ( campaign.creative[ field.key ] !== undefined ) {
						value = campaign.creative[ field.key ];
					}
				}

				creativeContainer.appendChild(
					field.type === 'textarea'
						? textAreaField( 'creative_' + field.key, field.label, value )
						: textField( 'creative_' + field.key, field.label, value )
				);
			} );
		}

		container.appendChild( form );
		form.querySelector( 'select[name="type"]' ).addEventListener( 'change', renderCreativeFields );
		renderCreativeFields();

		var actions = document.createElement( 'div' );
		actions.className = 'nd-ads-campaigns__actions';

		var saveButton = document.createElement( 'button' );
		saveButton.type = 'submit';
		saveButton.className = 'button button-primary';
		saveButton.textContent = campaign ? 'Guardar cambios' : 'Crear campaña';
		actions.appendChild( saveButton );

		var cancelButton = document.createElement( 'button' );
		cancelButton.type = 'button';
		cancelButton.className = 'button';
		cancelButton.textContent = 'Cancelar';
		cancelButton.addEventListener( 'click', function () {
			form.remove();
		} );
		actions.appendChild( cancelButton );

		form.appendChild( actions );

		form.addEventListener( 'submit', function ( event ) {
			event.preventDefault();
			submitForm( form );
		} );
	}

	function textField( name, label, value ) {
		return fieldWrapper( name, label, function ( input ) {
			input.type = 'text';
			input.name = name;
			input.value = value || '';
		} );
	}

	function numberField( name, label, value ) {
		return fieldWrapper( name, label, function ( input ) {
			input.type = 'number';
			input.name = name;
			input.value = value || 0;
		} );
	}

	function dateField( name, label, value ) {
		return fieldWrapper( name, label, function ( input ) {
			input.type = 'datetime-local';
			input.name = name;

			if ( value ) {
				input.value = String( value ).slice( 0, 16 ).replace( ' ', 'T' );
			}
		} );
	}

	function textAreaField( name, label, value ) {
		var wrapper = document.createElement( 'div' );
		wrapper.className = 'nd-ads-campaigns__field';

		var labelEl = document.createElement( 'label' );
		labelEl.textContent = label;
		wrapper.appendChild( labelEl );

		var textarea = document.createElement( 'textarea' );
		textarea.name = name;
		textarea.value = value || '';
		wrapper.appendChild( textarea );

		return wrapper;
	}

	function checkboxField( name, label, checked ) {
		var wrapper = document.createElement( 'div' );
		wrapper.className = 'nd-ads-campaigns__field';

		var labelEl = document.createElement( 'label' );
		var input = document.createElement( 'input' );
		input.type = 'checkbox';
		input.name = name;
		input.checked = !! checked;
		labelEl.appendChild( input );
		labelEl.appendChild( document.createTextNode( ' ' + label ) );
		wrapper.appendChild( labelEl );

		return wrapper;
	}

	function selectField( name, label, options, selected ) {
		var wrapper = document.createElement( 'div' );
		wrapper.className = 'nd-ads-campaigns__field';

		var labelEl = document.createElement( 'label' );
		labelEl.textContent = label;
		wrapper.appendChild( labelEl );

		var select = document.createElement( 'select' );
		select.name = name;

		options.forEach( function ( option ) {
			var optionEl = document.createElement( 'option' );
			optionEl.value = option;
			optionEl.textContent = option;
			optionEl.selected = option === selected;
			select.appendChild( optionEl );
		} );

		wrapper.appendChild( select );

		return wrapper;
	}

	function fieldWrapper( name, label, configureInput ) {
		var wrapper = document.createElement( 'div' );
		wrapper.className = 'nd-ads-campaigns__field';

		var labelEl = document.createElement( 'label' );
		labelEl.textContent = label;
		wrapper.appendChild( labelEl );

		var input = document.createElement( 'input' );
		configureInput( input );
		wrapper.appendChild( input );

		return wrapper;
	}

	function submitForm( form ) {
		var data = new FormData( form );
		var selectedType = data.get( 'type' );
		var creative = {};

		( CREATIVE_FIELDS[ selectedType ] || [] ).forEach( function ( field ) {
			var value = data.get( 'creative_' + field.key );

			if ( ! value ) {
				return;
			}

			if ( field.key === 'gam_size' ) {
				var parts = String( value ).split( 'x' ).map( function ( n ) {
					return parseInt( n, 10 );
				} );

				if ( parts.length === 2 && ! parts.some( isNaN ) ) {
					creative.gam_sizes = [ parts ];
				}

				return;
			}

			creative[ field.key ] = value;
		} );

		var payload = {
			name: data.get( 'name' ),
			advertiser: data.get( 'advertiser' ),
			type: selectedType,
			active: form.elements.active.checked,
			priority: parseInt( data.get( 'priority' ), 10 ) || 0,
			zones: splitList( data.get( 'zones' ) ),
			category_slugs: splitList( data.get( 'category_slugs' ) ),
			creative: creative,
			starts_at: normalizeDateTime( data.get( 'starts_at' ) ),
			ends_at: normalizeDateTime( data.get( 'ends_at' ) ),
		};

		var request = editingId
			? apiFetch( '/campaigns/' + editingId, {}, {
				method: 'PUT',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( payload ),
			} )
			: apiFetch( '/campaigns', {}, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify( payload ),
			} );

		request.then( load ).catch( function ( error ) {
			window.alert( 'No se pudo guardar: ' + error.message ); // eslint-disable-line no-alert
		} );
	}

	function splitList( value ) {
		return String( value || '' )
			.split( ',' )
			.map( function ( item ) {
				return item.trim();
			} )
			.filter( function ( item ) {
				return item !== '';
			} );
	}

	function normalizeDateTime( value ) {
		if ( ! value ) {
			return null;
		}

		return String( value ).replace( 'T', ' ' ) + ':00';
	}

	load();
} )();
