/* global ersaalWcSettings */
document.addEventListener( 'DOMContentLoaded', function () {
	var i18n        = ersaalWcSettings.i18n;
	var lastFocused = null;

	function updatePreview( textarea ) {
		var preview = document.getElementById( textarea.dataset.preview );
		if ( ! preview ) {
			return;
		}

		var text          = textarea.value;
		var arabic        = ( /[\u0600-\u06FF]/ ).test( text );
		var singleLimit   = arabic ? 70 : 160;
		var multipartLimit = arabic ? 67 : 153;
		var parts         = text.length === 0
			? 0
			: ( text.length > singleLimit ? Math.ceil( text.length / multipartLimit ) : 1 );

		preview.textContent = text || i18n.empty;

		var stats = preview.parentElement.querySelector( '.ersaal-template-stats' );
		if ( ! stats ) {
			stats           = document.createElement( 'div' );
			stats.className = 'ersaal-template-stats';
			preview.parentElement.appendChild( stats );
		}
		stats.textContent =
			i18n.characters + ': ' + text.length + ' \u00b7 ' +
			i18n.parts      + ': ' + parts        + ' \u00b7 ' +
			i18n.encoding   + ': ' + ( arabic ? 'Unicode' : 'GSM-7' );
	}

	document.querySelectorAll( '.ersaal-template-input' ).forEach( function ( textarea ) {
		textarea.addEventListener( 'input',  function () { updatePreview( textarea ); } );
		textarea.addEventListener( 'focus',  function () {
			lastFocused = textarea;
			document.getElementById( 'ersaal-variable-status' ).textContent = '';
		} );
		updatePreview( textarea );
	} );

	document.querySelectorAll( '.ersaal-var-btn' ).forEach( function ( button ) {
		button.addEventListener( 'click', function () {
			if ( ! lastFocused ) {
				document.getElementById( 'ersaal-variable-status' ).textContent = i18n.select_template;
				return;
			}

			var variable = button.dataset.var;
			var start    = lastFocused.selectionStart;
			var end      = lastFocused.selectionEnd;
			lastFocused.value =
				lastFocused.value.substring( 0, start ) +
				variable +
				lastFocused.value.substring( end );
			lastFocused.selectionStart = lastFocused.selectionEnd = start + variable.length;
			lastFocused.focus();
			updatePreview( lastFocused );
		} );
	} );

	document.querySelectorAll( '.ersaal-event-toggle' ).forEach( function ( toggle ) {
		toggle.addEventListener( 'change', function () {
			var badge       = document.getElementById( toggle.dataset.state );
			badge.textContent = toggle.checked ? i18n.enabled : i18n.disabled;
			badge.classList.toggle( 'ersaal-badge-success', toggle.checked );
			badge.classList.toggle( 'ersaal-badge-muted',   ! toggle.checked );
		} );
	} );
} );
