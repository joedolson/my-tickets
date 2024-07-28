jQuery(document).ready(function ($) {
    const cols = $( 'th[scope=col]' );
    $( '.show-button' ).attr( 'disabled', 'disabled' );

    cols.each( function() {
        let context = $( this ).attr( 'class' );
        let target  = $( this ).attr( 'id' );
        $( this ).append( '<button type="button" class="mt-hide-button" data-context="' + context + '" aria-describedby="' + target + '"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><span class="screen-reader-text">' + mtprint.mt_action_text + '</span></button>' );
    });

    $( 'button' ).on( 'click', function(e) {
      let effects = $(this).attr( 'data-context' );
	  let pressed = $(this).attr( 'aria-pressed' );
	  if ( 'true' === pressed ) {
		$( '.' + effects ).removeClass( 'mt-hidden' );
		$( this ).find( 'span' ).removeClass( 'dashicons-hidden' ).addClass( 'dashicons-visibility' );
		$( this ).removeAttr( 'aria-pressed' );
	  } else {
  	    $( '.' + effects ).addClass( 'mt-hidden' );
  	    $( '.show-button' ).removeAttr( 'disabled' );
		$( this ).find( 'span' ).removeClass( 'dashicons-visibility' ).addClass( 'dashicons-hidden' );
		$( this ).attr( 'aria-pressed', 'true' );
	  }
    });

    $( '.show-button' ).on( 'click', function(e) {
        $( 'th.mt-hidden, td.mt-hidden' ).removeClass( 'mt-hidden' );
        $( this ).attr( 'disabled', 'disabled' );
    });

});