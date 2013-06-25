<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/log/error.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/security/crypto.php';
class PayPalExpressCheckout
    {
    public function __construct($apikey, $useSandbox, $username, $password, $signature, $logDb = "", $logDbIsHosted = true, $sandboxEnvironment = "sandbox") 
        {
        if ($useSandbox == true)
            {
            $this->api_endpoint = "https://api-3t.$sandboxEnvironment.paypal.com/nvp";
            $this->redirect_endpoint = "https://www.sandbox.paypal.com/cgi-bin/webscr?";
            }
        if ($useSandbox == false)
            {
            $this->api_endpoint = "https://api-3t.paypal.com/nvp";
            $this->redirect_endpoint = "https://www.paypal.com/cgi-bin/webscr?";
            }
        $this->username = $username;
        $this->password = $password;
        $this->signature = $signature;
        $this->version = "72.0";
        
        $this->log_db = $logDb;
        $this->log_db_is_hosted = $logDbIsHosted;
        $this->framework_api_key = $apikey;
        }
    private $username;
    private $password;
    private $signature;
    private $api_endpoint;
    private $redirect_endpoint;
    private $version;
    private $return_url;
    private $cancel_url;
    private $log_db;
    private $log_db_is_hosted;
    private $method_name;
    private $nvpString;
    private $token;
    private $framework_api_key;
    private function doExpress($nvpString)
        {
        $methodName = urlencode($this->method_name);
        $API_UserName = urlencode($this->username);
        $API_Password = urlencode($this->password);
        $API_Signature = urlencode($this->signature);
        $API_Endpoint = $this->api_endpoint;
        if ($this->method_name == "ipn_notification")
            {
            $API_Endpoint = $this->redirect_endpoint;
            }
        $version = urlencode($this->version);
        $Cancel_URL = urlencode($this->cancel_url);
        $Return_URL = urlencode($this->return_url);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        $nvpreq = "METHOD=$methodName&VERSION=$version&PWD=$API_Password&USER=$API_UserName&SIGNATURE=$API_Signature&RETURNURL=$Return_URL&CANCELURL=$Cancel_URL$nvpString";
        if ($this->method_name == "ipn_notification")
            {
            $nvpreq = "cmd=_notify-validate&$nvpString";
            }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);
        $httpResponse = curl_exec($ch);
        if(!$httpResponse)
            {
            exit('$methodName_ failed: '.curl_error($ch).'('.curl_errno($ch).')');
            }
        $httpResponseAr = explode("&", $httpResponse);
        $httpParsedResponseAr = array();
        foreach ($httpResponseAr as $i => $value)
            {
                $tmpAr = explode("=", $value);
                if(sizeof($tmpAr) > 1)
                    {
                    $httpParsedResponseAr[$tmpAr[0]] = $tmpAr[1];
                    }
            }
        if ($this->method_name != "ipn_notification")
            {
            if((0 == sizeof($httpParsedResponseAr)) || !array_key_exists('ACK', $httpParsedResponseAr))
                {
                exit("Invalid HTTP Response for POST request($nvpreq) to $API_Endpoint.");
                }
            }
        $decodedHttpParsedResponsAr = array();
        $columns = array();
        $data = array();
        foreach($httpParsedResponseAr AS $key=>$value)
            {
            $decodedHttpParsedResponsAr[$key] = urldecode($value);
            $columns[] = urldecode($key);
            $data[] = urldecode($value);
            }
        require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/database/helper.php';
        $db = new DatabaseHelper($this->framework_api_key, $this->log_db, $this->log_db_is_hosted);
        $table = "log_paypal_express_response";
            if ($this->method_name != "ipn_notification")
            {
            $db->Insert($table, $columns, $data);
            }
        return $decodedHttpParsedResponsAr;
        }
    private function buildBeginPurchaseNvpString($nvpArray)
        {
        $nvpString = "&PAYMENTREQUEST_0_AMT=" . $nvpArray['amount'];
        $nvpString .= "&PAYMENTREQUEST_0_ITEMAMT=" . $nvpArray['amount'];
        $nvpString .= "&PAYMENTREQUEST_0_CURRENCYCODE=" . $nvpArray['currency'];//
        $nvpString .=  "&PAYMENTREQUEST_0_DESC=" . $nvpArray['description'];
        $nvpString .= "&PAYMENTREQUEST_0_PAYMENTACTION=Sale";
        $nvpString .= "&ALLOWNOTE=1";
        return $nvpString;
        }
    private function buildCompletePurchaseNVPString($paymentDetailArray)
        {
        $token = urldecode($paymentDetailArray['TOKEN']);
        $payer_id = urldecode($paymentDetailArray['PAYERID']);
        $amount = urldecode($paymentDetailArray['AMT']);
        $nvpString = "&PAYMENTREQUEST_0_PAYMENTACTION=Sale&PAYERID=$payer_id&TOKEN=$token&PAYMENTREQUEST_0_AMT=$amount";
        return $nvpString;
        }
    private function buildRefundNvpString($nvpArray)
        {
        $nvpString = "&TRANSACTIONID=" . $nvpArray['transaction_id'];
        $nvpString .= "&REFUNDTYPE=" . $nvpArray['refund_type'];
        $nvpString .= "&PAYMENTREQUEST_0_CURRENCYCODE=" . $nvpArray['currency'];
        $nvpString .=  "&AMT=" . $nvpArray['amount'];
        $nvpString .= "&NOTE=" . $nvpArray['note'];
        return $nvpString;
        }
    private function buildBeginSubscriptionNvpString($nvpArray)
        {
        $nvpString = "&PAYMENTREQUEST_0_AMT=" . $nvpArray['amount'];
        $nvpString .= "&&PAYMENTREQUEST_0_ITEMAMT=" . $nvpArray['amount'];
        $nvpString .= "&PAYMENTREQUEST_0_CURRENCYCODE=" . $nvpArray['currency'];//
        $nvpString .= "&L_BILLINGAGREEMENTDESCRIPTION0=" . $nvpArray['description'];
        $nvpString .= "&PAYMENTREQUEST_0_PAYMENTACTION=Sale&L_BILLINGTYPE0=RecurringPayments";
        $nvpString .= "&ALLOWNOTE=1";
        $subscriptionInfo = "&PROFILESTARTDATE=" . $nvpArray['start_date'];
        $subscriptionInfo .= "&DESC=" . $nvpArray['description'];
        $subscriptionInfo .= "&BILLINGPERIOD=" . $nvpArray['billing_period'];
        $subscriptionInfo .= "&BILLINGFREQUENCY=" . $nvpArray['billing_frequency'];
        $subscriptionInfo .= "&L_PAYMENTREQUEST_0_ITEMCATEGORY0=" . $nvpArray['item_category'];
        $subscriptionInfo .= "&L_PAYMENTREQUEST_0_NAME0=" . $nvpArray['description'];
        $subscriptionInfo .= "&L_PAYMENTREQUEST_0_QTY0=1";
        $c = new Cryptography();
        $this->return_url .= "?si=" . $c->urlsafe_b64encode($subscriptionInfo);
        return $nvpString;
        }
    private function buildCompleteSubscriptionNvpString($nvpArray)
        {
        $token = urldecode($nvpArray['TOKEN']);
        $email = urldecode($nvpArray['EMAIL']);
        $currencycode = urldecode($nvpArray['CURRENCYCODE']);
        $amount = urldecode($nvpArray['AMT']);
        $nvpString =  "&TOKEN=$token&AMT=$amount&CURRENCYCODE=$currencycode&EMAIL=$email&L_PAYMENTREQUEST_0_AMT0=$amount";
        return $nvpString;        
        }
    private function getExpressCheckoutDetails($token)
        {
        $this->method_name = "GetExpressCheckoutDetails";
        $responseArray = $this->doExpress("&token=$token");
        return $responseArray;
        }    
    private function completePayment($nvpString)
        {
        $this->method_name = "DoExpressCheckoutPayment";
        $responseArray = $this->doExpress($nvpString);
        return $responseArray;
        }
    public function beginPurchase($paymentArray, $returnUri, $cancelUri)
        {
        $this->method_name = "SetExpressCheckout";
        $this->return_url = $returnUri;
        $this->cancel_url = $cancelUri;
        $nvpString = $this->buildBeginPurchaseNvpString($paymentArray);
        $responseArray = $this->doExpress($nvpString);
        $token = urldecode($responseArray['TOKEN']);
        $redirectUri = $this->redirect_endpoint . "cmd=_express-checkout&token=".$token;
        return htmlentities($redirectUri);
        }    
    public function issueRefund($refundArray)
        {
        $this->method_name = "RefundTransaction";
        $nvpString = $this->buildRefundNvpString($refundArray);
        $responseArray = $this->doExpress($nvpString);
        return $responseArray;
        }  
    public function completePurchase($getData)
        {
        $token = $getData['token'];
        $buyerDetails = $this->getExpressCheckoutDetails($token);
        $nvpString = $this->buildCompletePurchaseNVPString($buyerDetails);
        $paymentDetails = $this->completePayment($nvpString);
        $responseArray = array("buyer_details" => $buyerDetails, "payment_details" => $paymentDetails);
        return $responseArray;        
        }
    public function beginSubscription($paymentArray, $returnUri, $cancelUri)
        {
        $this->method_name = "SetExpressCheckout";
        $this->return_url = $returnUri;
        $this->cancel_url = $cancelUri;
        $nvpString = $this->buildBeginSubscriptionNvpString($paymentArray);
        $responseArray = $this->doExpress($nvpString);
        $token = urldecode($responseArray['TOKEN']);
        $redirectUri = $this->redirect_endpoint . "cmd=_express-checkout&token=".$token;
        return htmlentities($redirectUri);
        }
    public function completeSubscription($getData)///continue here, parse get data and complete subscription
        {
        $token = $getData['token'];
        $buyerDetails = $this->getExpressCheckoutDetails($token);
        $this->method_name = "CreateRecurringPaymentsProfile";       
        $nvpString = $this->buildCompleteSubscriptionNvpString($buyerDetails);
        $c = new Cryptography();
        $subscriptionInfo = $c->urlsafe_b64decode($getData['si']);
        $nvpString .= $subscriptionInfo;
        $paymentDetails = $this->doExpress($nvpString);
        $responseArray = array("buyer_details" => $buyerDetails, "payment_details" => $paymentDetails);
        return $responseArray;  
        }
    public function manageProfile($profileId, $action, $note)
        {
        $this->method_name = "ManageRecurringPaymentsProfileStatus";
        $nvpString = "&PROFILEID=$profileId&ACTION=$action&NOTE=$note";
        $response = $this->doExpress($nvpString);
        return $response;
        }
    public function getProfileStatus($profileId)
        {
        $this->method_name = "GetRecurringPaymentsProfileDetails";
        $nvpString = "&PROFILEID=$profileId";
        $response = $this->doExpress($nvpString);
        return $response;
        }
    public function verifyIPN ($queryString, $getData)
        {
        $this->method_name = "ipn_notification";
        $response = $this->doExpress($queryString);
        $getData['status'] = "verified";//$response[0];
        $this->logIPN($getData);
        return $response;//[0];
        }
    private function logIPN($getData)
        {
        $db = new DatabaseHelper($this->framework_api_key, $this->log_db, $this->log_db_is_hosted);
        $table = "log_paypal_ipn";
        $columns = array();
        $data = array();
        foreach($getData AS $key=>$value)
            {
            $columns[] = $key;
            $data[] = $value;
            }
        echo "<pre>";
        print_r($columns);
        print_r($data);
        $db->Insert($table, $columns, $data);
        }
    }
//$p = new PayPalExpressCheckout("", true, "seller_1326400516_biz_api1.companycheckout.com", "1326400543", "Ap9Kuy6eXW0G3FJ.5l-bFawwwOVuAnOoVX6SLXVztMtRsh7kA2M7Dqsi");
//$getData = $_GET;
////if(empty ($_GET))
////{
////    echo "get is empty";
////}
//
//$queryString = $_SERVER['QUERY_STRING'];
//echo "<pre>";
//print_r($_GET);
//echo $queryString;
//require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/database/helper.php';
//$db = new DatabaseHelper("", "", true);
//$table = "log_paypal_ipn_q";
//$columns = array("qs");
//$data = array($queryString);
//$db->Insert($table, $columns, $data);
//$p->verifyIPN($queryString, $getData);
?>