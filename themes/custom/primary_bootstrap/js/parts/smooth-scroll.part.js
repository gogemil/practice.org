/*
 Targets all <a> tags where the first character is "#" but is not the only character.
 It is assumed that hrefs with only "#" are governed by other JavaScript functions and are excluded.
 */

(function ($) {
    BF.init(function () {
        $('a[href*="#"]:not([href="#"])').click(function() {
            if (location.pathname.replace(/^\//, '') == this.pathname.replace(/^\//, '') && location.hostname == this.hostname) {
                var $target = $(this.hash);
                target = $target.length ? $target : $('[name=' + this.hash.slice(1) + ']');
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 50
                    }, 500);
                    return false;
                }
            }
        });
    });
})(jQuery);