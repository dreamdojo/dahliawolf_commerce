<?php
/**
 * Tax class
 *
 * @since   02-13-2013
 *
 * - List of various functions of tax
 */

class Tax extends db {

	private $table_store = 'store';
	private $table_trgp = 'tax_rules_group_shop';
	private $table_trg = 'tax_rules_group';
	private $table_tr = 'tax_rule';
	private $table_tax = 'tax';
	private $table_pct = 'product_country_tax';
	#$tax_obj = new Tax();
	
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
	 * Find tax rate
	 *
	 *
	 * @param where
	 * @param 
	 * @return tax rate
	 */
	public function findTaxRate($store_id='1', $lang_id='1', $product_id, $user_id, $state_id, $country_id, $zip_code, $id_tax, $status = '1'){
		$error = NULL;

		# find State tax id
		if(isset($state_id) && isset($country_id)){
			$id_tax  = $this->findTaxId((int)$state_id,(int)$country_id,NULL);
			$id_tax  = json_decode($id_tax, true); // Decode result
			$id_tax = array_shift($id_tax); // Remove array keys
			$id_tax = $id_tax['id_tax'];
		}
		
		# find product tax id
		elseif(isset($country_id) && isset($product_id)){
			# find tax id
			$id_tax  = $this->findTaxId(NULL,(int)$country_id,(int)$product_id);
			$id_tax  = json_decode($id_tax, true); // Decode result
			$id_tax = array_shift($id_tax); // Remove array keys
			$id_tax = $id_tax['id_tax'];
		}
		
		if(!empty($id_tax)){
			if($status){
				$where['active'] = $status;
			}
			$where['id_tax'] = (int) $id_tax;
		}
		$fields = 'rate';
		$row = $this->get_row($this->table_tax, $where, $fields);
		if ($row === false) {
			return resultArray(false, NULL, 'Check your function parameters.');
		}			
		return json_encode($row);
	}
	/**
	 * Find Tax ID
	 *
	 *
	 * @param state_id
	 * @param country_id
	 * @return Tax Id
	 */
	public function findTaxId($state_id, $country_id, $product_id){
		if(!empty($country_id)){
			$where['id_country'] = $country_id;
		}
		if(!empty($state_id)){
			$where['id_state'] = $state_id;
		}
		if(!empty($state_id)){
			$where['id_state'] = $state_id;
		}
		$fields = 'id_tax';
		# State tax
		if(isset($state_id) && isset($country_id))
		{
			$id_tax = $this->get_row($this->table_tr, $where, $fields);
		}
		# Product country tax id
		elseif(isset($country_id) && isset($product_id))
		{
			$id_tax = $this->get_row($this->table_pct, $where, $fields);
		}
		return json_encode($id_tax);
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