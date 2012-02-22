<?php
/**
 * DokuWiki Plugin chatter (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN.'syntax.php';

class syntax_plugin_chatter extends DokuWiki_Syntax_Plugin {
    public function getType() {
        return 'substition';
    }

    public function getPType() {
        return 'block';
    }

    public function getSort() {
        return 333;
    }


    public function connectTo($mode) {
        $this->Lexer->addSpecialPattern('<CHATTER>',$mode,'plugin_chatter');
    }

    public function handle($match, $state, $pos, &$handler){
        $data = array();

        return $data;
    }

    public function render($mode, &$renderer, $data) {
        if($mode != 'xhtml') return false;


        $hlp = plugin_load('helper','chatter');
        $token = $hlp->load_accesstoken();


        $url = $hlp->getConf('instanceurl').'/services/data/v24.0'.'/chatter/feeds/record/me';
        $http = new DokuHTTPClient();
        $http->headers['Authorization'] = 'OAuth '.$token;
        $http->headers['Accept'] = 'application/json';
        $http->debug = 1;

        $body = $http->get($url);

        dbg($body);

        return true;
    }
}

// vim:ts=4:sw=4:et:
