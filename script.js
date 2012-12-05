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
    var formId = 0;
    jQuery('#chatter__window .chatter_comment').click(function(){

        jQuery('#chatter__window #chatter__commentjs').remove();

        // get chatter id out of parents id
        var container = jQuery(this).parents('li:first');
        var cid = container.attr('id').substring(16);

        formId++;
        var element = '<div id="chatter__commentjs">' +
            '<form method="post"><div>' +
            '<input type="hidden" name="parent" value="' +cid+ '" />' +
            '<label for="chatter__commentjs' + formId + '">Add Comment:</label>' +
            '<textarea name="subcomment" class="chatter_comment_text" id="chatter__commentjs' + formId + '"></textarea>' +
            '<input type="submit" class="button" />' +
            '</div></form></div>';


        container.find('.body:first').append(element);
        container.find('.chatter_comment_text').focus();
    });
});

jQuery(function() {

    function split( val ) {
        return val.split( /\s+/ );
    }
    function extractLast( term ) {
        return split( term ).pop();
    }

    jQuery('.chatter_comment_text')
        .keydown(function( event ) {
            if ( event.keyCode === jQuery.ui.keyCode.TAB &&
                jQuery( this ).data( "autocomplete" ).menu.active ) {
                event.preventDefault();
            }
        })
        .autocomplete({
            minLength: 0,
            source: function (request, response) {
                jQuery.ajax({
                    url:DOKU_BASE + 'lib/exe/ajax.php?call=chatter_autocomplete',
                    data:{term : extractLast( request.term )},
                    dataType:"json",
                    success: function(data) {
                        response(data);
                    },
                    error: function() {
                        response([])
                    }
                });
            },
            focus: function() {
                // prevent value inserted on focus
                return false;
            },
            select: function( event, ui ) {
                var terms = split( this.value );
                // remove the current input
                terms.pop();
                // add the selected item
                terms.push( '@[' + ui.item.label + ']' );
                // add placeholder to get the comma-and-space at the end
                terms.push( "" );
                this.value = terms.join( " " );
                jQuery('<input/>', {type:'hidden', name: 'mention['+ui.item.label+']', value:ui.item.value})
                    .appendTo(jQuery(this).parents('form'));
                return false;
            }
        });
    /*jQuery('.chatter_comment_text').bind('autocompletesearch', function(event, ui) {
            console.log(event);
            console.log(ui);

        });*/

});