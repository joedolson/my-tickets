jQuery(document).ready(function ($) {
	let initial_status    = $('.mt-trigger').prop('checked');
	const admissionType     = $('input[name=mt_general]');
	$( '.expire_date' ).hide();
	let admissionSelected = false;
	admissionType.each( function() {
		admissionSelected = $( this ).prop('checked' );
	});
	const selector = $( 'select[name=mt_valid]');
	if ( selector.val() === 'expire' ) {
		$( '.expire_date' ).show();
	}
	selector.on( 'change', function() {
		if ( $( this ).val() === 'expire' ) {
			$( '.expire_date' ).show();
		} else {
			$( '.expire_date' ).hide();
		}
	});
	const regExpire = $( '#reg_expires' );
	const responseRegion = $( '#reg_expiration' );
	regExpire.on( 'keyup change click', function(e) {
		let response;
		let val = Math.abs( $( this ).val() );
		let hours = Math.floor(val);
		let mins = 60 * (val - hours);
		let formatted = hours + ':' + mins.toString().padStart( 2, '0' );
	
		if ( $( this ).val() < 0 ) {
			response = mtShow.expireAfter.replace( '%s', formatted ); //+ ' after the event begins';
		} else {
			response = mtShow.expireBefore.replace( '%s', formatted ); // before the event begins';
		}
		responseRegion.text( response );
	});
	if (initial_status !== true) {
		$('.mt-ticket-form').hide();
		$( '.mt-ticket-form input' ).attr( 'disabled', 'disabled' );
		$('.mt-ticket-data input').removeAttr('required').removeAttr('aria-required');
	} else {
		let general_status = $('input[name=mt_general]:checked').val();
		if (general_status !== 'general') {
			$('.mt-ticket-dates input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-available-tickets input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-ticket-validity').hide();
		} else {
			$('.mt-ticket-dates input').removeAttr('required').removeAttr('aria-required');
			$('.mt-available-tickets input').removeAttr('required').removeAttr('aria-required');
			$('.mt-ticket-dates').hide();
		}
	}
	$('.mt-trigger').on('click', function () {
		let checked_status = $(this).prop('checked');
		if ( ! admissionSelected ) {
			$( '#mt-general-dated').prop( 'checked', true );
			$('.mt-ticket-dates').show();
			$('.mt-ticket-dates input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-available-tickets input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-ticket-validity').hide();
		}
		if (checked_status == true) {
			$( '.mt-ticket-form input' ).removeAttr( 'disabled' );
			$('.mt-ticket-data input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-ticket-form').show(300);
		} else {
			$( '.mt-ticket-form input' ).attr( 'disabled', 'disabled' );
			$('.mt-ticket-data input').removeAttr('required').removeAttr('aria-required').attr( 'disabled', 'disabled' );
			$('.mt-ticket-form').hide(200);
		}
	});
	$('input[name=mt_general]').on('change', function () {
		let checked_status = $('input[name=mt_general]:checked').val();
		if (checked_status == 'general') {
			$('.mt-ticket-dates').hide();
			$('.mt-ticket-dates input').removeAttr('required').removeAttr('aria-required');
			$('.mt-available-tickets input').removeAttr('required').removeAttr('aria-required');
			$('.mt-ticket-validity').show();
		} else {
			$('.mt-ticket-dates').show();
			$('.mt-ticket-dates input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-available-tickets input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-ticket-validity').hide();
		}
	});
});