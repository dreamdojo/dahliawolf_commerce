<?php 

require('models/database.php');

class SupplierLang extends db {

	private $table='supplier_lang';

	public function __construct() { 
	
		  parent::__construct();
	  }
	
	
	  public function addSupplierLang($data=array()){
	
		  $this->insert($this->table,$data);
		  return $this->insert_id;
	  
	  }
	
	  public function updateSupplierLang($data=array(), $where=array()){
	
		  $res = $this->update($this->table,$data,$where);
		  return $res;
	  
	  }
	
	  public function deleteSupplierLang($where=array()){
		  
		  $res = $this->delete($this->table,$where);
		  return $res;
	  }
	
	  public function getSupplierLang($condition){
	
		  $row = $this->get_row($this->table,$condition);
		  return json_encode($row) ;
	  
	  }

}

?>