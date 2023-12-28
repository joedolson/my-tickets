(function ($) {
	$(function () {
		mt_pricing_table();
		function mt_pricing_table() {
			$('.add-price').on('click', function () {
				var context = $( this ).attr( 'data-context' );
				var num = $('.clonedPrice.' + context).length; // how many "duplicatable" input fields we currently have
				var newNum = new Number(num + 1);      // the numeric ID of the new input field being added
				// create the new element via clone(), and manipulate it's ID using newNum value
				var newElem = $('#price' + context + num).clone().attr('id', 'price' + context + newNum);
				// manipulate the name/id values of the input inside the new element
				// insert the new element after the last "duplicatable" input field
				$('#price' + context + num).after(newElem);
				// enable the "remove" button
				$('.del-price.' + context).removeAttr('disabled');
				// business rule: you can only add 20 variations
				if (newNum == 20) {
					$( this ).attr('disabled', 'disabled');
				}
			});

			$('.del-price').on('click', function () {
				var context = $( this ).attr( 'data-context' );
				var num = $('.clonedPrice.' + context).length; // how many "duplicatable" input fields we currently have
				$('#price' + context + num).remove();     // remove the last element
				// enable the "add" button
				$( this ).removeAttr('disabled');
				// if only one element remains, disable the "remove" button
				if (num - 1 == 1) {
					$( this ).attr('disabled', 'disabled');
				}
			});
			$('.del-price').attr('disabled', 'disabled');

			$("button.up,button.down").on( 'click', function(e){
				e.preventDefault();
				$('.mt-pricing table tr').removeClass('fade');
				var row = $(this).parents("tr:first");
				if ($(this).is(".up")) {
					row.insertBefore(row.prev()).addClass('fade');
				} else {
					row.insertAfter(row.next()).addClass('fade');
				}
			});

			$('.deletable .mt-controls').append( '<button type="button" class="button delete"><span class="dashicons dashicons-no"></span><span class="screen-reader-text">' + mt.delete + '</span></button>' );
			$('.deletable .mt-controls .delete').on( 'click', function() {
				var is_undo = $( this ).hasClass( 'undo' );
				var parent = $(this).parents('.deletable');
				if ( is_undo ) {
					parent.find('input,button.up,button.down').removeAttr('disabled');
					parent.find('button.delete').removeClass('undo');
					parent.find('button.delete .dashicons').removeClass( 'dashicons-undo').addClass('dashicons-no');
					parent.find('button.delete .screen-reader-text').text(mt.delete);
				} else {
					parent.find('input,button.up,button.down').attr('disabled', 'disabled');
					parent.find('button.delete').addClass('undo');
					parent.find('button.delete .dashicons').removeClass( 'dashicons-no').addClass('dashicons-undo');
					parent.find('button.delete .screen-reader-text').text(mt.undo);
				}
			});
		}
		$( '.mt-load-model button' ).on( 'click', function() {
			var event_id = $(this).attr( 'data-event' );
			var model    = $(this).attr( 'data-model' );
			var event    = $( '.mt-ticket-data-json' ).html();
			$( '.mt-load-model button' ).attr( 'aria-selected', 'false' ).removeAttr( 'id' );
			$( this ).attr( 'aria-selected', 'true' ).attr( 'id', 'current-tab' );
			$( '.mt-ticket-wrapper-form' ).removeAttr( 'aria-labelledby' );
			var data     = {
				'action': mt.action,
				'event_id': event_id,
				'model': model,
				'event': event,
				'security': mt.security
			};

			$.post( ajaxurl, data, function (response) {
				var container = $( '.mt-ticket-wrapper-form' );
				var form      = response.form;

				container.attr( 'aria-labelledby', 'current-tab' );
				container.html( form );
				mt_pricing_table();
			}, "json" );
		});
	});
}(jQuery));

window.customElements.whenDefined( 'duet-date-picker' ).then(() => {
	elem = document.querySelectorAll('.duet-fallback');
	elem.forEach((el) => {
		el.parentNode.removeChild(el);
	});
});

const pickers = Array.prototype.slice.apply( document.querySelectorAll( 'duet-date-picker' ) );

pickers.forEach((picker) => {
	picker.localization = duetLocalization;
});
