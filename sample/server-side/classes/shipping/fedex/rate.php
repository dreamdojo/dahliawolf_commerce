<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/log/error.php';
class FedexRate
    {
    public function __construct($accessKey, $password, $accountNumber, $meterNumber, $useTestServer) 
        {
        $this->access_key = $accessKey;
        $this->password = $password;
        $this->account_number = $accountNumber;
        $this->meter_number = $meterNumber;
        $this->use_test_server = $useTestServer;
        }
    private $access_key;
    private $password;
    private $account_number;
    private $meter_number;
    private $use_test_server;
    private $server_uri;
    private $send_from_address;
    private $send_to_address;
    private $service;
    private $weight;
    private $width;
    private $length;
    private $height;    
    private function callService()
        {
        require_once $_SERVER['DOCUMENT_ROOT'] . "/lib/fedex/fedex-common.php";
        $path_to_wsdl = $_SERVER['DOCUMENT_ROOT'] . "/lib/fedex/RateService_v10.wsdl";
        ini_set("soap.wsdl_cache_enabled", "0");
        $client = new SoapClient($path_to_wsdl, array('trace' => 1));
        $request['WebAuthenticationDetail'] = array('UserCredential' =>
                                              array('Key' => $this->access_key, 'Password' => $this->password)); 
        $request['ClientDetail'] = array('AccountNumber' => $this->account_number, 'MeterNumber' => $this->meter_number);
        $request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Available Services Request v10 using PHP ***');
        $request['Version'] = array('ServiceId' => 'crs', 'Major' => '10', 'Intermediate' => '0', 'Minor' => '0');
        $request['ReturnTransitAndCommit'] = true;
        $request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP';
        $request['RequestedShipment']['ShipTimestamp'] = date('c');
        // Service Type and Packaging Type are not passed in the request
        $request['RequestedShipment']['Shipper'] = array('Address'=> $this->send_from_address);
        $request['RequestedShipment']['Recipient'] = array('Address'=> $this->send_to_address);
        $request['RequestedShipment']['ShippingChargesPayment'] = array('PaymentType' => 'SENDER',
                                                                'Payor' => array('AccountNumber' => $this->account_number,
                                                                             'CountryCode' => 'US'));
        $request['RequestedShipment']['RateRequestTypes'] = 'ACCOUNT'; 
        $request['RequestedShipment']['RateRequestTypes'] = 'LIST';
        $request['RequestedShipment']['PackageCount'] = '1';
        $request['RequestedShipment']['RequestedPackageLineItems'] = array(
        '0' => array(
            'SequenceNumber' => 1,
            'GroupPackageCount' => 1,
            'Weight' => array(
                'Value' => $this->weight,
                'Units' => 'LB'),
            'Dimensions' => array(
                'Length' => $this->length,
                'Width' => $this->width,
                'Height' => $this->height,
            'Units' => 'IN')
            )
        );
        //$client->__setLocation($this->server_uri);
        $response = $client ->getRates($request);
        $rate =0;
        foreach ($response -> RateReplyDetails as $rateReply)
            {
            if ($this->service == $rateReply->ServiceType)
                {
                $rate = $rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount;
                }
            }
        return $rate;
        }
    public function getRate($sendFromDetails, $sendToDetails, $service, $weight, $length, $width, $height)
        {
        $this->send_from_address = $sendFromDetails;
        $this->send_to_address = $sendToDetails;
        $this->service = $service;
        $this->weight = $weight;
        $this->length = $length;
        $this->width = $width;
        $this->height = $height;
        return $this->callService();
        }
    }
?>