<?php
function resultArray($success, $result, $errors = NULL) {
	$ret = array(
		'success' => ($success == TRUE ? TRUE : FALSE)
		, 'data' => $result
		, 'errors' => !empty($errors) ? (is_string($errors) ? array($errors) : $errors) : NULL
	);
	return $ret;
}

function outputResult($success, $result, $errors = NULL) {
	$resultArray = resultArray(
		$success
		, $result
		, $errors
	);
	
	outputResultArray($resultArray);
}

function outputResultArray($resultArray) {
	echo json_encode($resultArray);
	
	die();
}

function hq_api_request($service, $calls, $return_array = false) {
	if (!class_exists('HQ_API', false)) {
		require $_SERVER['DOCUMENT_ROOT'] . '/lib/php/HQ_API.php';
	}
	
	// Instantiate library helper
	$hq_api = new HQ_API(HQ_API_KEY_DEVELOPER, HQ_PRIVATE_KEY_DEVELOPER);
	
	// Make request
	$result = $hq_api->rest_api_request($service, $calls);
	
	if (!$return_array) {
		return $result;
	}
	
	$decoded = json_decode($result, true);
	if ($decoded) {
		return $decoded;
	}
	echo $result;
	return;
}

?>
