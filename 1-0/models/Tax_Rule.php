<?php
class Tax_Rule extends _Model {

	const TABLE = 'tax_rule';
	const PRIMARY_KEY_FIELD = 'id_tax_rule';
	
	public function get_tax_rules($id_tax_rules_group, $id_country, $id_state, $zip) {
		$sql = '
			SELECT tax_rule.*, tax.rate, tax_rules_group.name AS tax_name
			FROM tax_rules_group 
				INNER JOIN tax_rule ON tax_rules_group.id_tax_rules_group = tax_rule.id_tax_rules_group
				INNER JOIN tax ON tax_rule.id_tax = tax.id_tax
			WHERE tax_rule.id_tax_rules_group = :id_tax_rules_group 
				AND tax_rule.id_country = :id_country 
				AND tax_rule.id_state = :id_state
				AND (tax_rule.zipcode_from = 0 OR tax_rule.zipcode_from <= :zip)
				AND (tax_rule.zipcode_to = 0 OR tax_rule.zipcode_to >= :zip)
				AND tax_rules_group.active = :active
			ORDER BY tax_rule.id_tax_rule DESC
		';
		
		$params = array(
			':id_tax_rules_group' => $id_tax_rules_group
			, ':id_country' => $id_country
			, ':id_state' => $id_state
			, ':zip' => $zip
			, ':active' => '1'
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);
			
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get tax rules.');
		}
	}

	public function get_tax_info($id_tax_rules_group, $id_country, $id_state, $zip, $product_price, $product_quantity) {
		$tax_info = array(
			'tax_name' => NULL
			, 'rate' => NULL
			, 'id_tax' => NULL
			, 'unit_amount' => NULL
			, 'total_amount' => NULL
		);
		
		$tax_rules = $this->get_tax_rules($id_tax_rules_group, $id_country, $id_state, $zip);
		if (!empty($tax_rules)) {
			if ($tax_rules[0]['behavior'] == '1') {
				$tax_amount = $product_price * ($tax_rules[0]['rate'] / 100);
				$tax_amount = $this->truncateNum($tax_amount);
				$unit_amount = $product_price + $tax_amount;
				$total_product = $unit_amount * $product_quantity;
				
				$tax_info['tax_name'] = $tax_rules[0]['tax_name'];
				$tax_info['rate'] = $tax_rules[0]['rate'];
				$tax_info['id_tax'] = $tax_rules[0]['id_tax'];
				$tax_info['unit_amount'] = $unit_amount;
				$tax_info['total_amount'] = $total_product;
			}
		}
		
		return $tax_info;
		
	}
	
}
?>