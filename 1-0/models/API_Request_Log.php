<?
class API_Request_Log extends _Model {
	const TABLE = 'api_request_log';
	const PRIMARY_KEY_FIELD = 'api_request_log_id';
	
	public function save($info) {
		$values = array();
		
		$fields = array(
			'api_key'
			, 'ip_address'
			, 'endpoint'
			, 'protocol'
			, 'calls'
			, 'hmac'
			, 'status_codes'
		);
		
		foreach ($fields as $field) {
			if (array_key_exists($field, $info)) {
				$values[$field] = $info[$field];
			}
		}
		 
		try {
			return $this->do_db_save($values, $info);
			
		} catch(Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to api request.');
		}
		
	}
	
	
}
?>