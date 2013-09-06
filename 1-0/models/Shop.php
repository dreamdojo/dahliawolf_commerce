<?
class Shop extends _Model {
	const TABLE = 'shop';
	const PRIMARY_KEY_FIELD = 'id_shop';

	public function get_primary_shop_store_address($id_shop) {
		$sql = '
			SELECT store.address1, store.address2, store.city, store.postcode, store.phone
				, country.iso_code AS country
				, state.iso_code AS state
			FROM shop
				INNER JOIN store_shop ON shop.id_shop = store_shop.id_shop
				INNER JOIN store ON store_shop.id_store = store.id_store
				INNER JOIN country ON store.id_country = country.id_country
				INNER JOIN state ON store.id_state = state.id_state
			WHERE shop.id_shop = :id_shop
			ORDER BY store.date_add ASC
		';
		$params = array(
			':id_shop' => $id_shop
		);

		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->select_single($sql, $params);

			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get primary shop store address.');
		}
	}
}
?>