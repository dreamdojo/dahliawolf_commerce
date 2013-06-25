<?
class API_Website_Domain extends _Model {
	const TABLE = 'api_website_domain';
	const PRIMARY_KEY_FIELD = 'api_website_domain_id';
	
	public function save($info) {
		$values = array();
		
		$fields = array(
			'website_id'
			, 'domain'
		);
		
		foreach ($fields as $field) {
			if (array_key_exists($field, $info)) {
				$values[$field] = $info[$field];
			}
		}
		 
		try {
			return $this->do_db_save($values, $info);
			
		} catch(Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to save website domain.');
		}
		
	}
}
?>