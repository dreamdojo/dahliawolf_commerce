<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/log/error.php';
class AuthNetARB
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
    private $reference_id;
    private $subscription_array;
    private $authnet_subscription;
    private $subscription_id;
    private $response_array;
    private $user_domain_name;
    private $log_db_name;
    private $use_api_db;
    private $use_sandbox;
    private $framework_api_key;
    private function manageSubscription($action)
        {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/authorizenet/AuthorizeNet.php';
        $a = new AuthorizeNetARB($this->api_id, $this->transaction_key);
        $a->setSandbox($this->use_sandbox);
        $a->setRefId($this->reference_id);
        $responseData;        
        switch ($action)
            {
            case "create":
                $responseData = $a->createSubscription($this->authnet_subscription);
                break;
            case "update":
                $responseData = $a->updateSubscription($this->subscription_id, $this->authnet_subscription);
                break;
            case "cancel":
                $responseData = $a->cancelSubscription($this->subscription_id);
                 break;
            case "status":
                $responseData = $a->getSubscriptionStatus($this->subscription_id);
            }
        $responseArray = array();
        $responseArray['ref_id'] = $responseData->getRefID();
        $responseArray['result_code'] = $responseData->getResultCode();
        $responseArray['error_message'] = $responseData->getErrorMessage();
        $responseArray['message_code'] = $responseData->getMessageCode();
        $responseArray['message_text'] = $responseData->getMessageText();
        $responseArray['subscription_id'] = $responseData->getSubscriptionId();
        $responseArray['subscription_status'] = $responseData->getSubscriptionStatus();
        $this->response_array = $responseArray;
        $this->logResponse();
        }
    private function convertArrayToSubscription()
        {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/authorizenet/AuthorizeNet.php';
        $s = new AuthorizeNet_Subscription();
        if (isset ($this->subscription_array['refId']))
            {
            $this->reference_id = $this->subscription_array['refId'];
            }
        if (isset($this->subscription_array['name']))
            {
            $s->name = $this->subscription_array['name'];
            }
        if (isset($this->subscription_array['intervalLength']))
            {
            $s->intervalLength = $this->subscription_array['intervalLength'];
            }
        if (isset($this->subscription_array['intervalUnit']))
            {
            $s->intervalUnit = $this->subscription_array['intervalUnit'];
            }
        if (isset($this->subscription_array['startDate']))
            {
            $s->startDate = $this->subscription_array['startDate'];
            }
        if (isset($this->subscription_array['totalOccurrences']))
            {
            $s->totalOccurrences = $this->subscription_array['totalOccurrences'];
            }
        if (isset($this->subscription_array['trialOccurrences']))
            {
            $s->trialOccurrences = $this->subscription_array['trialOccurrences'];
            }
        if (isset($this->subscription_array['amount']))
            {
            $s->amount = $this->subscription_array['amount'];
            }
        if (isset($this->subscription_array['trialAmount']))
            {
            $s->trialAmount = $this->subscription_array['trialAmount'];
            }
        if (isset($this->subscription_array['creditCardCardNumber']))
            {
            $s->creditCardCardNumber = $this->subscription_array['creditCardCardNumber'];
            }
        if (isset($this->subscription_array['creditCardExpirationDate']))
            {
        $s->creditCardExpirationDate = $this->subscription_array['creditCardExpirationDate'];
            }
        if (isset($this->subscription_array['creditCardCardCode']))
            {
            $s->creditCardCardCode = $this->subscription_array['creditCardCardCode'];
            }
        if (isset($this->subscription_array['bankAccountAccountType']))
            {
            $s->bankAccountAccountType = $this->subscription_array['bankAccountAccountType'];
            }
        if (isset($this->subscription_array['bankAccountRoutingNumber']))
            {
            $s->bankAccountRoutingNumber = $this->subscription_array['bankAccountRoutingNumber'];
            }
        if (isset($this->subscription_array['bankAccountAccountNumber']))
            {
            $s->bankAccountAccountNumber = $this->subscription_array['bankAccountAccountNumber'];
            }
        if (isset($this->subscription_array['bankAccountNameOnAccount']))
            {
            $s->bankAccountNameOnAccount = $this->subscription_array['bankAccountNameOnAccount'];
            }
        if (isset($this->subscription_array['bankAccountEcheckType']))
            {
            $s->bankAccountEcheckType = $this->subscription_array['bankAccountEcheckType'];
            }
        if (isset($this->subscription_array['bankAccountBankName']))
            {
            $s->bankAccountBankName = $this->subscription_array['bankAccountBankName'];
            }
        if (isset($this->subscription_array['orderInvoiceNumber']))
            {
            $s->orderInvoiceNumber = $this->subscription_array['orderInvoiceNumber'];
            }
        if (isset($this->subscription_array['orderDescription']))
            {
            $s->orderDescription = $this->subscription_array['orderDescription'];
            }
        if (isset($this->subscription_array['customerId']))
            {
            $s->customerId = $this->subscription_array['customerId'];
            }
        if (isset($this->subscription_array['customerEmail']))
            {
            $s->customerEmail = $this->subscription_array['customerEmail'];
            }
        if (isset($this->subscription_array['customerPhoneNumber']))
            {
            $s->customerPhoneNumber = $this->subscription_array['customerPhoneNumber'];
            }
        if (isset($this->subscription_array['customerFaxNumber']))
            {
            $s->customerFaxNumber = $this->subscription_array['customerFaxNumber'];
            }
        if (isset($this->subscription_array['billToFirstName']))
            {
            $s->billToFirstName = $this->subscription_array['billToFirstName'];
            }
        if (isset($this->subscription_array['billToLastName']))
            {
            $s->billToLastName = $this->subscription_array['billToLastName'];
            }
        if (isset($this->subscription_array['billToCompany']))
            {
            $s->billToCompany = $this->subscription_array['billToCompany'];
            }
        if (isset($this->subscription_array['billToAddress']))
            {
            $s->billToAddress = $this->subscription_array['billToAddress'];
            }
        if (isset($this->subscription_array['billToCity']))
            {
            $s->billToCity = $this->subscription_array['billToCity'];
            }
        if (isset($this->subscription_array['billToState']))
            {
            $s->billToState = $this->subscription_array['billToState'];
            }
        if (isset($this->subscription_array['billToZip']))
            {
            $s->billToZip = $this->subscription_array['billToZip'];
            }
        if (isset($this->subscription_array['billToCountry']))
            {
            $s->billToCountry = $this->subscription_array['billToCountry'];
            }
        if (isset($this->subscription_array['shipToFirstName']))
            {
            $s->shipToFirstName = $this->subscription_array['shipToFirstName'];
            }
        if (isset($this->subscription_array['shipToLastName']))
            {
            $s->shipToLastName = $this->subscription_array['shipToLastName'];
            }
        if (isset($this->subscription_array['shipToCompany']))
            {
            $s->shipToCompany = $this->subscription_array['shipToCompany'];
            }
        if (isset($this->subscription_array['shipToAddress']))
            {
            $s->shipToAddress = $this->subscription_array['shipToAddress'];
            }
        if (isset($this->subscription_array['shipToCity']))
            {
            $s->shipToCity = $this->subscription_array['shipToCity'];
            }
        if (isset($this->subscription_array['shipToState']))
            {
            $s->shipToState = $this->subscription_array['shipToState'];
            }
        if (isset($this->subscription_array['shipToZip']))
            {
            $s->shipToZip = $this->subscription_array['shipToZip'];
            }
        if (isset($this->subscription_array['shipToCountry']))
            {
            $s->shipToCountry = $this->subscription_array['shipToCountry'];
            }
        $this->authnet_subscription = $s;        
        }
    private function logResponse()
        {
        require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/database/helper.php';
        require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/website/information.php';
        $table = "log_authnet_arb_response";
        $columns = array();
        $data = array();
        foreach ($this->response_array as $key => $value)
            {
            $columns[] = $key;
            $data[] = $value;
            }
        if ($this->use_api_db == true)
            {
            $website = new WebsiteInformation($this->user_domain_name);
            $columns[] = "website_id";
            $data[] = $website->getWebsiteId();
            }
        $db = new DatabaseHelper($this->framework_api_key, $this->log_db_name, $this->use_api_db);
        $db->Insert($table, $columns, $data);
        }
    public function createSubscription($subscriptionArray)
        {
        $this->subscription_array = $subscriptionArray;
        $this->convertArrayToSubscription();
        $this->manageSubscription("create");
        return $this->response_array;
        }
    public function getSubscriptionStatus($subscriptionId, $referenceId = "")
        {
        $this->reference_id = $referenceId;
        $this->subscription_id = $subscriptionId;
        $this->manageSubscription("status");
        return $this->response_array;
        }
    public function updateSubscription($subscriptionId, $subscriptionArray)
        {
        $this->subscription_id = $subscriptionId;
        $this->subscription_array = $subscriptionArray;
        $this->convertArrayToSubscription();
        $this->manageSubscription("update");
        return $this->response_array;
        }
    public function cancelSubscription($subscriptionId, $referenceId = "")
        {
        $this->reference_id = $referenceId;
        $this->subscription_id = $subscriptionId;
        $this->manageSubscription("cancel");
        return $this->response_array;
        }
    }
?>