jQuery(document).ready(function ($) {
    var cols = $( 'th[scope=col]' );
    $( '.show-button' ).hide();

    cols.each( function() {
        var context = $( this ).attr( 'class' );
        $( this ).append( '<br /><button data-context="' + context + '"><span class="hide">' + mt_action_text + '</span></button>' );
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