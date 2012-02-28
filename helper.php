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
    private $auth = null;
    private $user;

    public function __construct(){
        if($this->getConf('loginurl') == 'live'){
            $this->loginurl = 'https://login.salesforce.com/';
        }else{
            $this->loginurl = 'https://test.salesforce.com/';
        }
        $this->user = $_SERVER['REMOTE_USER'];
    }

    /**
     * Set a different user we're authenticating with
     */
    public function set_user($user){
        $this->user = $user;
        $this->auth = null;
    }

    /**
     * Loads the access info
     */
    public function load_auth(){
        if(!$this->user) return false;
        if(!is_null($this->auth)) return true;
        $tokenfile = getCacheName($this->user,'.chatter-auth');

        if(file_exists($tokenfile)){
            $this->auth = unserialize(io_readFile($tokenfile,false));
            return true;
        }else{
            return false;
        }
    }

    /**
     * Saves the access info
     */
    public function save_auth(){
        if(!$this->user) return false;
        if(is_null($this->auth)) return false;

        $tokenfile = getCacheName($this->user,'.chatter-auth');
        return io_saveFile($tokenfile, serialize($this->auth));
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
        $http->headers['Accept'] = 'application/json';
        $resp = $http->post($url,$data);
        if($resp === false) return false;
        $json = new JSON(JSON_LOOSE_TYPE);
        $resp = $json->decode($resp);

        $this->auth = $resp;
        return $this->save_auth();
    }

    /**
     * request a new auth key
     */
    public function oauth_refresh(){
        if(!$this->load_auth()) return false;
        $data = array(
            'grant_type'    => 'refresh_token',
            'refresh_token' => $this->auth['refresh_token'],
            'client_id'     => $this->getConf('consumer_key'),
            'client_secret' => $this->getConf('consumer_secret')
        );

        $url = $this->loginurl.'/services/oauth2/token?'.buildURLparams($data, '&');

        $http = new DokuHTTPClient();
        $http->headers['Accept'] = 'application/json';
        $resp = $http->get($url);
        if($resp === false) return false;
        $json = new JSON(JSON_LOOSE_TYPE);
        $resp = $json->decode($resp);

        $this->auth = $resp;
        return $this->save_auth();
    }

    /**
     * Execute an API call with the current author
     */
    public function apicall($method,$endpoint,$data=array()){
        if(!$this->load_auth()) return false;

        $json = new JSON(JSON_LOOSE_TYPE);
        $url   = $this->auth['instance_url'].'/services/data/v24.0'.$endpoint;

        $http = new DokuHTTPClient();
        $http->headers['Authorization'] = 'OAuth '.$token;
        $http->headers['Accept']        = 'application/json';
        $http->headers['Content-Type']  = 'application/json';
        $http->headers['X-PrettyPrint'] = '1';

        $http->debug = 1;

        $data = $json->encode($data);
        $resp = $http->sendRequest($url, $data, $method);
        $resp = $json->decode($resp);

        // session expired, request a new one and retry
        if($resp['errorCode'] == 'INVALID_SESSION_ID'){
            if($this->oauth_refresh()){
                return $this->apicall($method,$endpoint,$data);
            }else{
                return false;
            }
        }

        return $resp;
    }

}

// vim:ts=4:sw=4:et:
