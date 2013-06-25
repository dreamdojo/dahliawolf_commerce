<?php 

require('models/database.php');

class Warehouse extends db {

	private $table='warehouse';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addWarehouse($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateWarehouse($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteWarehouse($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getWarehouse($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }
	
	  public function allWarehouse(){
	
		  $rows = $this->get_all($this->table);
		  return json_encode($rows) ;
	  }

}

?>