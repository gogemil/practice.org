(function ($) {
    BF.init(function () {
        var $stickHeader = function() {
            var oSiteHeader = $('.site-header');
            if (oSiteHeader.length > 0) {
                var sticky = new Waypoint.Sticky({
                    element: $('.site-header')[0],
                    offset: 1
                })
            }
        }
        $stickHeader();

    });
})(jQuery);