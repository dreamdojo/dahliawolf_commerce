<?php 

require('models/database.php');

class CarrierGroup extends db {

	private $table='carrier_group';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addCarrierGroup($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateCarrierGroup($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteCarrierGroup($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getCarrierGroup($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }

}

?>