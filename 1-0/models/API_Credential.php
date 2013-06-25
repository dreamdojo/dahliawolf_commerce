<?php

class API_Credential extends _Model {
	const TABLE = 'api_credential';
	
	private $generated_guid;
	private $cleaned_guid;
	private $salted_guid;
	private $api_key;
	private $api_id;
	
	public function __destruct() {
		unset($this->api_id);
		unset($this->api_key);
		unset($this->salted_guid);
		unset($this->cleaned_guid);       
		unset($this->generated_guid);
	}
	
	private function new_GUID() {
		if (function_exists('com_create_guid') === true) {
			return trim(com_create_guid(), '{}');
		}
		
		return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
	}
    
    private function create_GUID() {
		$this->generated_guid = $this->new_GUID();
	}
	
	private function remove_special_characters() {
		$special_characters = array('{', '}', '-');
		$this->cleaned_guid = str_replace($special_characters, '', $this->generated_guid);
	}
		
	private function salt_GUID() {
		$Cryptography = new Cryptography();
		$this->salted_guid = $Cryptography->get_hash($this->cleaned_guid, 8);
	}
	
	private function split_salted_GUID() {
		$hashed_array = explode(':', $this->salted_guid);
		$this->api_key = $hashed_array[0];
		$this->api_id = $hashed_array[1];
	}
		
	private function generate_API_Key() {
		// create_GUID
		$guid = $this->new_GUID();
		
		// remove_special_characters
		$special_characters = array('{', '}', '-');
		$cleaned_guid = str_replace($special_characters, '', $guid);
		
		// salt_GUID
		$Cryptography = new Cryptography();
		$salted_guid = $Cryptography->get_hash($cleaned_guid, 8);
		
		// split salted GUID
		$hashed_array = explode(':', $salted_guid);
		$api_key = $hashed_array[0];
		$api_id = $hashed_array[1];
		
		return $api_key;
		/*
		$this->create_GUID();
		$this->remove_special_characters();
		$this->salt_GUID();
		$this->split_salted_GUID();
		
		return $this->api_key;
		*/
	}
		
	
		
	public function generate_developer_key($api_website_id) {
		$api_key = $this->generate_API_Key();
		$private_key = $this->generate_API_Key();
		
		$info = array(
			'api_website_id' => $api_website_id
			, 'api_key' => $api_key
			, 'private_key' => $private_key
			, 'key_type' => 'Developer'
		);
		
		$api_credential_id = $this->save_api_key($info);
		
		return $api_credential_id;
	}
		
	public function generate_production_key($api_website_id) {
		$api_key = $this->generate_API_Key();
		$private_key = $this->generate_API_Key();
		
		$info = array(
			'api_website_id' => $api_website_id
			, 'api_key' => $api_key
			, 'private_key' => $private_key
			, 'key_type' => 'Production'
		);
		
		$api_credential_id = $this->save_api_key($info);
		
		return $api_credential_id;
	}
	
	private function save_api_key($info) {
		
		$values = array(
			'api_website_id' => $info['api_website_id']
			, 'api_key' => $info['api_key']
			, 'private_key' => $info['private_key']
			, 'key_type' => $info['key_type']
		);
		
		try {
			$this->db_insert($values);
			$insert_id = $this->db_last_insert_id();
			
			return $insert_id;
			
		} catch(Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to save api key.');
		}
		
	}
	
	/*
	public function get_developer_api_key($api_key, $domain) {
		
		$data = $this->get_api_key($api_key, $domain, 'Developer');
		
		return $data;
	}
	
	public function get_production_api_key($api_key, $domain) {
		
		$data = $this->get_api_key($api_key, $domain, 'Production');
		
		return $data;
	}
	*/
	public function get_api_credential_by_api_key($api_key) {
		$sql = '
			SELECT api_credential.*
			FROM api_credential
			INNER JOIN api_website_domain ON api_credential.api_website_id = api_website_domain.api_website_id
			WHERE api_credential.api_key = :api_key 
		';
		
		$params = array(
			':api_key' => $api_key
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->select_single($sql, $params);
			
		} catch(Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to retrieve api key.');
		}
		
		return $data;
	}
	
	
}
?>