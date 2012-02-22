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
        if($this->getConf('loginurl') == 'live'){
            $this->loginurl = 'https://login.salesforce.com/';
        }else{
            $this->loginurl = 'https://test.salesforce.com/';
        }
    }


    /**
     * Loads the access token for a user
     *
     * If no user is given the currently logged in one is used
     */
    public function load_accesstoken($user=null){
        if(is_null($user)) $user = $_SERVER['REMOTE_USER'];
        if(!$user) return false;

        $tokenfile = getCacheName($user,'.chatter-auth');
        if(file_exists($tokenfile)){
            return trim(io_readFile($tokenfile));
        }else{
            return false;
        }
    }

    /**
     * Saves the access token for a user
     *
     * If no user is given the currently logged in one is used
     */
    public function save_accesstoken($token, $user=null){
        if(is_null($user)) $user = $_SERVER['REMOTE_USER'];
        if(!$user) return false;

        $tokenfile = getCacheName($user,'.chatter-auth');
        io_saveFile($tokenfile,$token);
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
     * by turning the given code into a permanent access token
     *
     * @param string $code the code requested by oauth_start()
     * @return the access token
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
        $resp = $http->post($url,$data);
        if($resp === false) return false;
        $json = new JSON(JSON_LOOSE_TYPE);
        $resp = $json->decode($resp);

        return $resp['access_token'];
    }

}

// vim:ts=4:sw=4:et:
