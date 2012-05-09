<?php

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
require_once(DOKU_INC.'inc/init.php');
session_write_close();

$CHATTER = plugin_load('helper','chatter');
$ID      = cleanID($_GET['id']);
$CID     = $CHATTER->id2chatter($ID);


html_header();
echo "<!-- $CID -->";

if(!$CID){
    html_authrequired();
}else{
    $FOLLOW  = $CHATTER->follow($CID,(isset($_GET['follow'])?$_GET['follow']:null));
    if($_POST['comment']){
        $CHATTER->addcomment($_POST['parent'],$_POST['comment']);
    } elseif ($_POST['subcomment']) {
        $CHATTER->addSubComment($_POST['parent'],$_POST['subcomment']);
    }


    $ret = $CHATTER->apicall('GET','/chatter/feeds/record/'.$CID.'/feed-items');
    if(!$ret){
        html_authrequired();
    }else{
        html_follow();
        html_comments($CID, $ret['items']);
        html_commentform();
    }
}

html_footer();

function html_follow(){
    global $FOLLOW;
    global $ID;

    if($FOLLOW){
        echo '<a href="frame.php?id='.$ID.'&amp;follow='.hsc($FOLLOW).'" class="button unfollow">Unfollow</a>';
    }else{
        echo '<a href="frame.php?id='.$ID.'&amp;follow=1" class="button follow">Follow</a>';
    }

}

function html_commentform($id = false){
    global $CID;
    $subComment = true;
    if (!$id) {
        $id = $CID;
        $subComment = false;
    }
    echo '<form method="post">';
    echo '<input type="hidden" name="parent" value="'.hsc($id).'" />';
    echo '<label for="chatter__comment">Add Comment:</label>';
    if ($subComment) {
        echo '<input type="text" name="subcomment" id="chatter__comment" /> ';
    } else {
        echo '<input type="text" name="comment" id="chatter__comment" /> ';
    }
    echo '<input type="submit" class="button" />';
    echo '</form>';
}


function html_comments($id, $items, $comments = true){
    echo '<ul>';

    foreach($items as $item){
        if(!isset($item['actor'])) $item['actor'] = $item['user'];
        if($item['type'] == 'TrackedChange') continue;

        echo '<li id="chatter__comment'.$item['id'].'">';
        echo '  <img src="'.$item['actor']['photo']['smallPhotoUrl'].'" width="45" height="45" /> ';
        echo '  <div class="body">';
        echo '    <div class="inner">';
        echo        '<b class="author">'.hsc($item['actor']['name']).':</b> ';
        echo        hsc($item['body']['text']);
        echo        '<br /><span class="date">'.dformat(strtotime($item['createdDate'])).'</span>';
        if ($comments && !count($item['comments']['comments'])) {
            echo '<a class="chatter_comment">Comment</a>';
        }
        echo '</div>';
        // recurse for replies
        if(count($item['comments']['comments']))
            html_comments($item['id'], $item['comments']['comments'], false);
        echo '</div>';
        echo '</li>';
    }

    if (!$comments) {
        html_commentform($id);
    }

    echo '</ul>';
}


function html_header(){
    echo '<html>';
    echo '<head>';
    echo '<title>Chatter</title>';
    tpl_metaheaders();
    echo '</head>';
    echo '<body id="chatter__window">';
END;
}

function html_footer(){
    echo <<<END
    </body>
    </html>
END;
}

function html_authrequired(){
    global $CHATTER;
    echo '<a href="'.$CHATTER->authurl.'" id="chatter__openauth" target="_blank" class="button">Please login and authorize Chatter access</a>';
}
