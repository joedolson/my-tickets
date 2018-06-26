jQuery(document).ready(function ($) {
    var initial_status = $('.mt-trigger').attr('checked');
    if (initial_status !== 'checked') {
        $('.mt-ticket-form').hide();
    }
    $('.mt-trigger').click(function () {
        var checked_status = $(this).prop('checked');
        if (checked_status == true) {
            $('.mt-ticket-dates input').attr('required', 'required').attr('aria-required', 'true');
            $('.mt-available-tickets input').attr( 'required', 'required' ).attr( 'aria-required', 'true' );
            $('.mt-ticket-form').show(300);
        } else {
            $('.mt-ticket-dates input').removeAttr('required').removeAttr('aria-required');
            $('.mt-available-tickets input').removeAttr( 'required' ).removeAttr( 'aria-required' );
            $('.mt-ticket-form').hide(200);
        }
    });
});