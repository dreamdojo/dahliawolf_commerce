<?php
/**
 * Tax class
 *
 * @since   02-13-2013
 *
 * - List of various functions of tax
 */

require('models/database.php');

class Tax extends db {

	private $table_store = 'store';
	private $table_trgp = 'tax_rules_group_shop';
	private $table_trg = 'tax_rules_group';
	private $table_tr = 'tax_rule';
	
	
	function __construct(){
		parent::__construct();
	}
	/**
	 * Set Store id
	 * 
	 *
	 * @param store_id
	 * @param status
	 * @return store id
	 */
	public function setStoreId($store_id='1', $status = '1'){
		$where=array();
		$where['id_store'] = $store_id;
		$where['active'] = $status;
		
		$row = $this->get_row($this->table_store, $where);
		if ($row === false) {
			 return resultArray(false, NULL, 'This store is not Exist.');
		}
		return $store_id;
		
	}
	/**
	 * Get id_tax_rules_group table data
	 * The $store_id passed.
	 *
	 *
	 * @param where
	 * @param fields
	 * @return json results 
	 */

	public function getShopTaxGroups($where=array(), $fields='*'){
		$error = NULL;
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$rows = $this->get_all($this->table_trgp, $where, $fields);
		if ($rows === false) {
			 return resultArray(false, NULL, 'Could not get shop tax groups.');
		}
		return json_encode($rows);
	}
	/**
	 * Get id_tax_rules_group, name and active values of tax rules group
	 *
	 *
	 * @param where
	 * @param fields
	 * @return json results
	 */
	public function getTaxGroups($where=array(), $fields = '*'){
	 	$error = NULL;
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$row = $this->get_row($this->table_trg, $where, $fields);
		if ($row === false) {
			 return resultArray(false, NULL, 'Could not get tax Groups.');
		}
		return json_encode($row) ;
	}

	/**
	 * Get tax_rule table records
	 *
	 *
	 * @param where
	 * @param fields
	 * @return json results
	 */
	public function getTaxRules($where=array(), $fields = '*'){
	 	$error = NULL;
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$row = $this->get_row($this->table_tr, $where, $fields);
		if ($row === false) {
			 return resultArray(false, NULL, 'Could not get tax rules.');
		}
		return json_encode($row) ;
	}
	/**
	 * Edit tax_rule table records
	 *
	 *
	 * @param where
	 * @param data
	 * @return updated array
	 */
	public function updateTaxRule($data = array(), $where = array()){
		$error = NULL;
		if (empty($data) || empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data) || !is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->update($this->table,$data,$where);
		
		if ($res === false) {
			 return resultArray(false, NULL, 'Could not update tax rule.');
		}
		
		return $res;
	}
	/**
	 * delete tax_rule table records
	 *
	 *
	 * @param where
	 * @return deleted record
	 */
	public function deleteTaxRule($where=array()){
		$error = NULL;
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->delete($this->table,$where);
		if ($res === false) {
			return resultArray(false, NULL, 'Could not delete tax rule.');
		}
		return $res;
	}
	/**
	 * Add tax_rule table records
	 *
	 *
	 * @param data
	 * @return add tax rule
	 */
	public function addTaxRule($data=array()){
		$error = NULL;
		if (empty($data)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$this->insert($this->table,$data);
		
		$insert_id = $this->insert_id;
		
		if (empty($insert_id)) {
			return resultArray(false, NULL, 'Could not add tax rule.');
		}
		return $insert_id;
	}
}

?>