jQuery(document).ready(function ($) {
    var tabs = $('.mt-tabs .wptab').length;
    $('.mt-tabs .tabs a[href="#' + mtTabs.firstItem + '"]').addClass('active').attr( 'aria-pressed', 'true' );
    if ( tabs > 1 ) {
        $('.mt-tabs .wptab').not('#' + mtTabs.firstItem).hide();
        $('.mt-tabs .tabs a').on('click', function (e) {
            e.preventDefault();
            $('.mt-tabs .tabs a').removeClass('active');
            $(this).addClass('active').attr( 'aria-pressed', 'true' );
            var target = $(this).attr('href');
            $('.mt-tabs .wptab').not(target).hide().removeAttr( 'aria-pressed' );
            $(target).show();
        });
    }
	// Handle report selector for subtypes.
	let curValue = $( '#mt_select_event' ).val();
	populateSelect( curValue );
	$( '#mt_select_event' ).on( 'change', function(e) {
		let value = $( this ).val();
		populateSelect( value);
	});

	function populateSelect( value ) {
		const json       = $( '#report-json' ).text();
		const optionsObj = JSON.parse(json);
		let select       = document.querySelector( '#mt_select_ticket_type' );
		let optionEls    = document.querySelectorAll( '#mt_select_ticket_type option' );
		optionEls.forEach( function(el) {
			let valueEl = el.getAttribute( 'value' );
			if ( 'all' !== valueEl ) {
				console.log( el );
				el.remove();
			}
		});
		select.parentElement.classList.add( 'hidden' );
		if ( 'false' !== value ) {
			let options = optionsObj[value];
			Object.keys(options).forEach(function(key) {
				let option = document.createElement( 'option' );
				option.value = key;
				option.text  = options[key].label;
				select.insertAdjacentElement('beforeend', option );
			});
			select.parentElement.classList.remove( 'hidden' );
		}
	}
});