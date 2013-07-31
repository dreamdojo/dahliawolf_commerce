<?php

class State extends _Model {

	const TABLE = 'state';
	const PRIMARY_KEY_FIELD = 'id_state';

	public function get_states($id_country) {
		$query = '
			SELECT state.*
			FROM state
			WHERE state.id_country = :id_country
			ORDER BY state.name ASC
		';
		$values = array(
			':id_country' => $id_country
		);

		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);

			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get states.');
		}
	}

	public function get_states_by_country_iso_code($iso_code) {
		$query = '
			SELECT state.*
			FROM state
				INNER JOIN country ON state.id_country = country.id_country
			WHERE country.iso_code = :iso_code
			ORDER BY state.name ASC
		';
		$values = array(
			':iso_code' => $iso_code
		);

		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);

			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get states by country ISO code.');
		}
	}
}
?>
