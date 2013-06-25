<?php
require('models/database.php');

class Product extends db {

	private $product_table = 'products';
	private $product_lang_table = 'product_lang';
	private $category_product_table = 'category_product';
	private $product_attribute_table = 'product_attribute';
	private $product_attribute_combination_table = 'product_attribute_combination';
	private $feature_product_table = 'feature_product';
	private $product_shop_table = 'product_shop';
	private $product_supplier_table = 'product_supplier';
	private $product_download_table = 'product_download';
	private $product_country_tax_table = 'product_country_tax';
	private $product_group_reduction_cache_table = 'product_group_reduction_cache';
	private $product_attachment_table = 'product_attachment';
	private $product_sale_table = 'product_sale';
	private $product_comment_table = 'product_comment';
	private $product_comment_grade_table = 'product_comment_grade';
	private $product_comment_report_table = 'product_comment_report';
	private $product_comment_usefulness_table = 'product_comment_usefulness';
	private $product_comment_criterion_product_table = 'product_comment_criterion_product';

	public function __construct() {
		parent::__construct();
	}

	public function addProduct($data=array()){
		$this->addProductbasicinfo($data['basic_info']);
		$product_id = $this->insert_id;
		$this->addProductcategory(array('id_product' => $product_id, 'id_category' => $data['basic_info']['id_category']));
		$this->addProductlang($data['product_lang_info']);
		return $product_id;
	}

	public function addProductbasicinfo($data=array()){
		$this->insert($this->product_table,$data);
		return $this->insert_id;
	}
	
	public function addProductsupplier($data=array()){
		$this->insert($this->product_supplier,$data);
		return $this->insert_id;
	}

	public function addProductattachments($data=array()){
		$this->insert($this->product_attachment_table,$data);
		return $this->insert_id;
	}

	public function addProductlang($data=array()){
		foreach($data as $key => $value) {
			$lang_data = array('id_product' => $product_id, 'id_shop' => $data['shop_id'], 'id_lang' => $key, 'description' => $value['description'],'description_short' => $value['description_short'], 'name' => $value['name']);
			$this->insert($this->product_lang_table, $lang_data);			
		}
		return true;
	}

	public function deleteProductlang($where=array()){
		$this->delete($this->product_lang_table, $where);
		return true;
	}
	
	public function deleteProductsupplier($where=array()){
		$this->delete($this->product_supplier, $where);
		return true;
	}
	
	public function deleteProductattachments($where=array()){
		$this->delete($this->product_attachment_table, $where);
		return true;
	}

	public function addProductcategory($data=array()){
		$this->insert($this->category_product_table,$data);
		return $this->insert_id;
	}
	
	public function addProductcomment($data=array()){
		$this->insert($this->product_comment_table,$data);
		return $this->insert_id;
	}
	
	public function deleteProductcomment($where=array()){
		$this->delete($this->product_comment_usefulness_table,$where);
		$this->delete($this->product_comment_report_table,$where);
		$this->delete($this->product_comment_grade_table,$where);
		$this->delete($this->product_comment_table,$where);
		return true;
	}

	public function updateProduct($data=array(), $where=array()){
		$res = $this->updateProductbasicinfo($data['basic_info'],$where);
		$this->deleteProductlang($data, $where);
		$this->addProductlang($data['product_lang_info']);
		return true;
	}

	public function updateProductbasicinfo($data=array(), $where){
		$this->update($this->product_table, $data, $where);
		return true;
	}

	public function updateProductprice($data=array(), $where){
		$this->update($this->product_table, $data, $where);
		return true;
	}	

	public function deleteProductcategory($where){
		$this->delete($this->category_product_table, $data, $where);
		return true;
	}
	
	public function updateProductcategory($data=array(), $where){
		$this->deleteProductcategory($where);
		$this->addProductcategory($data);
		return true;
	}

	public function addProductfeature($data=array()){
		$this->insert($this->feature_product_table,$data);
		return $this->insert_id;
	}
	
	public function deleteProductfeature($where){
		$this->delete($this->feature_product_table,$where);
		return true;
	}

	public function addProductattribute($data=array()){
		$this->insert($this->product_attribute_table,$data);
		return $this->insert_id;
	}

	public function deleteProductattribute($where){
		$this->delete($this->product_attribute_table,$where);
		return $this->insert_id;
	}
	
	public function addProductshop($data=array()){
		$this->insert($this->product_shop_table,$data);
		return true;
	}
	
	public function deleteProductshop($where){
		$this->delete($this->product_shop_table,$where);
		return true;
	}

	public function deleteProduct($where=array()){
		$this->deleteProductfeature($where);
		$this->deleteProductattribute($where);
		$this->deleteProductcategory($where);
		$this->deleteProductshop($where);
		$this->deleteProductlang($where);
		$this->deleteProductcomment($where);
		$this->deleteProductattachments($where);
		$this->delete($this->product_sale_table,$where);
		$this->delete($this->product_comment_criterion_product_table,$where);
		$this->delete($this->product_tag,$where);
		$this->delete($this->product_supplier_table,$where);
		$this->delete($this->product_table,$where);
		return true;
	}

	public function getProduct($condition){
		$row = array();
		$row['basic_info'] = $this->get_row($this->product_table,$condition);
		$row['product_lang_info'] = $this->getProductlang($condition);
		return json_encode($row) ;
	}
	
	public function allProduct($where = array()){
		$product_info = array();
		$product_rec = $this->get_all($this->product_table, $where);
		foreach($product_rec as $value) {
			$product_info['basic_info'] = $value;
			$product_lang_info = $this->getProductlang($where);
			$product_info['product_lang_info'] = $product_lang_info;
		}
		return json_encode($product_info);
	}
	
	public function getProductlang($condition){
		$row = $this->get_row($this->product_lang_table,$condition);
		return $row;
	}
	
	public function getProductfeatures($condition){
		$row = $this->get_all($this->feature_product_table, $condition);
		return json_encode($records);
	}
	
	public function getProductattributes($condition){
		$records = $this->get_all($this->product_attribute_table, $condition);
		return json_encode($records);
	}
	
	public function getProductcomments($condition){
		$records = $this->get_all($this->product_comment_table, $condition);
		return json_encode($records);
	}
}
?>