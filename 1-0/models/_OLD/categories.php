<?php

class Categories extends db {

	private $table = 'categories';

	public function __construct() { 
		parent::__construct();
	}

	// ?api=category&function=addcategory&params={"data":{"name":"rebeka;sfcsd"}}
	public function addCategory($params = array()) {
		$error = NULL;
		
		if (empty($params['data'])) {
			$error = 'Data is required.';
		}
		else if (!is_array($params['data'])) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$this->insert($this->table, $params['data']);
		
		$insert_id = $this->insert_id;
		
		if (empty($insert_id)) {
			return resultArray(false, NULL, 'Could not add category.');
		}
		
		return resultArray(true, $insert_id);
	}

	// ?api=category&function=updatecategory&params={"data":{"name":"my test@"},"where":{"id":"4"}}
	public function updateCategory($params = array()) {
		$error = NULL;
		
		if (empty($params['data'])) {
			$error = 'Data is required.';
		}
		else if (!is_array($params['data'])) {
			$error = 'Invalid data.';
		}
		else if (empty($params['where'])) {
			$error = 'Where conditions are required.';
		}
		else if (!is_array($params['where'])) {
			$error = 'Invalid conditions.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->update($this->table, $params['data'], $params['where']);
		if ($res === false) {
			 return resultArray(false, NULL, 'Could not update category.');
		}
		
		return resultArray(true, $res);
	}
	
	// ?api=category&function=deletecategory&params={"where":{"id":"3"}}
	public function deleteCategory($params = array()) {
		$error = NULL;
		
		if (empty($params['where'])) {
			$error = 'Where conditions are required.';
		}
		else if (!is_array($params['where'])) {
			$error = 'Invalid conditions.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->delete($this->table, $params['where']);
		if ($res === false) {
			 return resultArray(false, NULL, 'Could not delete category.');
		}

		return resultArray(true, $res);
	}

	// ?api=category&function=getCategory&params={"conditions":{"id":"4"}}
	public function getCategory($params = array()) {
		$error = NULL;
		
		/*
		$parameters = array(
			'conditions' => array(
				'id' => '3'
			)
		);
		echo '&params=' . json_encode($parameters);
		*/
		if (empty($params['conditions'])) {
			$error = 'Conditions are required.';
		}
		else if (!is_array($params['conditions'])) {
			$error = 'Invalid conditions.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$row = $this->get_row($this->table, $params['conditions']);
		if ($row === false) {
			 return resultArray(false, NULL, 'Could not get category.');
		}
		
		return resultArray(true, $row);
	}

	// ?api=category&function=allcategory
	public function allCategory() { 
		$rows = $this->get_all($this->table);
		if ($rows === false) {
			 return resultArray(false, NULL, 'Could not get categories.');
		}
		
		return resultArray(true, $rows);
	}
}
?>