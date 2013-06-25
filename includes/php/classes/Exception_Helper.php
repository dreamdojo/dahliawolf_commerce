<?
class Exception_Helper {
	
	public static $Status_Code;
	 
	public function __construct() {
		self::$Status_Code = new Status_Code();
	}
	
	// Typically for DB errors
	public function server_error_exception($errors) {
		$status_code = self::$Status_Code->get_status_code_server_error();
		$this->throw_custom_exception($errors, $status_code);
	}
	
	// For deleting or updating a row that doesn't exist
	public function request_failed_exception($errors) {
		$status_code = self::$Status_Code->get_status_code_request_failed();
		$this->throw_custom_exception($errors, $status_code);
	}
	
	// Invalid parameters
	public function bad_request_exception($errors) {
		$status_code = self::$Status_Code->get_status_code_bad_request();
		$this->throw_custom_exception($errors, $status_code);
	}
	
	public function throw_custom_exception($errors, $status_code) {
		$error_str = is_string($errors) ? $errors : '';
		$exception = new Custom_Exception($error_str);
		$errors = is_string($errors) ? array($errors) : $errors;
		$exception->set_errors($errors);
		$exception->set_status_code($status_code);
		
		throw $exception;
	}
	
}

?>