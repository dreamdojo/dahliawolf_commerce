<?
/**
 * API_Website description goes here
 *
 * @package API_Website
 */
class API_Website_Controller extends _Controller {
	
	public function __construct() {
		
		parent::__construct();
		
		$this->Validate = new Validate();
		
	}
	
	/**
	 * Save a api_website
	 *
	 * If creating a new api_website, also creates the API credentials.
	 *
	 * @param array $api_website API_Website details.
	 * @param int $api_website[api_website_id] (NULL) API_Website id.
	 * @param int $api_website[customer_id] (NULL) {1} Customer id.
	 * @param string $api_website[name] (NULL) {'Example api_website'} API_Website name.
	 * @param int $api_website[description] (NULL) {'description'} Description.
	 * @param bool $return_object (false) {true} Flag to return api_website details.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array API_Website id and api_website details if $return_object is true.
	 */
	public function save_api_website($params = array()) {
		$this->load('API_Website');
		$this->load('API_Credential');
		
		// Set parameters
		$info = !empty($params['api_website']) ? $params['api_website'] : array();
		$return_object = !empty($params['return_object']) && $params['return_object'] == true ? true : false;
		
		$data = array();
		
		/*
		Convert blank values to null. 
		For now, going keep it this way until we find a better solution
		*/
		array_walk_recursive($info, array($this, 'convert_null_value'));
		
		// Validation
		$require_all = !array_key_exists(API_Website::PRIMARY_KEY_FIELD, $info);
	
		$input_validations = array(
			'customer_id' => array(
				'label' => 'Customer'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_whole_num' => NULL
				)
			)
			, 'name' => array(
				'label' => 'Name'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
		);
		
		$this->Validate->add_many($input_validations, $info, $require_all);
		
		$this->Validate->run();
		
		// Validate is good, create api_website
		$data[API_Website::PRIMARY_KEY_FIELD] = $this->API_Website->save($info);
		if (empty($data[API_Website::PRIMARY_KEY_FIELD])) {
			_Model::$Exception_Helper->request_failed_exception('API_Website does not exist.');
		}
		
		// Create API keys
		if (empty($info[API_Website::PRIMARY_KEY_FIELD])) {
			$this->API_Credential->generate_developer_key($data[API_Website::PRIMARY_KEY_FIELD]);
			$this->API_Credential->generate_production_key($data[API_Website::PRIMARY_KEY_FIELD]);
		}
		
		// Return Object
		if ($return_object) {
			$data['api_website'] = $this->API_Website->get_row(array('api_website.api_website_id' => $data[API_Website::PRIMARY_KEY_FIELD]));
		}
		
		return static::wrap_result(true, $data, $this->get_status_code($info, $data, API_Website::PRIMARY_KEY_FIELD));
	}
	
	/**
	 * Save a api_website domain
	 *
	 * @param array $api_website_domain API_Website domain details.
	 * @param int $api_website_domain[api_website_domain_id] (NULL) API_Website Domain id.
	 * @param int $api_website_domain[api_website_id] (NULL) {1} API_Website id.
	 * @param string $api_website_domain[domain] (NULL) {'www.example.com'} Domain name.
	 * @param bool $return_object (false) {false} Flag to return api_website domain details.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array API_Website domain id and api_website domain details if $return_object is true.
	 */
	public function save_domain($params = array()) {
		$this->load('API_Website_Domain');
		
		// Set parameters
		$info = !empty($params['api_website_domain']) ? $params['api_website_domain'] : array();
		$return_object = !empty($params['return_object']) && $params['return_object'] == true ? true : false;
		
		$data = array();
		
		/*
		Convert blank values to null. 
		For now, going keep it this way until we find a better solution
		*/
		array_walk_recursive($info, array($this, 'convert_null_value'));
		
		// Validation
		$require_all = !array_key_exists(API_Website_Domain::PRIMARY_KEY_FIELD, $info);
	
		$input_validations = array(
			'api_website_id' => array(
				'label' => 'API_Website'
				, 'rules' => array(
					'is_set' => NULL
					, 'is_whole_num' => NULL
				)
			)
			, 'domain' => array(
				'label' => 'Domain'
				, 'rules' => array(
					'is_set' => NULL
				)
			)
			
		);
		
		$this->Validate->add_many($input_validations, $info, $require_all);
		
		$this->Validate->run();
		
		// Validate is good, create api_website
		$data[API_Website_Domain::PRIMARY_KEY_FIELD] = $this->API_Website_Domain->save($info);
		if (empty($data[API_Website_Domain::PRIMARY_KEY_FIELD])) {
			_Model::$Exception_Helper->request_failed_exception('API_Website Domain does not exist.');
		}
		
		// Return Object
		if ($return_object) {
			$data['api_website_domain'] = $this->API_Website_Domain->get_row(array('api_website_domain.api_website_domain_id' => $data[API_Website_Domain::PRIMARY_KEY_FIELD]));
		}
		
		return static::wrap_result(true, $data, $this->get_status_code($info, $data, API_Website_Domain::PRIMARY_KEY_FIELD));
	}
	
}
?>