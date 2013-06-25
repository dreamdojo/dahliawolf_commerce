<?
class HQ_API {
	private $api_key;
	private $private_key;
	private $api_domain = 'http://api.bidsthatgive.com';
	private $api_url = 'http://api.bidsthatgive.com/1-0';
	
	private $SoapClients = array();
	
	public function __construct($api_key, $private_key) {
		$this->api_key = $api_key;
		$this->private_key = $private_key;
	}
	
	public function rest_api_request($api_service, $calls = array(), $reponse_format = 'json') {
		$api_url = $this->api_url . '/' . $api_service . '.' . $reponse_format;
		
		// Initialize
		$ch = curl_init();
		$this->set_rest_api_request_options($ch, $api_url, $calls);
		
		// Attempt to connect up to 3 times
		$attempts = 0;
		do {
			if ($attempts > 0) {
				sleep(5); // sleep for 5 seconds between retrys
			}
	
			$curlError = '';
			$errorNum = '';
			$result = curl_exec($ch); //execute post and get results
			$curlError = curl_error($ch);
			$errorNum = curl_errno($ch);
			$attempts++;
	
		} while ($curlError != '' && $attempts < 3); 
	
		curl_close($ch);
	
		// Call Failed
		if ($curlError != '') {
			$result = array(
				'errors' => 'Curl Error:' . $curlError
			);
	
			$result = json_encode($result);
		}
	
		return $result; 
	}
	
	private function set_rest_api_request_options(&$ch, $api_url, $calls = array()) {
		curl_setopt($ch, CURLOPT_URL, $api_url);
	
		// Use HTTP POST to send form data
		if (is_array($calls) && !empty($calls)) {
			$rest_request = $this->generate_rest_request($calls);
		
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $rest_request);
		}
		
		// Turning off the server and peer verification(TrustManager Concept).
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	
		// Returns response data instead of TRUE(1)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	}
	
	public function get_hmac($calls) {
		if (is_array($calls) && !empty($calls)) {
			$json_encoded_calls = json_encode($calls);
			$soap_request = array(
				'api_key' => $this->api_key
				, 'calls' => $json_encoded_calls
			);
			
			$param_string = urldecode(http_build_query($soap_request));
			$hmac = hash_hmac('sha256', $param_string, $this->private_key);
			
			return $hmac;
		}
		return NULL;
	}
	
	public function generate_soap_request($calls) {
		return array(
			'api_key' => $this->api_key
			, 'calls' => $calls
			, 'hmac' => $this->get_hmac($calls)
		);
	}
	
	public function generate_rest_request($calls) {
		return 'api_key=' . $this->api_key . '&calls=' . urlencode(json_encode($calls)) . '&hmac=' . $this->get_hmac($calls);
	}
	
	public function rest_api_requests($api_requests, $reponse_format = 'json') {
		$responses = array();
		if (!empty($api_requests)) {
			$handles = array();
			$mh = curl_multi_init();
			foreach ($api_requests as $api_service => $calls) {
				$ch = curl_init();
				$api_url = $this->api_url . '/' . $api_service . '.' . $reponse_format;
				$this->set_rest_api_request_options($ch, $api_url, $calls);
				$handles[$api_service] = $ch;
			}
			
			// multi handle
			$mh = curl_multi_init();
			
			// add handles to multi handle
			foreach ($handles as $handle) {
				curl_multi_add_handle($mh, $handle);
			}
			
			$active = NULL;
			
			/*
			Mainly for creating connections. It does not wait for the full response
			First do-while loop repeatedly calls curl_multi_exec.
			This function is non blocking.
			It executes as little as possible and returns a status value.
			As long as the returned value is the constant CURLM_CALL_MULTI_PERFORM, 
			it means that there is still more immediate work to do.
			That's why we keep calling it until the return value is something else.
			*/
			/*
			do { // Mainly for creating connections. It does not wait for the full response.
				$mrc = curl_multi_exec($mh, $active);
			} while($mrc == CURLM_CALL_MULTI_PERFORM);
			*/
			
			do { // Mainly for creating connections. It does not wait for the full response.
				$mrc = curl_multi_exec($mh, $active);
			} while($mrc == CURLM_CALL_MULTI_PERFORM || $active);
			
			/* 
			In the following while loop, we continue as long as $active
			variable is true. It is set to true as long as there are
			active connections within the multi handle. Next thing is to
			call curl_multi_select. This function is blocking until there is
			any connection activity, such as receiving a response. When that
			happens, we go into yet another do-while loop to continue executing.
			
			*/
			/*
			while ($active && $mrc == CURLM_OK) { // runs as long as there is some activity in the multi handle
				if (curl_multi_select($mh) != -1) { // waits the script until an activity happens with any of the requests
					do { // fetching response data
						$mrc = curl_multi_exec($mh, $active);
					} while($mrc == CURLM_CALL_MULTI_PERFORM);
					
					if ($mh_info = curl_multi_info_read($mh)) {
						$ch_info = curl_multi_getcontent($mh_info['handle']);
						//echo $ch_info;
					}
					
				}
			}
			*/
			foreach ($api_requests as $api_service => $calls) {
				$curl_error = curl_error($ch);
				if ($curl_error != '') {
					$result = array(
						'errors' => 'Curl Error:' . $curlError
					);
			
					$responses[$api_service] = json_encode($result);
				}
				else {
					$responses[$api_service] = curl_multi_getcontent($handles[$api_service]);
				}
				
				curl_multi_remove_handle($mh, $handles[$api_service]);
				/*
				It is always a good idea to use curl_close on all individual 
				curl handles after executing curl_multi_remove_handle. 
				This will free up additional memory resources.
				*/
				// curl_close($handles[$api_service]); // handled with curl_multi_close
			}
			curl_multi_close($mh);
		}
		
		return $responses;
	}
	
	public function soap_api_request($api_service, $calls = array(), $reponse_format = 'xml') {
		try {
			if (empty($this->SoapClients[$api_service])) {
				$this->SoapClients[$api_service] = new SoapClient(
					NULL
					, array(
						'uri' => $this->api_domain,
						'location' => $this->api_url . '/' . $api_service . '.xml',
						'trace' => true
					)
				);
			}
			
			// Set Requests
			$soap_request = $this->generate_soap_request($calls);
			
			// Send Request
			$soap_result = $this->SoapClients[$api_service]->process_request($soap_request);
			
			// XML Request
			$soap_xml_request = $this->SoapClients[$api_service]->__getLastRequest();
			
			// XML Response
			$soap_xml_response = $this->SoapClients[$api_service]->__getLastResponse();
			return $soap_xml_response;
		} catch (Exception $e) {
			$soap_xml_request = $this->SoapClients[$api_service]->__getLastRequest();
			
			return 'SOAP Error: ' . $e->getMessage();
		}
	}
}
?>