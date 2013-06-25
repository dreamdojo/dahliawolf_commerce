<?php
require('models/database.php');

class Invoice extends db {

	private $order_invoice_table = 'order_invoice';
	private $order_invoice_payment_table = 'order_invoice_payment';
	private $order_invoice_tax_table = 'order_invoice_tax';

	public function __construct() {
		parent::__construct();
	}
	
	public function addInvoice($data=array()){
		$this->insert($this->order_invoice_table,$data);
		return $this->insert_id;
	}
	
	public function addInvoicepayment($data=array()){
		$this->insert($this->order_invoice_payment_table,$data);
		return true;
	}
	
	public function addInvoicetax($data=array()){
		$this->insert($this->order_invoice_tax_table,$data);
		return true;
	}
	
	public function updateInvoice($data=array(), $where){
		$msg = $this->update($this->order_invoice_table,$data,$where);
		return $msg;
	}
	
	public function updateInvoicetax($data=array(), $where){
		$msg = $this->update($this->order_invoice_tax_table,$data,$where);
		return $msg;
	}

	public function deleteInvoice($where=array()){
		$this->delete($this->order_invoice_table,$where);
		return true;
	}
	
	public function deleteInvoicepayment($where=array()){
		$this->delete($this->order_invoice_payment_table,$where);
		return true;
	}

	public function deleteInvoicetax($where=array()){
		$this->delete($this->order_invoice_tax_table,$where);
		return true;
	}

	public function updateInvoicepayment($data, $where=array()){
		$this->deleteInvoicepayment($where);
		$this->addInvoicepayment($data);
		return true;
	}
	
	public function addInvoicetax($data=array()){
		$this->insert($this->order_invoice_tax_table,$data);
		return true;
	}
	
	public function getInvoicetax($condition){
		$records = $this->get_all($this->order_invoice_tax_table, $condition);
		return json_encode($records);
	}
	
	public function getInvoicepayment($condition){
		$records = $this->get_all($this->order_invoice_payment_table, $condition);
		return json_encode($records);
	}
	
	public function getInvoice($condition){
		$records = $this->get_all($this->order_invoice_table, $condition);
		return json_encode($records);
	}
}
?>