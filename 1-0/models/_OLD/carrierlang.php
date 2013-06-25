<?php 

require('models/database.php');

class CarrierLang extends db {

	private $table='carrier_lang';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addCarrierLang($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateCarrierLang($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteCarrierLang($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getCarrierLang($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }

}

?>