<?
/*

Example syntax for PDO transactions:
	$db->begin_transaction();

	$data = $db->crud('INSERT INTO "site" (customer_id) VALUES (1)');
	
	var_dump($data);
	
	echo $db->last_error;
	
	$data = $db->crud('INSERT INTO "site" () VALUES (');
	
	var_dump($data);echo $db->last_error;
	
	$success = $db->commit_transaction();
	
	var_dump($success);
	
	echo $db->last_error;
*/

class Database_Helper {

	private $pdo;
	private $log_pdo;
	private $sth;
	private $db_name;
	public $last_error;
	private $active_transaction = false;
	private $active_transaction_failed = false;
	private static $Exception_Helper;
	private $sql_log_table = 'db_sql_action_log';
	private $identifier_quote_character = '"';
	private $reserved_words_default = array(
		'NULL'
		, 'CURRENT_TIMESTAMP'
	);
	
	private $trigger_events = array(
		'insert'
		, 'update'
		, 'delete'
	);
	
	private $trigger_timings = array(
		'before'
		, 'after'
	);
	
	private $routine_types = array(
		'PROCEDURE'
		, 'FUNCTION'
	);
	
	/**
	 * Constructor, creates new Exception_Helper()
	 *
	 */
 	public function __construct() {
		//$this->open_connection($host, $user, $password, $db_name);
		self::$Exception_Helper = new Exception_Helper();
	}
	
	/**
	 * Destructor, close connection
	 *
	 */
	public function __destruct() {
		$this->close_connection();
	}
	
	/**
	 * Open connection
	 *
	 * @param array Connection settings.
	 * @param array SQL log connection settings.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function open_connection($settings, $log_settings = NULL) {
		try {
			if (!is_array($settings)) {
				throw new Exception('Database Helper, settings must be an array.');
			}
			
			$host = $settings['host'];
			$user = $settings['user'];
			$password = $settings['password'];
			$db_name = $settings['db_name'];
			$this->db_name = $db_name;
			
			$dsn = 'mysql:dbname=' . $db_name . ';host=' . $host;
			$this->pdo = new PDO(
				$dsn
				, $user
				, $password
				, array(
					//PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'
					/* 
					Updating Mysql table with identical values nothing's
					really affected so rowCount will return 0.
					Change this behaviour with this.
					*/
					PDO::MYSQL_ATTR_FOUND_ROWS => true
				)
				
			);
			
			if (!empty($log_settings)) {
				$log_host = $log_settings['host'];
				$log_user = $log_settings['user'];
				$log_password = $log_settings['password'];
				$log_db_name = $log_settings['db_name'];
				
				$dsn = 'mysql:dbname=' . $log_db_name . ';host=' . $log_host;
				$this->log_pdo = new PDO(
					$dsn
					, $log_user
					, $log_password
					, array(
						PDO::MYSQL_ATTR_FOUND_ROWS => true
					)
					
				);
			}
			
			// Enable ANSI_QUOTES SQL mode to quote identifiers with double quotes
			$this->enable_ANSI_QUOTES_SQL_mode();
			
			return true;
			
		} catch (Exception $e) {
			$error = $e->getMessage();
			$this->last_error = $error;
			$this->error_handler($error);
			self::$Exception_Helper->server_error_exception($error);
		}
		
	}
	
	/**
	 * Check if the connection exists
	 *
	 * @return bool True if connection exists, false otherwise.
	 */
	public function connection_exists() {
		return !empty($this->pdo);
	}
	
	/**
	 * Close connection
	 *
	 */
	public function close_connection() {
		$this->pdo = NULL;
		$this->log_pdo = NULL;
	}
	
	private function check_database_connection($sql = '') {
		if (!$this->connection_exists()) {
			$this->last_error = 'PDO Object is empty.';
			$this->error_handler($this->last_error, $sql);
			self::$Exception_Helper->server_error_exception($this->last_error);
		}
	}
	
	/**
	 * PDO beginTransaction
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function begin_transaction() {
		$this->check_database_connection();
		
		try {
			$this->pdo->beginTransaction();
			$this->active_transaction = true;
			
			return true;
			
		} catch (Exception $e) {
			$error = $e->getMessage();
			$this->last_error = $error;
			$this->error_handler($error);
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * PDO commit
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function commit() {
		$this->check_database_connection();
		
		try {
			$this->pdo->commit();
			return true;
			
		} catch (Exception $e) {
			$error = $e->getMessage();
			$this->last_error = $error;
			$this->error_handler($error);
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * PDO conditional commit
	 * 
	 * Attempt a PDO commit, rollback if no active transaction exists.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function commit_transaction() {
		try {
			if (!$this->active_transaction_failed) {
				$this->commit();
				return true;
			}
			else {
				$this->last_error = 'Could not commit transaction. Rolled back transaction.';
				$this->rollback();
				self::$Exception_Helper->server_error_exception($this->last_error);
			}
			
		} catch (Exception $e) {
			$error = $e->getMessage();
			$this->last_error = $error;
			$this->error_handler($error);
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * PDO rollback
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function rollback() {
		$this->check_database_connection();
		
		try {
			$this->pdo->rollBack();
			return true;
			
		} catch (Exception $e) {
			$error = $e->getMessage();
			$this->last_error = 'Could not commit transaction. Could not rollback transaction: ' . $error;
			$this->error_handler($this->last_error);
			self::$Exception_Helper->server_error_exception($this->last_error);
		}
	}
	
	/**
	 * Create database
	 *
	 * @param string $database_name Database name.
	 * @param array $options (array()) {array('character_set'=> '', 'collation' => '')} Array of options.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function create_database($database_name, $options = array()) {
		
		$sql = 'CREATE DATABASE "' . $database_name . '"';
		
		if (!empty($options['character_set']) && !empty($options['collation'])) {
			$sql .= ' DEFAULT CHARACTER SET ' . $options['character_set'] . ' COLLATE ' . $options['collation'];
		}
		
		$sql .= ';';
		
		try {
			return $this->prepare_exec($sql);
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Alter database
	 *
	 * @param string $database_name Database name.
	 * @param array $options (array()) {array('character_set'=> '', 'collation' => '')} Array of options.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function alter_database($database_name, $options = array()) {
		$sql = 'ALTER DATABASE "' . $database_name . '" ';
		
		if (!empty($options['character_set']) && !empty($options['collation'])) {
			$sql .= ' DEFAULT CHARACTER SET ' . $options['character_set'] . ' COLLATE ' . $options['collation'];
		}
		
		$sql .= ';';
		
		try {
			return $this->prepare_exec($sql);
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
		
	}
	
	/**
	 * Create table
	 *
	 * @param string $table_name Table name.
	 * @param array $fields {array(array('name'=> '', 'type' => ''))} Array of fields.
	 * @param array $options (array()) {array('character_set'=> '', 'collation' => '')} Array of options.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool true on success.
	 */
	public function create_table($table_name, $fields, $options = array()) {
		
		$sql = $this->get_sql_create_table($table_name, $fields, $options);
		
		
		try {
			$result = $this->prepare_exec($sql);
			return $result;
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Get Create Table SQL
	 *
	 * @param string $table_name Table name.
	 * @param array $fields {array(array('name'=> '', 'type' => ''))} Array of fields.
	 * @param array $options (array()) {array('character_set'=> '', 'collation' => '')} Array of options.
	 *
	 * @return string Create Table SQL.
	 */
	private function get_sql_create_table($table_name, $fields, $options) {
		$field_definitions = array();
		$indexes = array();
		
		$num_fields = count($fields);
		foreach ($fields as $i => $field) {
			array_push($field_definitions
				, '"' . $field['name'] . '" '
				. $field['type'] . ' '
				. (!empty($field['character_set']) && !empty($field['collation']) ? ' CHARACTER SET ' . $field['character_set'] . ' COLLATE ' . $field['collation'] : '') . ' '
				. (array_key_exists('null', $field) && $field['null'] == '1' ? 'NULL' : 'NOT NULL') . ' '
				. (isset($field['default']) && $field['default'] != '' ? 'DEFAULT ' . (!in_array($field['default'], $this->reserved_words_default) ? "'" : '') . $field['default'] . (!in_array($field['default'], $this->reserved_words_default) ? "'" : '') : '') . ' '
				. (array_key_exists('auto_increment', $field) && $field['auto_increment'] == '1' ? 'AUTO_INCREMENT' : '') . ' '
				. (!empty($field['index']) && $field['index'] == 'PRIMARY' ? 'PRIMARY KEY ' : '') . ' '
			);
			
			if (!empty($field['index']) && $field['index'] != 'PRIMARY') {
				array_push($indexes, $field['index'] . ' ("' . $field['name'] . '")');
			}
		}
		
		$sql = '
			CREATE TABLE "' . $table_name . '" 
			(' . implode(', ', $field_definitions) . ' 
		';
		
		if (!empty($indexes)) {
			$sql .= ', ' . implode(', ', $indexes);
		}
		
		$sql .= ')';
		
		if (!empty($options['engine'])) {
			$sql .= ' ENGINE = ' . $options['engine'];
		}
		
		if (!empty($options['character_set']) && !empty($options['collation'])) {
			$sql .= ' CHARACTER SET ' . $options['character_set'] . ' COLLATE ' . $options['collation'];
		}
		/*
		if (!empty($options['comment']) {
			$sql .= ' COMMENT = ""';
		}
		*/
		
		$sql .= ';';
		
		return $sql;
	}
	
	/**
	 * Alter table
	 *
	 * @param string $table_name Table name.
	 * @param array $options (array()) {array('character_set'=> '', 'collation' => '')} Array of options.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function alter_table($table_name, $options = array()) {
		
		$sql = '
			ALTER TABLE "' . $table_name . '"
		';
		
		if (!empty($options['comment'])) {
			$sql .= " COMMENT = ''";
		}
		
		if (!empty($options['engine'])) {
			$sql .= ' ENGINE = ' . $options['engine'];
		}
		
		if (!empty($options['character_set']) && !empty($options['collation'])) {
			$sql .= ' DEFAULT CHARACTER SET ' . $options['character_set'] . ' COLLATE ' . $options['collation'];
		}
		
		$sql .= ';';
		
		try {
			return $this->prepare_exec($sql);
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
		
	}
	
	/**
	 * Drop table
	 *
	 * @param string $table_name Table name.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function drop_table($table_name) {
		$sql = 'DROP TABLE "' . $table_name . '"';
		
		try {
			$result = $this->prepare_exec($sql);
			
			return $result;
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
		
	}
	
	/**
	 * Add table index
	 *
	 * Add a new index on a table. If an index already
	 *
	 * exists for $field_names, will drop index before adding 
	 *
	 * new index if the current index type is different or will
	 *
	 * do nothing if the current index is the same.
	 *
	 * @param string $table_name Table name.
	 * @param string[] $field_names Array of field names.
	 * @param string $index_type Index Type: PRIMARY, UNIQUE, FULLTEXT, INDEX.
	 * @param bool $drop_existing (false) {true} Flag to drop existing trigger.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function add_index($table_name, $field_names, $index_type, $drop_existing = false) {
		
		$this->add_drop_index('add', $table_name, $field_names, $index_type, $drop_existing);
		
		if (is_string($field_names)) {
			$field_names = array($field_names);
		}
		
		$index_exists = false;
		$num_fields = count($field_names);
		$statements = array();
		$indexes_to_drop = array();
		$current_indexes = $this->show_indexes($table_name);
		
		// Group by index name
		if (!empty($current_indexes)) {
			$current_indexes = rows_to_groups($current_indexes, 'Key_name');
			
			foreach ($current_indexes as $index_name => $fields) {
				$index_exists = false;
				
				$num_indexed_fields = count($fields);
				$sequence_nums = rows_to_array($fields, 'Column_name', 'Seq_in_index');
				
				if ($num_fields != $num_indexed_fields) {
					continue;
				}
				
				$an_index_exists = true;
				foreach ($field_names as $i => $field_name) {
					$sequence_num = $i + 1;
					if (!array_key_exists($field_name, $sequence_nums) 
						|| $sequence_nums[$field_name] != $sequence_num
					) {
						$an_index_exists = false;
						break;
					}
				}
				
				if (!$an_index_exists) {
					continue;
				}
				
				// Check if existing index is the same
				if ($an_index_exists) {
					$check_field = $fields[0];
					
					if (($check_field['Key_name'] == 'PRIMARY' && $index_type == 'PRIMARY')
						|| ($check_field['Non_unique'] == '0' && $index_type == 'UNIQUE')
						|| ($check_field['Index_type'] == 'FULLTEXT' && $index_type == 'FULLTEXT')
						|| ($check_field['Non_unique'] == '1' && $index_type == 'INDEX')
					) {
						$index_exists = true;
						break;
					}
				}
				
				if (!$index_exists) {
					array_push($indexes_to_drop, $index_name);
					//$this->last_error = 'Index already exists on this field.';
					//self::$Exception_Helper->bad_request_exception($this->last_error);
				}
			}
		}
		
		// Index already exists
		if ($index_exists) {
			return;
		}
		
		if (!empty($indexes_to_drop)) {
			if ($drop_existing) {
				foreach ($indexes_to_drop as $index_name) {
					array_push($statements, 'DROP INDEX "' . $index_name . '"');
				}
			}
			else {
				$this->last_error = 'Index already exists on this field.';
				self::$Exception_Helper->bad_request_exception($this->last_error);
			}
		}
		
		//array_push($statements, 'ADD ' . $index_type . ' "' . $index_name . '" ("' . implode('", "', $field_names) . '")');
		
		$index_name = implode('_', $field_names);
		
		array_push($statements, 'ADD ' . $index_type . ' "' . $index_name . '" ("' . implode('", "', $field_names) . '")');
		
		foreach ($statements as $statement) {
			$sql = 'ALTER TABLE "' . $table_name . '" ' . $statement . ';';
			
			try {
				$result = $this->prepare_exec($sql);
			} catch(Exception $e) {
				$error = $e->getMessage();
				self::$Exception_Helper->server_error_exception($error);
			}
		}
		
		return $result;
	}
	
	public function add_drop_index($add_drop, $table_name, $field_names, $index_type, $drop_existing = false) {
		$dropping = ($add_drop == 'drop') ? true : false;
		
		if (is_string($field_names)) {
			$field_names = array($field_names);
		}
		
		$index_exists = false;
		$num_fields = count($field_names);
		$statements = array();
		$indexes_to_drop = array();
		$current_indexes = $this->show_indexes($table_name);
		
		// Group by index name
		if (!empty($current_indexes)) {
			$current_indexes = rows_to_groups($current_indexes, 'Key_name');
			
			foreach ($current_indexes as $index_name => $fields) {
				$index_exists = false;
				
				$num_indexed_fields = count($fields);
				$sequence_nums = rows_to_array($fields, 'Column_name', 'Seq_in_index');
				
				if ($num_fields != $num_indexed_fields) {
					continue;
				}
				
				$an_index_exists = true;
				foreach ($field_names as $i => $field_name) {
					$sequence_num = $i + 1;
					if (!array_key_exists($field_name, $sequence_nums) 
						|| $sequence_nums[$field_name] != $sequence_num
					) {
						$an_index_exists = false;
						break;
					}
				}
				
				if (!$an_index_exists) {
					continue;
				}
				
				// Check if existing index is the same
				if ($an_index_exists) {
					$check_field = $fields[0];
					
					if (($check_field['Key_name'] == 'PRIMARY' && $index_type == 'PRIMARY')
						|| ($check_field['Non_unique'] == '0' && $index_type == 'UNIQUE')
						|| ($check_field['Index_type'] == 'FULLTEXT' && $index_type == 'FULLTEXT')
						|| ($check_field['Non_unique'] == '1' && $index_type == 'INDEX')
					) {
						$index_exists = true;
						
						if ($dropping) {
							array_push($indexes_to_drop, $index_name);
						}
						
						continue;
					}
				}
				
				if (!$index_exists && !$dropping) {
					array_push($indexes_to_drop, $index_name);
				}
			}
		}
		
		// Index already exists
		if ($index_exists && !$dropping && !$drop_existing) {
			$this->last_error = 'Index already exists on this field.';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		else if ($index_exists && !$dropping) {
			return true;
		}
		
		if (!empty($indexes_to_drop)) {
			if ($drop_existing || $dropping) {
				foreach ($indexes_to_drop as $index_name) {
					array_push($statements, 'DROP INDEX "' . $index_name . '"');
				}
			}
			else {
				$this->last_error = 'Index already exists on this field.';
				self::$Exception_Helper->bad_request_exception($this->last_error);
			}
		}
		
		//array_push($statements, 'ADD ' . $index_type . ' "' . $index_name . '" ("' . implode('", "', $field_names) . '")');
		
		if (!$dropping) {
			$index_name = implode('_', $field_names);
		
			array_push($statements, 'ADD ' . $index_type . ' "' . $index_name . '" ("' . implode('", "', $field_names) . '")');
		}
		else if (empty($indexes_to_drop)) {
			$this->last_error = 'Index does not exist.';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		
		foreach ($statements as $statement) {
			$sql = 'ALTER TABLE "' . $table_name . '" ' . $statement . ';';
			
			try {
				$result = $this->prepare_exec($sql);
			} catch(Exception $e) {
				$error = $e->getMessage();
				self::$Exception_Helper->server_error_exception($error);
			}
		}
		
		return $result;
	}
	
	/**
	 * Drop table index
	 *
	 * Drop a new index on a table. 
	 *
	 * @param string $table_name Table name.
	 * @param string[] $field_names Array of field names.
	 * @param string $index_type Index Type: PRIMARY, UNIQUE, FULLTEXT, INDEX.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function drop_index($table_name, $field_names, $index_type) {
		$this->add_drop_index('drop', $table_name, $field_names, $index_type, $drop_existing);
	}

	/**
	 * Add table column
	 *
	 * @param string $table_name Table name.
	 * @param array $field {array('name'=> '', 'type' => '')} Array of field properties.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function add_column($table_name, $field) {
		$sql = $this->get_sql_add_column($table_name, $field);
			
		try {
			$result = $this->prepare_exec($sql);
			
			return $result;
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}	
	}
	
	/**
	 * Get add column SQL
	 *
	 * @param string $table_name Table name.
	 * @param array $field {array('name'=> '', 'type' => '')} Array of field properties.
	 * @param string $after_field_name (NULL) Field name that the column will go after.
	 *
	 * @return string Add column SQL.
	 */
	private function get_sql_add_column($table_name, $field, $after_field_name = NULL) {
		$sql = '
			ALTER TABLE "' . $table_name . '"  
			ADD "' . $field['name'] . '" '
			. $field['type'] . ' '
			. (!empty($field['character_set']) && !empty($field['collation']) ? ' CHARACTER SET ' . $field['character_set'] . ' COLLATE ' . $field['collation'] : '') . ' '
			. (array_key_exists('null', $field) && $field['null'] == '1' ? ' NULL' : ' NOT NULL') . ' '
			. (isset($field['default']) && $field['default'] != '' ? 'DEFAULT ' . (!in_array($field['default'], $this->reserved_words_default) ? "'" : '') . $field['default'] . (!in_array($field['default'], $this->reserved_words_default) ? "'" : '') : '') . ' '
			. (array_key_exists('auto_increment', $field) && $field['auto_increment'] == '1' ? 'AUTO_INCREMENT' : '') . ' '
			. (!empty($field['index']) ? 'ADD ' . $field['index'] . ' ("' . $field['name'] . '")' : '') . ' '
			. (!empty($after_field_name) ? 'AFTER "' . $after_field_name . '"' : '') . ';';
			
		return $sql;
	}
	
	/**
	 * Get table indexes
	 *
	 * @param string $table_name Table name.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array Array of table indexes.
	 */
	public function show_indexes($table_name) {
		$sql = 'SHOW INDEXES FROM "' . $table_name . '"';
		
		try {
			$this->prepare_exec($sql);
			// if no rows have been return, fetchAll returns an empty array
			$data = $this->sth->fetchAll(PDO::FETCH_ASSOC);
			return $data;
			
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Change column
	 *
	 * @param string $table_name Table name.
	 * @param array $field {array('name'=> '', 'type' => '')} Array of field properties.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function change_column($table_name, $field) {
		$sql = $this->get_sql_change_column($table_name, $field);
		
		if (!empty($field['index']) && $field['index'] == 'PRIMARY') {
			$field['null'] = '1';
			$field['auto_increment'] = '0';
			$field['index'] = NULL;
		}
		
		try {
			$result = $this->prepare_exec($sql);
			
			return $result;
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}	
	}
	
	/**
	 * Get change column SQL
	 *
	 * @param string $table_name Table name.
	 * @param array $field {array('name'=> '', 'type' => '')} Array of field properties.
	 *
	 * @return string Change column SQL.
	 */
	private function get_sql_change_column($table_name, $field) {
		$sql = '
			ALTER TABLE "' . $table_name . '"  
			CHANGE "' . $field['name_current'] . '" "' . $field['name'] . '" '
			. (!empty($field['type']) ? $field['type'] : '') . ' '
			. (!empty($field['character_set']) && !empty($field['collation']) ? ' CHARACTER SET ' . $field['character_set'] . ' COLLATE ' . $field['collation'] : '') . ' '
			. (array_key_exists('null', $field) ? ($field['null'] == '1' ? ' NULL' : ' NOT NULL') : '') . ' '
			. (isset($field['default']) && $field['default'] != '' ? 'DEFAULT ' . (!in_array($field['default'], $this->reserved_words_default) ? "'" : '') . $field['default'] . (!in_array($field['default'], $this->reserved_words_default) ? "'" : '') : '') . ' '
			. (array_key_exists('auto_increment', $field) && $field['auto_increment'] == '1' ? 'AUTO_INCREMENT' : '');
			
		$sql .= ';';
		
		return $sql;
	}
	
	/**
	 * Drop coloumn
	 *
	 * @param string $table_name Table name.
	 * @param string $field_name Field name.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool true on success.
	 */
	public function drop_column($table_name, $field_name) {
		$sql = 'ALTER TABLE "' . $table_name . '" DROP "' . $field_name . '";';
		
		try {
			$result = $this->prepare_exec($sql);
			
			return $result;
			
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Rename table
	 *
	 * @param string $table_name Table name.
	 * @param string $new_table_name New Table name.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool true on success.
	 */
	public function rename_table($table_name, $new_table_name) {
		
		$sql = $this->get_sql_rename_table($table_name, $new_table_name);
		
		try {
			$result = $this->prepare_exec($sql);
			
			return $result;
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
		
	}
	
	/**
	 * Get Rename table SQL
	 *
	 * @param string $table_name Table name.
	 * @param string $new_table_name New Table name.
	 *
	 * @return string Rename table SQL.
	 */
	private function get_sql_rename_table($table_name, $new_table_name) {
		return 'RENAME TABLE "' . $table_name . '" TO "' . $new_table_name . '";';
	}
	
	/**
	 * Drop Database
	 *
	 * @param string $database_name Database name.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
    public function drop_database($database_name) {
        $sql = 'DROP DATABASE "' . $database_name . '"';
		
		try {
			return $this->prepare_exec($sql);
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}	
	}
	
	/**
	 * Get tables
	 *
	 * @param string $database_name Database name.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array Array of tables.
	 */
	public function show_tables($database_name = NULL) {
		$sql = 'SHOW TABLES';
		
		if (!empty($database_name)) {
			$sql .= ' FROM "' . $database_name . '"';
		}
		
		try {
			$this->prepare_exec($sql);
			$data = $this->sth->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_GROUP);
			
			if (!empty($data)) {
				$data = array_keys($data);
			}
			return $data;
			
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Get table columns
	 *
	 * Example: $options = array('exclude_list' => array('site.site_id'), 'include_list' => array());
	 * 
	 * $columns = $db->show_columns('site', $options);
	 * 
	 * @param string $table Table name.
	 * @param array $options (array()) {array('exclude_list' => array('site.site_id'), 'include_list' => array())} Array of options.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array Array of tables columns.
	 */
	public function show_columns($table, $options = array()) {
		$exclude_list = !empty($options['exclude_list']) ? $options['exclude_list'] : array();
		$include_list = !empty($options['include_list']) ? $options['include_list'] : array();
		$columns = array();
		
		$sql = '
			SHOW FULL COLUMNS 
			FROM "' . $table . '"';
			
		try {
			$this->prepare_exec($sql);
			$all_columns = $this->sth->fetchAll(PDO::FETCH_ASSOC);
			
			if (!empty($all_columns)) {
				foreach ($all_columns as $column) {
					if (
						(empty($include_list) && empty($exclude_list))
						|| (!empty($include_list) && in_array($table . '.' . $column['Field'], $include_list))
						|| (!empty($exclude_list) && !in_array($table . '.' . $column['Field'], $exclude_list))
					) {
						array_push($columns, $column);
					}
				}
			}
			
			return empty($columns) ? array() : $columns;
			
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Get column names and types
	 *
	 * Example: $options = array('exclude_list' => array('site.site_id'), 'include_list' => array());
	 * 
	 * $columns = $db->get_column_names_types('site', $options);
	 * 
	 * @param string $table Table name.
	 * @param array $options (array()) {array('exclude_list' => array('site.site_id'), 'include_list' => array())} Array of options.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array Array of field names, sql names, and field types.
	 */
	public function get_column_names_types($table, $options = array()) {
		$names = array();
		$sql_names = array();
		$types = array();
		
		$columns = $this->show_columns($table, $options);
		if ($columns === false) {
			return false;
		}
		
		if (!empty($columns)) {
			foreach ($columns as $column) {
				array_push($names, $column['Field']);
				array_push($sql_names, $table . '.' . $column['Field']);
				array_push($types, $column['Type']);
			}
		}
		
		return array(
			'names' => $names
			, 'sql_names' => $sql_names
			, 'types' => $types
		);
	}
	
	/**
	 * Get ENUM values for a field
	 *
	 * @param string $table Table name.
	 * @param string $field Field name.
	 *
	 * @throws Exception on failure.
	 *
	 * @return string[] Array of enum values.
	 */
	public function get_enum_values($table, $field) {
		$enum_values = array();
		$sql = '
			SHOW FULL COLUMNS 
			FROM "' . $table . '"
			LIKE :field
		'; 
		$parameters = array(
			':field' => $field
		);
		
		$result = $this->exec($sql, $parameters);
		if ($result === false) {
			return false;
		}
		
		if (!empty($result)) {
			$row = $result[0];
			$type = $row['Type'];
			if (preg_match('/^enum/', $type)) {
				$type = str_replace("''", "'", $type);
				$type = rtrim(rtrim(ltrim($type, "enum('"), ")"), "'");
				$enum_values = explode("','", $type);
			}
		}
		
		return $enum_values;
	}
	
	/**
	 * Get a value of a field for a certain entity
	 *
	 * Example: $site_name = $db->get_entity_value('site', 'name', 'site_id', 1);
	 *
	 * @param string $table_name Table name.
	 * @param string $field_name Field name.
	 * @param string $entity_id_field Entity id field.
	 * @param string $entity_id Entity id.
	 *
	 * @throws Exception on failure.
	 *
	 * @return string The value.
	 */
	public function get_entity_value($table_name, $field_name, $entity_id_field, $entity_id) {
		
		$sql = '
			SELECT "' . $table_name . '"."' . $field_name . '"
			FROM "' . $table_name . '"
			WHERE "' . $table_name . '"."' . $entity_id_field . '" = :entity_id 
		';
		
		$parameters = array(
			':entity_id' => $entity_id
		);
		
		$data = $this->select_single($sql, $parameters);
		if ($data === false) {
			return false;
		}
		
		if (!empty($data)){
			return $data[$field_name];	
		}
		
		return NULL;
		
	}
	
	/**
	 * Get entity info
	 *
	 * Example:
		 $options = array(
			'exclude_list' => array()
			, 'include_list' => array()
			, 'joins' => array(
				array(
					'join_type' => 'INNER'
					, 'join_table' => 'template'
					, 'join_on' => 'site.template_id = template.template_id'
				)
				, array(
					'join_type' => 'INNER'
					, 'join_table' => 'customer'
					, 'join_on' => 'site.customer_id = customer.customer_id'
				)
			)
		);
		$test = $db->get_entity_info('site', 'site_id', 1, $options);
	 *
	 * @param string $primary_table Primary table.
	 * @param array $primary_key Primary key.
	 * @param array $primary_id Primary id.
	 * @param array $options (array()) Array of options.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array Array of entity info.
	 */
	public function get_entity_info($primary_table, $primary_key, $primary_id, $options = array()) {
		
		$joins = !empty($options['joins']) ? $options['joins'] : array();
		$exclude_list = !empty($options['exclude_list']) ? $options['exclude_list'] : array();
		$include_list = !empty($options['include_list']) ? $options['include_list'] : array();
		
		$sql = '';
		$options = array(
			'exclude_list' => $exclude_list
			, 'include_list' => $include_list
		);
		$columns = $this->get_column_names_types($primary_table, $options);
		if ($columns === false) {
			return false;
		}
		
		$columns = $columns['sql_names'];
		
		if (!empty($joins)) {
			foreach ($joins as $join_info) {
				$join_columns = $this->get_column_names_types($join_info['join_table'], $options);
				if ($join_columns === false) {
					return false;
				}
				
				$join_columns = $join_columns['sql_names'];
				
				$columns = array_merge(
					$columns
					, $join_columns
				);
			}
		}
		
		if (!empty($columns)) {
			$sql = '
				SELECT ' . implode(', ', $columns) . '
				FROM "' . $primary_table . '"
			';
			
			if (!empty($joins)) {
				foreach ($joins as $join_info) {
					$sql .= ' ' . $join_info['join_type'] . ' JOIN "' . $join_info['join_table'] . '" ON ' . $join_info['join_on'];
				}
			}
			
			$sql .= ' WHERE "' . $primary_table . '"."' . $primary_key . '" = :id';
			$parameters = array(
				':id' => $primary_id
			);
			
			$data = $this->select_single($sql, $parameters);
			if ($data === false) {
				return false;
			}
			
			return $data;
		}
		
		$this->last_error = 'No columns selectd';
		self::$Exception_Helper->bad_request_exception($this->last_error);
		
	}
	
	/**
	 * Insert row
	 *
	 * @param string $table Table name.
	 * @param array $fields Associative array of column names and values.
	 * @param bool $replace Flag to use REPLACE Keyword.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function insert($table, $fields = array(), $replace = false) {
		$values = array();
		$parameters = array();
		
		if (empty($fields)) {
			$sql = ($replace == false ? 'INSERT' : 'REPLACE') . '
				 INTO "' . $table . '" () 
				 VALUES ();
			';
		}
		else {
			foreach ($fields as $field => $value) {
				if (is_null($value)) {
					array_push($values, ' NULL ');
				}
				else {
					array_push($values, ':' . $field);
					$parameters[':' . $field] = $value;
				}
			}
			
			$sql = ($replace == false ? 'INSERT' : 'REPLACE') . '
				 INTO "' . $table . '" ("' . implode('", "', array_keys($fields)) . '") 
				 VALUES (' . implode(', ', $values) . ')
			';
		}
		
		try {
			$result = $this->prepare_exec($sql, $parameters);
			
			return $result;
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Insert multiple rows
	 *
	 * @param string $table Table name.
	 * @param array $value_lists Two-dimensional array of column names and values.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function insert_many($table, $value_lists) {
		
		if (!is_array($value_lists[0])) {
			$this->last_error = 'value_lists should be a double array.';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		
		//make sure values are safe
		$value_strs = array();
		$parameters = array();
		foreach ($value_lists as $i => $value_list) {
			$values = array();
			foreach ($value_list as $field => $value) {
				if (is_null($value)) {
					array_push($values, ' NULL ');
				}
				else {
					array_push($values, ':' . $field . $i);
					$parameters[':' . $field . $i] = $value;
				}
			}
			
			array_push($value_strs, '(' . implode(',', $values) . ')' );
		}
		
		$sql = 'INSERT INTO "' . $table . '" ("' . implode('", "', array_keys($value_lists[0])) . '") VALUES ' . implode(', ', $value_strs);
		
		try {
			return $this->prepare_exec($sql, $parameters); 
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Get last insert id
	 *
	 * @throws Exception on failure.
	 *
	 * @return int MySQL last insert id.
	 */
	public function last_insert_id() {
		$this->check_database_connection();
		
		return $this->pdo->lastInsertId();
	}
	
	/**
	 * Get number of affected rows
	 *
	 * @throws Exception on failure.
	 *
	 * @return int Number of affected rows.
	 */
	public function row_count() {
		if (!empty($this->sth)) {
			return $this->sth->rowCount();
		}
		
		$error = 'PDO object does not exist';
		$this->last_error = $error;
		$this->error_handler($error);
		self::$Exception_Helper->server_error_exception($error);
	}
	
	/**
	 * Run SQL statement
	 *
	 * @param string $sql SQL Statement with parameter markers.
	 * @param array $parameters (array()) Array of parameters.
	 * @param bool $return_data (true) Flag to return data from PDO fetchAll.
	 * 
	 * @throws Exception on failure.
	 *
	 * @return mixed bool or array of data.
	 */
	public function exec($sql, $parameters = array(), $return_data = true) {
		
		if (empty($sql)) {
			$this->last_error = 'Query is empty';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		try {//echo $sql;
			if (!empty($parameters) && !is_assoc($parameters)) {
				$parameters = array_values($parameters);
			}
			
			//print_r($parameters);
			$result = $this->prepare_exec($sql, $parameters);
			if ($return_data) {
				// if no rows have been return, fetchAll returns an empty array
				$data = $this->sth->fetchAll(PDO::FETCH_ASSOC);
				return $data;
			}
			
			return $result;
			
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	private function enable_ANSI_QUOTES_SQL_mode(){
		$sql = "SET sql_mode='ANSI_QUOTES';";
		$parameters = array();
		
		$this->check_database_connection($sql);
		
		$sth = $this->pdo->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		
		if (!$sth->execute($parameters)) {
			if ($this->active_transaction) {
				$this->active_transaction_failed = true;
			}
			$error_info = $sth->errorInfo();
			$this->last_error = $error_info[2];
			$this->error_handler($error_info[2], $sql);
			self::$Exception_Helper->server_error_exception($this->last_error);
		}
		
		// Log
		if (!empty($this->log_pdo)) {
			$log_sth = $this->log_pdo->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		
			if (!$log_sth->execute($parameters)) {
				$error_info = $log_sth->errorInfo();
				$this->last_error = $error_info[2];
				$this->error_handler($error_info[2], $sql);
				self::$Exception_Helper->server_error_exception($this->last_error);
			}
		}
		
		return true;
	}
	
	/**
	 * Select a single row
	 *
	 * @param string $sql SQL Statement with parameter markers.
	 * @param array $parameters (array()) Array of parameters.
	 * 
	 * @throws Exception on failure.
	 *
	 * @return array Array of row column/values.
	 */
	public function select_single($sql, $parameters = array()) {
		if (empty($sql)) {
			$this->last_error = 'Query was empty';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		
		$sql .= ' LIMIT 1';
		$data = $this->exec($sql, $parameters);
		if ($data === false) {
			return false;
		}
		
		if (!empty($data)){
			return $data[0];	
		}
		return array();
	}
	
	/**
	 * Update table
	 *
	 * Example: 
	 *
	 * $table = 'product';
	 *
	 * $fields = array('price' => '5.00', 'quantity_remaining' => '1');
	 *
	 * $where_sql = 'product_id = :product_id';
	 *
	 * $where_values = array(':product_id' => 33);
	 *
	 * $operators = array('quantity_remaining' => '-');
	 *
	 * $db->update($table, $fields, $where_sql, $where_values, $operators);
	 *
	 * @param string $table Table name.
	 * @param array $fields Associative array of column names and values.
	 * @param string $where_sql ('') SQL Statement with parameter markers.
	 * @param array $where_values (array()) Array of parameters.
	 * @param array $operators (array()) Arithmetic operators to apply to fields.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function update($table, $fields, $where_sql = '', $where_values = array(), $operators = array()) {
		$parameters = array();
		if (empty($fields)) {
			$this->last_error = 'Fields are empty';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		
		$sql = 'UPDATE "' . $table . '" SET ';
		$sets = array();
		$is_assoc = !empty($where_values) && is_assoc($where_values) ? true : false;
		foreach ($fields as $field => $value) {
			if (!empty($operators[$field])) {
				$set = '"' . $field . '" = "' . $field . '" ' . $operators[$field] . ' ' . $value;
			}
			else {
				$set = '"' . $field . '" = ';
				if (is_null($value)) {
					$set .=	'NULL';	
				}
				else {
					if (!$is_assoc) {
						$set .= '?';
						array_push($parameters, $value);
					}
					else {
						// need to make sure that there is not conflicts with where values
						$key = ':val_' . $field; 
						$set .= $key;
						$parameters[$key] = $value;
					}
				}
			}
			array_push($sets, $set);
		}
		
		$sql .= implode($sets, ', ');
		
		if (!empty($where_sql)) {
			$parameters = array_merge($parameters, $where_values);
			$sql .= ' WHERE ' . $where_sql;
		}
		
		try {
			return $this->prepare_exec($sql, $parameters);
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Delete row
	 *
	 * @param string $table Table name.
	 * @param string $where_sql ('') SQL Statement with parameter markers.
	 * @param array $parameters (array()) Array of parameters.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function delete($table, $where_sql = '', $parameters = array()) {
		
		$sql = 'DELETE FROM "' . $table . '"';
		if (!empty($where_sql)) {
			$sql .= ' WHERE ' . $where_sql;
		}
		
		try {
			if (!empty($parameters) && !is_assoc($parameters)) {
				$parameters = array_values($parameters);
			}
			
			return $this->prepare_exec($sql, $parameters);
			
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Procedure Analyse
	 *
	 * Examines the result from a query and returns an analysis of the results
	 * that suggess optimal data types for each column that may help reduce
	 * table sizes.
	 *
	 * @param string $sql {'SELECT * FROM user'} SQL.
	 * @param array $parameters (array()) Array of parameters.
	 *
	 * @throws Exception on failure.
	 * 
	 * @return array Analysis of results.
	 */
	public function procedure_analyse($sql, $parameters = array()) {
		
		if (empty($sql)) {
			$this->last_error = 'Query is empty';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		
		$data = $this->exec($sql . ' PROCEDURE ANALYSE()', $parameters);
		
		return $data;
	}
	
	/**
	 * Explain
	 *
	 * Display information from the optimizer about the query execution plan.
	 *
	 * @param string $sql {'SELECT * FROM user'} SQL.
	 * @param array $parameters (array()) Array of parameters.
	 *
	 * @throws Exception on failure.
	 * 
	 * @return array Analysis of results.
	 */
	public function explain($sql, $parameters = array()) {
		if (empty($sql)) {
			$this->last_error = 'Query is empty';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		
		$data = $this->exec('EXPLAIN ' . $sql, $parameters);
		
		if (!empty($data)){
			return $data[0];	
		}
		
		return $data;
	}
	
	/**
	 * Create trigger
	 *
	 * @param string $table_name {'db_database'} Table name.
	 * @param string $timing {'BEFORE'} Timing.
	 * @param string $event {'UPDATE'} Event.
	 * @param string $client_statement {'DECLARE one int (10); SELECT 1 INTO one;'} Client statement.
	 * @param bool $drop_existing (false) {true} Flag to drop existing trigger.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function create_trigger($table_name, $timing, $event, $client_statement, $drop_existing = false) {
		$timing = strtolower($timing);
		$event = strtolower($event);
		
		$this->validate_trigger_event($event);
		$this->validate_trigger_timing($timing);
		
		// Get current triggers
		$current_trigger = $this->get_trigger($table_name, strtoupper($event), strtoupper($timing));
		$current_trigger_name = NULL;
		
		$trigger_body = '
		
		' . $client_statement;
		
		// Generate TRIGGER SQL
		$trigger_body = $this->trigger_wrap_begin_end($trigger_body);
		$sql = $this->get_sql_trigger($table_name, $timing, $event, $trigger_body, $current_trigger_name);
		
		// Drop existing
		if ($drop_existing) {
			$this->exec($sql['drop']);
		}
		
		// Create trigger
		try {	
			$result = $this->prepare_exec($sql['create']);
			
			return $result;
		} catch(Exception $e) {
			$error = $e->getMessage();
			
			// Restore original trigger
			if ($drop_existing && !empty($current_trigger)) {
				$sql = $this->get_sql_trigger($table_name, $timing, $event, $current_trigger['Statement'], $current_trigger_name);
				
				try {
					//$result = $this->prepare_exec($sql['drop']);
					$result = $this->prepare_exec($sql['create']);
					
				} catch(Exception $e) {
					$error = 'Error creating trigger: ' . $error . "\n\n";
					$error .= 'Error restoring original trigger: ' . $e->getMessage();
					self::$Exception_Helper->server_error_exception($error);
				}
			}
			
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Drop trigger
	 *
	 * @param string $table_name {'db_database'} Table name.
	 * @param string $timing {'BEFORE'} Timing.
	 * @param string $event {'UPDATE'} Event.
	 * @param bool $if_exists (true) Flag to add IF EXISTS clause.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function drop_trigger($table_name, $timing, $event, $if_exists = true) {
		$timing = strtolower($timing);
		$event = strtolower($event);
		
		$this->validate_trigger_event($event);
		$this->validate_trigger_timing($timing);
		
		// Get current triggers
		$current_trigger = $this->get_trigger($table_name, strtoupper($event), strtoupper($timing));
		$current_trigger_name = NULL;
		if (empty($current_trigger) && !$if_exists) {
			$this->last_error = 'Trigger does not exist.';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		
		$current_trigger_name = $current_trigger['Trigger'];
		
		$trigger_body = '';
		
		// Generate TRIGGER SQL
		$trigger_body = $this->trigger_wrap_begin_end($trigger_body);
		$sql = $this->get_sql_trigger($table_name, $timing, $event, $trigger_body, $current_trigger_name);
		
		// Drop existing
		$this->exec($sql['drop']);
	}
	
	/**
	 * Drop routine
	 *
	 * @param string $type {'FUNCTION'} Routine type.
	 * @param string $routine {'test_function'} Routine name.
	 * @param bool $if_exists (true) Flag to add IF EXISTS clause.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function drop_routine($type, $routine, $if_exists = true) {
		$this->validate_routine_type($type);
		
		$sql = 'DROP ' . $type . ($if_exists ? ' IF EXISTS' : '') . ' "' . $routine . '"';
		
		try {
			$result = $this->prepare_exec($sql);
			
			return $result;
		} catch(Exception $e) {
			$error = $e->getMessage();
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	private function validate_trigger_event($event) {
		if (!in_array($event, $this->trigger_events)) {
			$this->last_error = 'Invalid trigger event.';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		
		return true;
	}
	
	private function validate_trigger_timing($timing) {
		if (!in_array($timing, $this->trigger_timings)) {
			$this->last_error = 'Invalid trigger timing.';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		
		return true;
	}
	
	private function validate_routine_type($type) {
		if (!in_array($type, $this->routine_types)) {
			$this->last_error = 'Invalid routine type.';
			self::$Exception_Helper->bad_request_exception($this->last_error);
		}
		
		return true;
	}
	
	/**
	 * Get trigger
	 *
	 * @param string $table_name {'db_database'} Table name.
	 * @param string $event {'UPDATE'} Event.
	 * @param string $timing {'BEFORE'} Timing.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array Trigger info.
	 */
	public function get_trigger($table_name, $event, $timing) {
		$sql = '
			SHOW TRIGGERS 
			WHERE "Table" = :table 
				AND "Event" = :event 
				AND "Timing" = :timing
		';
		
		$params = array(
			':table' => $table_name
			, ':event' => strtoupper($event)
			, ':timing' => strtoupper($timing)
		);
		
		$data = $this->exec($sql, $params);
		
		if (!empty($data)) {
			return $data[0];	
		}
		
		return array();
	}
	
	/**
	 * Get routine
	 *
	 * @param string $type {'FUNCTION'} Routine type.
	 * @param string $routine {'test_function'} Routine name.
	 *
	 * @throws Exception on failure.
	 *
	 * @return array Routine info.
	 */
	public function get_routine($type, $routine) {
		$this->validate_routine_type($type);
		
		$db_name = $this->db_name;
		
		$sql = '
			SELECT ROUTINE_NAME 
			FROM information_schema.ROUTINES 
			WHERE ROUTINE_TYPE = :type 
			AND ROUTINE_SCHEMA = :schema 
			AND ROUTINE_NAME = :name
		';
		
		$params = array(
			':type' => $type
			, ':schema' => $db_name
			, ':name' => $routine
		);
		
		$data = $this->exec($sql, $params);
		
		if (!empty($data)) {
			$sql = 'SHOW CREATE ' . $type . ' "' . $routine . '"';
			$data = $this->exec($sql);
			
			if (!empty($data)){
				return $data[0];	
			}
		}
		
		return array();
	}
	
	/**
	 * Create routine
	 *
	 * @param string $type {'FUNCTION'} Routine type.
	 * @param string $routine {'test_function'} Routine name.
	 * @param string $body {'DECLARE new_number INT(10); SET new_number = number + 1; RETURN new_number;'} Body.
	 * @param string $parameters (NULL) {'number INT(10)'} Comma delimited parameters.
	 * @param string $returns (NULL) {'int(10)'} Return type.
	 * @param string $characteristics (NULL) {'DETERMINISTIC'} Characteristics.
	 * @param bool $drop_existing (false) {true} Flag to drop existing routine.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	public function create_routine($type, $routine, $body, $parameters = NULL, $returns = NULL, $characteristics = NULL, $drop_existing = false) {
		
		$type = strtoupper($type);
		
		$this->validate_routine_type($type);
		
		// Generate CREATE SQL
		$sql = 'CREATE ' . $type . ' "' . $routine . '" (' . $parameters . ')';
		
		if ($type == 'FUNCTION') {
			$sql .= ' RETURNS ' . $returns;
		}
		
		$sql .= '
' . $characteristics;
		
		$sql .= '
BEGIN
' . $body . '
END';
		
		// Drop existing
		if ($drop_existing) {
			$current_routine = $this->get_routine($type, $routine);
			
			$this->exec('DROP ' . $type . ' IF EXISTS "' . $routine . '"');
		}
		
		// Create routine
		try {
			$result = $this->prepare_exec($sql);
			
			return $result;
		} catch(Exception $e) {
			$error = $e->getMessage();
			
			// Restore original routine
			if ($drop_existing && !empty($current_routine)) {
				$sql = $current_routine['Create ' . ucwords(strtolower($type))];
				
				try {
					$result = $this->prepare_exec($sql);
					
				} catch(Exception $e) {
					$error = 'Error creating routine: ' . $error . "\n\n";
					$error .= 'Error restoring original routine: ' . $e->getMessage();
					self::$Exception_Helper->server_error_exception($error);
				}
			}
			
			self::$Exception_Helper->server_error_exception($error);
		}
	}
	
	/**
	 * Prepare and execute SQL statement
	 *
	 * @access private
	 *
	 * @param string $sql Parameterized SQL query.
	 * @param array $parameters (array()) Array of parameters.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool - True if query executed successfully.
	 */
	private function prepare_exec($sql, $parameters = array()) {
		$this->check_database_connection($sql);
		
		$this->sth = $this->pdo->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		
		if (!$this->sth->execute($parameters)) {
			if ($this->active_transaction) {
				$this->active_transaction_failed = true;
			}
			$error_info = $this->get_last_sth_error();
			$this->last_error = $error_info[2];
			$this->error_handler($error_info[2], $sql);
			
			// If there is a current transaction, don't stop execution
			if (!$this->active_transaction) {
				self::$Exception_Helper->server_error_exception($this->last_error);
			}
		}
		
		// Log
		if (!empty($this->log_pdo)) {
			$this->log_sql($sql, $parameters);
		}
		
		return true;
	}
	
	/**
	 * Insert SQL statement into log table
	 *
	 * @access private
	 *
	 * @param string $log_sql SQL query.
	 * @param array $log_parameters (array()) Array of parameters.
	 *
	 * @throws Exception on failure.
	 *
	 * @return bool True on success.
	 */
	private function log_sql($log_sql, $log_parameters = array()) {
		if (empty($this->log_pdo)) {
			return;
		}
		
		$values = array();
		$parameters = array();
		
		if (empty($log_sql)) {
			array_push($values, ' NULL ');
		}
		else {
			array_push($values, ':sql');
			$parameters[':sql'] = strip_whitespace($log_sql);
		}
		
		if (empty($log_parameters)) {
			array_push($values, ' NULL ');
		}
		else {
			array_push($values, ':log_parameters');
			$parameters[':log_parameters'] = json_encode($log_parameters);
		}
		
		$sql = '
			INSERT 
			INTO "' . $this->sql_log_table . '" ("sql", "parameters")
			VALUES (' . implode(', ', $values) . ');
		';
		
		$this->log_sth = $this->log_pdo->prepare($sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		
		if (!$this->log_sth->execute($parameters)) {
			$error_info = $this->log_sth->errorInfo();
			$this->last_error = $error_info[2];
			$this->error_handler($error_info[2], $sql);
			self::$Exception_Helper->server_error_exception($this->last_error);
		}
		
		return true;
	}
	
	/**
	 * Get last error
	 *
	 * @return string Error message.
	 */
	public function get_last_sth_error() {
		$error_info = $this->sth->errorInfo();
		return $error_info;
	}
	
	/**
	 * Handle errors
	 *
	 * Output errors for development / writes errors to file for production.
	 *
	 * @access private.
	 * 
	 * @param string $error Error string.
	 * @param string $sql ("") SQL that caused the error.
	 */
	private function error_handler($error, $sql = '') {
		$error = $_SERVER['REQUEST_URI'] . "\Database_Helper SQL Error: \n" . $error . "\n" . $sql;
		if (DEVELOPMENT == true || !function_exists('log_error')) {
			echo nl2br($error);
		}
		else {
			log_error($error, 'database');
		}
	}
	
	/**
	 * Quote identifier
	 *
	 * @param string $identifier Identifier (field name, table name ect).
	 * 
	 * @return string Quoted identifier.
	 */
	public function quote_identifier($identifier) {
		
		return $this->identifier_quote_character . str_replace('.', $this->identifier_quote_character . '.' . $this->identifier_quote_character, $identifier) . $this->identifier_quote_character;
	}
	
	/**
	 * Unquote identifier
	 *
	 * @param string $identifier Identifier (field name, table name ect).
	 * 
	 * @return string Unquoted identifier.
	 */
	public function unquote_identifier($identifier) {
		return str_replace($this->identifier_quote_character, '', $identifier);
	}
	
	/**
	 * Get current timestamp
	 *
	 * @param bool $show_micro_seconds (true) Flag to include micro seconds.
	 *
	 * @return string Timestamp (Y-m-d H:i:s:u).
	 */
	private function get_timestamp($integer = false, $show_micro_seconds = false) {
		if ($integer) {
			$timestamp = $show_micro_seconds ? microtime(true) : time();
		}
		else {
			list($timestamp, $micro_seconds) = explode('.', microtime(true));
			$timestamp = date('Y-m-d H:i:s', $timestamp);
			
			if ($show_micro_seconds) {
				$timestamp .= ':' . $micro_seconds;
			}
		}
		
		return $timestamp;
	}
	
	
	
	/**
	 * Get SQL to create trigger
	 *
	 * @param string $table_name {'db_database'} Table name.
	 * @param string $timing {'BEFORE'} Timing.
	 * @param string $event {'UPDATE'} Event.
	 * @param string $trigger_body {'BEGIN DECLARE one int (10); SELECT 1 INTO one; END'} Trigger body.
	 * @param string $current_trigger_name (NULL) Current trigger name.
	 *
	 * @return array SQL to drop/create trigger.
	 */
	private function get_sql_trigger($table_name, $timing, $event, $trigger_body, $current_trigger_name = NULL) {
		
		$timing = strtolower($timing);
		$event = strtolower($event);
		
		$this->validate_trigger_event($event);
		$this->validate_trigger_timing($timing);
		
		$trigger_name = $table_name . '_' . $event . '_' . $timing;
		$current_trigger_name = empty($current_trigger_name) ? $trigger_name : $current_trigger_name;
		
		$trigger_sql = 'CREATE TRIGGER "' . $trigger_name . '" ' . strtoupper($timing) . ' ' . strtoupper($event) . ' ON "' . $table_name . '"
	FOR EACH ROW ' . $trigger_body;
		
		$trigger_sql = array(
			'drop' => 'DROP TRIGGER IF EXISTS "' . $current_trigger_name . '"'
			, 'create' => $trigger_sql
		);
		
		return $trigger_sql;
	}

	private function trigger_unwrap_begin_end($body) {
		$body = explode('BEGIN', $body, 2);
		$body = $body[1];
		$body = explode('END', $body, -1);
		$body = implode('END', $body);
		
		return $body;
	}
	
	private function trigger_wrap_begin_end($body) {
		
		$body = 'BEGIN

' . $body . '

	END';
		
		return $body;
	}
	
	private function get_ip_int() {
		return sprintf("%u", ip2long($_SERVER['REMOTE_ADDR']));
	}
	
	private function copy_table($table_name, $target_table_name) {
		$copy_sql = 'CREATE TABLE "' . $target_table_name . '" LIKE ' . $table_name;
		return $this->exec($copy_sql);
	}
	private function table_exists($table_name) {
		$table_exists = $this->exec("SHOW TABLES LIKE '" . $table_name . "'");
		return !empty($table_exists) ? true : false;
	}
	private function convert_primary_key_to_unique($table_name) {
		$primary_keys = $this->exec('SHOW INDEXES FROM ' . $table_name . " WHERE Key_name = 'PRIMARY'");
		
		if (!empty($primary_keys)) {
			$primary_key_columns = array();
			
			foreach ($primary_keys as $primary_key) {
				array_push($primary_key_columns, $primary_key['Column_name']);
			}
			
			// Drop existing primary key
			$drop_key = $this->exec('ALTER TABLE ' . $table_name . ' DROP PRIMARY KEY');
			
			// Create new unique key from old primary key columns
			$unique = $this->exec('ALTER TABLE ' . $table_name . ' ADD UNIQUE("' . implode('","', $primary_key_columns) . '")');
			
			return $primary_key_columns;
		}
		
		return NULL;
	}
	
}

?>