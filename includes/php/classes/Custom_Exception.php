<?
class Custom_Exception extends Exception {
	public $errors = array();
	public $status_code = NULL;
	
	public function set_errors($errors = array()) {
		$this->errors = $errors;
	}
	
	public function get_errors() {
		return $this->errors;
	}
	
	public function set_status_code($status_code = NULL) {
		$this->status_code = $status_code;
	}
	
	public function get_status_code() {
		return $this->status_code;
	}
}
?>