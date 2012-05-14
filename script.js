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

jQuery(function() {

    jQuery('#chatter__window .chatter_comment').click(function(){

        jQuery('#chatter__window #chatter__commentjs').remove();

        // get chatter id out of parents id
        var container = jQuery(this).parents('li:first');
        var cid = container.attr('id').substring(16);

        var element = '<div id="chatter__commentjs">' +
            '<form method="post"><div>' +
            '<input type="hidden" name="parent" value="' +cid+ '" />' +
            '<label for="chatter__commentjs">Add Comment:</label>' +
            '<input type="text" name="subcomment" id="chatter__comment" />' +
            '<input type="submit" class="button" />' +
            '</div></form></div>';


        container.find('.body:first').append(element);
        container.find('#chatter__comment').focus();
    });


});