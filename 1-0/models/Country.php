<?php

class Country extends _Model {

	const TABLE = 'country';
	const PRIMARY_KEY_FIELD = 'id_country';

	public function get_countries($id_lang) {
		$query = '
			SELECT country.*
				, country_lang.name
			FROM country
				INNER JOIN country_lang ON country.id_country = country_lang.id_country
			WHERE country_lang.id_lang = :id_lang
			ORDER BY country_lang.name ASC
		';
		$values = array(
			':id_lang' => $id_lang
		);

		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);

			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get countries.');
		}
	}
}
?>
