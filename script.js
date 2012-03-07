jQuery(function(){

    jQuery('#chatter__openauth').click(function(e){
        window.open(this.href,'oauth','width=660,height=490');
        e.preventDefault();
        e.stopPropagation();
    });


});
