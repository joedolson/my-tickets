jQuery(document).ready(function ($) {
	var initial_status = $('.mt-trigger').prop('checked');
	if (initial_status !== true) {
		$('.mt-ticket-form').hide();
	}
	var general_status = $('input[name=mt_general]:checked').val();
	console.log( general_status );
	if (general_status !== 'general') {
		$('.mt-ticket-validity').hide();
	} else {
		$('.mt-ticket-dates').hide();
	}
	$('.mt-trigger').on( 'click', function() {
		var checked_status = $(this).prop('checked');
		if (checked_status == true) {
			$('.mt-ticket-dates input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-available-tickets input').attr( 'required', 'required' ).attr( 'aria-required', 'true' );
			$('.mt-ticket-form').show(300);
		} else {
			$('.mt-ticket-dates input').removeAttr('required').removeAttr('aria-required');
			$('.mt-available-tickets input').removeAttr( 'required' ).removeAttr( 'aria-required' );
			$('.mt-ticket-form').hide(200);
		}
	});
	$('input[name=mt_general]').on( 'change', function() {
		var checked_status = $('input[name=mt_general]:checked').val();
		console.log( 'change', checked_status );
		if (checked_status == 'general' ) {
			$('.mt-ticket-dates' ).hide();
			$('.mt-ticket-validity').show();
		} else {
			$('.mt-ticket-dates' ).show();
			$('.mt-ticket-validity').hide();
		}
	});
});