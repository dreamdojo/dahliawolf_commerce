<?

/*
Example Input Object
[name] => Input Object
(
	[name] => name
	[value] =>
	[validations] => Array
		(
			[0] => Array
				(
					[rule] => is_min
					[arg_list] => Array
						(
							[0] => 1
						)
				)
		)
)

*/

class Validate {

	public $inputs = array();

	public function __construct() {

	}

	public function add($name, $label, $value) {
		$this->inputs[$name] = new Input($name, $label, $value);

		return $this->inputs[$name];
	}

	public function add_many($input_validations, $source, $require_all = false) {
		if (!empty($input_validations)) {
			foreach ($input_validations as $key => $input_info) {
				$value = array_key_exists($key, $source) ? $source[$key] : NULL;
				$this->add($key, $input_info['label'], $value);

				if (!empty($input_info['rules'])) {
					foreach ($input_info['rules'] as $rule => $arg) {

						if (!$require_all && $rule == 'is_set') {
							continue;
						}

						if (is_null($arg)) {
							$this->inputs[$key]->add_validation($rule);
						}
						else {
							$this->inputs[$key]->add_validation($rule, $arg);
						}
					}
				}

			}
		}
	}
	/*
	public function add_many($input_validations, $source, $require_all = false) {
		if (!empty($input_validations)) {
			foreach ($input_validations as $key => $input_info) {
				if ($require_all || array_key_exists($key, $source)) {
					$value = array_key_exists($key, $source) ? $source[$key] : NULL;
					$this->add($key, $input_info['label'], $value);
					if (!empty($input_info['rules'])) {
						foreach ($input_info['rules'] as $rule => $arg) {
							if (is_null($arg)) {
								$this->inputs[$key]->add_validation($rule);
							}
							else {
								$this->inputs[$key]->add_validation($rule, $arg);
							}
						}
					}
				}
			}
		}
	}
	*/

	/**
    * Validate values
    *
    * @access public
    */
	public function run() {
		$errors = array();

		// Go through arra of Input Objects
		foreach ($this->inputs as $key => $input) {
			$inputErrors = array();

			// Check each inputs validation rules
			foreach ($input->validations as $index => $validation) {
				$blankStr = '';
				if (method_exists($this, $validation['rule'])) {
					$evalString = 'return $this->' . $validation['rule'] . '($input->value';
					if (!empty($validation['arg_list'])) {
						foreach ($validation['arg_list'] as $i => $arg) {
							$evalString .= ", \$validation['arg_list'][" . $i . "]";
						}

						//$evalString .= ', ' . implode(', ', $validation['arg_list']);
					}

					$evalString .= ');';
					$validationResult = eval($evalString);

					// Validateion Failed
					if ($validationResult !== true) {
						log_error('Input Validation Failed: ' . $key . ': ' . $validationResult, 'user');

						array_push($inputErrors, $validationResult);
					}
				}
			}

			if (!empty($inputErrors)) {
				$errors[$key]['label'] = $input->label;
				$errors[$key]['errors'] = $inputErrors;
			}

		}

		if (!empty($errors)) {
			$errors = array(
				'input_validation' => $errors
			);

			$Exception_Helper = new Exception_Helper();
			$Exception_Helper->bad_request_exception($errors);
		}

		return true;


	}

	//----- Validators
	public function is_set($value) {
		if (is_string($value)) {
			$value = trim($value);
		}

		if (empty($value) && !is_numeric($value)) {
			return 'is required';
		}

		return true;
	}

	public function is_not_null($value) {
		if (is_null($value)) {
			return 'cannot be null';
		}

		return true;
	}

	public function is_alpha($str) {
		if (preg_match("/^[a-z]+$/i", $str) || $str == '') {
			return true;
		}
		return 'must be alpha';
	}

	public function is_alpha_space($str) {
		if (preg_match("/^[a-z]+$/i", $str) || $str == '') {
			return true;
		}
		return 'must be alpha or space';
	}

	public function is_alpha_num($str) {
		if (preg_match("/^[a-z0-9]+$/i", $str) || $str == '') {
			return true;
		}
		return 'must be alpha numeric';
	}

	public function is_alpha_num_sym($str) {
		if (preg_match("/^[a-z0-9_.]+$/i", $str) || $str == '') {
			return true;
		}
		return 'must be alpha numeric';
	}

	public function no_spaces($str) {
		if (preg_match("/ /", $str) && $str != '') {
			return 'must not contain spaces';
		}
		return true;
	}

	public function is_len_min($str, $min) {
		if ($str == '' || isset($str[$min - 1])) {
			return true;
		}
		return 'must be a minimum of ' . $min . ' character' . ($min == 1 ? '' : 's');
	}
	public function is_min_len($str, $min) {
		return $this->is_len_min($str, $min);
	}

	public function is_len_max($str, $max) {
		if ($str == '' || isset($str[$max])) {
			return 'must be at most ' . $max . ' character' . ($max == 1 ? '' : 's');
		}
		return true;
	}

	public function is_max_len($str, $max) {
		return $this->is_len_max($str, $max);
	}

	public function is_len($str, $len) {
		if (!isset($str[$len - 1]) || isset($str[$len])) {
			return 'must be ' . $len . ' character' . ($len == 1 ? '' : 's');
		}
		return true;
	}

	public function is_decimal($str, $decimal_places) {
		if ($str == '') {
			return true;
		}

		if ($str != '' && !is_numeric($str)) {
			return 'must be a number';
		}

		$parts = explode('.', $str);

		if (count($parts) > 1 && strlen($parts[1]) > $decimal_places) {
			return 'must be a decimal with ' . $decimal_places . ' decimal places';
		}

		return true;
	}

	public function is_number($str) {
		if ($str != '' && !is_numeric($str)) {
			return 'must be a number';
		}

		return true;
	}

	public function is_positive($str) {
		if (!is_numeric($str) || $str < 0) {
			return 'must be a positive number';
		}

		return true;
	}

	public function is_negative($str) {
		if (!is_numeric($str) || $str > 0) {
			return 'must be a negative number';
		}

		return true;
	}

	public function is_num_min($str, $min) {
		if (!is_numeric($str) || $str < $min) {
			return 'must be a minimum of ' . $min;
		}
		return true;
	}

	public function is_min_num($str, $min) {
		return $this->is_num_min($str, $min);
	}

	public function is_num_max($str, $max) {
		if (!is_numeric($str) || $str > $max) {
			return 'must be at most ' . $max;
		}
		return true;
	}

	public function is_max_num($str, $max) {
		return $this->is_num_max($str, $max);
	}

	public function is_int($str) {
		if ($str != '' && (!is_numeric($str) || strstr($str, '.'))) {
			return 'must be a whole number';
		}

		return true;
	}

	public function is_array($str) {
		if (!is_array($str)) {
			return 'must be a list';
		}

		return true;
	}

	public function is_whole_num($str) {
		return $this->is_int($str);
	}

	public function is_boolean($str) {

		if ($str != '' && (!is_numeric($str) || strstr($str, '.') || ($str != 0 && $str != 1))) {
			return 'must be 0 or 1';
		}

		return true;
	}

	public function is_min_elements($array, $min) {
		if (!is_array($array) || count($array) < $min) {
			return 'must choose at least ' . $min;
		}
		return true;
	}

	public function is_email($str) {
		if (empty($str)) {
			return true;
		}
		if (preg_match("/^[_a-z0-9-]+[\._a-z0-9-\+]*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/i", $str)) {
			return true;
		}


		return 'must be a valid email address';
	}

	public function is_phone($str) {

		return $this->is_int($str);
		/*
		if (preg_match("/^[0-9]{3}-[0-9]{3}-[0-9]{4}$/i", $str) || $str == '') {
			return true;
		}
		return 'must be in ###-###-#### format';
		 *
		 */
	}

	public function is_date($str, $us = false) {
		$regex = $us ? '^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$' : '^[0-9]{4}-[0-9]{2}-[0-9]{2}$';
		if (preg_match('/' . $regex . '/', $str) || $str == '') {
			return true;
		}
		return 'must be in ' . ($us ? 'mm/dd/yyyy' : 'yyyy-mm-dd') . ' format';
	}

	public function is_date_time($str) {
		if (preg_match('/^[0-9]{2}\/[0-9]{2}\/[0-9]{4} [0-9]{2}:[0-9]{2} (a|p){1}m{1}$/', $str) || $str == '') {
			return true;
		}
		return 'must be in mm/dd/yyyy hh:mm am/pm format';
	}

	public function is_time($str) {
		if (preg_match('/^[0-9]{2}:[0-9]{2} (a|p){1}m{1}$/', $str) || $str == '') {
			return true;
		}
		return 'must be in hh:mm am/pm format';
	}

	public function is_url($str) {
		if (filter_var($str, FILTER_VALIDATE_URL) !== false || $str == '') {
			return true;
		}
		return 'must be a valid url';
	}

	public function is_in($str, $enums) {

		if ($str != '' && !in_array($str, $enums)) {
			return 'is invalid';
		}
		return true;
	}

}

?>