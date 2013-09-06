<?php
error_reporting(E_ALL & ~E_NOTICE);
//require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/log/error.php';
class UpsLable
    {
    public function __construct($accessKey, $username, $password, $accountNumber, $shipper_info, $return_label, $useTestServer = false)
        {
        $this->access_key = $accessKey;
        $this->username = $username;
        $this->password = $password;
        $this->account_number = $accountNumber;
        $this->shipper_info = $shipper_info;
		$this->return_label = $return_label;
        if ($useTestServer == false)
            {
            $this->ship_confirm_service_uri = "https://www.ups.com/ups.app/xml/ShipConfirm";
			$this->ship_accept_service_uri = "https://www.ups.com/ups.app/xml/ShipAccept";
			$this->label_recovery_service_uri = 'https://wwwcie.ups.com/ups.app/xml/LabelRecovery';//"https://www.ups.com/ups.app/xml/LabelRecovery";
            }
        if ($useTestServer == false)
            {
            $this->ship_confirm_service_uri = "https://www.ups.com/ups.app/xml/ShipConfirm";
			$this->ship_accept_service_uri = "https://www.ups.com/ups.app/xml/ShipAccept";
			$this->label_recovery_service_uri = 'https://wwwcie.ups.com/ups.app/xml/LabelRecovery';//"https://www.ups.com/ups.app/xml/LabelRecovery";
            }
        }
    private $access_key;
    private $username;
    private $password;
    private $account_number;
    private $ship_from_zip;
    private $service_uri;
	
	// Step 1
   // function shipConfirm($shipToZip, $shipToCountryCode, $service,$weight,$length,$width,$height)
	 function shipConfirm($shipTo, $service, $service_description, $weight, $CustomerComment = '') {

        if(floatval($weight) < .5) $weight = 1;

$data ="<?xml version=\"1.0\"?>
<AccessRequest xml:lang=\"en-US\">
  <AccessLicenseNumber>$this->access_key</AccessLicenseNumber>
  <UserId>$this->username</UserId>
  <Password>$this->password</Password>
</AccessRequest> 
<?xml version=\"1.0\"?>
<ShipmentConfirmRequest xml:lang=\"en-US\">
  <Request>
    <TransactionReference>
      <CustomerContext>$CustomerComment</CustomerContext>
      <XpciVersion/>
    </TransactionReference>
    <RequestAction>ShipConfirm</RequestAction>
    <RequestOption>validate</RequestOption>
  </Request>
  <LabelSpecification>
    <LabelPrintMethod>
      <Code>GIF</Code>
      <Description>gif file</Description>
    </LabelPrintMethod>
    <HTTPUserAgent/>
    <LabelImageFormat>
      <Code>GIF</Code>
      <Description>gif</Description>
    </LabelImageFormat>
  </LabelSpecification>
  <Shipment>
   <RateInformation>
      <NegotiatedRatesIndicator/> 
    </RateInformation> 
	<Description>Clothing and Accessories</Description>";
	
	if ($this->return_label) {
		$data .= "
			<ReturnService>
				<Code>9</Code>
			</ReturnService>
		";
	}
	
	$data .= "
    <Shipper>
	  <AttentionName>" . $this->shipper_info['name'] . "</AttentionName>
      <Name>" . $this->shipper_info['name'] . "</Name>
      <PhoneNumber>" . $this->shipper_info['phone'] . "</PhoneNumber>
      <ShipperNumber>" . $this->account_number . "</ShipperNumber>
	  <TaxIdentificationNumber/>
      <Address>
    	<AddressLine1>" . $this->shipper_info['address'] . "</AddressLine1>
    	<City>" . $this->shipper_info['city'] . "</City>
    	<StateProvinceCode>" . $this->shipper_info['state'] . "</StateProvinceCode>
    	<PostalCode>" . $this->shipper_info['zip'] . "</PostalCode>
    	<PostcodeExtendedLow></PostcodeExtendedLow>
    	<CountryCode>" . $this->shipper_info['country'] . "</CountryCode>
     </Address>
    </Shipper>";
	if ($this->return_label) {
		$data .= "
	<ShipTo>
     <CompanyName>" . $this->shipper_info['name'] . "</CompanyName>
      <AttentionName>" . $this->shipper_info['name'] . "</AttentionName>
      <PhoneNumber>" . $this->shipper_info['phone'] . "</PhoneNumber>
      <Address>
        <AddressLine1>" . $this->shipper_info['address'] . "</AddressLine1>
        <City>" . $this->shipper_info['city'] . "</City>
        <StateProvinceCode>" . $this->shipper_info['state'] . "</StateProvinceCode>
        <PostalCode>" . $this->shipper_info['zip'] . "</PostalCode>
        <CountryCode>" . $this->shipper_info['country'] . "</CountryCode>
      </Address>
    </ShipTo>
    <ShipFrom>
      <CompanyName>" . $shipTo['name'] . "</CompanyName>
      <AttentionName>" . $shipTo['name'] . "</AttentionName>
      <PhoneNumber>" . $shipTo['phone'] . "</PhoneNumber>
	  <TaxIdentificationNumber/>
      <Address>
        <AddressLine1>" . $shipTo['address'] . "</AddressLine1>
        <City>" . $shipTo['city'] . "</City>
    	<StateProvinceCode>" . $shipTo['state'] . "</StateProvinceCode>
    	<PostalCode>" . $shipTo['zip'] . "</PostalCode>
    	<CountryCode>" . $shipTo['country'] . "</CountryCode>
      </Address>
    </ShipFrom>";
	}
	else {
		$data .= "
	<ShipTo>
     <CompanyName>" . $shipTo['name'] . "</CompanyName>
      <AttentionName>" . $shipTo['name'] . "</AttentionName>
      <PhoneNumber>" . $shipTo['phone'] . "</PhoneNumber>
      <Address>
        <AddressLine1>" . $shipTo['address'] . "</AddressLine1>
        <City>" . $shipTo['city'] . "</City>
        <StateProvinceCode>" . $shipTo['state'] . "</StateProvinceCode>
        <PostalCode>" . $shipTo['zip'] . "</PostalCode>
        <CountryCode>" . $shipTo['country'] . "</CountryCode>
      </Address>
    </ShipTo>
    <ShipFrom>
      <CompanyName>" . $this->shipper_info['name'] . "</CompanyName>
      <AttentionName>" . $this->shipper_info['name'] . "</AttentionName>
      <PhoneNumber>" . $this->shipper_info['phone'] . "</PhoneNumber>
	  <TaxIdentificationNumber/>
      <Address>
        <AddressLine1>" . $this->shipper_info['address'] . "</AddressLine1>
        <City>" . $this->shipper_info['city'] . "</City>
    	<StateProvinceCode>" . $this->shipper_info['state'] . "</StateProvinceCode>
    	<PostalCode>" . $this->shipper_info['zip'] . "</PostalCode>
    	<CountryCode>" . $this->shipper_info['country'] . "</CountryCode>
      </Address>
    </ShipFrom>";
	}
	$data .= "
     <PaymentInformation>
      <Prepaid>
        <BillShipper>
          <AccountNumber>$this->account_number</AccountNumber>
        </BillShipper>
      </Prepaid>
    </PaymentInformation>
    <Service>
      <Code>$service</Code>
      <Description>$service_description</Description>
    </Service>
    <Package>
      <PackagingType>
        <Code>02</Code>
        <Description>Customer Supplied</Description>
      </PackagingType>
      <Description>Package Description</Description>";
	  /*
	  <ReferenceNumber>
	  	<Code>00</Code>
		<Value>Package</Value>
	  </ReferenceNumber>
	  */
	  $data .= "
      <PackageWeight>
        <UnitOfMeasurement>
        <Code>LBS</Code>
        </UnitOfMeasurement>
        <Weight>$weight</Weight>
      </PackageWeight>
      <LargePackageIndicator/>
      <AdditionalHandling>0</AdditionalHandling>
    </Package>
  </Shipment>
</ShipmentConfirmRequest>";
		//echo $data; die();
        $ch = curl_init($this->ship_confirm_service_uri);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_TIMEOUT, 60);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $result=curl_exec ($ch);
		
        //echo '<!-- '. $result. ' -->'; // THIS LINE IS FOR DEBUG PURPOSES ONLY-IT WILL SHOW IN HTML COMMENTS
        $data = strstr($result, '<?');
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $data, $vals, $index);
        xml_parser_free($xml_parser);
        $params = array();
        $level = array();
        foreach ($vals as $xml_elem)
            {
            if ($xml_elem['type'] == 'open')
                {
                if (array_key_exists('attributes',$xml_elem))
                    {
                    list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);
                    }
                else
                    {
                    $level[$xml_elem['level']] = $xml_elem['tag'];
                    }
                }
            if ($xml_elem['type'] == 'complete')
                {
                $start_level = 1;
                $php_stmt = '$params';
                while($start_level < $xml_elem['level'])
                    {
                    $php_stmt .= '[$level['.$start_level.']]';
                    $start_level++;
                    }
                $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
                eval($php_stmt);
                }
            }
        curl_close($ch);
		
		if (!isset($params['SHIPMENTCONFIRMRESPONSE']) 
			|| !isset($params['SHIPMENTCONFIRMRESPONSE']['RESPONSE']) 
			|| !isset($params['SHIPMENTCONFIRMRESPONSE']['RESPONSE']['RESPONSESTATUSCODE'])
			|| $params['SHIPMENTCONFIRMRESPONSE']['RESPONSE']['RESPONSESTATUSCODE'] != '1'
		) {
			return array(
				'success' => false
				, 'error' => $params['SHIPMENTCONFIRMRESPONSE']['RESPONSE']['ERROR']['ERRORDESCRIPTION']
				, 'data' => NULL
			);
		}
		
		return array(
			'success' => true
			, 'error' => NULL
			, 'data' => $params
		);
		
        }
		
		// Step 2
		 function shipAccept($ShipmentDigest, $CustomerComment = '') {

        $data ="<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>
<AccessRequest>
    <AccessLicenseNumber>$this->access_key</AccessLicenseNumber>
    <UserId>$this->username</UserId>
    <Password>$this->password</Password>
</AccessRequest>
<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>
<ShipmentAcceptRequest>
    <Request>
        <TransactionReference>
            <CustomerContext>$CustomerComment</CustomerContext>
        </TransactionReference>
        <RequestAction>ShipAccept</RequestAction>
        <RequestOption>1</RequestOption>
    </Request>
    <ShipmentDigest>$ShipmentDigest</ShipmentDigest>
</ShipmentAcceptRequest>
";
		
        $ch = curl_init($this->ship_accept_service_uri);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_TIMEOUT, 60);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $result=curl_exec ($ch);
		//echo '<pre>';echo $data; echo $result; return; 
        //echo '<!-- '. $result. ' -->'; // THIS LINE IS FOR DEBUG PURPOSES ONLY-IT WILL SHOW IN HTML COMMENTS
        $data = strstr($result, '<?');
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $data, $vals, $index);
        xml_parser_free($xml_parser);
        $params = array();
        $level = array();
        foreach ($vals as $xml_elem)
            {
            if ($xml_elem['type'] == 'open')
                {
                if (array_key_exists('attributes',$xml_elem))
                    {
                    list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);
                    }
                else
                    {
                    $level[$xml_elem['level']] = $xml_elem['tag'];
                    }
                }
            if ($xml_elem['type'] == 'complete')
                {
                $start_level = 1;
                $php_stmt = '$params';
                while($start_level < $xml_elem['level'])
                    {
                    $php_stmt .= '[$level['.$start_level.']]';
                    $start_level++;
                    }
                $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
                eval($php_stmt);
                }
            }
        curl_close($ch);
		
		if (!isset($params['SHIPMENTACCEPTRESPONSE']) 
			|| !isset($params['SHIPMENTACCEPTRESPONSE']['RESPONSE']) 
			|| !isset($params['SHIPMENTACCEPTRESPONSE']['RESPONSE']['RESPONSESTATUSCODE'])
			|| $params['SHIPMENTACCEPTRESPONSE']['RESPONSE']['RESPONSESTATUSCODE'] != '1'
		) {
			return array(
				'success' => false
				, 'error' => $params['SHIPMENTACCEPTRESPONSE']['RESPONSE']['ERROR']['ERRORDESCRIPTION']
				, 'data' => NULL
			);
		}
		
		return array(
			'success' => true
			, 'error' => NULL
			, 'data' => $params
		);
		
		
        }
		
		
		public function returnLabel($trackingNumber, $CustomerComment = '') {

        $data ="<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>
<AccessRequest>
    <AccessLicenseNumber>$this->access_key</AccessLicenseNumber>
    <UserId>$this->username</UserId>
    <Password>$this->password</Password>
</AccessRequest>
<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>
<LabelRecoveryRequest>
    <Request>
        <TransactionReference>
            <CustomerContext>$CustomerComment</CustomerContext>
        </TransactionReference>
        <RequestAction>LabelRecovery</RequestAction>
    </Request>
	<LabelSpecification>
		<HTTPUserAgent/>
		<LabelImageFormat>
		  <Code>GIF</Code>
		</LabelImageFormat>
	  </LabelSpecification>
    <TrackingNumber>1ZVV99080292743049</TrackingNumber>
</LabelRecoveryRequest>
";
		//echo $data;
        $ch = curl_init($this->label_recovery_service_uri);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch,CURLOPT_POST,1);
        curl_setopt($ch,CURLOPT_TIMEOUT, 60);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);
        $result=curl_exec ($ch);
		//echo '<pre>';echo $data; echo $result; return; 
        //echo '<!-- '. $result. ' -->'; // THIS LINE IS FOR DEBUG PURPOSES ONLY-IT WILL SHOW IN HTML COMMENTS
        $data = strstr($result, '<?');
        $xml_parser = xml_parser_create();
        xml_parse_into_struct($xml_parser, $data, $vals, $index);
        xml_parser_free($xml_parser);
        $params = array();
        $level = array();
        foreach ($vals as $xml_elem) {
            if ($xml_elem['type'] == 'open') {
                if (array_key_exists('attributes',$xml_elem)) {
                    list($level[$xml_elem['level']],$extra) = array_values($xml_elem['attributes']);
                  }
                else {
                    $level[$xml_elem['level']] = $xml_elem['tag'];
                    }
                }
            if ($xml_elem['type'] == 'complete') {
                $start_level = 1;
                $php_stmt = '$params';
                while($start_level < $xml_elem['level'])
                    {
                    $php_stmt .= '[$level['.$start_level.']]';
                    $start_level++;
                    }
                $php_stmt .= '[$xml_elem[\'tag\']] = $xml_elem[\'value\'];';
                eval($php_stmt);
                }
            }
        curl_close($ch);
		//print_r($params);
		if (!isset($params['SHIPMENTACCEPTRESPONSE']) 
			|| !isset($params['SHIPMENTACCEPTRESPONSE']['RESPONSE']) 
			|| !isset($params['SHIPMENTACCEPTRESPONSE']['RESPONSE']['RESPONSESTATUSCODE'])
			|| $params['SHIPMENTACCEPTRESPONSE']['RESPONSE']['RESPONSESTATUSCODE'] != '1'
		) {
			return array(
				'success' => false
				, 'error' => $params['SHIPMENTACCEPTRESPONSE']['RESPONSE']['ERROR']['ERRORDESCRIPTION']
				, 'data' => NULL
			);
		}
		
		return array(
			'success' => true
			, 'error' => NULL
			, 'data' => $params
		);
		
		
        }
    }
?>