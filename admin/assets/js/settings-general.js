/* global ajaxurl, ersaalSettingsGeneral */
jQuery( function ( $ ) {
	$( '#ersaal-test-api-btn' ).on( 'click', function () {
		var button  = $( this );
		var result  = $( '#ersaal-test-api-result' );
		var spinner = $( '#ersaal-test-api-spinner' );

		button.prop( 'disabled', true );
		spinner.addClass( 'is-active' );
		result.prop( 'hidden', true ).removeClass( 'ersaal-alert-success ersaal-alert-danger' ).empty();

		$.post(
			ajaxurl,
			{
				action: 'ersaal_test_api',
				nonce:  ersaalSettingsGeneral.nonce
			},
			function ( response ) {
				var success = Boolean( response.success );
				result
					.addClass( success ? 'ersaal-alert-success' : 'ersaal-alert-danger' )
					.text( response.data.message )
					.prop( 'hidden', false );
			}
		).fail( function () {
			result
				.addClass( 'ersaal-alert-danger' )
				.text( ersaalSettingsGeneral.i18n.connectionFailed )
				.prop( 'hidden', false );
		} ).always( function () {
			button.prop( 'disabled', false );
			spinner.removeClass( 'is-active' );
		} );
	} );
} );
