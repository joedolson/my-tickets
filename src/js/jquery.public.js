(function ($) {
	$(function () {
		$(".cc-num-valid").hide();
		$(".cc-num-invalid").hide();
		$("input.cc-num").payment('formatCardNumber');
		$('input.cc-exp').payment('formatCardExpiry');
		$('input.cc-cvc').payment('formatCardCVC');

		$("input[type='number']").on('keydown', function (e) {
			if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
					// Allow: Ctrl combinations. This allows pasting letters, but OH WELL.
				( e.ctrlKey === true ) ||
					// Allow: home, end, left, right, up, down
				(e.keyCode >= 35 && e.keyCode <= 40 )) {
				// let it happen, don't do anything
				return;
			}
			// Ensure that it is a number and stop the keypress
			if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
				e.preventDefault();
			}
		});


		function validateEmail(email) {
			const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
			return re.test(String(email).toLowerCase());
		}

		$( '.mt_email_check span' ).hide();

		$( '#mt_email2' ).on( 'keyup', function() {
			var email_one = $( '#mt_email' ).val();
			var email_two = $( '#mt_email2' ).val();
			if ( ! validateEmail( email_one ) ) {
				$( '.mt_email_check .notemail' ).show();
				$( '.mt_email_check .ok, .mt_email_check .mismatch' ).hide();
			} else if ( email_one == email_two && validateEmail(email_one) ) {
				$( '.mt_email_check .ok' ).show();
				$( '.mt_email_check .mismatch, .mt_email_check .notemail' ).hide();
			} else {
				$( '.mt_email_check .mismatch' ).show();
				$( '.mt_email_check .ok, .mt_email_check .notemail' ).hide();
			}
		});


		$('.mt-error-notice').hide();
		$('.tickets_field').on('blur', function () {
			var remaining = 0;
			var purchasing = 0;
			if ( $(this).val() == '' ) {
				$(this).val( '0' );
			}
			$('.tickets-remaining .value').each(function () {
				var current_value = parseInt($(this).text());
				remaining = remaining + current_value;
			});
			$('.tickets_field').each(function () {
				var disabled = $( this ).attr( 'disabled' ) == 'disabled';
				if ( ! disabled ) {
					var current_value = Number($(this).val());
					purchasing = purchasing + current_value;
				}
			});
			if (purchasing > remaining) {
				$('button[name="mt_add_to_cart"]').addClass('mt-invalid-purchase').attr('disabled', 'disabled');
			} else {
				$('button[name="mt_add_to_cart"]').removeClass('mt-invalid-purchase').removeAttr('disabled');
			}
		});
		$('.mt_cart button:not(.mt-plugin)').on('click', function (e) {
			$( 'input[name="mt_submit"]' ).prop( 'disabled', true );
			$( '.mt-response' ).html( '<p class="mt-response-processing">' + mt_ajax_cart.processing + '</p>' ).show();
			e.preventDefault();
			var action = $(this).attr('class');
			var target = $(this).attr('rel');
			var event_id = $(this).attr('data-id');
			var event_type = $(this).attr('data-type');
			var val = $(target + ' .mt_count').val();
			var remain = $(target + ' .count').attr( 'data-limit' );

			if ( action == 'more' ) {
				var newval = parseInt(val) + 1;
			} else if ( action == 'less' ) {
				if ( parseInt(val) == 0 ) {
					var newval = 0;
					$(target).addClass('removed');
				}
				var newval = parseInt(val) - 1;
			} else {
				var newval = 0;
				$(target).addClass('removed');
			}
			// Prevent setting negative values.
			if ( 0 > newval ) {
				newval = 0;
			}
			if ( newval > remain && action == 'more' ) {
				$( '.mt-response').html("<p>" + mt_ajax_cart.max_limit + "</p>").show(300);
			} else {
				$(target + ' .mt_count').val(newval);
				$(target + ' span.count').text(newval);
				var total = 0;
				var tCount = 0;
				$('td .count').each(function () {
					if ($(this).is(':visible')) {
						var count = $(this).text();
						var price = $(this).parent('td').children('.price').text();
						price = price.replace( mt_ajax_cart.thousands, '' );
						total += parseInt(count) * parseFloat(price);
						tCount += parseInt(count);
					}
				});
				var mtTotal = parseFloat(total).toFixed(2).replace('/(\d)(?=(\d{3})+\.)/g', "$1,");
				$('.mt_total_number').text( mt_ajax.currency + mtTotal.toString());

				var data = {
					'action': mt_ajax_cart.action,
					'data': {mt_event_id: event_id, mt_event_tickets: newval, mt_event_type: event_type},
					'security': mt_ajax_cart.security
				};
				$.post(mt_ajax_cart.url, data, function (response) {
					if (response.success == 1 ) {
						$('.mt-response').html("<p>" + response.response + "</p>").show(300);
						if ( !( mtTotal < 0 || tCount <= 0 ) ) {
							$( 'input[name="mt_submit"]').prop( 'disabled', false );
						}
					} else {
						$( '.mt-response' ).html( '<p class="mt-response-error">' + response.response + '</p>' ).show(300);
					}
				}, "json");
			}
		   // $( '#mtd_donation').on( 'change', function(e) {

		  //  });
		});

		$('.gateway-selector a').on('click', function (e) {
			e.preventDefault();
			$('.gateway-selector li').removeClass('active');
			$(this).parent('li').addClass('active');
			$('.gateway-selector a' ).removeAttr( 'aria-current' );
			$(this).attr( 'aria-current', 'true' );
			var gateway = $(this).attr('data-assign');
			$('input[name="mt_gateway"]').val(gateway);
		});

		var orderButton = $( '.ticket-orders button' );
	   orderButton.on( 'click', function(e) {
		   var fields       = [];
		   let allAreFilled = true;
			$( ".ticket-orders *[required]").each(function(index,i) {
				if ( !i.value ) {
					allAreFilled = false;
					fields.push( i );
				}
				if ( i.type === 'radio' ) {
					let radioValueCheck = false;
					document.querySelectorAll(`.ticket-orders input[name=${i.name}]`).forEach(function(r) {
						if (r.checked) {
							radioValueCheck = true;
						}
					});
					if ( ! radioValueCheck ) {
						fields.push( i );
					}
					allAreFilled = radioValueCheck;
				}
			});
			if ( !allAreFilled ) {
				var response = $( this ).parents( '.mt-order' ).find( '.mt-response');
				var list = '';
				fields.forEach( function(index, e) {
					var id = index.id;
					var name = $( 'label[for=' + id + ']' ).text();
					var error = '<li><a href="#' + id + '">' + name + '</a></li>';
					list += error;
				});
				response.html( '<p>Please complete all required fields!</p><ul>' + list + '</ul>' );
			} else {
				$('.mt-processing').show();
				e.preventDefault();
				var post = $(this).closest('.ticket-orders').serialize();
				var data = {
					'action': mt_ajax.action,
					'data': post,
					'function': 'add_to_cart',
					'security': mt_ajax.security
				};
				$.post(mt_ajax.url, data, function (response) {
					$('#mt-response-' + response.event_id).html("<p>" + response.response + "</p>").show(300).attr('tabindex','-1').focus();
					if ( response.success == 1 ) {
						if ( mt_ajax.redirect == '0' ){
							$('.mt_qc_tickets').text(response.count);
							$('.mt_qc_total').text(parseFloat(response.total, 10).toFixed(2).replace('/(\d)(?=(\d{3})+\.)/g', "$1,").toString());
						} else {
							window.location.replace( mt_ajax.cart_url );
						}
					}
				}, "json");
				$('.mt-processing').hide();
			}
		});

		// on checkbox, update private data
		$('.mt_save_shipping').on('click', function (e) {
			e.preventDefault();
			$('.mt-processing').show();

			var street  = $('.mt_street').val();
			var street2 = $('.mt_street2').val();
			var city    = $('.mt_city').val();
			var state   = $('.mt_state').val();
			var code    = $('.mt_code').val();
			var country = $('.mt_country').val();

			var post = {
				"street": street,
				"street2": street2,
				"city": city,
				"state": state,
				"code": code,
				"country": country
			};

			var data = {
				'action': mt_ajax.action,
				'data': post,
				'function': 'save_address',
				'security': mt_ajax.security
			};
			$.post( mt_ajax.url, data, function (response) {
				var message = response.response;
				$( '.mt-response' ).html( "<p>" + message + "</p>" ).show( 300 );
			}, "json" );
			$('.mt-processing').hide();
		});
		// Remove unsubmitted flag.
		$( '.mt-payment-form form' ).on( 'submit', function(e) {
			var unsubmitted = $( '#mt_unsubmitted' );
			unsubmitted.remove();
		});

		$('.mt-payments button').on( 'click', function (e) {
			var expanded = ( $( this ).attr( 'aria-expanded' ) == 'true' ) ? true : false;
			var controls = $( this ).next( '.mt-payment-details' );
			console.log( {expanded, controls} );
			if ( expanded ) {
				controls.hide();
				$( this ).attr( 'aria-expanded', 'false' );
			} else {
				controls.show();
				$( this ).attr( 'aria-expanded', 'true' );
			}
		});
	});
}(jQuery));

window.addEventListener( 'beforeunload', function(e) {
	var unsubmitted = document.getElementById( 'mt_unsubmitted' );
	var hold        = ( typeof( unsubmitted ) != 'undefined' && unsubmitted != null ) ? true : false;
	if ( hold ) {
		//following two lines will cause the browser to ask the user if they
		//want to leave. The text of this dialog is controlled by the browser.
		e.preventDefault(); //per the standard
		e.returnValue = ''; //required for Chrome
	}
	//else: user is allowed to leave without a warning dialog
});