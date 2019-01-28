/*

(function($){
	BF.init(function(){


		// Bind menu toggle on hover
    // This was replaced with a CSS solution in the Primary theme.
		$("body").on("mouseenter mouseleave",".dropdown", function(){
			$(this).children('.dropdown-toggle').dropdown('toggle');
		});

		// Stop clicks inside a dropdown menu from toggling dropdown
    // This is provided by a copy of this file in the primary theme
		$(".dropdown a").on("click",function(e){
			e.stopPropagation();

			var $a = $(this);
			if($a.hasClass('dropdown-toggle') && $(window).width() > 768)
				window.location = $a.attr('href');
		});

	});
})(jQuery);
// */
