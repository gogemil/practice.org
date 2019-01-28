/*
    If the user is logged in, pulls the welcome message via jscript
    Member Central markup in modules/custom/ntca/src/Controller/UserStatusController.php
*/
(function ($) {
    BF.init(function () {
        if ($('body').hasClass('user-logged-in')) {
            $.ajax({
                url: '/userstatus',
                dataType: 'html',
                success: function(data, sStatus, jqXHR) {
                    // data is pure string
                    // we need to insert it somewhere
                    $('#block-primary-bootstrap-main-menu').before("<!-- From welcome-message.part.js -->" + data);
                }
            });
        }
    });
})(jQuery);
