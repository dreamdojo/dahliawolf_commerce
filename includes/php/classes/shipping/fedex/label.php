<?
ini_set("soap.wsdl_cache_enabled", "0");

class FedexLabel{
	private $key = NULL;
	private $password = NULL;
	private $account = NULL;
	private $billingAccount = NULL;
	private $meter = NULL;
	private $soap = NULL;
	private $wsdlPath = array('ship' => '/lib/php/fedex/ShipService_v9.wsdl'
							  , 'rate' => '/php/shipping-process/fedex/RateService_v9.wsdl'
							  );
	private $requests = array();
	public $response = NULL;
	public $error = false;
	public $trackingNumber = NULL;
	public $notifications = array();
	
	public function __construct($accountInfo, $service) {
		$this->key = $accountInfo['key'];
		$this->password = $accountInfo['password'];
		$this->account = $accountInfo['account'];
		$this->billingAccount = $accountInfo['billingAccount'];
		$this->meter = $accountInfo['meter'];
		
		$this->requests = array('WebAuthenticationDetail' => array('UserCredential' => array('Key' => $this->key
																							 , 'Password' => $this->password
																							 )
																   )
								, 'ClientDetail' => array('AccountNumber' => $this->account
														  , 'MeterNumber' => $this->meter
														  )
								, 'TransactionDetail' => array('CustomerTransactionId' => 'Leave Blank For Now')
								//, 'ShipTimestamp' => date('c', TIME)
								//, 'DropoffType' => 'REGULAR_PICKUP' // valid values REGULAR_PICKUP, REQUEST_COURIER, DROP_BOX, BUSINESS_SERVICE_CENTER and STATION
								);
		
		$this->setVersion($service);
		
		$this->requests['RequestedShipment'] = array('ShipTimestamp' => date('c', TIME)
													 , 'DropoffType' => 'REGULAR_PICKUP'
													 , 'RateRequestTypes' => array('ACCOUNT') // valid values ACCOUNT and LIST
													 );
		
		
		/*
		$this->requests['InternationalDetail'] = array('CustomsValue' => 5.00
													 );
		$this->requests['Commodity'] = array('CustomsValue' => 5.00
													 );
		*/
		
	}
	
	public function setVersion($service){
		if ($service == 'rate') {
			$this->requests['Version'] = array('ServiceId' => 'crs'
											   , 'Major' => '9'
											   , 'Intermediate' => '0'
											   , 'Minor' => '0'
											   );	
		}
		else {
			$this->requests['Version'] = array('ServiceId' => 'ship'
											   , 'Major' => '9'
											   , 'Intermediate' => '0'
											   , 'Minor' => '0'
											   );	
			
		}
	}
	
	//$service: valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...s
	/*
	$shipper/$recipient => array('name' => 'Sender Name'
					  , 'company' => 'Company Name'
					  , 'phone' => '0805522713'
					  , 'street' => Address Line 1
					  , 'street2' => Address Line 2
					  , 'city' => 'Austin'
					  , 'stateOrProvince' => 'TX'
					  , 'postalCode' => '73301'
					  , 'country' => 'US'
					  )
	*/
	
	public function setShipper($shipper){
		$residential = ($shipper['residential'] == '1') ? true : false;
		$this->requests['RequestedShipment']['Shipper'] =  array('Contact' => array('PersonName' => $shipper['name']
																					, 'CompanyName' => $shipper['company']
																					, 'PhoneNumber' => preg_replace('/[^0-9]/', '', $shipper['phone'])
																					)
																 , 'Address' => array('StreetLines' => $shipper['street2'] == '' ? array($shipper['street']) : array($shipper['street'], $shipper['street2'])
																					   , 'City' => $shipper['city']
																					   , 'StateOrProvinceCode' => $shipper['stateOrProvince']
																					   , 'PostalCode' => $shipper['postalCode']
																					   , 'CountryCode' => $shipper['country']
																					   , 'Residential' => $residential
																					   )
																 );
	}
	
	public function setRecipient($recipient){
		$residential = ($recipient['residential'] == '1') ? true : false;
		$this->requests['RequestedShipment']['Recipient'] =  array('Contact' => array('PersonName' => $recipient['name']
																						, 'CompanyName' => $recipient['company']
																						, 'PhoneNumber' => preg_replace('/[^0-9]/', '', $recipient['phone'])
																						)
																	 , 'Address' => array('StreetLines' => $recipient['street2'] == '' ? array($recipient['street']) : array($recipient['street'], $recipient['street2'])
																						   , 'City' => $recipient['city']
																						   , 'StateOrProvinceCode' => $recipient['stateOrProvince']
																						   , 'PostalCode' => $recipient['postalCode']
																						   , 'CountryCode' => $recipient['country']
																						   , 'Residential' => $residential
																						   )
																	 );
	}
	
	/*
	$package => array('weight' => 50.0
					  , 'length' => 1
					  , 'width' => 10
					  , 'height' => 3
					  )
	*/
	public function setPackage($package){
		if(floatval($package['weight']) < .5) $package['weight'] = 1;
		
		$this->requests['RequestedShipment']['TotalWeight'] =  array('Value' => $package['weight']
																	  , 'Units' => 'LB' // valid values LB and KG
																	  );
		
		/*
		PackagingType
		Required. Valid values for this element are:
			FEDEX_BOX
			FEDEX_ENVELOPE
			FEDEX_PAK
			FEDEX_TUBE
			INDIVIDUAL_PACKAGES
			YOUR_PACKAGING
			
			// intl packages
			FEDEX_10KG_BOX
			FEDEX_25KG_BOX
			
		*/
		
		$this->requests['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING';
		$this->requests['RequestedShipment']['PackageCount'] = '1'; //keep it simple and use only 1 for now
		$this->requests['RequestedShipment']['PackageDetail'] = 'INDIVIDUAL_PACKAGES';
		
		$this->requests['RequestedShipment']['RequestedPackageLineItems'] = array('0' => array('Weight' => array('Value' => $package['weight']
																												, 'Units' => 'LB' // LB or KG
																												)
																							
																							//, 'SequenceNumber' => '1'
																						)
																			   ); 
		
		if (isset($package['length']) && is_numeric($package['length']) && isset($package['width']) && is_numeric($package['width']) && isset($package['height']) && is_numeric($package['height'])) {
			$this->requests['RequestedShipment']['RequestedPackageLineItems']['0']['Dimensions'] = array('Length' => $package['length']
																										 , 'Width' => $package['width']
																										 , 'Height' => $package['height']
																										 , 'Units' => 'IN' // IN or CM
																										 );
																							
																						
		}
		
	}
	
	public function processShipment(){
		
		$this->requests['RequestedShipment']['LabelSpecification'] = array('LabelFormatType' => 'COMMON2D' // valid values COMMON2D, LABEL_DATA_ONLY
																			, 'ImageType' => 'PDF' // valid values DPL, EPL2, PDF, ZPLII and PNG
																			, 'LabelStockType' => 'PAPER_7X4.75'
																			);
		$this->requests['RequestedShipment']['ShippingChargesPayment'] = array('PaymentType' => 'SENDER' // valid values RECIPIENT, SENDER and THIRD_PARTY
																			   , 'Payor' => array('AccountNumber' => $this->billingAccount
																								  , 'CountryCode' => 'US'
																								  )
																			   );
		
		$this->call('processShipment');
	}
	
	public function setCustomsValue($items = array()){ // required for processShipment
		
		if (empty($items)) {
			trigger_error('fedex->setCustomsValue(): items is empty', E_USER_ERROR);
		}
		
		$amount = 0;
		$commodities = array();
		foreach ($items as $item) {
			$pounds = ($item['product_weight'] / 16);
			$pounds = number_format($pounds, 2, '.', '');
			$commodity = array('NumberOfPieces' => 1 // Total number of packages in this shipment.
							 , 'Description' => $item['product']
							 , 'CountryOfManufacture' => 'US'
							 , 'Weight' => array('Units' => 'LB'
												 , 'Value' => $pounds
												 )
							 , 'Quantity' => $item['quantity']
							 , 'QuantityUnits' => 'EA'
							 , 'UnitPrice' => array('Currency' => 'USD'
													, 'Amount' => $item['product_price']
													)
							/* , 'CustomsValue' => array('Currency' => 'USD'
													   , 'Amount' => $amount
													   )
							*/
							 );
			
			array_push($commodities, $commodity);
			$amount += $item['product_price'] * $item['quantity'];
		}
		
		$this->requests['RequestedShipment']['CustomsClearanceDetail'] = array('CustomsValue' => array('Currency' => 'USD'
																									   , 'Amount' => $amount
																									   )
																			   , 'Commodities' => $commodities
																			   , 'DutiesPayment' => array('PaymentType' => 'SENDER'
																										  , 'Payor' => array('AccountNumber' => $this->billingAccount
																															 , 'CountryCode' => 'US'
																															 )
																										  )
																			   );
	 
		 
	}
	
	// valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...s	
	public function getRates($service = NULL){
		if($service != NULL){
			$this->setServiceType($service);
		}
		
		$this->requests['RequestedShipment']['RateRequestTypes'] = 'ACCOUNT'; 
		$this->requests['RequestedShipment']['RateRequestTypes'] = 'LIST';
		$this->requests['RequestedShipment']['ReturnTransitAndCommit'] = true;
		
		$this->call('getRates');
		if ($this->error) {
			return false;
		}
		//logError(print_r($this->requests, true), false);
		//print_r($this->response);
		if($service != NULL){
			$rateReply = $this->response->RateReplyDetails;
			$delivery = ($rateReply->RateReplyDetails[0]->DeliveryTimestamp != '') ? $rateReply->RateReplyDetails[0]->DeliveryTimestamp : $rateReply->RateReplyDetails[0]->TransitTime;
			$rate = array('service' => $rateReply->ServiceType
						  , 'price' => $rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount
						  , 'delivery' => $delivery
						  );
			return $rate;
		}
		else{
			$rates = array();
			
			if(!empty($this->response->RateReplyDetails)){
				foreach ($this->response->RateReplyDetails as $rateDetail){
					$ratedShipmentDetails = is_array($rateDetail->RatedShipmentDetails) ? $rateDetail->RatedShipmentDetails[0] : $rateDetail->RatedShipmentDetails; //some times it can be am array
					$delivery = ($rateDetail->DeliveryTimestamp != '') ? $rateDetail->DeliveryTimestamp : $rateDetail->TransitTime;
					$rate = array('service' => $rateDetail->ServiceType
								  , 'price' => $ratedShipmentDetails->ShipmentRateDetail->TotalNetCharge->Amount
								  , 'delivery' => $delivery
								  );
					array_push($rates, $rate);
				}
			}
		}
		
		return $rates;
	}
	
	public function setServiceType($service = NULL){
		/* ServiceType
			Required. Valid values for this element are:
			PRIORITY_OVERNIGHT
			STANDARD_OVERNIGHT
			FEDEX_2_DAY
			FEDEX_EXPRESS_SAVER
			FIRST_OVERNIGHT
		*/
		
		if($service != NULL){
			$this->requests['RequestedShipment']['ServiceType'] = $service; 	
		}
	}
	
	public function outputLabel($path = NULL){
		if($path == NULL){
			echo $this->response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image;
		}
		else{
			$fp = fopen(DR . $path, 'w+');   
			fwrite($fp, $this->response->CompletedShipmentDetail->CompletedPackageDetails->Label->Parts->Image);
			fclose($fp);
			return true;
		}
	}
	
	private function call($function){
		$this->error = false;
		//logError(print_r($this->requests, true), false);
		switch($function){
			case 'processShipment':
				$wsdlPath = $this->wsdlPath['ship'];
			break;
			
			case 'getRates':
				$wsdlPath = $this->wsdlPath['rate'];
			break;
		}
		
		$this->soap = new SoapClient($_SERVER['DOCUMENT_ROOT'] . $wsdlPath, array('trace' => 1)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information
		
		try {
			switch($function){
				case 'processShipment':
					$this->response = $this->soap->processShipment($this->requests);
					$this->trackingNumber = $this->response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber;
					//echo $this->trackingNumber; die();
					//echo 'Tracking NUmber'. $this->response->CompletedShipmentDetail->CompletedPackageDetails->TrackingIds->TrackingNumber;
					//print_r($this->response);
					//die();
				break;
				
				case 'getRates':
					$this->response = $this->soap->getRates($this->requests);
				break;
			}
			
			$this->getNotifications($this->response->Notifications);
			
			if ($this->response->HighestSeverity == 'FAILURE' || $this->response->HighestSeverity == 'ERROR'){
				//$this->printError();
				$this->error = true;
			}
		} catch (SoapFault $exception) {
			array_push($this->notifications, array('Severity' => 'ERROR'
												   , 'Code' => 'faultcode: ' . $exception->faultcode
												   , 'Message' => $exception->faultstring
												   )
					   );
			//echo '<h2>Fault</h2><br>' . "\n";                        
			//echo '<b>Code:</b>{' . $exception->faultcode . '}<br>' . "\n";
			//echo '<b>String:</b>{' . $exception->faultstring . '}<br>' . "\n";
			$this->error = true;
		}
	}
	
	private function getNotifications($notes){ // notifications can be an object, or an array of objects
		if(is_array($notes)){
			foreach($notes as $note){
				$data = get_object_vars($note);
				array_push($this->notifications, $data);
			}
		}
		else if(is_object($notes)){
			$data = get_object_vars($notes);
			array_push($this->notifications, $data);
		}
	}
	
	private function printError(){
		echo '<h2>Error returned in processing transaction</h2>';
		echo "\n";
		$this->printNotifications($this->response->Notifications);
		$this->printRequestResponse($this->soap, $this->response);
	}
	
	private function printNotifications($notes){
		foreach($notes as $noteKey => $note){
			if(is_string($note)){    
				echo $noteKey . ': ' . $note . Newline;
			}
			else{
				printNotifications($note);
			}
		}
		echo Newline;
	}
	
	private function printRequestResponse(){
		echo '<h2>Request</h2>' . "\n";
		echo '<pre>' . htmlspecialchars($this->soap->__getLastRequest()) . '</pre>';  
		echo "\n";
	   
		echo '<h2>Response</h2>'. "\n";
		echo '<pre>' . htmlspecialchars($this->soap->__getLastResponse()) . '</pre>';
		echo "\n";
	}
}

?>