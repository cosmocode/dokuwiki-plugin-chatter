<?php

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
define('DOKU_DISABLE_GZIP_OUTPUT', 1);
require_once(DOKU_INC.'inc/init.php');
session_write_close();

$plugin = plugin_load('helper','chatter');

echo '<html>';
echo '<head>';
echo '<title>Chatter</title>';
tpl_metaheaders();
echo '</head>';
echo '<body id="chatter__auth">';

if($_GET['code']){
    if($plugin->oauth_finish($_GET['code'])){
        echo '<h1>Authentication successful</h1>';
        echo '<script type="text/javascript" lang="JavaScript">';
        echo 'if(window.opener){
                window.opener.location.reload();
                window.close();
              }';
        echo '</script>';
    }else{
        echo '<h1>Something went wrong.</h1>';
        echo '<p>Why not <a href="auth.php">try again?</a></p>';
    }
}else{
    $plugin->oauth_start();
}

echo '</body>';
echo '</auth>';
