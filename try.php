<?php

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../../').'/');
define('DOKU_DISABLE_GZIP_OUTPUT', 1);
require_once(DOKU_INC.'inc/init.php');
session_write_close();

$plugin = plugin_load('helper','chatter');

if($_GET['code']){
    if($plugin->oauth_finish($_GET['code'])){
        echo '<h2>Authentication successful</h1>';

    }else{
        echo 'barf';
    }
}else{
    $plugin->oauth_start();
}
