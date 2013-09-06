<?php
function USPSLabel($username, $from, $to, $ounces, $service_code) {

	$userName = $username; // Your USPS Username
	$FromName = $from['name'];
	$FromAddress = $from['address'];
	$FromAddress2 = $from['address2'];
	$FromCity = $from['city'];
	$FromState = $from['state'];
	$FromZip5 = $from['zip'];
	
	$ToName = $to['name'];
	$ToAddress = $to['address'];
	$ToAddress2 = $to['address2'];
	$ToCity = $to['city'];
	$ToState = $to['state'];
	$ToZip5 = $to['zip'];
	
	$weightOunces = $ounces;
	if(floatval($weightOunces) < .5) $weightOunces = 1;
	
	// =============== DON'T CHANGE BELOW THIS LINE ===============
	
	$url = "https://secure.shippingapis.com/ShippingAPI.dll";
	$ch = curl_init();
	
	// set the target url
	curl_setopt($ch, CURLOPT_URL,$url);
	//curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// parameters to post
	curl_setopt($ch, CURLOPT_POST, true);
	
	$data = "API=DeliveryConfirmationV4&XML=<DeliveryConfirmationV4.0Request USERID=\"$userName\">
	<Option>1</Option>
	<ImageParameters />
	<FromName>$FromName</FromName>
	<FromFirm />
	<FromAddress1>$FromAddress2</FromAddress1>
	<FromAddress2>$FromAddress</FromAddress2>
	<FromCity>$FromCity</FromCity>
	<FromState>$FromState</FromState>
	<FromZip5>$FromZip5</FromZip5>
	<FromZip4 />
	<ToName>$ToName</ToName>
	<ToFirm />
	<ToAddress1>$ToAddress2</ToAddress1>
	<ToAddress2>$ToAddress</ToAddress2>
	<ToCity>$ToCity</ToCity>
	<ToState>$ToState</ToState>
	<ToZip5>$ToZip5</ToZip5>
	<ToZip4 />
	<WeightInOunces>$weightOunces</WeightInOunces>
	<ServiceType>$service_code</ServiceType>
	<POZipCode/>
	<ImageType>PDF</ImageType>
	<LabelDate />
	</DeliveryConfirmationV4.0Request>";
	
	//return $data;
	// send the POST values to USPS
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$result=curl_exec ($ch);
	
	//$data = strstr($result, '<?');
	// echo '<!-- '. $data. ' -->'; // Uncomment to show XML in comments
	
	
	$xmlParser = new uspsxmlParser();
	$fromUSPS = $xmlParser->xmlparser($result);
	$fromUSPS = $xmlParser->getData();
	
	curl_close($ch);
	
	return $fromUSPS;
}

function USPSExpressLabel($username, $from, $to, $ounces, $service_code) {

	$userName = $username; // Your USPS Username
	$FromName = isset($from['name']) ? $from['name'] : '';
	$FromAddress = $from['address'];
	$FromAddress2 = $from['address2'];
	$FromCity = $from['city'];
	$FromState = $from['state'];
	$FromZip5 = $from['zip'];
	$FromPhone = preg_replace("/[^0-9]/", "", $from['phone']);
	$FromFirm = $FromName;
	
	$ToFirstName = isset($to['first_name']) ? $to['first_name'] : '';
	$ToLastName = isset($to['last_name']) ? $to['last_name'] : '';
	$ToAddress = $to['address'];
	$ToAddress2 = $to['address2'];
	$ToCity = $to['city'];
	$ToState = $to['state'];
	$ToZip5 = $to['zip'];
	$ToPhone = preg_replace("/[^0-9]/", "", $to['phone']);
	$ToFirm = $ToFirstName . ' ' . $ToLastName;
	
	$weightOunces = $ounces;
	if(floatval($weightOunces) < .5) $weightOunces = 1;
	
	// =============== DON'T CHANGE BELOW THIS LINE ===============
	
	$url = "https://secure.shippingapis.com/ShippingAPI.dll";
	$ch = curl_init();
	
	// set the target url
	curl_setopt($ch, CURLOPT_URL,$url);
	//curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// parameters to post
	curl_setopt($ch, CURLOPT_POST, true);
	
	$data = "API=ExpressMailLabel&XML=<ExpressMailLabelRequest USERID=\"$userName\">
	<Option>1</Option>
	<ImageParameters />";
	if (isset($from['first_name']) && isset($from['last_name'])) {
        $data .= "<FromFirstName>" . $from['first_name'] . "</FromFirstName>
        <FromLastName>" . $from['last_name'] . "</FromLastName>
        <FromFirm/>";
	}
	else {
		$data .= "
        <FromFirstName/>
        <FromLastName/>
        <FromFirm>$FromFirm</FromFirm>";
	}
	
	$data .= "
	<FromAddress1>$FromAddress2</FromAddress1>
	<FromAddress2>$FromAddress</FromAddress2>
	<FromCity>$FromCity</FromCity>
	<FromState>$FromState</FromState>
	<FromZip5>$FromZip5</FromZip5>
	<FromZip4 />
	<FromPhone>$FromPhone</FromPhone>";
	
	if (isset($to['name'])) {
		$data .= "
	<ToFirstName/>
	<ToLastName/>
	<ToFirm>" . $to['name'] . "</ToFirm>";
	}
	else {
		$data .= "
		<ToFirstName>$ToFirstName</ToFirstName>
		<ToLastName>$ToLastName</ToLastName>
		<ToFirm></ToFirm>";
	}
	
	$data .= "
	<ToAddress1>$ToAddress2</ToAddress1>
	<ToAddress2>$ToAddress</ToAddress2>
	<ToCity>$ToCity</ToCity>
	<ToState>$ToState</ToState>
	<ToZip5>$ToZip5</ToZip5>
	<ToZip4 />
	<ToPhone>$ToPhone</ToPhone>
	<WeightInOunces>$weightOunces</WeightInOunces>
	<ShipDate/>
	<FlatRate/>
	<SundayHolidayDelivery/>
	<StandardizeAddress/>
	<WaiverOfSignature/>
	<NoHoliday/>
	<NoWeekend/>
	<SeparateReceiptPage/>
	<POZipCode/>
	<ImageType>PDF</ImageType>
	<LabelDate />
	</ExpressMailLabelRequest>";
	
	//return $data;
	// send the POST values to USPS
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$result=curl_exec ($ch);
	
	//$data = strstr($result, '<?');
	// echo '<!-- '. $data. ' -->'; // Uncomment to show XML in comments
	
	
	$xmlParser = new uspsxmlParser();
	$fromUSPS = $xmlParser->xmlparser($result);
	$fromUSPS = $xmlParser->getData();
	
	curl_close($ch);
	
	return $fromUSPS;
}

// INTERNATIONAL
function USPSIntlExpressLabel($username, $from, $to, $items, $ounces, $service_code) {

	$userName = $username; // Your USPS Username
	$FromName = $from['name'];
	$FromAddress = $from['address'];
	$FromAddress2 = $from['address2'];
	$FromCity = $from['city'];
	$FromState = $from['state'];
	$FromZip5 = $from['zip'];
	$FromPhone = preg_replace("/[^0-9]/", "", $from['phone']);
	$FromFirm = $FromName;
	
	$ToFirstName = $to['first_name'];
	$ToLastName = $to['last_name'];
	$ToAddress = $to['address'];
	$ToAddress2 = $to['address2'];
	$ToCity = $to['city'];
	$ToCountry = $to['country'];
	$ToZip5 = $to['zip'];
	$ToPhone = preg_replace("/[^0-9]/", "", $to['phone']);
	$ToFirm = $ToFirstName . ' ' . $ToLastName;
	
	$weightOunces = $ounces;
	if(floatval($weightOunces) < .5) $weightOunces = 1;
	
	// =============== DON'T CHANGE BELOW THIS LINE ===============
	
	$url = "https://secure.shippingapis.com/ShippingAPI.dll";
	$ch = curl_init();
	
	// set the target url
	curl_setopt($ch, CURLOPT_URL,$url);
	//curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// parameters to post
	curl_setopt($ch, CURLOPT_POST, true);
	
	$data = "API=ExpressMailIntl&XML=<ExpressMailIntlRequest USERID=\"$userName\">
	<ImageParameters/>
	<FromFirstName/>
	<FromLastName/>
	<FromFirm>$FromFirm</FromFirm>
	<FromAddress1>$FromAddress2</FromAddress1>
	<FromAddress2>$FromAddress</FromAddress2>
	<FromCity>$FromCity</FromCity>
	<FromState>$FromState</FromState>
	<FromZip5>$FromZip5</FromZip5>
	<FromZip4 />
	<FromPhone>$FromPhone</FromPhone>
	<ToFirstName>$ToFirstName</ToFirstName>
	<ToLastName>$ToLastName</ToLastName>
	<ToFirm></ToFirm>
	<ToAddress1>$ToAddress2</ToAddress1>
	<ToAddress2>$ToAddress</ToAddress2>
	<ToCity>$ToCity</ToCity>
	<ToProvince/>
	<ToCountry>$ToCountry</ToCountry>
	<ToPostalCode>$ToZip5</ToPostalCode>
	<ToPOBoxFlag>N</ToPOBoxFlag>
	<ToPhone>$ToPhone</ToPhone>
	<ToFax/>
	<ToEmail/>
	<ToCustomsReference/>
	<NonDeliveryOption>RETURN</NonDeliveryOption>
	<AltReturnAddress1/>
	<AltReturnAddress2/>
	<AltReturnAddress3/>
	<AltReturnAddress4/>
	<AltReturnAddress5/>
	<AltReturnAddress6/>
	<AltReturnCountry/>
	<Container/>
	 <ShippingContents>";
	 $gross_ounces = 0;
	 foreach ($items as $item) {
		 $gross_ounces += $item['product_weight'];
		$pounds = floor($item['product_weight'] / 16);
		//$pounds = min(1, $pounds);
		 $ounces = $item['product_weight'] % 16;
		 
		$data .= "
      <ItemDetail>
            <Description>" . $item['product'] . "</Description>
            <Quantity>" . $item['quantity'] . "</Quantity>
            <Value>" . $item['quantity'] * $item['product_price'] . "</Value>
            <NetPounds>" . $pounds . "</NetPounds>
            <NetOunces>" . $ounces . "</NetOunces>
            <HSTariffNumber/>
            <CountryOfOrigin/>
      </ItemDetail>";
	 }

	$pounds = floor($gross_ounces / 16);
		//$pounds = min(1, $pounds);
		 $ounces = $gross_ounces % 16;

     $data .="
  </ShippingContents>
	<InsuredNumber/>
	<InsuredAmount/>
	<Postage/>
	<GrossPounds>" . $pounds . "</GrossPounds>
	<GrossOunces>" . $ounces . "</GrossOunces>
	<ContentType>MERCHANDISE</ContentType>
	<ContentTypeOther/>
	<Agreement>Y</Agreement>
	<Comments></Comments>
	<ImageType>PDF</ImageType>
	<ImageLayout>ALLINONEFILE</ImageLayout>
	<LabelDate />
	</ExpressMailIntlRequest>";
	
	//return $data;
	// send the POST values to USPS
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$result=curl_exec ($ch);
	
	//$data = strstr($result, '<?');
	// echo '<!-- '. $data. ' -->'; // Uncomment to show XML in comments
	
	
	$xmlParser = new uspsxmlParser();
	$fromUSPS = $xmlParser->xmlparser($result);
	$fromUSPS = $xmlParser->getData();
	
	curl_close($ch);
	
	return $fromUSPS;
}

function USPSIntlPriorityLabel($username, $from, $to, $items, $ounces, $service_code) {

	$userName = $username; // Your USPS Username
	$FromName = $from['name'];
	$FromAddress = $from['address'];
	$FromAddress2 = $from['address2'];
	$FromCity = $from['city'];
	$FromState = $from['state'];
	$FromZip5 = $from['zip'];
	$FromPhone = preg_replace("/[^0-9]/", "", $from['phone']);
	$FromFirm = $FromName;
	
	$ToFirstName = $to['first_name'];
	$ToLastName = $to['last_name'];
	$ToAddress = $to['address'];
	$ToAddress2 = $to['address2'];
	$ToCity = $to['city'];
	$ToCountry = $to['country'];
	$ToZip5 = $to['zip'];
	$ToPhone = preg_replace("/[^0-9]/", "", $to['phone']);
	$ToFirm = $ToFirstName . ' ' . $ToLastName;
	
	$weightOunces = $ounces;
	if(floatval($weightOunces) < .5) $weightOunces = 1;
	
	// =============== DON'T CHANGE BELOW THIS LINE ===============
	
	$url = "https://secure.shippingapis.com/ShippingAPI.dll";
	$ch = curl_init();
	
	// set the target url
	curl_setopt($ch, CURLOPT_URL,$url);
	//curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// parameters to post
	curl_setopt($ch, CURLOPT_POST, true);
	
	$data = "API=PriorityMailIntl&XML=<PriorityMailIntlRequest USERID=\"$userName\">
	<ImageParameters/>
	<FromFirstName/>
	<FromLastName/>
	<FromFirm>$FromFirm</FromFirm>
	<FromAddress1>$FromAddress2</FromAddress1>
	<FromAddress2>$FromAddress</FromAddress2>
	<FromCity>$FromCity</FromCity>
	<FromState>$FromState</FromState>
	<FromZip5>$FromZip5</FromZip5>
	<FromZip4/>
	<FromPhone>$FromPhone</FromPhone>
	<ToFirstName>$ToFirstName</ToFirstName>
	<ToLastName>$ToLastName</ToLastName>
	<ToFirm></ToFirm>
	<ToAddress1>$ToAddress2</ToAddress1>
	<ToAddress2>$ToAddress</ToAddress2>
	<ToCity>$ToCity</ToCity>
	<ToProvince/>
	<ToCountry>$ToCountry</ToCountry>
	<ToPostalCode>$ToZip5</ToPostalCode>
	<ToPOBoxFlag>N</ToPOBoxFlag>
	<ToPhone>$ToPhone</ToPhone>
	<ToFax/>
	<ToEmail/>
	<ToCustomsReference/>
	<NonDeliveryOption>RETURN</NonDeliveryOption>
	<AltReturnAddress1/>
	<AltReturnAddress2/>
	<AltReturnAddress3/>
	<AltReturnAddress4/>
	<AltReturnAddress5/>
	<AltReturnAddress6/>
	<AltReturnCountry/>
	<Container/>
	 <ShippingContents>";
	 $gross_ounces = 0;
	 foreach ($items as $item) {
		 $gross_ounces += $item['product_weight'];
		$pounds = floor($item['product_weight'] / 16);
		//$pounds = min(1, $pounds);
		 $ounces = $item['product_weight'] % 16;
		 
		$data .= "
      <ItemDetail>
            <Description>" . $item['product'] . "</Description>
            <Quantity>" . $item['quantity'] . "</Quantity>
            <Value>" . $item['quantity'] * $item['product_price'] . "</Value>
            <NetPounds>" . $pounds . "</NetPounds>
            <NetOunces>" . $ounces . "</NetOunces>
            <HSTariffNumber/>
            <CountryOfOrigin/>
      </ItemDetail>";
	 }

	$pounds = floor($gross_ounces / 16);
		//$pounds = min(1, $pounds);
		 $ounces = $gross_ounces % 16;

     $data .="
  </ShippingContents>
	<InsuredNumber/>
	<InsuredAmount/>
	<Postage/>
	<GrossPounds>" . $pounds . "</GrossPounds>
	<GrossOunces>" . $ounces . "</GrossOunces>
	<ContentType>MERCHANDISE</ContentType>
	<ContentTypeOther/>
	<Agreement>Y</Agreement>
	<Comments></Comments>
	<ImageType>PDF</ImageType>
	<ImageLayout>ALLINONEFILE</ImageLayout>
	<LabelDate />
	</PriorityMailIntlRequest>";
	
	//return $data;
	// send the POST values to USPS
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$result=curl_exec ($ch);
	
	//$data = strstr($result, '<?');
	// echo '<!-- '. $data. ' -->'; // Uncomment to show XML in comments
	
	
	$xmlParser = new uspsxmlParser();
	$fromUSPS = $xmlParser->xmlparser($result);
	$fromUSPS = $xmlParser->getData();
	
	curl_close($ch);
	
	return $fromUSPS;
}

function USPSIntlFirstClassLabel($username, $from, $to, $items, $ounces, $service_code) {

	$userName = $username; // Your USPS Username
	$FromName = $from['name'];
	$FromAddress = $from['address'];
	$FromAddress2 = $from['address2'];
	$FromCity = $from['city'];
	$FromState = $from['state'];
	$FromZip5 = $from['zip'];
	$FromPhone = preg_replace("/[^0-9]/", "", $from['phone']);
	$FromFirm = $FromName;
	
	$ToFirstName = $to['first_name'];
	$ToLastName = $to['last_name'];
	$ToAddress = $to['address'];
	$ToAddress2 = $to['address2'];
	$ToCity = $to['city'];
	$ToCountry = $to['country'];
	$ToZip5 = $to['zip'];
	$ToPhone = preg_replace("/[^0-9]/", "", $to['phone']);
	$ToFirm = $ToFirstName . ' ' . $ToLastName;
	
	$weightOunces = $ounces;
	if(floatval($weightOunces) < .5) $weightOunces = 1;
	
	// =============== DON'T CHANGE BELOW THIS LINE ===============
	
	$url = "https://secure.shippingapis.com/ShippingAPI.dll";
	$ch = curl_init();
	
	// set the target url
	curl_setopt($ch, CURLOPT_URL,$url);
	//curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	
	// parameters to post
	curl_setopt($ch, CURLOPT_POST, true);
	
	$data = "API=FirstClassMailIntl&XML=<FirstClassMailIntlRequest USERID=\"$userName\">
	<ImageParameters/>
	<FromFirstName/>
	<FromLastName/>
	<FromFirm>$FromFirm</FromFirm>
	<FromAddress1>$FromAddress2</FromAddress1>
	<FromAddress2>$FromAddress</FromAddress2>
	<FromUrbanization/>
	<FromCity>$FromCity</FromCity>
	<FromState>$FromState</FromState>
	<FromZip5>$FromZip5</FromZip5>
	<FromZip4/>
	<FromPhone>$FromPhone</FromPhone>
	<ToFirstName>$ToFirstName</ToFirstName>
	<ToLastName>$ToLastName</ToLastName>
	<ToFirm></ToFirm>
	<ToAddress1>$ToAddress2</ToAddress1>
	<ToAddress2>$ToAddress</ToAddress2>
	<ToCity>$ToCity</ToCity>
	<ToProvince/>
	<ToCountry>$ToCountry</ToCountry>
	<ToPostalCode>$ToZip5</ToPostalCode>
	<ToPOBoxFlag>N</ToPOBoxFlag>
	<ToPhone>$ToPhone</ToPhone>
	<ToFax/>
	<ToEmail/>
	<FirstClassMailType>PARCEL</FirstClassMailType>
	<ShippingContents>";
	 $gross_ounces = 0;
	 foreach ($items as $item) {
		 $gross_ounces += $item['product_weight'];
		$pounds = floor($item['product_weight'] / 16);
		//$pounds = min(1, $pounds);
		 $ounces = $item['product_weight'] % 16;
		 
		$data .= "
      <ItemDetail>
            <Description>" . $item['product'] . "</Description>
            <Quantity>" . $item['quantity'] . "</Quantity>
            <Value>" . $item['quantity'] * $item['product_price'] . "</Value>
            <NetPounds>" . $pounds . "</NetPounds>
            <NetOunces>" . $ounces . "</NetOunces>
            <HSTariffNumber/>
            <CountryOfOrigin/>
      </ItemDetail>";
	 }

	$pounds = floor($gross_ounces / 16);
		//$pounds = min(1, $pounds);
		 $ounces = $gross_ounces % 16;

     $data .="
  </ShippingContents>
  
	<Postage/>
	<GrossPounds>" . $pounds . "</GrossPounds>
	<GrossOunces>" . $ounces . "</GrossOunces>
	<Machinable/>
	<ContentType>MERCHANDISE</ContentType>
	<ContentTypeOther/>
	<Agreement>Y</Agreement>
	<Comments></Comments>
	<LicenseNumber/>
	<CertificateNumber/>
	<InvoiceNumber/>
	<ImageType>PDF</ImageType>
	<ImageLayout>ALLINONEFILE</ImageLayout>
	<CustomerRefNo/>
	<LabelDate/>
	<HoldForManifest/>
	<EELPFC/>
	</FirstClassMailIntlRequest>";
	
	//return $data;
	// send the POST values to USPS
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	
	$result = curl_exec ($ch);
	
	//$data = strstr($result, '<?');
	// echo '<!-- '. $data. ' -->'; // Uncomment to show XML in comments
	
	
	$xmlParser = new uspsxmlParser();
	$fromUSPS = $xmlParser->xmlparser($result);
	$fromUSPS = $xmlParser->getData();
	
	curl_close($ch);
	
	return $fromUSPS;
}


class uspsxmlParser {

	var $params = array(); //Stores the object representation of XML data
	var $root = NULL;
	var $global_index = -1;
	var $fold = false;
	
	/* Constructor for the class
	* Takes in XML data as input( do not include the <xml> tag
	*/
	function xmlparser($input, $xmlParams=array(XML_OPTION_CASE_FOLDING => 0)) {
		$xmlp = xml_parser_create();
			foreach($xmlParams as $opt => $optVal) {
				switch( $opt ) {
				case XML_OPTION_CASE_FOLDING:
					$this->fold = $optVal;
				break;
				default:
				break;
				}
				xml_parser_set_option($xmlp, $opt, $optVal);
		}
	
		if(xml_parse_into_struct($xmlp, $input, $vals, $index)) {
			$this->root = $this->_foldCase($vals[0]['tag']);
			$this->params = $this->xml2ary($vals);
		}
		xml_parser_free($xmlp);
	}
	
	function _foldCase($arg) {
		return( $this->fold ? strtoupper($arg) : $arg);
	}
	
	/*
	 * Credits for the structure of this function
	 * http://mysrc.blogspot.com/2007/02/php-xml-to-array-and-backwards.html
	 *
	 * Adapted by Ropu - 05/23/2007
	 *
	*/
	
	function xml2ary($vals) {
	
		$mnary=array();
		$ary=&$mnary;
		foreach ($vals as $r) {
			$t=$r['tag'];
			if ($r['type']=='open') {
				if (isset($ary[$t]) && !empty($ary[$t])) {
					if (isset($ary[$t][0])){
						$ary[$t][]=array();
					} else {
						$ary[$t]=array($ary[$t], array());
					}
					$cv=&$ary[$t][count($ary[$t])-1];
				} else {
					$cv=&$ary[$t];
				}
				$cv=array();
				if (isset($r['attributes'])) {
					foreach ($r['attributes'] as $k=>$v) {
					$cv[$k]=$v;
					}
				}
	
				$cv['_p']=&$ary;
				$ary=&$cv;
	
				} else if ($r['type']=='complete') {
					if (isset($ary[$t]) && !empty($ary[$t])) { // same as open
						if (isset($ary[$t][0])) {
							$ary[$t][]=array();
						} else {
							$ary[$t]=array($ary[$t], array());
						}
					$cv=&$ary[$t][count($ary[$t])-1];
				} else {
					$cv=&$ary[$t];
				}
				if (isset($r['attributes'])) {
					foreach ($r['attributes'] as $k=>$v) {
						$cv[$k]=$v;
					}
				}
				$cv['VALUE'] = (isset($r['value']) ? $r['value'] : '');
	
				} elseif ($r['type']=='close') {
					$ary=&$ary['_p'];
				}
		}
	
		$this->_del_p($mnary);
		return $mnary;
	}
	
	// _Internal: Remove recursion in result array
	function _del_p(&$ary) {
		foreach ($ary as $k=>$v) {
		if ($k==='_p') {
			  unset($ary[$k]);
			}
			else if(is_array($ary[$k])) {
			  $this->_del_p($ary[$k]);
			}
		}
	}
	
	/* Returns the root of the XML data */
	function GetRoot() {
	  return $this->root;
	}
	
	/* Returns the array representing the XML data */
	function GetData() {
	  return $this->params;
	}
}
?>