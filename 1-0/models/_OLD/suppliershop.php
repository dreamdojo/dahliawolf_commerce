<?php 

require('models/database.php');

class SupplierShop extends db {

	private $table='supplier_shop';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addSupplierShop($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateSupplierShop($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteSupplierShop($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getSupplierShop($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }

}

?>