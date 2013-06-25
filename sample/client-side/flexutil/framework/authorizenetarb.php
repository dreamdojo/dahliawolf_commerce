<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/framework/flex.php';
class AuthorizeNetARB
    {
    public function __construct($userDomainName, $logDbName = "", $useApiDb = true)
        {
        include($_SERVER['DOCUMENT_ROOT'] . '/flexutil/config.php');
        $this->api_key = $flexutil_api_key;
        if ($flexutil_developer_mode == false)
            {
            $this->server_location = "api.companycheckout.com";            
            $this->client_address = $_SERVER['SERVER_NAME'];
            }
        else
            {
            $this->server_location = "dev.companycheckout.com";
            $this->client_address = empty($_SERVER['SERVER_ADDR']) ? $_SERVER['LOCAL_ADDR']  : $_SERVER['SERVER_ADDR'];
            }
        if ($flexutil_use_ssl == false)
            {
            $this->server_protocol = "http";
            }
        else
            {
            $this->server_protocol = "https";
            }
        $this->server_address = $this->server_protocol . '://' . $this->server_location . '/ws/authorizenetarb.php';        
        $this->user_domain_name = $userDomainName;
        $this->log_db_name = $logDbName;
        $this->log_to_api_db = $useApiDb;        
        $this->authnet_api_id = $authnet_api_id;
        $this->authnet_trans_key = $authnet_trans_key;
        if ($authnet_developer_mode == true)
            {
            $this->use_authnet_sandbox = true;
            }
        if ($authnet_developer_mode == false)
            {
            $this->use_authnet_sandbox = false;
            }
        }
    private $server_protocol;
    private $server_location;
    private $server_address;
    private $call_name;
    private $api_key;    
    private $client_address;
    private $parameters;    
    private $user_domain_name;
    private $log_db_name;
    private $log_to_api_db;    
    private $authnet_api_id;
    private $authnet_trans_key;
    private $use_authnet_sandbox;    
    private function callService()
        {
        $ws = new FlexAPIClient($this->server_address, $this->call_name, $this->parameters);
        return $ws->callService();
        }    
    public function createSubscription($subscriptionArray)
        {
        $this->call_name ='createSubscription';
        $this->parameters = array($this->api_key, $this->client_address, $this->authnet_api_id, $this->authnet_trans_key, $this->use_authnet_sandbox, $this->user_domain_name , $this->log_db_name, $this->log_to_api_db, $subscriptionArray);
        return $this->callService();
        }
    public function updateSubscription($subscriptionId, $subscriptionArray)
        {
        $this->call_name ='updateSubscription';
        $this->parameters = array($this->api_key, $this->client_address, $this->authnet_api_id, $this->authnet_trans_key, $this->use_authnet_sandbox, $this->user_domain_name , $this->log_db_name, $this->log_to_api_db, $subscriptionId, $subscriptionArray);
        return $this->callService();
        }
    public function subscriptionStatus($subscriptionId, $referenceId)
        {
        $this->call_name ='subscriptionStatus';
        $this->parameters = array($this->api_key, $this->client_address, $this->authnet_api_id, $this->authnet_trans_key, $this->use_authnet_sandbox, $this->user_domain_name , $this->log_db_name, $this->log_to_api_db, $subscriptionId, $referenceId);
        return $this->callService();
        }
    public function cancelSubscription($subscriptionId, $referenceId)
        {
        $this->call_name ='cancelSubscription';
        $this->parameters = array($this->api_key, $this->client_address, $this->authnet_api_id, $this->authnet_trans_key, $this->use_authnet_sandbox, $this->user_domain_name , $this->log_db_name, $this->log_to_api_db, $subscriptionId, $referenceId);
        return $this->callService();
        }
    }
?>