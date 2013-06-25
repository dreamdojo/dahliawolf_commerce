<?
class Config extends _Model {
	const TABLE = 'config';
	const PRIMARY_KEY_FIELD = 'config_id';
	
	protected $fields = array(
		'config_section_id'
		, 'name'
		, 'clean_name'
		, 'value'
		, 'description'
		, 'type'
		, 'values'
		, 'edit'
		, 'required'
		, 'position'
	);
	
	public function get_value($name) {
		$query = '
			SELECT value
			FROM config
			WHERE name = :name
		';
		$values = array(
			':name' => $name
		);
		
		try {
			$config = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);
			
			if (!empty($config)) {
				return $config['value'];
			}
			
			return NULL;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get config value.');
		}
	}
	
	public function get_configs_by_section($section) {
		$query = '
			SELECT config.name, value
			FROM config
				INNER JOIN config_section ON config.config_section_id = config_section.config_section_id
			WHERE config_section.name = :name
		';
		$values = array(
			':name' => $section
		);
		
		try {
			$config = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);
			
			if (!empty($config)) {
				return rows_to_array($config, 'name', 'value');
			}
			
			return NULL;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get configs by section.');
		}
	}
}
?>