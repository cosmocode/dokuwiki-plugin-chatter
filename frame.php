<?php

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
require_once(DOKU_INC.'inc/init.php');
session_write_close();

$CHATTER = plugin_load('helper','chatter');
$ID      = cleanID($_GET['id']);
$CID     = $CHATTER->id2chatter($ID);



html_header();

if(!$CID){
    html_authrequired();
}else{

    if($_POST['comment']) $CHATTER->addcomment($_POST['parent'],$_POST['comment']);


    $ret = $CHATTER->apicall('GET','/chatter/feeds/record/'.$CID.'/feed-items');
    if(!$ret){
        html_authrequired();
    }else{
        html_comments($ret['items']);
        html_commentform();
    }
}

html_footer();


function html_commentform(){
    global $CID;
    echo '<form method="post">';
    echo '<input type="hidden" name="parent" value="'.hsc($CID).'" />';
    echo '<textarea name="comment"></textarea>';
    echo '<input type="submit" />';
    echo '</form>';
}


function html_comments($items){
    echo '<ul>';

    foreach($items as $item){
        if(!isset($item['actor'])) $item['actor'] = $item['user'];
        #FIXME skip non-comments

        echo '<li id="chatter__comment'.$item['id'].'>';
        echo '<div class="body">'.hsc($item['body']['text']).'</div>';

        echo '<div class="author">';
        echo '<img src="'.$item['actor']['photo']['smallPhotoUrl'].'"> ';
        echo '<div class="name">'.hsc($item['actor']['name']).'</div>';
        echo '<div class="date">'.dformat(strtotime($item['createdDate'])).'</div>';
        echo '</div>';


        // recurse for replies
        if(count($item['comments']['comments']))
            html_comments($item['comments']['comments']);

        echo '</li>';
    }

    echo '</ul>';
}


function html_header(){
    echo <<<END
    <html>
    <head>
        <title>Chatter</title>
    </head>
    <body>
END;
}

function html_footer(){
    echo <<<END
    </body>
    </html>
END;
}

function html_authrequired(){
    #FIXME no JS link
    echo <<<END
    <a href="javascript:window.open('try.php','auth')">Please login and authorize Chatter access</a>
END;
}
