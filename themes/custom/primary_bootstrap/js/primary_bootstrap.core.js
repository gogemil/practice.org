(function($){
    var BF = BF || {};

    /**
     * BF.init accepts functions to call on page ready. This is just a wrapper to avoid having to add the jQuery binding in multiple scripts
     */
    var _init = [];
    BF.init = function(method){
        _init.push(method);
    };

    $(function(){
        for(var i in _init){
            if(!_init.hasOwnProperty(i))
                continue;

            _init[i]();
        }
    });

    window.BF = BF;
})(jQuery);