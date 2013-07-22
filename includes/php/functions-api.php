<?
if (!function_exists('api_errors_to_array')) {
	function api_errors_to_array($result, $api_call = NULL) {
		$errors = array();
		$the_errors = array();

		if (!empty($result['errors'])) {
			$the_errors = $result['errors'];
		}
		else if (!empty($api_call) && !empty($result['data'][$api_call]['errors'])) {
			$the_errors = $result['data'][$api_call]['errors'];
		}
		else if (!empty($result['data'])) {
			foreach ($result['data'] as $api_call => $info) {
				if (!empty($result['data'][$api_call]['errors'])) {
					$the_errors = array_merge($the_errors, $result['data'][$api_call]['errors']);
				}
			}
		}

		if (!empty($the_errors)) {
			foreach ($the_errors as $index => $error) {
				if ($index === 'input_validation') {
					foreach ($error as $field => $info) {
						array_push($errors, $info['label'] . ' ' . (implode(' and ', $info['errors'])) . '.');
					}
				}
				else {
					array_push($errors, $error);
				}
			}
		}

		return $errors;
	}
}
?>