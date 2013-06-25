<?php 

require('models/database.php');

class CarrierZone extends db {

	private $table='carrier_zone';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addCarrierZone($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateCarrierZone($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteCarrierZone($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getCarrierZone($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }

}

?>