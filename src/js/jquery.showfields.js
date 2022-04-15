jQuery(document).ready(function ($) {
	var initial_status    = $('.mt-trigger').prop('checked');
	var admissionType     = $('input[name=mt_general]');
	var admissionSelected = false;
	admissionType.each( function() {
		admissionSelected = $( this ).prop('checked' );
	});
	if (initial_status !== true) {
		$('.mt-ticket-form').hide();
		$('.mt-ticket-data input').removeAttr('required').removeAttr('aria-required');
	} else {
		var general_status = $('input[name=mt_general]:checked').val();
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
		var checked_status = $(this).prop('checked');
		if ( ! admissionSelected ) {
			$( '#mt-general-dated').prop( 'checked', true );
			$('.mt-ticket-dates').show();
			$('.mt-ticket-dates input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-available-tickets input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-ticket-validity').hide();
		}
		if (checked_status == true) {
			$('.mt-ticket-dates input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-available-tickets input').attr('required', 'required').attr('aria-required', 'true');
			$('.mt-ticket-form').show(300);
		} else {
			$('.mt-ticket-data input').removeAttr('required').removeAttr('aria-required');
			$('.mt-ticket-form').hide(200);
		}
	});
	$('input[name=mt_general]').on('change', function () {
		var checked_status = $('input[name=mt_general]:checked').val();
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