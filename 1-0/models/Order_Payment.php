<?php

class Order_Payment extends _Model {

	const TABLE = 'order_payment';
	const PRIMARY_KEY_FIELD = 'id_order_payment';

	protected $fields = array(
		'id_order'
		, 'payment_method_id'
		, 'order_reference'
		, 'id_currency'
		, 'amount'
		, 'payment_method'
		, 'conversion_rate'
		, 'transaction_id'
		, 'card_number'
		, 'card_brand'
		, 'card_expiration'
		, 'card_holder'
		, 'date_add'
		, 'amount_authorized'
		, 'authorization_transaction_id'
		, 'void_transaction_id'
		, 'refund_transaction_id'
	);

	public function get_order_payment($id_order) {
		$query = '
			SELECT order_payment.*
				, payment_method.name AS payment_method
			FROM order_payment
				INNER JOIN admin_offline_v1_2013.payment_method ON order_payment.payment_method_id = payment_method.payment_method_id
			WHERE order_payment.id_order = :id_order
		';
		$values = array(
			':id_order' => $id_order
		);

		try {
			$order_payment = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $order_payment;
		}
		catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get order payment details.');
		}
	}
}
?>