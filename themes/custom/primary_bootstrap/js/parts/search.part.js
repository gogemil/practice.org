/*
 Targets all <a> tags where the first character is "#" but is not the only character.
 It is assumed that hrefs with only "#" are governed by other JavaScript functions and are excluded.
 */

(function ($) {
    BF.init(function () {
        var siteHeader = $('.site-header'),
            searchTrigger = $('.search-trigger'),
            searchField = $('#views-exposed-form-search-page-page-1 .form-control');

        searchTrigger.on('click', function () {
            siteHeader.toggleClass('search-active');
            searchField.focus();
        });
    });
})(jQuery);