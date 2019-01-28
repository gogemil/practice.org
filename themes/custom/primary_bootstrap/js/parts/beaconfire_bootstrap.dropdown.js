// This is copied from the BFRED Bootstrap theme.
// CUSTOM
// modified to add support for FRS menu
(function($){
  BF.init(function(){

    var link = $("#block-primary-bootstrap-main-menu .dropdown a, #block-frsmainnavigation-2 .dropdown a");

    if (link.length) {
      $.each(link, function() {

        // Stop clicks inside a dropdown menu from toggling dropdown
        $(this).on('click',function(e){
          e.stopPropagation();
          window.location = $(this).attr('href');
        });

        //Show dropdown when caret clicked
        $(this).on('click', '.caret', function(e) {
          e.preventDefault;
          var dropdown = $(this).parents('.dropdown');
          if (dropdown.hasClass('open')) {
            $('.open').removeClass('open');
            return false;
          } else {
            $('.open').removeClass('open');
            dropdown.addClass('open');
            return false;
          }
        });

      });
    }

    //Close open sub menu if windodw resized to >= 788px
    // $(window).resize(function() {
    //   if($(window).width() >= 768) {
    //     $('.open').removeClass('open');
    //   }
    // });

  });
})(jQuery);