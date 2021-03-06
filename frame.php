<?php

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
require_once(DOKU_INC.'inc/init.php');
session_write_close();

/**
 * @var helper_plugin_chatter $CHATTER
 */
$CHATTER = plugin_load('helper','chatter');
$ID      = cleanID($_GET['id']);
$CID     =  $CHATTER->id2chatter($ID);


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
    } elseif ($_POST['like']) {
        $CHATTER->like($_POST['like'], isset($_POST['subLike']));
    } elseif ($_POST['unlike']) {
        $CHATTER->unlike($_POST['unlike']);
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

    static $fieldId = 0;
    $fieldId++;

    echo '<form method="post"><div>';
    echo '<input type="hidden" name="parent" value="'.hsc($id).'" />';
    echo '<label for="chatter__comment'. $fieldId .'">Add Comment:</label>';
    if ($subComment) {
        echo '<textarea name="subcomment" class="chatter_comment_text" id="chatter__comment'. $fieldId .'"></textarea>';
    } else {
        echo '<textarea name="comment" class="chatter_comment_text" id="chatter__comment'. $fieldId .'"></textarea>';
    }
    echo '<input type="submit" class="button" />';
    echo '<ul class="suggest"></ul>';
    echo '</div></form>';
}


function html_comments($id, $items, $comments = true){
    global $CHATTER;
    echo '<ul>';

    foreach($items as $item){
        if(!isset($item['actor'])) $item['actor'] = $item['user'];
        if($item['type'] == 'TrackedChange') continue;

        echo '<li id="chatter__comment'.$item['id'].'">';
        echo '  <img src="'.$item['actor']['photo']['smallPhotoUrl'].'" width="45" height="45" /> ';
        echo '  <div class="body">';
        echo '    <div class="inner">';
        echo        '<b class="author">'.hsc($item['actor']['name']).':</b> ';
        echo        $CHATTER->bodyToText($item['body']);
        echo        '<br /><span class="date">'.dformat(strtotime($item['createdDate'])).'</span>';

        if ($comments && !count($item['comments']['comments'])) {
            echo '<a class="chatter_comment">Comment</a>';
        }

        if ($item['myLike'] === null) {
            html_like($item['id'], !$comments);
        } else {
            html_unlike($item['myLike']['id']);
        }

        if ($item['likes']['total'] > 0) {
            echo '<span class="likes">' . $item['likes']['total'] . ' person like this</span>';
        }

        echo '</div>';
        // recurse for replies
        if(count($item['comments']['comments']))
            html_comments($item['id'], $item['comments']['comments'], false);
        echo '</div>';
        echo '</li>';
    }
    echo '</ul>';

    if (!$comments) {
        html_commentform($id);
    }

}

function html_like($id, $subLike = false) {
    echo '<form method="post">';
    echo '<input type="hidden" name="like" value="'.hsc($id).'" />';
    if ($subLike) {
        echo '<input type="hidden" name="subLike" value="1" />';
    }
    echo '<input type="submit" class="like" value="Like" />';
    echo '</form>';
}

function html_unlike($id) {
    echo '<form method="post">';
    echo '<input type="hidden" name="unlike" value="' .hsc($id). '" />';
    echo '<input type="submit" class="like" value="Unlike" />';
    echo '</form>';
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