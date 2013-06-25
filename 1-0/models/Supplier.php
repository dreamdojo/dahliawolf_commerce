<?php
class Supplier extends db {

	private $table 		= 'supplier';
	private $table_lang = 'supplier_lang';
	private $table_shop = 'supplier_shop';

	public function __construct() { 		
  		parent::__construct();
	}
	
	public function get_supplier_of_product($params) {

		$query = 'SELECT product_supplier.id_supplier
			FROM product_supplier
				INNER JOIN supplier ON supplier.id_supplier = product_supplier.id_supplier
			WHERE product_supplier.id_product = :id_product
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
		);
		
		$stmt = $this->run($query, $values);
		$this->result = $stmt->fetchAll();
       	
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get shop categories.');
      	}
	
		$pass_params = array();
		$pass_params['conditions']['id_supplier'] = $this->result[0]['id_supplier'];
		$pass_params['conditions']['id_lang'] = $params['conditions']['id_lang'];
		$pass_params['conditions']['id_shop'] = $params['conditions']['id_shop'];
		$supplier_info = $this->get_supplier($pass_params);
	
        return resultArray(true, $supplier_info);
	
		//$row = $this->get_row($this->table,$condition);
		//return json_encode($row) ;
	}
	
	public function get_supplier($params) {
		$query = '
			SELECT supplier.id_supplier, supplier.name, supplier_lang.description
			FROM supplier
				INNER JOIN supplier_shop ON supplier.id_supplier = supplier_shop.id_supplier
				INNER JOIN supplier_lang ON supplier.id_supplier = supplier_lang.id_supplier
			WHERE supplier_shop.id_shop = :id_shop
				AND supplier_lang.id_lang = :id_lang
				AND supplier.id_supplier = :id_supplier
		';
		
		$values = array(
			':id_shop' => $params['conditions']['id_shop']
			, ':id_lang' => $params['conditions']['id_lang']
			, ':id_supplier' => $params['conditions']['id_supplier']
		);
		
		$stmt = $this->run($query, $values);
		$this->result = $stmt->fetchAll();
       	
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get supplier info.');
      	}

        return resultArray(true, $this->result);
	}
	
	/*
	public function get_all_supplier() {
		$rows = $this->get_all($this->table);
		return json_encode($rows) ;
	}
	
	public function add_supplier($params) {
		$this->insert($this->table,$data);
		return $this->insert_id;
	}
	
	public function update_supplier($params){
		$res = $this->update($this->table,$data,$where);
		return $res;
	}
	
	public function delete_supplier($where=array()){
		$res = $this->delete($this->table,$where);
		return $res;
	}
	*/
}
?>
