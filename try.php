<?php

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
define('DOKU_DISABLE_GZIP_OUTPUT', 1);
require_once(DOKU_INC.'inc/init.php');
session_write_close();

$plugin = plugin_load('helper','chatter');

if($_GET['code']){
    $token = $plugin->oauth_finish($_GET['code']);

    if($token){
        $plugin->save_accesstoken($token);
        echo '<h1>Authentication successful</h1>';
    }
}else{
    $plugin->oauth_start();
}