<?php
require('models/database.php');

class Orders extends db {

	private $orders_table = 'orders';
	private $order_carrier_table = ' Structureorder_carrier';
	private $order_cart_rule_table = 'order_cart_rule';
	private $order_detail_table = 'order_detail';
	private $order_detail_tax_table = 'order_detail_tax';
	private $order_history_table = 'order_history';
	private $order_message_table = 'order_message';
	private $order_message_lang_table = 'order_message_lang';
	private $order_payment_table = 'order_payment';
	private $order_return_table = 'order_return';
	private $order_return_detail_table = 'order_return_detail';
	private $order_return_state_table = 'order_return_state';
	private $order_return_state_lang_table = 'order_return_state_lang';
	private $order_slip_table = 'order_slip';
	private $order_slip_detail_table = 'order_slip_detail';
	private $order_state_table = 'order_state';
	private $order_state_lang_table = 'order_state_lang';

	public function __construct() {
		parent::__construct();
	}
	
	public function addOrder($data=array()){
		$this->insert($this->orders_table,$data['order_basic_info']);
		$order_id = $this->insert_id;
		$this->addOrdercarrier(array('order_carrier_info' => $data['order_carrier_info'], 'order_id' => $order_id));
		$order_carrier_id = $this->insert_id;
		$this->addOrderdetail(array('order_details_info' => $data['order_details_info'], 'order_products' => $data['order_products'], 'order_id' => $order_id, 'id_order_invoice' => $order_id));
		return $order_id;
	}

	public function addOrderdetail($data=array()){
		foreach($data['order_products'] as $key => $value) {
			array('id_order' => $data['order_id'], $data['order_details_info']);
			$this->insert($this->orders_table,$data['order_basic_info']);
		}
		return true;
	}
	
	public function addOrderhistory($data=array()){
		$this->insert($this->order_history_table,$data);
		$order_history_id = $this->insert_id;
		return $order_history_id;
	}
	
	public function deleteOrderhistory($condition){
		$this->delete($this->order_history_table, $condition);
		return true;
	}
	
	public function getOrderhistory($condition){
		$row = $this->get_all($this->order_history_table, $condition);
		return json_encode($records);
	}
	
	public function addOrderdetail($data=array()){
		$this->insert($this->order_state_table,$data);
		$order_state_id = $this->insert_id;
		return $order_detail_id;
	}
	
	public function getOrderslip($condition=array()){
		$row = $this->get_all($this->order_slip_table, $condition);
		return json_encode($records);
	}
	
	public function addOrdermessage($data=array()){
		$this->insert($this->order_message_table,$data);
		$order_msg_id = $this->insert_id;
		return $order_msg_id;
	}
	
	public function deleteOrdermessage($where){
		$this->delete($this->order_message_table,$where);		
		return true;
	}
	
	public function addOrdermessagelang($data=array()){
		foreach($data as $key => $value) {
			$this->insert($this->order_message_lang_table, $data);
		}
		return true;
	}
	
	public function deleteOrdermessagelang($where){
		$this->delete($this->order_message_lang_table,$where);		
		return true;
	}
	
	public function updateOrdermessage($data, $where){
		$this->update($this->order_message_table, $data, $where);		
		return true;
	}
	
	public function getOrdermessage($where){
		$row = $this->get_all($this->order_message_table, $condition);
		return json_encode($row);
	}

	public function addOrderstatus($data=array()){
		$this->insert($this->order_state_table,$data);
		$order_state_id = $this->insert_id;
		return $order_detail_id;
	}

	public function addOrderstatuslang($data=array()){
		foreach($data as $key => $value) {
			$this->insert($this->order_state_lang_table);
		}
		return true;
	}
	
	public function deleteOrderstate($where){
		$this->delete($this->order_state_table,$where);		
		return true;
	}
	
	public function getOrderstate($where){
		$records = $this->get_all($this->order_state_table,$where);
		return json_encode($records);
	}
	
	public function getOrderstatelang($where){
		$records = $this->get_all($this->order_state_lang_table,$where);
		return json_encode($records);
	}
	
	public function deleteOrderstatelang($where){
		$this->delete($this->order_state_lang_table,$where);		
		return true;
	}

	public function addOrdercartrule($data=array()){
		$this->insert($this->order_cart_rule_table,$data);
		$order_cart_rule_id = $this->insert_id;
		return $order_cart_rule_id;
	}
	
	public function getOrdercartrule($where){
		$records = $this->get_all($this->order_cart_rule_table,$where);
		return json_encode($records);
	}
	
	public function deleteOrdercartrule($where){
		$this->delete($this->order_cart_rule_table,$where);
		return true;
	}

	public function addOrderreturn($data=array()){
		$this->insert($this->order_return_table,$data);
		$order_return_id = $this->insert_id;
		return $order_return_id;
	}
	
	public function getOrderreturn($where){
		$rows = $this->get_all($this->order_return_table,$where);
		return json_encode($rows);
	}
	
	public function addOrderreturndetail($data=array()){
		$this->insert($this->order_return_detail_table,$data);
		$order_return_detail_id = $this->insert_id;
		return $order_return_detail_id;
	}
	
	public function getOrderreturndetail($where){
		$rows = $this->get_all($this->order_return_detail_table,$where);
		return json_encode($rows);
	}
	
	public function deleteOrderreturndetails($where)){
		$this->delete($this->order_return_detail_table,$where);
		return true;
	}
	
	public function addOrderreturnstate($data=array()){
		$this->insert($this->order_return_state_table,$data);
		$order_return_state_id = $this->insert_id;
		return $order_return_id;
	}
	
	public function getOrderreturnstate($where){
		$rows = $this->get_all($this->order_return_state_id,$where);
		return json_encode($rows);
	}
	
	public function deleteOrderreturnstate($where){
		$rows = $this->get_all($this->order_return_state_id,$where);
		return json_encode($rows);
	}
	
	public function addOrderreturnstatelang($data=array()){
		$this->insert($this->order_return_state_lang_table,$data);
		$order_return_state_lang_id = $this->insert_id;
		return $order_return_state_lang_id;
	}
	
	public function getOrderreturnstate($where){
		$rows = $this->get_all($this->order_return_state_lang_table,$where);
		return json_encode($rows);
	}
	
	public function deleteOrderreturnstatelang($where){
		$this->get_all($this->order_return_state_lang_table,$where);
		return true;
	}
	
	public function addOrderreturn($data=array()){
		$this->insert($this->order_return_table,$data);
		$order_return_id = $this->insert_id;
		return $order_return_id;
	}
	
	public function getOrderreturn($data=array()){
		$rows = $this->get_all($this->order_return_table,$where);
		return json_encode($rows);
	}

	public function addOrdercarrier($data=array()){
		$this->insert($this->order_state_table,$data);
		$order_state_id = $this->insert_id;
		return $order_detail_id;
	}
	
	public function getOrdercarrier($condition){
		$rows = $this->get_all($this->order_carrier_table,$where);
		return json_encode($rows);
	}
	
	
	
	public function updateOrdercarrier($data = array(), $condition){
	}
	
	public function addOrderreturndetail($data=array()){
	}
	
	public function getOrderreturndetail($condition){
		$rows = $this->get_all($this->order_carrier_table,$where);
		return json_encode($rows);
	}
	
	public function updateOrder($data=array(), $where){
		$this->update($this->order_table,$data,$where);		
		return true;
	}
	
	public function updateOrderdetail($data=array(), $where){
		$this->update($this->order_detail_table,$data,$where);
		return true;
	}
	
	public function getOrdercarrier($condition){
		$rows = $this->get_all($this->order_carrier_table,$where);
		return json_encode($rows);
	}
	
	public function updateOrderreturndetail($data=array(), $where){
		$this->update($this->order_return_detail_table,$where);		
		return true;
	}
	
	public function updateOrderreturn($data=array(), $where){
		$this->update($this->order_return_table,$data,$where);
		return true;
	}
	
	public function updateOrderreturndetail($data=array(), $where){
		$this->update($this->order_return_detail_table,$data,$where);
		return true;
	}
	
	public function deleteOrderreturn($condition){
		$this->delete($this->order_return_table,$where);		
		return true;
	}
	
	public function deleteOrdermsglang($where){
		$this->delete($this->order_message_lang_table,$where);		
		return true;
	}
	
	public function deleteOrdermsg($where){
		$this->delete($this->order_message_table,$where);		
		return true;
	}
	
	public function deleteOrdercarrier($where){
		$this->delete($this->order_carrier_table,$where);		
		return true;
	}
	
	public function deleteOrderslip($where){
		$this->delete($this->order_slip_table,$where);		
		return true;
	}
	
	public function deleteOrderpayment($where){
		$this->delete($this->order_payment_table,$where);		
		return true;
	}
	
	public function deleteOrdertax($where){
		$this->delete($this->order_detail_tax_table,$where);		
		return true;
	}
}
?>