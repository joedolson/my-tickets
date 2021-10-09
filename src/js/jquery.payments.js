(function ($) {
    $(function() {
        $( 'button.toggle-debug' ).on( 'click', function() {
            var next = $( this ).next( 'pre' );
            if ( $( this ).next( 'pre' ).is( ':visible' ) ) {
                $( this ).next( 'pre' ).hide();
                $( this ).attr( 'aria-expanded', 'false' );
            } else {
                $( this ).next( 'pre' ).show();
                $( this ).attr( 'aria-expanded', 'true' );
            }
        });
    })
})(jQuery);