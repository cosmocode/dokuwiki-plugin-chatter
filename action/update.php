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

class action_plugin_chatter_update extends DokuWiki_Action_Plugin {

    /**
     * @var helper_plugin_chatter
     */
    private $helper;

    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('INDEXER_TASKS_RUN', 'BEFORE', $this, 'updateUsers');
        $this->helper = plugin_load('helper', 'chatter');
    }

    function updateUsers(Doku_Event &$event, $params) {

        $cacheName = getCacheName('chatter plugin users', 'chatter');
        if (!is_file($cacheName)) {
            dbglog('no cache', 'chatter update');
            $this->update($cacheName);
            return;
        }

        if (filemtime($cacheName) < time() - 60*60*24) {
            dbglog('old cache', 'chatter update');
            $this->update($cacheName);
            return;
        }
        dbglog('done nothing', 'chatter update');
    }

    function update($cacheName) {
        dbglog('running update', 'chatter update');
        set_time_limit(0);

        $query = 'SELECT Id, Name From User';
        $query = array('q' => $query);
        $query = '/query?' . http_build_query($query);
        $result = $this->callUpdate($query);
        if ($result === false) {
            dbglog('no update - no result', 'chatter update');
            return;
        }

        io_saveFile($cacheName, serialize($result));
        dbglog('update success', 'chatter update');
    }

    function callUpdate($url) {
        $result = $this->helper->apicall('GET', $url);
        if ($result === false) {
            return false;
        }
        $return = array();
        foreach ($result['records'] as $user) {
            $return[] = array('label' => $user['Name'], 'value' => $user['Id']);
        }

        if (isset($result['nextRecordsUrl'])) {
            $parts = explode('/', $result['nextRecordsUrl']);
            $call = '/query/' . $parts[count($parts) -1];
            $nextResults = $this->callUpdate($call);
            if ($nextResults === false) {
                return false;
            }
            $return = array_merge($return, $nextResults);
        }

        return $return;
    }


}