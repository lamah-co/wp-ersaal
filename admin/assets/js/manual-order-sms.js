/* global ajaxurl, ersaalOrderSms */
document.addEventListener( 'DOMContentLoaded', function () {
	var btn = document.getElementById( 'ersaal_order_send_btn' );
	if ( ! btn ) {
		return;
	}

	btn.addEventListener( 'click', function () {
		var message     = document.getElementById( 'ersaal_order_message' ).value.trim();
		var payment     = document.getElementById( 'ersaal_order_payment_type' ).value;
		var nonce       = document.getElementById( 'ersaal_manual_order_sms_nonce' ).value;
		var orderId     = this.getAttribute( 'data-order-id' );
		var responseDiv = document.getElementById( 'ersaal_order_response' );
		var spinner     = document.getElementById( 'ersaal_order_spinner' );

		if ( message === '' ) {
			responseDiv.innerHTML = '<p style="color:red;">' + ersaalOrderSms.i18n.messageRequired + '</p>';
			return;
		}

		btn.disabled = true;
		spinner.classList.add( 'is-active' );
		responseDiv.innerHTML = '';

		var formData = new URLSearchParams();
		formData.append( 'action',       'ersaal_manual_order_sms' );
		formData.append( 'nonce',        nonce );
		formData.append( 'order_id',     orderId );
		formData.append( 'message',      message );
		formData.append( 'payment_type', payment );

		fetch( ajaxurl, {
			method:  'POST',
			body:    formData,
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		} )
		.then( function ( res ) { return res.json(); } )
		.then( function ( res ) {
			btn.disabled = false;
			spinner.classList.remove( 'is-active' );
			var p       = document.createElement( 'p' );
			p.style.color = res.success ? 'green' : 'red';
			p.textContent = ( res.data && res.data.message ) ? res.data.message : '';
			responseDiv.innerHTML = '';
			responseDiv.appendChild( p );

			if ( res.success ) {
				document.getElementById( 'ersaal_order_message' ).value = '';
			}
		} )
		.catch( function () {
			btn.disabled = false;
			spinner.classList.remove( 'is-active' );
			var p       = document.createElement( 'p' );
			p.style.color = 'red';
			p.textContent = ersaalOrderSms.i18n.unexpectedError;
			responseDiv.innerHTML = '';
			responseDiv.appendChild( p );
		} );
	} );
} );
