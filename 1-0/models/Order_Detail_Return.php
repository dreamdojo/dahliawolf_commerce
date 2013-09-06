<?
class Order_Detail_Return extends _Model {
	const TABLE = 'order_detail_return';
	const PRIMARY_KEY_FIELD = 'id_order_detail_return';

	protected $fields = array(
		'id_order_detail'
		, 'product_quantity'
		, 'type'
		, 'status'
		, 'date_accepted'
		, 'date_rejected'
		, 'exchange_product_attribute_id'
	);
	
	public function get_pending_returns_by_order_for_shipment_label($id_order, $user_id, $id_shop, $id_lang) {
		$query = '
			SELECT order_detail.product_name AS product, order_detail.product_price, order_detail.product_weight
				, order_detail_return.id_order_detail_return, order_detail_return.product_quantity AS quantity
				, attributes.attribute_names AS attribute
			FROM orders
				INNER JOIN customer ON orders.id_customer = customer.id_customer
				INNER JOIN order_detail ON orders.id_order = order_detail.id_order
				INNER JOIN order_detail_return ON order_detail.id_order_detail = order_detail_return.id_order_detail
				LEFT JOIN (
					SELECT id_product_attribute, GROUP_CONCAT(CONCAT(attribute_group_name, \': \', attribute_name) ORDER BY attribute_group_name ASC SEPARATOR 0x1D) AS attribute_names
					FROM
					(
						SELECT product_attribute_combination.id_product_attribute, attribute_lang.name AS attribute_name, attribute_group_lang.name AS attribute_group_name
						FROM product_attribute_combination
							INNER JOIN attribute ON product_attribute_combination.id_attribute = attribute.id_attribute
							INNER JOIN attribute_lang ON (attribute.id_attribute = attribute_lang.id_attribute
								AND attribute_lang.id_lang = :id_lang)
							INNER JOIN attribute_group ON attribute_group.id_attribute_group = attribute.id_attribute_group
							INNER JOIN attribute_group_lang ON (attribute_group_lang.id_attribute_group = attribute_group.id_attribute_group
								AND attribute_lang.id_lang = :id_lang)
						GROUP BY product_attribute_combination.id_product_attribute_combination
					) AS product_combinations
					GROUP BY id_product_attribute
				) AS attributes ON order_detail.product_attribute_id = attributes.id_product_attribute
				
				LEFT JOIN (
					SELECT id_product_attribute, GROUP_CONCAT(CONCAT(attribute_group_name, \': \', attribute_name) ORDER BY attribute_group_name ASC SEPARATOR 0x1D) AS attribute_names
					FROM
					(
						SELECT product_attribute_combination.id_product_attribute, attribute_lang.name AS attribute_name, attribute_group_lang.name AS attribute_group_name
						FROM product_attribute_combination
							INNER JOIN attribute ON product_attribute_combination.id_attribute = attribute.id_attribute
							INNER JOIN attribute_lang ON (attribute.id_attribute = attribute_lang.id_attribute
								AND attribute_lang.id_lang = :id_lang)
							INNER JOIN attribute_group ON attribute_group.id_attribute_group = attribute.id_attribute_group
							INNER JOIN attribute_group_lang ON (attribute_group_lang.id_attribute_group = attribute_group.id_attribute_group
								AND attribute_lang.id_lang = :id_lang)
						GROUP BY product_attribute_combination.id_product_attribute_combination
					) AS product_combinations
					GROUP BY id_product_attribute
				) AS exchange_attributes ON order_detail_return.exchange_product_attribute_id = exchange_attributes.id_product_attribute
				
			WHERE orders.id_order = :id_order
				AND orders.id_shop = :id_shop
				AND orders.id_lang = :id_lang
				AND customer.user_id = :user_id
		';
		$values = array(
			':id_order' => $id_order
			, ':id_shop' => $id_shop
			, ':id_lang' => $id_lang
			, ':user_id' => $user_id
		);
		
		try {
			$returns = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);

			return $returns;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get order returns by order.');
		}
	}

	public function get_returns_by_order($id_order, $user_id, $id_shop, $id_lang) {
		$query = '
			SELECT orders.*
				, order_detail.id_order_detail, order_detail.product_id AS id_product, order_detail.product_name
				, order_detail_return.id_order_detail_return, order_detail_return.product_quantity AS return_product_quantity, order_detail_return.status, order_detail_return.type AS return_type
				, (SELECT product_file.product_file_id FROM product_file WHERE product_file.product_id = order_detail.product_id ORDER BY product_file.product_file_id ASC LIMIT 1) AS product_file_id
				, attributes.attribute_names AS attributes
				, exchange_attributes.attribute_names AS exchange_attributes
			FROM orders
				INNER JOIN customer ON orders.id_customer = customer.id_customer
				INNER JOIN order_detail ON orders.id_order = order_detail.id_order
				INNER JOIN order_detail_return ON order_detail.id_order_detail = order_detail_return.id_order_detail
				LEFT JOIN (
					SELECT id_product_attribute, GROUP_CONCAT(CONCAT(attribute_group_name, \': \', attribute_name) ORDER BY attribute_group_name ASC SEPARATOR 0x1D) AS attribute_names
					FROM
					(
						SELECT product_attribute_combination.id_product_attribute, attribute_lang.name AS attribute_name, attribute_group_lang.name AS attribute_group_name
						FROM product_attribute_combination
							INNER JOIN attribute ON product_attribute_combination.id_attribute = attribute.id_attribute
							INNER JOIN attribute_lang ON (attribute.id_attribute = attribute_lang.id_attribute
								AND attribute_lang.id_lang = :id_lang)
							INNER JOIN attribute_group ON attribute_group.id_attribute_group = attribute.id_attribute_group
							INNER JOIN attribute_group_lang ON (attribute_group_lang.id_attribute_group = attribute_group.id_attribute_group
								AND attribute_lang.id_lang = :id_lang)
						GROUP BY product_attribute_combination.id_product_attribute_combination
					) AS product_combinations
					GROUP BY id_product_attribute
				) AS attributes ON order_detail.product_attribute_id = attributes.id_product_attribute
				
				LEFT JOIN (
					SELECT id_product_attribute, GROUP_CONCAT(CONCAT(attribute_group_name, \': \', attribute_name) ORDER BY attribute_group_name ASC SEPARATOR 0x1D) AS attribute_names
					FROM
					(
						SELECT product_attribute_combination.id_product_attribute, attribute_lang.name AS attribute_name, attribute_group_lang.name AS attribute_group_name
						FROM product_attribute_combination
							INNER JOIN attribute ON product_attribute_combination.id_attribute = attribute.id_attribute
							INNER JOIN attribute_lang ON (attribute.id_attribute = attribute_lang.id_attribute
								AND attribute_lang.id_lang = :id_lang)
							INNER JOIN attribute_group ON attribute_group.id_attribute_group = attribute.id_attribute_group
							INNER JOIN attribute_group_lang ON (attribute_group_lang.id_attribute_group = attribute_group.id_attribute_group
								AND attribute_lang.id_lang = :id_lang)
						GROUP BY product_attribute_combination.id_product_attribute_combination
					) AS product_combinations
					GROUP BY id_product_attribute
				) AS exchange_attributes ON order_detail_return.exchange_product_attribute_id = exchange_attributes.id_product_attribute
				
			WHERE orders.id_order = :id_order
				AND orders.id_shop = :id_shop
				AND orders.id_lang = :id_lang
				AND customer.user_id = :user_id
		';
		$values = array(
			':id_order' => $id_order
			, ':id_shop' => $id_shop
			, ':id_lang' => $id_lang
			, ':user_id' => $user_id
		);

		try {
			$returns = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);

			return $returns;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get order returns by order.');
		}
	}
	
	public function get_return($id_order_detail_return) {
		$sql = '
			SELECT order_detail_return.*, order_detail.id_order, order_detail.unit_price_tax_incl AS product_price, (order_detail.unit_price_tax_incl * order_detail_return.product_quantity) AS return_amount, customer.user_id, product_lang.name AS product, CONCAT(attribute_group_lang.name, \': \', attribute_lang.name) AS attribute
			FROM order_detail_return
				INNER JOIN order_detail ON order_detail_return.id_order_detail = order_detail.id_order_detail
				INNER JOIN orders ON order_detail.id_order = orders.id_order
				INNER JOIN customer ON orders.id_customer = customer.id_customer
				LEFT JOIN product ON order_detail.product_id = product.id_product
				LEFT JOIN product_lang ON product_lang.id_product = product.id_product
				LEFT JOIN product_attribute_combination ON order_detail.product_attribute_id = product_attribute_combination.id_product_attribute
				LEFT JOIN attribute ON product_attribute_combination.id_attribute = attribute.id_attribute
				LEFT JOIN attribute_lang ON (attribute.id_attribute = attribute_lang.id_attribute AND attribute_lang.id_lang = orders.id_lang)
				LEFT JOIN attribute_group_lang ON attribute.id_attribute_group = attribute_group_lang.id_attribute_group
			WHERE order_detail_return.id_order_detail_return = :id_order_detail_return
			GROUP BY order_detail_return.id_order_detail_return
		';
		
		$params = array(
			':id_order_detail_return' => $id_order_detail_return
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->select_single($sql, $params);

			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get order detail return.');
		}
		
	}
}
?>