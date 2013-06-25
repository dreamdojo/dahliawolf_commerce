<?
class Status_Code {
	
	private $status_codes = array();
	
	public function __construct() {
		$this->status_codes = array(
			'200' => array(
				'name' => 'OK'
				, 'description' => 'The request has succeeded.'
			)
			, '201' => array(
				'name' => 'Created'
				, 'description' => 'The request has been fulfilled and resulted in a new resource being created.'
			)
			, '204' => array(
				'name' => 'No Content'
				, 'description' => 'The server successfully processed the request, but is not returning any content.'
			)
			, '400' => array(
				'name' => 'Bad Request'
				, 'description' => 'The request could not be understood by the server due to malformed syntax.'
			)
			, '401' => array(
				'name' => 'Unauthorized'
				, 'description' => 'The request requires user authentication.'
			)
			, '403' => array(
				'name' => 'Forbidden'
				, 'description' => 'The server understood the request, but is refusing to fulfill it.'
			)
			, '404' => array(
				'name' => 'Not Found'
				, 'description' => 'The server has not found anything matching the Request-URI.'
			)
			, '419' => array(
				'name' => 'Request Failed'
				, 'description' => 'Parameters were valid, but request failed.'
			)
			, '500' => array(
				'name' => 'Server Error'
				, 'description' => 'The server encountered an unexpected condition which prevented it from fulfilling the request.'
			)
		);
	}
	
	public function get_status_codes() {
		return $this->status_codes;
	}
	
	public function get_status_code_ok() {
		return '200';
	}
	
	public function get_status_code_created() {
		return '201';
	}
	
	public function get_status_code_no_content() {
		return '204';
	}
	
	public function get_status_code_bad_request() {
		return '400';
	}
	
	public function get_status_code_unauthorized() {
		return '401';
	}
	
	public function get_status_code_forbidden() {
		return '403';
	}
	
	public function get_status_code_not_found() {
		return '404';
	}
	
	public function get_status_code_request_failed() {
		return '419';
	}
	
	public function get_status_code_server_error () {
		return '500';
	}
	
}

?>