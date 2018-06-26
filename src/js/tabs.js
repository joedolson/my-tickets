jQuery(document).ready(function ($) {
    var tabs = $('.mt-tabs .wptab').length;
    $('.mt-tabs .tabs a[href="#' + firstItem + '"]').addClass('active');
    if (tabs > 1) {
        $('.mt-tabs .wptab').not('#' + firstItem).hide();
        $('.mt-tabs .tabs a').on('click', function (e) {
            e.preventDefault();
            $('.mt-tabs .tabs a').removeClass('active');
            $(this).addClass('active');
            var target = $(this).attr('href');
            $('.mt-tabs .wptab').not(target).hide();
            $(target).show();
        });
    }
});