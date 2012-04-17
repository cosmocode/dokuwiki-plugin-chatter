jQuery(function(){

    jQuery('#chatter__openauth').click(function(e){
        window.open(this.href,'oauth','width=660,height=490');
        e.preventDefault();
        e.stopPropagation();
    });

    // toggle support
    var $iframe = jQuery('#chatter__frame');
    if($iframe.length){
        var cookie = DokuCookie.getValue('chatter');
        if(!cookie) cookie = 'none';
        $iframe.css('display',cookie);

        var img = document.createElement('img');
        img.id = 'chatter__toggle';
        img.title = LANG.plugins.chatter.toggle;
        if(cookie == 'none'){
            img.src = DOKU_BASE+'lib/plugins/chatter/pix/down.png';
        }else{
            img.src = DOKU_BASE+'lib/plugins/chatter/pix/up.png';
        }

        $iframe.before(img);
        jQuery(img).click(function(){
            $iframe.slideToggle('fast',function(){
                DokuCookie.setValue('chatter',$iframe.css('display'));
                if($iframe.css('display') == 'none'){
                    img.src = DOKU_BASE+'lib/plugins/chatter/pix/down.png';
                }else{
                    img.src = DOKU_BASE+'lib/plugins/chatter/pix/up.png';
                }
            });
        });
    }

});
