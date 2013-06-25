<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/framework/flex.php';
class PayPalExpressCheckout
    {
        public function __construct($logDbName = "", $useApiDb = true)
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
        $this->server_address = $this->server_protocol . '://' . $this->server_location . '/ws/paypalexpress.php';
        $this->log_db = $logDbName;
        $this->use_api_db = $useApiDb;
        if ($paypal_developer_mode == true)
            {$this->developer_mode = "true";}
        if ($paypal_developer_mode == false)
            {$this->developer_mode  = "false";}
        $this->developer_environment = $paypal_developer_environment;
        $this->username = $paypal_api_username;
        $this->password = $paypal_api_password;
        $this->signature = $paypal_api_signature;
        $this->return_uri = $paypal_expressCO_return;
        $this->cancel_uri = $paypal_expressCO_cancel;
        }
    private $server_address;
    private $api_key;
    private $client_address;
    private $call_name;
    private $parameters;
    private $log_db;
    private $use_api_db;
    private $developer_mode;
    private $developer_environment;
    private $username;
    private $password;
    private $signature; 
    private $return_uri;
    private $cancel_uri;
    private function callService()
        {
        $ws = new FlexAPIClient($this->server_address, $this->call_name, $this->parameters);
        return $ws->callService();
        }
    public function beginPurchase($paymentArray, $returnUri = "", $cancelUri="")
        {
        if ($returnUri == "")
            {
            $returnUri = $this->return_uri;
            }
        if ($cancelUri == "")
            {
            $cancelUri = $this->cancel_uri;
            }
        $this->call_name = "beginPurchase";
        $this->parameters = array($this->api_key, $this->client_address, $this->developer_mode, $this->username, $this->password, $this->signature, $paymentArray, $returnUri, $cancelUri, $this->log_db, $this->use_api_db, $this->developer_environment );
        $redirect =  $this->callService();
        $decodedUri = html_entity_decode($redirect);
        echo $decodedUri;
        header('Location: ' . $decodedUri );
        }
    public function completePurchase($getData)
        {
        $this->call_name = "completePurchase";
        $this->parameters = array($this->api_key, $this->client_address, $this->developer_mode, $this->username, $this->password, $this->signature, $getData, $this->log_db, $this->use_api_db, $this->developer_environment );
        $response =  $this->callService();
        return $response;
        }
    public function beginSubscription($paymentArray, $returnUri = "", $cancelUri="")
        {
        if ($returnUri == "")
            {
            $returnUri = $this->return_uri;
            }
        if ($cancelUri == "")
            {
            $cancelUri = $this->cancel_uri;
            }
        $this->call_name = "beginSubscription";
        $this->parameters = array($this->api_key, $this->client_address, $this->developer_mode, $this->username, $this->password, $this->signature, $paymentArray, $returnUri, $cancelUri, $this->log_db, $this->use_api_db, $this->developer_environment );
        $redirect =  $this->callService();
        $decodedUri = html_entity_decode($redirect);
        echo $decodedUri;
        header('Location: ' . $decodedUri );
        }
    public function completeSubscription($getData)
        {
        $this->call_name = "completeSubscription";
        $this->parameters = array($this->api_key, $this->client_address, $this->developer_mode, $this->username, $this->password, $this->signature, $getData, $this->log_db, $this->use_api_db, $this->developer_environment );
        $response =  $this->callService();
        return $response;
        }
    public function issueRefund($refundArray)
        {
        $this->call_name = "issueRefund";
        $this->parameters = array($this->api_key, $this->client_address, $this->developer_mode, $this->username, $this->password, $this->signature, $refundArray, $this->log_db, $this->use_api_db, $this->developer_environment );
        $response =  $this->callService();
        return $response;
        }
    public function cancelSubscription($profileId, $note)
        {
        $this->call_name = "issueRefund";
        $this->parameters = array($this->api_key, $this->client_address, $this->developer_mode, $this->username, $this->password, $this->signature, $this->log_db, $this->use_api_db, $this->developer_environment, $profileId, $note );
        $response =  $this->callService();
        return $response;
        }
    public function subscriptionStatus($profileId)
        {
        $this->call_name = "subscriptionStatus";
        $this->parameters = array($this->api_key, $this->client_address, $this->developer_mode, $this->username, $this->password, $this->signature,$profileId ,$this->log_db, $this->use_api_db, $this->developer_environment);
        $response =  $this->callService();
        return $response;
        }
    }
?>