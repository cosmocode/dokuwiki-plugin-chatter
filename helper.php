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
    public  $authurl;

    public function __construct(){
        if($this->getConf('loginurl') == 'live'){
            $this->loginurl = 'https://login.salesforce.com/';
        }else{
            $this->loginurl = 'https://test.salesforce.com/';
        }
        $this->user = $_SERVER['REMOTE_USER'];
        $this->authurl = DOKU_URL.'lib/plugins/chatter/auth.php';
    }

    public function tpl_frame(){
        global $ID;
        if(!$_SERVER['REMOTE_USER']) return;
        echo '<iframe src="'.DOKU_BASE.'lib/plugins/chatter/frame.php?id='.$ID.'" id="chatter__frame"></iframe>';
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
            'redirect_uri'  => $this->authurl,
            'display'       => 'popup',
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
            'redirect_uri'  => $this->authurl,
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
        $resp = $http->post($url,array());
        if($resp === false) return false;
        $json = new JSON(JSON_LOOSE_TYPE);
        $resp = $json->decode($resp);

        $this->auth = $resp;
        return $this->save_auth();
    }

    /**
     * Execute an API call with the current author
     */
    public function apicall($method,$endpoint,$data=array(),$usejson=true){
        if(!$this->load_auth()) return false;

        $json = new JSON(JSON_LOOSE_TYPE);
        $url   = $this->auth['instance_url'].'/services/data/v24.0'.$endpoint;

        $http = new DokuHTTPClient();
        $http->headers['Authorization'] = 'OAuth '.$this->auth['access_token'];
        $http->headers['Accept']        = 'application/json';
        $http->headers['X-PrettyPrint'] = '1';

#        $http->debug = 1;

        if($data){
            if($usejson){
                $data = $json->encode($data);
                $http->headers['Content-Type']  = 'application/json';
            }
            // else default to standard POST encoding
        }

        $http->sendRequest($url, $data, $method);
        if(!$http->resp_body) return false;
        $resp = $json->decode($http->resp_body);

        // session expired, request a new one and retry
        if($resp[0]['errorCode'] == 'INVALID_SESSION_ID'){
            if($this->oauth_refresh()){
                return $this->apicall($method,$endpoint,$data);
            }else{
                return false;
            }
        }

        if($http->status < 200 || $http->status > 399) return false;

        return $resp;
    }

    /**
     * Get the Chatter ID for a given wiki ID
     *
     * Creates the Chatter ID if not exists, yet
     */
    public function id2chatter($id){
        $inst = $this->getConf('consumer_key'); //unique key per setup
        $key  = p_get_metadata($id,"plugin chatter $inst");
        if($key) return $key;

        // no key yet, try to create a new SF object
        $resp = $this->apicall('POST','/sobjects/WikiPage__c',array('name'=>$id,'url__c'=>wl($id,'',true,'&')));
        if(!$resp) return false;

        $key = $resp['id'];
        p_set_metadata($id,array('plugin' => array('chatter' => array($inst => $key))));

        return $key;
    }

    /**
     * Set and get the follow state for a given Chatter ID
     *
     * Use a subscription ID in $set to unfollow by deleting this subscription
     * use a '1' to subscribe to a ressource. Keep null to only query the follow
     * state
     *
     * @param string $cid Chatter ID
     * @param string $set Subscription ID
     * @returns mixed false or a subscription ID
     */
    public function follow($cid, $set=null){
        // set state
        if(is_string($set) && strlen($set) > 1){
            // unsubscribe
            $this->apicall('DELETE','/chatter/subscriptions/'.$set);
        }elseif(!is_null($set)){
            // subscribe
            $this->apicall('POST','/chatter/users/me/following',array('subjectId' => $cid));
        }

        // read state
        $resp = $this->apicall('GET','/chatter/records/'.$cid.'/followers');
        if(isset($resp['mySubscription'])){
            return $resp['mySubscription']['id'];
        }

        return false;
    }

    /**
     * Attach a comment to a Chatter object
     */
    public function addcomment($cid, $text){
        $this->apicall('POST','/chatter/feeds/record/'.$cid.'/feed-items/',array('text' => $text),false);
    }
}

// vim:ts=4:sw=4:et:
