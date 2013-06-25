<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/database/helper.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/website/information.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/log/error.php';
class AuthNetAIM
    {
    public function __construct($apikey, $authnetApiId, $authnetTransKey, $useSandbox,  $userDomainName, $logDbName, $useApiDb)
        {
        $this->api_id = $authnetApiId;
        $this->transaction_key = $authnetTransKey;
        $this->user_domain_name = $userDomainName;
        $this->log_db_name = $logDbName;
        $this->use_api_db = $useApiDb;
        $this->use_sandbox = $useSandbox;
        $this->framework_api_key = $apikey;
        }
    private $api_id;
    private $transaction_key;
    private $payment_array;
    private $payment_type;
    private $transaction_id;
    private $amount;
    private $cc_number;
    private $user_domain_name;
    private $log_db_name;
    private $use_api_db;
    private $use_sandbox;
    private $framework_api_key;
    private function logResponse($result)
        {
        $table = "log_authnet_aim_response";
        $columns = array();
        $data = array();
        $responseCode;
        $returnData = array();
        foreach ($result as $key => $value)
            {
            $columns[] = $key;
            $data[] = $value;
            if ($key == "response_code")
                {
                $responseCode = $value;
                $responseText;
                switch ($responseCode)
                    {
                    case 1:
                        $responseText = "approved";
                        break;
                    case 2:
                        $responseText = "declined";
                        break;
                    case 3:
                        $responseText = "error";
                        break;
                    case 4:
                        $responseText = "review";
                        break;
                    }
                $returnData["response_text"] = $responseText;
                }
            if ($key == "response_reason_text" || $key == "authorization_code" || $key == "transaction_id" )
                {
                $returnData[$key] = $value;
                }
            }
        if ($this->use_api_db == true)
            {
            $website = new WebsiteInformation($this->user_domain_name);
            $columns[] = "website_id";
            $data[] = $website->getWebsiteId();
            }
        $db = new DatabaseHelper($this->framework_api_key, $this->log_db_name, $this->use_api_db);
        $db->Insert( $table, $columns, $data);
        return $returnData;
        }
    private function doTransaction($transactionType)
        {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/authorizenet/AuthorizeNet.php';
        $a = new AuthorizeNetAIM($this->api_id,$this->transaction_key);
        $a->setSandbox($this->use_sandbox);
        if ($transactionType == "authorizeCapture" || $transactionType == "authorizeOnly")
            {
            foreach($this->payment_array AS $name=>$value)
                {
                $a->__set($name, $value);           
                }
            if ($this->payment_type == 'echeck' || $this->payment_type ==  'check')
                {
                $this->payment_array['method'] = 'echeck';
                $this->payment_array['echeck_type'] = 'WEB';
                }
            }
        $result;
        switch ($transactionType)
            {
            case "authorizeCapture":
                $result = $a->authorizeAndCapture();
                break;
            case "authorizeOnly":
                $result = $a->authorizeOnly();
                break;
            case "capturePriorAuthorization":
                $result = $a->priorAuthCapture($this->transaction_id, $this->amount);
                break;
            case "voidTransaction":
                $result = $a->void($this->transaction_id);
                break;
            case "issueRefund":
                $result = $a->credit($this->transaction_id, $this->amount, $this->cc_number);
                break;            
            }
        $returnData = $this->logResponse($result);
        return $returnData;
        }
    public function authorizeCapture($paymentArray, $paymentType = "credit card")
        {
        $this->payment_array = $paymentArray;
        $this->payment_type = $paymentType;
        $result = $this->doTransaction("authorizeCapture");
        return $result;
        }
    public function authorizeOnly($paymentArray, $paymentType = "credit card")
        {
        $this->payment_array = $paymentArray;
        $this->payment_type = $paymentType;
        $result = $this->doTransaction("authorizeOnly");
        return $result;
        }
    public function capturePriorAuthorization($transactionId, $amount = null)
        {
        $this->transaction_id = $transactionId;
        $this->amount = $amount;
        $result = $this->doTransaction("capturePriorAuthorization");
        return $result;
        }
    public function voidTransaction($transactionId)
        {
        $this->transaction_id = $transactionId;
        $result = $this->doTransaction("voidTransaction");
        return $result;
        }
    public function issueRefund($transactionId, $amount, $ccNumber)
        {
        $this->transaction_id = $transactionId;
        $this->amount = $amount;
        $this->cc_number  = $ccNumber;
        $result = $this->doTransaction("issueRefund");
        return $result;
        }
    }    
?>