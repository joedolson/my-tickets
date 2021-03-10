(function ($) {
    $(function () {
        $('.suggest').autocomplete({
                minLength: 3,
                source: function (req, response) {
                    $.getJSON(ajaxurl + '?callback=?&action=' + mtAjax.action, req, response);
                },
                select: function (event, ui) {
                    var label = $(this).attr('id');
                    $(this).val(ui.item.id);
                    $('label[for=' + label + '] span').text(' (' + ui.item.value + ')');
                    return false;
                }
            }
        );
    });
}(jQuery));