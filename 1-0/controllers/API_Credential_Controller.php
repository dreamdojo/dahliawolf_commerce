<?
/**
 * API Credential description goes here
 *
 * @package API Credential
 */
class API_Credential_Controller extends _Controller {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->load('API_Credential');
	}
	
	
	/**
	 * Generate developer key
	 *
	 * Generate developer key and save to database.
	 *
	 * @param int $website_id {6} Website Id.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array API Credential Id.
	 */
	public function generate_developer_key($params = array()) {
		// Set parameters
		$website_id = !empty($params['website_id']) ? $params['website_id'] : NULL;
		
		$data = array();
		
		$data = $this->API_Credential->generate_developer_key($website_id);
		
		return static::wrap_result(true, $data, _Model::$Status_Code->get_status_code_created());
	}
	
	/**
	 * Generate production key
	 *
	 * Generate production key and save to database.
	 *
	 * @param int api_website_id {6} API Website Id.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array API Credential Id.
	 */
	public function generate_production_key($params = array()) {
		// Set parameters
		$website_id = !empty($params['api_website_id']) ? $params['api_website_id'] : NULL;
		
		$data = array();
		
		$data = $this->API_Credential->generate_production_key($website_id);
		
		return static::wrap_result(true, $data, _Model::$Status_Code->get_status_code_created());
	}
	
	
}
?>