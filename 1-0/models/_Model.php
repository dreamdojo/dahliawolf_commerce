<?
// Base Model utility properties and methods
class _Model extends Jk_Base{
	public static $domain;
	public static $site_name;
	public static $time;
	public static $date;
	public static $date_time;
	public static $year;
	public static $site_key; // Key for SESSION vars
	public static $config;
	public static $Status_Code;
	public static $Exception_Helper;
	
	protected static $dbs = array();
    protected $logger;

    protected $db_host = '';
	protected $db_name = '';
    protected $db_user = '';
    protected $db_password = '';


    protected $errors =null;

    static protected $data_tables;
    static protected $primary_fields;

    public static function setDataTable($table)
    {
        if(!is_array(self::$data_tables)) self::$data_tables = array();
        $called_class = get_called_class();
        self::$data_tables[$called_class] = $table;
    }

    public static function getDataTable()
    {
        if(!is_array(self::$data_tables)) self::$data_tables = array();
        $called_class = get_called_class();
        return self::$data_tables[$called_class];
    }


    public static function setPrimaryField($pf)
    {
        if(!is_array(self::$primary_fields)) self::$primary_fields = array();
        $called_class = get_called_class();
        self::$primary_fields[$called_class] = $pf;
    }

    public static function getPrimaryField()
    {
        if(!is_array(self::$primary_fields)) self::$primary_fields = array();
        $called_class = get_called_class();
        self::$primary_fields[$called_class];
    }


    protected function getDbCredentials()
    {
        $settings = array(
            'host' => $this->db_host,
            'user' => $this->db_user,
            'password' => $this->db_password,
            'db_name' => $this->db_name,
        );

        return $settings;
    }
	
	public function __construct($db_host = DB_API_HOST, $db_user = DB_API_USER, $db_password = DB_API_PASSWORD, $db_name = DB_API_DATABASE)
    {
        $this->db_host = $db_host;
        $this->db_user = $db_user;
        $this->db_password = $db_password;
        $this->db_name = $db_name;
		
		if (class_exists('Database_Helper') 
			&& (empty(self::$dbs[$db_host]) 
			|| empty(self::$dbs[$db_host][$db_name]))
		){
		
			if (empty(self::$dbs[$db_host])) {
				self::$dbs[$db_host] = array();
			}
			
			$settings = array(
                'host' => $db_host,
                'user' => $db_user,
                'password' => $db_password,
                'db_name' => $db_name,
			);
			self::$dbs[$db_host][$db_name] = new Database_Helper();
			self::$dbs[$db_host][$db_name]->open_connection($settings);
			
		}
		
		// Date & time
		if (empty(self::$time)) {
			self::$time = time();
			self::$date = date('Y-m-d', self::$time);
			self::$date_time = date('Y-m-d H:i:s', self::$time);
			self::$year = date('Y', self::$time);
			self::$Status_Code = new Status_Code();
			self::$Exception_Helper = new Exception_Helper();
		}
		
	}

    public function get_datetime() {
    		return date('Y-m-d H:i:s', time());
    }


    protected function addError($key, $msg)
    {
        if(!$this->errors) $this->errors = array();
        $this->errors[$key] = $msg;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function hasError()
    {
        return (@count($this->errors)>0);
    }
	
	public function __destruct() {
		/*
		foreach (self::$dbs as $host => $connections) {
			foreach ($connections as $db_name => $db_helper) {
				self::$dbs[$host][$db_name]->close_connection();
			}
		}
		*/
	}
	
	public function set_static_vars($vars = array()) {
		if (!empty($vars)) {
			foreach ($vars as $key => $value) {
				$this->set_static_var($key, $value);
			}
			
			if (!empty($vars['domain'])) {
				$this->set_static_var('domain', $_SERVER['SCRIPT_FILENAME']);
			}
		}
	}
	
	private function set_static_var($var, $value) {
		if (!isset(self::$$var)) {
			self::$$var = $value;
		}
	}
	
	protected function load($model) {		
		if (!class_exists($model, false)) {
			require_once __DIR__ . '/' . $model . '.php';
		}
	}
	
	public function db_update($fields, $where_sql = false, $where_values = false, $operators = false) {
		$called_class = get_called_class();
		
		return self::$dbs[$this->db_host][$this->db_name]->update($called_class::TABLE, $fields, $where_sql, $where_values, $operators);
	}
	
	public function db_insert($fields, $replace = false, $ignore=true) {
		$called_class = get_called_class();
        $data_table = ( self::getDataTable() ? self::getDataTable() : $called_class::TABLE);

		return self::$dbs[$this->db_host][$this->db_name]->insert($data_table, $fields, $replace, $ignore);
	}
	
	public function db_insert_many($value_lists) {		
		$called_class = get_called_class();
        $data_table = ( self::getDataTable() ? self::getDataTable() : $called_class::TABLE);
		
		return self::$dbs[$this->db_host][$this->db_name]->insert_many($data_table, $value_lists);
	}
	
	public function db_delete($where_sql, $parameters) {
		$called_class = get_called_class();
        $data_table = ( self::getDataTable() ? self::getDataTable() : $called_class::TABLE);

		return self::$dbs[$this->db_host][$this->db_name]->delete($data_table, $where_sql, $parameters);
	}
	
	public function db_last_insert_id() {
        return self::$dbs[$this->db_host][$this->db_name]->last_insert_id();
	}
	
	public function db_row_count() {
		return self::$dbs[$this->db_host][$this->db_name]->row_count();
	}
	
	protected function do_db_save($values, $info, $has_date_modified = true, $ignore=true)
    {
		$called_class = get_called_class();
		$key_field = self::getPrimaryField() ? self::getPrimaryField() : $called_class::PRIMARY_KEY_FIELD;
        $logger = new Jk_Logger(APP_PATH.'logs/db_inserts.log');

		// Update
		if (!empty($info[$key_field]) && is_numeric($info[$key_field])) {
			$where_values = array(
				':' . $key_field => $info[$key_field]
			);
			$this->db_update($values, $key_field . ' = :' . $key_field, $where_values);

            //$logger->LogInfo("do_db_save: update");
			
			return $this->db_row_count() ? $info[$key_field] : NULL;
		}
		// Insert
		else {
            //$logger->LogInfo("do_db_save: insert");

			$this->db_insert($values, false, $ignore);
			$insert_id = $this->db_last_insert_id();
			
			return $insert_id;
		}
	}
	
	public function get_public_fields($params = array(), $options = array()) {
		$called_class = get_called_class();
		
		$public_fields = !empty($this->public_fields) ? $this->public_fields : (!empty($this->fields) ? $this->fields : array());
		$options['select_fields'] = array_merge(array($called_class::PRIMARY_KEY_FIELD), $public_fields);
		
		return $this->get_rows($params, $options);
	}
	
	public function get_rows($params = array(), $options = array()) {
		$called_class = get_called_class();
		
		// Default options
        $data_table = ( self::getDataTable() ? self::getDataTable() : $called_class::TABLE);
        $primary_field = ( self::getPrimaryField() ? self::getPrimaryField() : $called_class::PRIMARY_KEY_FIELD);

        $single = !empty($options['single']) ? $options['single'] : false;
		$select_fields = !empty($options['select_fields']) ? $options['select_fields'] : array($data_table . '.*');
		
		// Format select
		$select_str = implode(', ', $select_fields);
		
		$wheres = array();
		$pdo_params = array();
		foreach ($params as $param => $value) {
			if (is_null($value)) {
				array_push($wheres, $param . ' IS NULL');
			}
			else {
				array_push($wheres, $param . ' = :' . $param);
				$pdo_params[':' . $param] = $value;
			}
		}
		$where_str = implode(' AND ', $wheres);

		$sql = '
			SELECT ' . $select_str . '
			FROM ' . $data_table . '
			WHERE ' . (!empty($where_str) ? $where_str : '1') . ' ';
			
		if (!empty($options['order_by_field'])) {
			$sql .= ' ORDER BY ' . $options['order_by_field'];
			if (!empty($options['order_by_desc']) && $options['order_by_desc'] === true) {
				$sql .= ' DESC ';
			}
			else {
				$sql .= ' ASC ';
			}
		}
		else {
			$sql .= ' ORDER BY ' . $primary_field . ' ASC ';
		}
		
		$sql .=  $single ? ' LIMIT 1' : '';
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $pdo_params);
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception($e->getMessage());
		}
		
		return !empty($data) ? ($single ? $data[0] : $data) : array();
	}
	public function get_row($params, $options = array()) {
		$options['single'] = true;
		return $this->get_rows($params, $options);
	}
	
	public function save($info) {
		$called_class = get_called_class();
		
		if (empty($info)) {
			self::$Exception_Helper->server_error_exception('Unable to save empty row to "' . $called_class::TABLE . '".');
		}
		
		$values = array();
		
		$fields = $this->fields;
		
		foreach ($fields as $field) {
			if (array_key_exists($field, $info)) {
				$values[$field] = $info[$field];
			}
		}
		 
		try {
			return $this->do_db_save($values, $info);
			
		} catch(Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to save row to "' . $called_class::TABLE . '". '  . $e->getMessage() . '.');
		}
	}
	
	public function delete_by_primary_key($primary_key) {
		$called_class = get_called_class();
		
		try {
			$params = array(
				':primary_key' => $primary_key
			);
			$this->db_delete($called_class::PRIMARY_KEY_FIELD . ' = :primary_key', $params);
			
			return $this->db_row_count();
			
		} catch(Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to delete row.');
		}
	}
	
	
	public function get_enum_values($field) {
		$called_class = get_called_class();
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->get_enum_values($called_class::TABLE, $field);
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception($e->getMessage());
		}
		
		return $data;
	}
	
	public function get_primary_key_id_by_field_value($field, $value) {
		$called_class = get_called_class();
		
		// Check that field is valid
		$fields = $this->fields;
		array_push($fields, $called_class::PRIMARY_KEY_FIELD);
		if (!in_array($field, $fields)) {
			self::$Exception_Helper->bad_request_exception('Invalid field: ' . $field);
		}
		
		$parameter = ':' . $field;
		$query = '
			SELECT ' . $called_class::PRIMARY_KEY_FIELD . '
			FROM ' . $called_class::TABLE . '
			WHERE ' . $field . ' = ' . $parameter . '
		';
		$values = array(
			$parameter => $value
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception($e->getMessage());
		}
		
		if (!empty($data) && !empty($data[$called_class::PRIMARY_KEY_FIELD])) {
			return $data[$called_class::PRIMARY_KEY_FIELD];
		}
		
		return NULL;
	}


    public function truncateNum ($number, $decimals = 2) {
   		return round(floor($number * 100) / 100, $decimals);
   	}


    public function fetch($sql, $pdo_params)
    {
        return self::query($sql, $pdo_params);
    }


    public function query($sql, $pdo_params)
    {
        try {
            $data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $pdo_params);
            return $data;
        } catch (Exception $e) {
            self::$Exception_Helper->server_error_exception($e->getMessage());
            return null;
        }
        return null;
    }


    protected function trace($m, $general_log=true)
    {
        $m = ( is_array($m) || is_object($m) ?  json_encode($m) : "$m");
        if($this->logger==null) $this->logger = new Jk_Logger(APP_PATH . sprintf('logs/%s.log', ($general_log?'user_log':strtolower(get_class($this)))), Jk_Logger::DEBUG);

        $this->logger->LogInfo($m);
    }

}
?>