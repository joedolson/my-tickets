jQuery(document).ready(function ($) {
    var cols = $( 'th[scope=col]' );
    $( '.show-button' ).hide();

    cols.each( function() {
        var context = $( this ).attr( 'class' );
        var target  = $( this ).attr( 'id' );
        $( this ).append( '<button type="button" class="mt-hide-button" data-context="' + context + '" aria-describedby="' + target + '"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><span class="screen-reader-text">' + mtprint.mt_action_text + '</span></button>' );
    });

    $( 'button' ).on( 'click', function(e) {
      var effects = $(this).attr( 'data-context' );
      $( '.' + effects ).addClass( 'hidden' );
      $( '.show-button' ).show();
    });

    $( '.show-button' ).on( 'click', function(e) {
        $( 'th.hidden, td.hidden' ).removeClass( 'hidden' );
        $( this ).hide();
    });

});