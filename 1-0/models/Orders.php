<?php

class Orders extends _Model {

	const TABLE = 'orders';
	const PRIMARY_KEY_FIELD = 'id_order';

	protected $fields = array(
		'reference'
		, 'id_shop_group'
		, 'id_shop'
		, 'id_carrier'
		, 'id_delivery'
		, 'id_lang'
		, 'id_customer'
		, 'id_cart'
		, 'id_currency'
		, 'id_address_delivery'
		, 'id_address_invoice'
		, 'current_state'
		, 'secure_key'
		, 'payment'
		, 'conversion_rate'
		, 'module'
		, 'recyclable'
		, 'gift'
		, 'gift_message'
		, 'shipping_number'
		, 'total'
		, 'total_discounts'
		, 'total_discounts_tax_incl'
		, 'total_discounts_tax_excl'
		, 'total_paid'
		, 'total_paid_tax_incl'
		, 'total_paid_tax_excl'
		, 'total_paid_real'
		, 'total_products'
		, 'total_products_wt'
		, 'total_shipping'
		, 'total_shipping_tax_incl'
		, 'total_shipping_tax_excl'
		, 'carrier_tax_rate'
		, 'total_wrapping'
		, 'total_wrapping_tax_incl'
		, 'total_wrapping_tax_excl'
		, 'invoice_number'
		, 'delivery_number'
		, 'invoice_date'
		, 'delivery_date'
		, 'valid'
		, 'date_add'
		, 'date_upd'
		, 'product_tax'
		, 'shipping_tax'
		, 'discount_tax'
		, 'wrapping_tax'
		, 'payment_status'
	);

    public function add_customer($user, $cust) {
        if(isset($user) && isset($cust)) {
            $query = "
                INSERT INTO dahliawolf_v1_2013.customers (user_id, customer_id)
                VALUES (:userId, :custId)
            ";

            $values = array(
                ':userId' => $user,
                ':custId' => $cust
            );

            try {
                $data = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);

                return $data;
            } catch (Exception $e) {
                self::$Exception_Helper->server_error_exception('Unable to get order details.');
            }
        }
    }

	public function get_shipping_method($id_order) {
		$query = '
			SELECT delivery.name AS delivery
				, carrier.name AS carrier
			FROM orders
				INNER JOIN delivery ON orders.id_delivery = delivery.id_delivery
				INNER JOIN carrier ON delivery.id_carrier = carrier.id_carrier
			WHERE orders.id_order = :id_order
		';
		$values = array(
			':id_order' => $id_order
		);

		try {
			$shipping_method = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $shipping_method;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get order payment details.');
		}
	}
	
	public function get_user_order_shipping_method($id_order, $user_id) {
		$query = '
			SELECT delivery.name AS delivery
				, carrier.name AS carrier
			FROM orders
				INNER JOIN customer ON orders.id_customer = customer.id_customer
				INNER JOIN delivery ON orders.id_delivery = delivery.id_delivery
				INNER JOIN carrier ON delivery.id_carrier = carrier.id_carrier
			WHERE orders.id_order = :id_order AND customer.user_id = :user_id
		';
		$values = array(
			':id_order' => $id_order
			, ':user_id' => $user_id
		);

		try {
			$shipping_method = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $shipping_method;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get order payment details.');
		}
	}

	/*public function get_points_spent($id_order) {
		$query = '

		';
		$values = array(
			':id_order' => $id_order
		);

		try {
			$order = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $order;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get points spent.');
		}
	}*/

	public function get_order_details($id_order) {
		$query = '
			SELECT orders.*
				, order_detail.id_order_detail, order_detail.product_quantity
				, SUM(order_detail_return.product_quantity) AS return_product_quantity
				, IFNULL(rejected_returns.rejected_quantity, 0) AS rejected_return_quantity
			FROM orders
				INNER JOIN order_detail ON orders.id_order = order_detail.id_order
				LEFT JOIN order_detail_return ON order_detail.id_order_detail = order_detail_return.id_order_detail
				LEFT JOIN (
					SELECT order_detail.id_order_detail, SUM(order_detail_return.product_quantity) AS rejected_quantity
					FROM order_detail
					INNER JOIN order_detail_return ON order_detail.id_order_detail = order_detail_return.id_order_detail
					WHERE order_detail_return.status = \'Rejected\'
					GROUP BY order_detail.id_order_detail
				) AS rejected_returns ON order_detail.id_order_detail = rejected_returns.id_order_detail
			WHERE orders.id_order = :id_order
			GROUP BY order_detail.id_order_detail
		';
		$values = array(
			':id_order' => $id_order
		);

		try {
			$order = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);

			return $order;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get order details.');
		}
	}
	
	public function get_return_shipment_info($id_order, $user_id) {
		$query = '
			SELECT orders.id_order, customer.user_id, orders.id_lang, orders.id_shop, orders.id_address_delivery, SUM(IFNULL(order_detail.product_weight, 0) * IFNULL(order_detail_return.product_quantity, 0)) AS total_weight, delivery.code AS service_code, delivery.label_code AS service_label_code, delivery.name AS service_name, delivery.is_intl, carrier.name AS carrier
			FROM orders
			INNER JOIN customer ON orders.id_customer = customer.id_customer
			INNER JOIN delivery ON orders.id_delivery = delivery.id_delivery
			INNER JOIN carrier ON delivery.id_carrier = carrier.id_carrier
			INNER JOIN order_detail ON orders.id_order = order_detail.id_order
			INNER JOIN order_detail_return ON order_detail.id_order_detail = order_detail_return.id_order_detail
			WHERE orders.id_order = :id_order AND order_detail_return.status = :return_status AND customer.user_id = :user_id
			GROUP BY orders.id_order
		';
		
		$values = array(
			':id_order' => $id_order
			, ':return_status' => 'Pending'
			, ':user_id' => $user_id
		);

		try {
			$order = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $order;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get order return shipment details.');
		}
	}
	
}
?>