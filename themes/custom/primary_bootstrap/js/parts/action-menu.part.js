(function ($) {
    BF.init(function () {
        var $toggleActionMenu = function() {
            var $toggleButton = $("#toggle-action"),
                $actionMenu = $(".site-header .action-menu");
            if($toggleButton.length) {
                $toggleButton.on("click", function(e) {
                    e.preventDefault();
                    $actionMenu.toggleClass('active');
                });
            }
        }
        $toggleActionMenu();
    });
})(jQuery);