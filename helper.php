<?php
/**
 * DokuWiki Plugin chatter (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Andreas Gohr <gohr@cosmocode.de>
 * @link http://developer.force.com/cookbook/recipe/interact-with-the-forcecom-rest-api-from-php
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();


class helper_plugin_chatter extends DokuWiki_Plugin {
    private $loginurl;

    public function __construct(){
        if($this->getConf['loginurl'] == 'live'){
            $this->loginurl = 'https://test.salesforce.com/';
        }else{
            $this->loginurl = 'https://login.salesforce.com/';
        }
    }

    /**
     * Initialize the OAuth process
     *
     * by redirecting the user to the login site
     * @link http://bit.ly/y7WOmy
     */
    public function oauth_start(){
        $data = array(
            'response_type' => 'code',
            'client_id'     => $this->getConf('consumer_key'),
            'redirect_uri'  => 'https://localhost/dw-2011-11-07/lib/plugins/chatter/try.php',
            'display'       => 'page', # popup, touch
        );

        $url = $this->loginurl.'/services/oauth2/authorize?'.buildURLparams($data, '&');
        send_redirect($url);
    }

    /**
     * Finish the the OAuth process
     *
     * by turning the given code into a permanent authorization code
     *
     * @param string $code the code requested by oauth_start()
     */
    public function oauth_finish($code){
        $data = array(
            'code'       => $code,
            'grant_type' => 'authorization_code',
            'client_id'     => $this->getConf('consumer_key'),
            'client_secret' => $this->getConf('consumer_secret'),
            'redirect_uri'  => 'https://localhost/dw-2011-11-07/lib/plugins/chatter/try.php', #why??
        );

        $url = $this->loginurl.'/services/oauth2/token';

        $http = new DokuHTTPClient();
        $http->debug = 1;

        $resp = $http->post($url,$data);

        dbg($resp);
    }

}

// vim:ts=4:sw=4:et:
