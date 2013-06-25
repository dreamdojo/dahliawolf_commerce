<?php 

require('models/database.php');

class StoreShop extends db {

	private $table='store_shop';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addStoreShop($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateStoreShop($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteStoreShop($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getStoreShop($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }
	
	  public function allStoreShop(){
	
		  $rows = $this->get_all($this->table);
		  return json_encode($rows) ;
	  }

}

?>