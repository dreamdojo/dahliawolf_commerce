<?php

class Carrier extends _Model {

	const TABLE = 'carrier';
	const PRIMARY_KEY_FIELD = 'id_carrier';

	private function get_carrier_options_sql($range_table) {
		$sql = '
			SELECT delivery.*
				, delivery.name AS delay
				, carrier.name AS carrier_name
				, carrier.id_tax_rules_group
			FROM carrier_zone
				INNER JOIN carrier_shop ON carrier_zone.id_carrier = carrier_shop.id_carrier
				INNER JOIN carrier ON carrier_zone.id_carrier = carrier.id_carrier
				INNER JOIN ' . $range_table . ' ON carrier_zone.id_carrier = ' . $range_table . '.id_carrier
				INNER JOIN delivery ON (delivery.id_shop = carrier_shop.id_shop AND delivery.id_carrier = carrier.id_carrier AND ' . $range_table . '.id_' . $range_table . ' = delivery.id_' . $range_table . ' AND delivery.id_zone = carrier_zone.id_zone)
			WHERE carrier_zone.id_zone = :id_zone
				AND carrier_shop.id_shop = :id_shop
				AND carrier.deleted = :deleted
				AND carrier.active = :active
				AND ' . $range_table . '.delimiter1 <= :value
				AND ' . $range_table . '.delimiter2 >= :value
				AND delivery.active = :active
			ORDER BY delivery.price ASC, carrier.position ASC, carrier.name ASC, delivery.name ASC
		';

		return $sql;
	}

	public function get_carrier_options_by_price($id_zone, $id_shop, $id_lang, $total_price) {

		$sql = $this->get_carrier_options_sql('range_price');

		$params = array(
			':id_zone' => $id_zone
			, ':id_shop' => $id_shop
			, ':deleted' => '0'
			, ':active' => '1'
			, ':value' => $total_price
		);

		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);

			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get carriers options.');
		}
	}

	public function get_carrier_options_by_weight($id_zone, $id_shop, $id_lang, $total_weight) {

		$sql = $this->get_carrier_options_sql('range_price');

		$params = array(
			':id_zone' => $id_zone
			, ':id_shop' => $id_shop
			, ':deleted' => '0'
			, ':active' => '1'
			, ':value' => $total_weight
		);

		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);

			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get carriers options.');
		}
	}

}
?>
