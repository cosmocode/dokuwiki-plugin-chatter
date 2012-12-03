<?php

/**
 * DokuWiki Plugin sfauth (Action Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Dominik Eckelmann, Andreas Gohr
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

require_once DOKU_PLUGIN . 'action.php';

class action_plugin_chatter_ajax extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_chatter
     */
    private $helper;

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'autocomplete');
        $this->helper = plugin_load('helper', 'chatter');
    }

    function autocomplete(Doku_Event &$event, $params) {
        if ($event->data !== 'chatter_autocomplete') {
            return;
        }
        $event->preventDefault();
        $event->stopPropagation();

        if (!isset($_GET['term'])) {
            echo '[]';
            return;
        }

        $_GET['term'] = ltrim($_GET['term'], '@[');
        $_GET['term'] = rtrim($_GET['term'], ']');

        $userData = $this->loadUsers();
        $users = array_filter($userData, array($this, 'containsTerm'));

        echo json_encode(array_values($users));
    }

    function containsTerm($name) {
        return stripos($name['label'], $_GET['term']) !== false;
    }

    function loadUsers() {
        $cacheName = getCacheName('chatter plugin users', 'chatter');
        $users = unserialize(io_readFile($cacheName, false));
        if ($users === false) {
            return array();
        }
        return $users;
    }
}
