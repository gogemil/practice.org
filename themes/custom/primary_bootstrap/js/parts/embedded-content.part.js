(function ($) {
    BF.init(function () {
        var $embeddedContent = function() {

            //Add Classes
            var halfWidthElement = $('.view-mode-embedded-alt-1');
            if (halfWidthElement.length) {
                $.each(halfWidthElement, function() {
                    if ($(this).hasClass('media-image')) {
                        $(this).parents('.embedded-entity').addClass('half-image');
                    } else if ($(this).hasClass('media-video')) {
                        $(this).parents('.embedded-entity').addClass('half-video');
                    }
                });
            }

            //Caption Width
            var embeddedContent = $('.embedded-entity'),
                setCaptionWidth = function() {
                    if (embeddedContent.length) {
                        $.each(embeddedContent, function() {
                            var media = $(this).find('img, iframe'),
                                caption = $(this).find('figcaption');
                            if (media.length && caption.length) {
                                var mediaWidth = media.width() + 'px';
                                caption.css('max-width', mediaWidth);
                            }
                        });
                    }
                };
            setCaptionWidth();
            $(window).resize(function () {
                setCaptionWidth();
            });

        }
        $embeddedContent();
    });
})(jQuery);