jQuery(function(){

    jQuery('#chatter__openauth').click(function(e){
        window.open(this.href,'oauth','width=660,height=490');
        e.preventDefault();
        e.stopPropagation();
    });

    // toggle support
    var $iframe = jQuery('#chatter__frame');
    var $headln = jQuery('#chatter__headline');
    if($iframe.length){
        var cookie = DokuCookie.getValue('chatter');
        if(!cookie) cookie = 'none';
        $iframe.css('display',cookie);

        if($iframe.css('display') == 'none'){
            $headln.addClass('chatter_closed');
        }else{
            $headln.addClass('chatter_open');
        }

        $headln.click(function(){
            $iframe.slideToggle('fast',function(){
                DokuCookie.setValue('chatter',$iframe.css('display'));
                $headln.toggleClass('chatter_closed');
                $headln.toggleClass('chatter_open');
            });
        });
    }

});
