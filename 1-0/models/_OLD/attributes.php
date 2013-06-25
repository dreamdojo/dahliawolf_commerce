<?php
require('models/database.php');

class Attributes extends db {

	private $attribute_table = 'attribute';
	private $attribute_group_table = 'attribute_group';
	private $attribute_group_lang_table = 'attribute_group_lang';
	private $attribute_group_shop_table = 'attribute_group_shop';
	private $attribute_lang_table = 'attribute_lang';
	private $attribute_shop_table = 'attribute_shop';

	public function __construct() {
		parent::__construct();
	}

	public function addAttributegroup($data=array()){
		$is_color = $this->check_color_exists($data['group_type']);
		$this->insert($this->attribute_group_table, array('is_color_group' => $is_color, 'group_type' => $data['group_type']));
		$attribute_group_id = $this->insert_id;
		
		$this->insert($this->attribute_group_shop_table,array('id_attribute_group' => $attribute_group_id, 'id_shop' => $data['id_shop']));
		$this->addAttributegrouplang($data['attribute_group_lang']);
		return $attribute_group_id;
	}

	public function addAttributegrouplang($data=array()){
		foreach($data as $key => $value) {
			if($value != '') {
				$data = array('id_attribute_group' => $attribute_group_id, 'id_lang' => $key, 'name' => $value);
				$this->insert($this->attribute_group_lang_table,$data);
			}
		}
		return $this->insert_id;
	}
	
	public function check_color_exists($data) {
		if($data['group_type'] == 'color') {
			$is_color = 1;
		}else{
			$is_color = 0;
		}
		return $is_color;
	}
	
	public function updateAttributegroup($data=array(), $where=array()){
		$is_color = $this->check_color_exists($data['group_type']);
		$where_attribute_id = array( 'id_attribute_group' =>$where['id_attribute_group']);
		$res = $this->update($this->attribute_group_table,array('is_color_group' => $is_color, 'group_type' => $data['group_type']),$where_attribute_id);
		$this->deleteAttributegrouplang($where_attribute_id);
		$this->addAttributegrouplang($data['attribute_group_lang']);
		return $res;
	}
	
	public function deleteAttributegrouplang($where=array()){
		$res = $this->delete($this->attribute_group_lang_table,$where);
		return $res;
	}
}
?>