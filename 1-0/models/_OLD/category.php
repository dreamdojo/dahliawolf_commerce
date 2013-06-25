<?php require('models/database.php');

class Category extends db {

	private $table='categories';

	public function __construct() { 
		parent::__construct();
	}

	public function addCategory($data=array()){
		$this->insert($this->table,$data);
		return $this->insert_id;
	}

	public function updateCategory($data = array(), $where = array()){
		$res = $this->update($this->table,$data,$where);
		return $res;
	}

	public function deleteCategory($where=array()){
		$res = $this->delete($this->table,$where);
		return $res;
	}

	public function getCategory($condition){
		$row = $this->get_row($this->table,$condition);
		return json_encode($row) ;
	}

	public function allCategory(){
		$rows = $this->get_all($this->table);
		return json_encode($rows) ;
	}
}
?>