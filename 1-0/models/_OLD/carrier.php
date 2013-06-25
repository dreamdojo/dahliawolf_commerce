<?php 

require('models/database.php');

class Carrier extends db {

	private $table='carrier';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addCarrier($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateCarrier($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteCarrier($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getCarrier($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }
	
	  public function allCarrier(){
	
		  $rows = $this->get_all($this->table);
		  return json_encode($rows) ;
	  }

}

?>