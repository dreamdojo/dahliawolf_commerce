<?php 

require('models/database.php');

class Product extends db {

	private $table='product';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addProduct($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateProduct($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteProduct($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getProduct($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }
	
	  public function allProduct(){
	
		  $rows = $this->get_all($this->table);
		  return json_encode($rows) ;
	  }

}

?>