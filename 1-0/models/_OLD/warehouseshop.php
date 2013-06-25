<?php 

require('models/database.php');

class WarehouseShop extends db {

	private $table='warehouse_shop';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addWarehouseShop($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateWarehouseShop($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteWarehouseShop($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getWarehouseShop($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }

}

?>