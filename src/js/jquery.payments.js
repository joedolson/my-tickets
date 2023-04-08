(function ($) {
    $(function() {
        $( 'button.toggle-debug' ).on( 'click', function() {
            if ( $( this ).next( 'pre' ).is( ':visible' ) ) {
                $( this ).next( 'pre' ).hide();
                $( this ).attr( 'aria-expanded', 'false' );
            } else {
                $( this ).next( 'pre' ).show();
                $( this ).attr( 'aria-expanded', 'true' );
            }
        });

		$( 'button.edit-ticket' ).on( 'click', function() {
            var controls = $( this ).attr( 'aria-controls' );
			var target   = $( '#' + controls );
            if ( target.is( ':visible' ) ) {
               target.hide();
               target.attr( 'aria-expanded', 'false' );
            } else {
               target.show();
               target.attr( 'aria-expanded', 'true' );
            }
        });

		$( 'button.mt-move-tickets-button' ).on( 'click', function() {
			var button     = $(this);
			var container  = button.parents( '.mt-move-tickets' );
			var event_id   = button.attr( 'data-event' );
			var payment_id = button.attr( 'data-payment' );
			var target     = container.find( '.mt-move-tickets-target' ).val();
			var ticket     = button.attr( 'data-ticket' );
			var data = {
				'action': mt_data.action,
				'event_id': event_id,
				'payment_id': payment_id,
				'target': target,
				'ticket': ticket,
				'security': mt_data.security
			};
			$.post( ajaxurl, data, function (response) {
				console.log( response );
				var responseField = container.find( '.mt-ticket-moved-response' );
				if ( response.success == 1 ) {
					responseField.removeClass( 'error' ).addClass( 'success' );
					button.attr( 'data-event', target );
				} else {
					responseField.removeClass( 'success' ).addClass( 'error' );
				}
				responseField.text( response.response ).show( 300 );
			}, "json" );
		});

		$( 'button.mt-delete-ticket-button' ).on( 'click', function() {
			var button     = $(this);
			var container  = button.parents( '.mt-move-tickets' );
			var event_id   = button.attr( 'data-event' );
			var payment_id = button.attr( 'data-payment' );
			var ticket     = button.attr( 'data-ticket' );
			var data = {
				'action': mt_data.deleteaction,
				'event_id': event_id,
				'payment_id': payment_id,
				'ticket': ticket,
				'security': mt_data.security
			};
			$.post( ajaxurl, data, function (response) {
				console.log( response );
				var responseField = container.find( '.mt-ticket-moved-response' );
				if ( response.success == 1 ) {
					responseField.removeClass( 'error' ).addClass( 'success' );
					setTimeout( function() {
						container.parents( 'li' ).hide( 500 ).remove();
					}, 5000 );
				} else {
					responseField.removeClass( 'success' ).addClass( 'error' );
				}
				responseField.text( response.response ).show( 300 );
			}, "json" );
		});
    })

})(jQuery);