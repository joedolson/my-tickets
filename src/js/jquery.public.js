(function ($) {
	$(function () {
		mtAddToCart();
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
			let email_one = $( '#mt_email' ).val();
			let email_two = $( '#mt_email2' ).val();
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

		$('.mt_cart button:not(.mt-plugin,.mt-gateway-selector)').on('click', function (e) {
			$( 'input[name="mt_submit"]' ).prop( 'disabled', true );
			$( '.mt-response' ).html( '<p class="mt-response-processing">' + mt_ajax_cart.processing + '</p>' ).show();
			e.preventDefault();
			let action = $(this).attr('class');
			let target = $(this).attr('rel');
			let event_id = $(this).attr('data-id');
			let event_type = $(this).attr('data-type');
			let val = $(target + ' .mt_count').val();
			let remain = $(target + ' .count').attr( 'data-limit' );
			let newval;

			if ( action == 'more' ) {
				newval = parseInt(val) + 1;
			} else if ( action == 'less' ) {
				if ( parseInt(val) == 0 ) {
					newval = 0;
					$(target).addClass('removed');
				}
				newval = parseInt(val) - 1;
			} else {
				newval = 0;
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
				let total = 0;
				let tCount = 0;
				$('td .count').each(function () {
					if ($(this).is(':visible')) {
						let count = $(this).text();
						let price = $(this).parent('td').children('.price').text();
						price = price.replace( mt_ajax_cart.thousands, '' );
						total += parseInt(count) * parseFloat(price);
						tCount += parseInt(count);
					}
				});
				let mtTotal = parseFloat(total).toFixed(2).replace('/(\d)(?=(\d{3})+\.)/g', "$1,");
				$('.mt_total_number').text( mt_ajax.currency + mtTotal.toString());

				let data = {
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
		});

		$('.gateway-selector button').on('click', function (e) {
			e.preventDefault();
			$('.gateway-selector li').removeClass('active');
			$(this).parent('li').addClass('active');
			$('.gateway-selector button' ).removeAttr( 'aria-current' );
			$(this).attr( 'aria-current', 'true' );
			let gateway = $(this).attr('data-assign');
			let handling = mt_ajax_cart.handling[gateway];
			$( '.mt_cart_handling .price' ).text( handling );
			$('input[name="mt_gateway"]').val(gateway);
		});


		// on checkbox, update private data
		$('.mt_save_shipping').on('click', function (e) {
			e.preventDefault();
			$('.mt-processing').show();

			let street  = $('.mt_street').val();
			let street2 = $('.mt_street2').val();
			let city    = $('.mt_city').val();
			let state   = $('.mt_state').val();
			let code    = $('.mt_code').val();
			let country = $('.mt_country').val();

			let post = {
				"street": street,
				"street2": street2,
				"city": city,
				"state": state,
				"code": code,
				"country": country
			};

			let data = {
				'action': mt_ajax.action,
				'data': post,
				'function': 'save_address',
				'security': mt_ajax.security
			};
			$.post( mt_ajax.url, data, function (response) {
				let message = response.response;
				$( '.mt-response' ).html( "<p>" + message + "</p>" ).show( 300 );
			}, "json" );
			$('.mt-processing').hide();
		});

		/* Add to Cart form */
		function mtAddToCart() {
			const addToCart = $( '.mt-order .ticket-orders' );

			$('.mt-error-notice') .hide();
			addToCart.on('blur', '.tickets-field', function () {
				let remaining = 0;
				let purchasing = 0;
				if ( $(this).val() == '' ) {
					$(this).val( '0' );
				}
				$('.tickets-remaining .value').each(function () {
					let current_value = parseInt($(this).text());
					remaining = remaining + current_value;
				});
				$('.tickets_field').each(function () {
					let disabled = $( this ).attr( 'disabled' ) == 'disabled';
					if ( ! disabled ) {
						let current_value = Number($(this).val());
						purchasing = purchasing + current_value;
					}
				});
				if (purchasing > remaining) {
					$('button[name="mt_add_to_cart"]').addClass('mt-invalid-purchase').attr('disabled', 'disabled');
				} else {
					$('button[name="mt_add_to_cart"]').removeClass('mt-invalid-purchase').removeAttr('disabled');
				}
			});
			/* Custom ticket count incrementing. */
			addToCart.on( 'click', '.mt-increment', function() {
				let field = $( this ).parent( '.mt-ticket-input' ).find( 'input' );
				let value = parseInt( field.val() );
				let max   = parseInt( field.attr( 'max' ) );
				let newval = value + 1;
				if ( newval <= max ) {
					field.val( newval );
				} else {
					field.val( max );
					newval = max;
				}
				newval = newval.toString();
				wp.a11y.speak( newval, 'assertive' );
			});

			addToCart.on( 'click', '.mt-decrement', function() {
				let field = $( this ).parent( '.mt-ticket-input' ).find( 'input' );
				let value = parseInt( field.val() );
				let min   = parseInt( field.attr( 'min' ) );
				let newval = value - 1;
				if ( newval >= min ) {
					field.val( newval );
				} else {
					field.val( min );
					newval = min;
				}
				newval = newval.toString();
				wp.a11y.speak( newval, 'assertive' );
			});
			/* Check whether the form requirements are fulfilled. */
			addToCart.on( 'click', 'button[name="mt_add_to_cart"]', function(e) {
				let fields       = [];
				let allAreFilled = true;
				$( ".ticket-orders *[required]" ).each(function(index,i) {
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
					let response = $( this ).parents( '.mt-order' ).find( '.mt-response');
					let list = '';
					fields.forEach( function(index, e) {
						let id = index.id;
						let name = $( 'label[for=' + id + ']' ).text();
						let error = '<li><a href="#' + id + '">' + name + '</a></li>';
						list += error;
					});
					response.html( '<p>' + mt_ajax.requiredFieldsText + '</p><ul>' + list + '</ul>' );
				} else {
					$('.mt-processing').show();
					e.preventDefault();
					let post = $(this).closest('.ticket-orders').serialize();
					let data = {
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
		}

		// extend cart expiration.
		$('.mt-extend-button').on('click', function (e) {
			$('.mt-processing').show();

			let data = {
				'action': mt_ajax.action,
				'function': 'extend_cart',
				'security': mt_ajax.security
			};
			$.post( mt_ajax.url, data, function (response) {
				let message = response.response;
				$( '.mt-expiration-update' ).html( "<p>" + message + "</p>" ).show( 300 );
			}, "json" );
			$('.mt-processing').hide();
		});

		// Remove unsubmitted flag.
		$( '.mt-payment-form form' ).on( 'submit', function(e) {
			let unsubmitted = $( '#mt_unsubmitted' );
			unsubmitted.remove();
		});

		$('.mt-payments button').on( 'click', function (e) {
			let expanded = ( $( this ).attr( 'aria-expanded' ) == 'true' ) ? true : false;
			let controls = $( this ).next( '.mt-payment-details' );
			if ( expanded ) {
				controls.hide();
				$( this ).attr( 'aria-expanded', 'false' );
			} else {
				controls.show();
				$( this ).attr( 'aria-expanded', 'true' );
			}
		});

		setTimeout( function() { 
			setInterval( function() {
				let time = $('.mt-expiration-update').text();
				wp.a11y.speak( time );
			}, 1000 * 60 ); 
		}, 1000 );

		const timer = $('#mt-timer');
		setInterval( mtUpdateTimer, 5000, timer );
	
		function mtUpdateTimer( timer ) {
			let seconds = timer.data('start');
			if ( seconds > 0 ) {
				let second = seconds - 5;
				timer.data( 'start', second );
	
				let date = new Date(null);
				date.setSeconds( second ); 
				let min = date.getMinutes();
				let sec = date.getSeconds();
				timer.html( min + 'min ' + sec + 'sec' );
			} else {
				timer.html( 'Expired' );
			}
		}
	});
}(jQuery));

window.addEventListener( 'beforeunload', function(e) {
	let unsubmitted = document.getElementById( 'mt_unsubmitted' );
	let hold        = ( typeof( unsubmitted ) != 'undefined' && unsubmitted != null ) ? true : false;
	if ( hold ) {
		// following lines cause the browser to ask the user if they want to leave.
		// The text of this dialog is controlled by the browser.
		e.preventDefault(); // per the standard
		e.returnValue = ''; // required for Chrome
	}
});