(function (wp) {
    wp.data.dispatch('core/notices').createWarningNotice(
        wp.i18n.__( 'This is a required page for My Tickets. You can modify it or rename it. If you delete it, you will need to assign a new page to replace it in the My Tickets settings.', 'my-tickets' ),
        {
            id: 'mt-required-page-notice',
            isDismissible: false,
        }
    );
})(window.wp);