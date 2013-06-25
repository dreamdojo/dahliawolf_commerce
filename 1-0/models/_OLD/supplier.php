<?php 

require('models/database.php');

class Supplier extends db {

	private $table='supplier';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addSupplier($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateSupplier($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteSupplier($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getSupplier($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }
	
	  public function allSupplier(){
	
		  $rows = $this->get_all($this->table);
		  return json_encode($rows) ;
	  }

}

?>