<?php 

require('models/database.php');

class WarehouseProductLocation extends db {

	private $table='warehouse_product_location';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addWarehouseProductLocation($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateWarehouseProductLocation($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteWarehouseProductLocation($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getWarehouseProductLocation($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }

}

?>