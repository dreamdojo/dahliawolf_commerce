<?php
class Product extends _Model {

	const TABLE = 'product';
	const PRIMARY_KEY_FIELD = 'id_product';
	
	// 3/1/2013
	public function get_product($id_product, $id_shop, $id_lang, $user_id = NULL) {
		$sql = '
		SELECT product.*, product_lang.name AS product_lang_name, product_lang.name AS product_name, shop.name AS shop_name, lang.name AS lang_name, supplier.name AS supplier, manufacturer.name AS manufacturer, default_shop.name AS default_shop_name, tax_rules_group.name AS tax_rules_group, product_lang.description, product_lang.description_short, product_lang.meta_description, product_lang.meta_keywords, product_lang.meta_title, customer.username, (SELECT product_file.product_file_id FROM product_file WHERE product_file.product_id = product.id_product ORDER BY product_file.product_file_id ASC LIMIT 1) AS product_file_id, IF(EXISTS(SELECT category_product.id_category_product FROM category_product WHERE category_product.id_category = 1 AND category_product.id_product = product.id_product), 1, 0) AS is_new
		FROM product 
			INNER JOIN product_shop ON product.id_product = product_shop.id_product
			INNER JOIN shop ON product_shop.id_shop = shop.id_shop
			INNER JOIN product_lang ON product.id_product = product_lang.id_product
			INNER JOIN lang ON product_lang.id_lang = lang.id_lang
			LEFT JOIN supplier ON product.id_supplier = supplier.id_supplier
			LEFT JOIN manufacturer ON product.id_manufacturer = manufacturer.id_manufacturer
			LEFT JOIN category ON product.id_category_default = category.id_category
			LEFT JOIN shop AS default_shop ON product.id_shop_default = default_shop.id_shop
			LEFT JOIN tax_rules_group ON product.id_tax_rules_group = tax_rules_group.id_tax_rules_group
			LEFT JOIN customer ON product.user_id = customer.user_id
            WHERE product.id_product = :id_product AND shop.id_shop = :id_shop AND lang.id_lang = :id_lang AND product_shop.active = :active
		';
		
		if (!empty($user_id)) {
			$sql .= ' AND product.user_id = :user_id';
		}
		
		$params = array(
			':id_product' => $id_product
			, ':id_shop' => $id_shop
			, ':id_lang' => $id_lang
			, ':active' => '1'
		);
		
		if (!empty($user_id)) {
			$params[':user_id'] = $user_id;
		}
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->select_single($sql, $params);
			
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get product details.');
		}
	}

	public function get_products($id_shop, $id_lang, $user_id = NULL) {
		$sql = '
		SELECT product.*, product_lang.name AS product_lang_name, product_lang.name AS product_name, shop.name AS shop_name, lang.name AS lang_name, supplier.name AS supplier, manufacturer.name AS manufacturer, default_shop.name AS default_shop_name, tax_rules_group.name AS tax_rules_group, product_lang.description, product_lang.description_short, product_lang.meta_description, product_lang.meta_keywords, product_lang.meta_title, customer.username, (SELECT product_file.product_file_id FROM product_file WHERE product_file.product_id = product.id_product ORDER BY product_file.product_file_id ASC LIMIT 1) AS product_file_id, IF(EXISTS(SELECT category_product.id_category_product FROM category_product WHERE category_product.id_category = 1 AND category_product.id_product = product.id_product), 1, 0) AS is_new
		FROM product 
			INNER JOIN product_shop ON product.id_product = product_shop.id_product
			INNER JOIN shop ON product_shop.id_shop = shop.id_shop
			INNER JOIN product_lang ON product.id_product = product_lang.id_product
			INNER JOIN lang ON product_lang.id_lang = lang.id_lang
			LEFT JOIN supplier ON product.id_supplier = supplier.id_supplier
			LEFT JOIN manufacturer ON product.id_manufacturer = manufacturer.id_manufacturer
			LEFT JOIN category ON product.id_category_default = category.id_category
			LEFT JOIN shop AS default_shop ON product.id_shop_default = default_shop.id_shop
			LEFT JOIN tax_rules_group ON product.id_tax_rules_group = tax_rules_group.id_tax_rules_group
			LEFT JOIN customer ON product.user_id = customer.user_id
            WHERE shop.id_shop = :id_shop AND lang.id_lang = :id_lang AND product_shop.active = :active';
		//product.status != :not_status AND 
		if (!empty($user_id)) {
			$sql .= ' AND product.user_id = :user_id';
		}
		
		$sql .= '
            ORDER BY product.id_product
		';
		
		$params = array(
			':id_shop' => $id_shop
			, ':id_lang' => $id_lang
			, ':active' => '1'
			//, ':not_status' => 'Pending'
		);
		
		if (!empty($user_id)) {
			$params[':user_id'] = $user_id;
		}
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);
			
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get product details.');
		}
	}
	
	public function get_products_in_category($params) {
	
		$query = '
			SELECT product.*, product_lang.name AS product_lang_name, product_lang.name AS product_name, product_lang.description, product_lang.description_short, product_lang.meta_description, product_lang.meta_keywords, product_lang.meta_title, product_lang.link_rewrite, category_product.position, customer.username, (SELECT product_file.product_file_id FROM product_file WHERE product_file.product_id = product.id_product ORDER BY product_file.product_file_id ASC LIMIT 1) AS product_file_id, IF(EXISTS(SELECT category_product.id_category_product FROM category_product WHERE category_product.id_category = 1 AND category_product.id_product = product.id_product), 1, 0) AS is_new
			FROM category
				INNER JOIN category_shop ON category.id_category = category_shop.id_category
				INNER JOIN category_product ON category.id_category = category_product.id_category
				INNER JOIN product ON category_product.id_product = product.id_product
				INNER JOIN product_lang ON product.id_product = product_lang.id_product
				INNER JOIN product_shop ON product.id_product = product_shop.id_product
				LEFT JOIN customer ON product.user_id = customer.user_id
			WHERE category_shop.id_shop = :id_shop
				AND category.id_category = :id_category
				AND product_lang.id_lang = :id_lang
				AND product_shop.active = 1
				AND category.active = 1';
		
		if (!empty($params['user_id'])) {
			$query .= ' AND product.user_id = :user_id';
		}
		
		$query .= '
			ORDER BY category_product.position ASC
		';
		
		$values = array(
			':id_shop' 			=> $params['id_shop']
			, ':id_category' 	=> $params['id_category']
			, ':id_lang' 		=> $params['id_lang']
		);
		
		if (!empty($params['user_id'])) {
			$values[':user_id'] = $params['user_id'];
		}
		
		try {
			$query_result = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);
			if (empty($query_result)) return NULL;
			return $query_result;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Could not get products in category');
		}
	}
	
	public function get_number_of_products_in_category($params) {

         $query = '
			SELECT COUNT(*) AS count
			FROM category
				INNER JOIN category_shop ON category.id_category = category_shop.id_category
				INNER JOIN category_product ON category.id_category = category_product.id_category
				INNER JOIN product ON category_product.id_product = product.id_product
				INNER JOIN product_lang ON product.id_product = product_lang.id_product
				INNER JOIN product_shop ON product.id_product = product_shop.id_product
				LEFT JOIN customer ON product.user_id = customer.user_id
			WHERE category_shop.id_shop = :id_shop
				AND category.id_category = :id_category
				AND product_lang.id_lang = :id_lang
				AND product_shop.active = 1
				AND category.active = 1
		';
		
		if (!empty($params['user_id'])) {
			$query .= ' AND product.user_id = :user_id';
		}
		
		$values = array(
			':id_shop' => $params['conditions']['id_shop']
			, ':id_category' => $params['conditions']['id_category']
			, ':id_lang' => $params['conditions']['id_lang']
		);
		
		if (!empty($params['user_id'])) {
			$values[':user_id'] = $params['conditions']['user_id'];
		}
		
		try {
			$query_result = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);
			if (empty($query_result)) return NULL;
			return $query_result;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Could not get number of products in category');
		}
	}

	// 3/1/2013
	public function get_product_combinations($id_product, $id_shop, $id_lang, $id_product_attribute = NULL) {
		$sql = "
		SELECT shop.id_shop, lang.id_lang, product_attribute.*, attributes.attribute_names
			FROM product
			INNER JOIN product_shop ON product.id_product = product_shop.id_product
			INNER JOIN shop ON product_shop.id_shop = shop.id_shop
			INNER JOIN product_lang ON product.id_product = product_lang.id_product
			INNER JOIN lang ON product_lang.id_lang = lang.id_lang
		    INNER JOIN product_attribute ON product.id_product = product_attribute.id_product
			INNER JOIN product_attribute_shop ON product_attribute.id_product_attribute = product_attribute_shop.id_product_attribute
			INNER JOIN shop AS shop2 ON (product_attribute_shop.id_shop = shop2.id_shop AND shop.id_shop = shop2.id_shop)
			LEFT JOIN (
				SELECT id_product_attribute, GROUP_CONCAT(CONCAT(attribute_group_name, ': ', attribute_name) ORDER BY attribute_group_name ASC SEPARATOR 0x1D) AS attribute_names
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
			) AS attributes ON product_attribute.id_product_attribute = attributes.id_product_attribute
		WHERE product.id_product = :id_product AND shop.id_shop = :id_shop AND product_lang.id_lang = :id_lang";
		
		if (is_numeric($id_product_attribute)) {
			$sql .= " AND product_attribute.id_product_attribute = :id_product_attribute";
		}
		
		$sql .= " ORDER BY product_attribute.id_product_attribute ASC";
		
		$params = array(
			':id_product' => $id_product
			, ':id_shop' => $id_shop
			, ':id_lang' => $id_lang
		);
		
		if (is_numeric($id_product_attribute)) {
			$params[':id_product_attribute'] = $id_product_attribute;
		}
		
		try {
			if (is_numeric($id_product_attribute)) {
				$data = self::$dbs[$this->db_host][$this->db_name]->select_single($sql, $params);
			}
			else {
				$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);
			}
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get product combinations.' . $e->getMessage());
		}
	}
	
	// 3/1/2013
	public function get_product_features($id_product, $id_shop, $id_lang) {
		$sql = "
		SELECT product.id_product, shop.id_shop, lang.id_lang, feature.id_feature, CONCAT(feature_lang.name, ': ', feature_value_lang.value) AS feature_name, feature.position
		FROM product
			INNER JOIN product_shop ON product.id_product = product_shop.id_product
			INNER JOIN shop ON product_shop.id_shop = shop.id_shop
			INNER JOIN product_lang ON product.id_product = product_lang.id_product
			INNER JOIN lang ON product_lang.id_lang = lang.id_lang
			INNER JOIN feature_product ON product.id_product = feature_product.id_product
			INNER JOIN feature ON feature_product.id_feature = feature.id_feature
			INNER JOIN feature_shop ON feature.id_feature = feature_shop.id_feature
			INNER JOIN shop AS shop2 ON (feature_shop.id_shop = shop2.id_shop AND shop.id_shop = shop2.id_shop )
			INNER JOIN feature_lang ON (feature.id_feature = feature_lang.id_feature AND feature_lang.id_lang = lang.id_lang)
			INNER JOIN feature_value ON feature_product.id_feature_value = feature_value.id_feature_value
			INNER JOIN feature_value_lang ON (feature_value.id_feature_value = feature_value_lang.id_feature_value AND feature_value_lang.id_lang = lang.id_lang)
		WHERE product.id_product = :id_product AND shop.id_shop = :id_shop AND lang.id_lang = :id_lang
		ORDER BY feature.position ASC, feature_lang.name ASC
		";
		
		$params = array(
			':id_product' => $id_product
			, ':id_shop' => $id_shop
			, ':id_lang' => $id_lang
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);
			
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get product features.' . $e->getMessage());
		}
	}
	
	// 3/1/2013
	public function get_product_tags($id_product, $id_shop, $id_lang) {
		$sql = "
		SELECT product.id_product, shop.id_shop, lang.id_lang, tag.id_tag, tag.name AS tag_name
		FROM product
			INNER JOIN product_shop ON product.id_product = product_shop.id_product
			INNER JOIN shop ON product_shop.id_shop = shop.id_shop
			INNER JOIN product_lang ON product.id_product = product_lang.id_product
			INNER JOIN lang ON product_lang.id_lang = lang.id_lang
			INNER JOIN product_tag ON product.id_product = product_tag.id_product
			INNER JOIN tag ON (product_tag.id_tag = tag.id_tag AND tag.id_lang = lang.id_lang)
		WHERE product.id_product = :id_product AND shop.id_shop = :id_shop AND lang.id_lang = :id_lang
		ORDER BY tag.name ASC
		";
		
		$params = array(
			':id_product' => $id_product
			, ':id_shop' => $id_shop
			, ':id_lang' => $id_lang
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);
			
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get product features.' . $e->getMessage());
		}
	}
	
	// 3/1/2013
	public function get_product_comments($id_product, $id_shop, $id_lang) {
		$sql = "
		SELECT shop.id_shop, lang.id_lang, product_comment.*, customer.email AS customer_email
		FROM product
			INNER JOIN product_shop ON product.id_product = product_shop.id_product
			INNER JOIN shop ON product_shop.id_shop = shop.id_shop
			INNER JOIN product_lang ON product.id_product = product_lang.id_product
			INNER JOIN lang ON product_lang.id_lang = lang.id_lang
			INNER JOIN product_comment ON product.id_product = product_comment.id_product
			LEFT JOIN customer ON product_comment.id_customer = customer.id_customer
		WHERE product.id_product = :id_product AND shop.id_shop = :id_shop AND lang.id_lang = :id_lang AND product_comment.deleted = :deleted
		ORDER BY product_comment.date_add DESC, product_comment.id_product_comment DESC
		";
		
		$params = array(
			':id_product' => $id_product
			, ':id_shop' => $id_shop
			, ':id_lang' => $id_lang
			, ':deleted' => '0'
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);
			
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get product comments.' . $e->getMessage());
		}
	}
	
	// 3/1/2013
	public function get_product_files($id_product, $id_shop, $id_lang) {
		$sql = "
		SELECT shop.id_shop, lang.id_lang, product_file.*
		FROM product
			INNER JOIN product_shop ON product.id_product = product_shop.id_product
			INNER JOIN shop ON product_shop.id_shop = shop.id_shop
			INNER JOIN product_lang ON product.id_product = product_lang.id_product
			INNER JOIN lang ON product_lang.id_lang = lang.id_lang
			INNER JOIN product_file ON product.id_product = product_file.product_id
		WHERE product.id_product = :id_product AND shop.id_shop = :id_shop AND lang.id_lang = :id_lang
		ORDER BY product_file.product_file_id ASC
		";
		
		$params = array(
			':id_product' => $id_product
			, ':id_shop' => $id_shop
			, ':id_lang' => $id_lang
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);
			
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get product files.' . $e->getMessage());
		}
	}
	
	public function get_order_products($id_order, $id_shop, $id_lang) {
		$sql = "
		SELECT order_detail.*, attributes.attribute_names AS attributes, (SELECT product_file.product_file_id FROM product_file WHERE product_file.product_id = order_detail.product_id ORDER BY product_file.product_file_id ASC LIMIT 1) AS product_file_id
			FROM order_detail
            LEFT JOIN product ON order_detail.product_id = product.id_product
			LEFT JOIN (
				SELECT id_product_attribute, GROUP_CONCAT(CONCAT(attribute_group_name, ': ', attribute_name) ORDER BY attribute_group_name ASC SEPARATOR 0x1D) AS attribute_names
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
		WHERE order_detail.id_order = :id_order AND order_detail.id_shop = :id_shop
		";
		
		$params = array(
			':id_order' => $id_order
			, ':id_shop' => $id_shop
			, ':id_lang' => $id_lang
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);
			
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get order products.' . $e->getMessage());
		}
	}
	
	public function get_product_attributes($params) {
		
		if($params['conditions']['id_shop']=='' ){
		return resultArray(false, NULL, 'Please pass id_shop in parameter!');
		}
		if($params['conditions']['id_lang']=='' ){
		return resultArray(false, NULL, 'Please pass id_lang in parameter!');
		}
        if($params['conditions']['id_product']=='' ){
		return resultArray(false, NULL, 'Please pass id_product in parameter!');
		}
		if($params['conditions']['id_product_attribute']=='' ){
			$addToQuery = "";
			
			$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_shop' => $params['conditions']['id_shop']
			);
		} else {
			$addToQuery = " AND product_attribute_shop.id_product_attribute = :id_product_attribute ";
			
			$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_shop' => $params['conditions']['id_shop']
			, ':id_product_attribute' => $params['conditions']['id_product_attribute']
			);
		}
		
		$query = '
			SELECT
				product_attribute.id_product_attribute
				, product_attribute.id_product 
				, product_attribute.reference
				, product_attribute.supplier_reference
				, product_attribute.location
				, product_attribute.ean13
				, product_attribute.upc
				, product_attribute.wholesale_price
				, product_attribute.price
				, product_attribute.ecotax
				, product_attribute.quantity
				, product_attribute.weight
				, product_attribute.unit_price_impact
				, product_attribute.default_on
				, product_attribute.minimal_quantity
				, product_attribute.available_date 
				
				, product_attribute_shop.id_product_attribute
				, product_attribute_shop.id_shop
				, product_attribute_shop.wholesale_price
				, product_attribute_shop.price
				, product_attribute_shop.ecotax
				, product_attribute_shop.weight
				, product_attribute_shop.unit_price_impact
				, product_attribute_shop.default_on
				, product_attribute_shop.minimal_quantity
				, product_attribute_shop.available_date
				
				, image.id_image
				, image.id_product
				, image.position
				, image.cover
				, image_lang.legend
				, image_shop.cover
				
				, attribute_group.id_attribute_group
				, attribute_group.is_color_group
				, attribute_group.group_type
				, attribute_group.position
				
				, attribute_group_lang.id_attribute_group
				, attribute_group_lang.id_lang
				, attribute_group_lang.name
				, attribute_group_lang.public_name
				
				, attribute.id_attribute
				, attribute.id_attribute_group
				, attribute.color
				, attribute.position
				
				, attribute_lang.id_attribute
				, attribute_lang.id_lang
				, attribute_lang.name
				
				, attribute_shop.id_attribute_shop
				, attribute_shop.id_attribute
				, attribute_shop.id_shop
				
				, attribute_impact.id_attribute_impact
				, attribute_impact.id_product
				, attribute_impact.id_attribute
				, attribute_impact.weight
				, attribute_impact.price
				
			FROM product_attribute
				INNER JOIN product_attribute_shop ON product_attribute.id_product_attribute = product_attribute_shop.id_product_attribute 
				INNER JOIN product_attribute_image ON product_attribute.id_product_attribute = product_attribute_image.id_product_attribute 
				INNER JOIN image ON product_attribute_image.id_image = image.id_image
				INNER JOIN image_lang ON image.id_image = image_lang.id_image
				INNER JOIN image_shop ON image.id_image = image_shop.id_image
				
				INNER JOIN product_attribute_combination ON product_attribute_combination.id_product_attribute = product_attribute.id_product_attribute
				
				INNER JOIN attribute ON product_attribute_combination.id_attribute = attribute.id_attribute 
				INNER JOIN attribute_lang ON attribute.id_attribute = attribute_lang.id_attribute
				INNER JOIN attribute_shop ON attribute.id_attribute = attribute_shop.id_attribute
				
				INNER JOIN attribute_group ON attribute_group.id_attribute_group = attribute.id_attribute_group
				INNER JOIN attribute_group_lang ON attribute_group_lang.id_attribute_group = attribute_group.id_attribute_group
				INNER JOIN attribute_group_shop ON attribute_group_shop.id_attribute_group = attribute_group.id_attribute_group
				
				INNER JOIN attribute_impact ON attribute_impact.id_attribute = attribute.id_attribute
			WHERE 
				product_attribute.id_product = :id_product
				AND attribute_impact.id_product = :id_product
				AND image.id_product = :id_product
				
				AND product_attribute_shop.id_shop = :id_shop
				AND attribute_group_shop.id_shop = :id_shop
				AND image_shop.id_shop = :id_shop
				AND attribute_shop.id_shop = :id_shop
				
				AND attribute_group_lang.id_lang = :id_lang
				AND image_lang.id_lang = :id_lang
				AND attribute_lang.id_lang = :id_lang
				
			'. $addToQuery;
			
		$stmt = $this->run($query, $values);
		$this->result = $stmt->fetchAll();
       	
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product attributes.');
      	}

        return resultArray(true, $this->result[0]);
	}
	
	public function get_attributes($params) {
		if($params['conditions']['id_shop']=='' ){
		return resultArray(false, NULL, 'Please pass id_shop in parameter!');
		}
		if($params['conditions']['id_lang']=='' ){
		return resultArray(false, NULL, 'Please pass id_lang in parameter!');
		}
        if($params['conditions']['id_attribute']=='' ){
		return resultArray(false, NULL, 'Please pass id_attribute in parameter!');
		}

		$query = '
			SELECT
				attribute.id_attribute
				, attribute.id_attribute_group
				, attribute.color
				, attribute.position
				
				, attribute_lang.id_attribute 
				, attribute_lang.id_lang 
				, attribute_lang.name
				
				, attribute_shop.id_attribute
				, attribute_shop.id_attribute_shop
				, attribute_shop.id_shop
				
			FROM attribute
				INNER JOIN attribute_lang ON attribute.id_attribute = attribute_lang.id_attribute
				INNER JOIN attribute_shop ON attribute.id_attribute = attribute_shop.id_attribute
				
				INNER JOIN attribute_group ON attribute_group.id_attribute_group = attribute.id_attribute_group
				INNER JOIN attribute_group_lang ON attribute_group_lang.id_attribute_group = attribute_group.id_attribute_group
				INNER JOIN attribute_group_shop ON attribute_group_shop.id_attribute_group = attribute_group.id_attribute_group
				
				INNER JOIN attribute_impact ON attribute_impact.id_attribute = attribute.id_attribute
			WHERE 
				attribute.id_attribute = :id_attribute
				
				AND attribute_lang.id_lang = :id_lang
				AND attribute_shop.id_shop = :id_shop
				
				AND attribute_group_lang.id_lang = :id_lang
				AND attribute_group_shop.id_shop = :id_shop
		';
		$values = array(
			':id_lang'   => $params['conditions']['id_lang']
			, ':id_shop' => $params['conditions']['id_shop']
			, ':id_shop' => $params['conditions']['id_shop']
		);
		
		$stmt = $this->run($query, $values);
		$this->result = $stmt->fetchAll();
       	
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get attributes.');
      	}

        return resultArray(true, $this->result[0]);
	}

	public function get_image($params) {
		if($params['conditions']['id_shop'] == '' ){
		return resultArray(false, NULL, 'Please pass id_shop in parameter!');
		}
		if($params['conditions']['id_lang'] == '' ){
		return resultArray(false, NULL, 'Please pass id_lang in parameter!');
		}
        if(($params['conditions']['id_image'] == '' ) && ($params['conditions']['id_product'] == '' ) ){
		return resultArray(false, NULL, 'Please pass id_image or id_product in parameter!');
		}

		$values = array(
			':id_lang' => $params['conditions']['id_lang']
			, ':id_shop' => $params['conditions']['id_shop']
		);
		if($params['conditions']['id_image'] > 0 ){
			$addToQuery = " AND image.id_image = :id_image ";
			$values[':id_image'] = $params['conditions']['id_image'];
		}
		if($params['conditions']['id_product'] > 0 ){
			$addToQuery = " AND image.id_product = :id_product ";
			$values[':id_product'] = $params['conditions']['id_product'];
		}

		$query = '
			SELECT
				image.id_image
				, image.id_product
				, image.position
				, image.cover
				 
				, image_lang.legend
				
				, image_shop.cover
			FROM image
				INNER JOIN image_lang ON image.id_image = image_lang.id_image
				INNER JOIN image_shop ON image.id_image = image_shop.id_image
			WHERE 
				image_lang.id_lang = :id_lang
				AND image_shop.id_shop = :id_shop
		'. $addToQuery;
		
		$stmt = $this->run($query, $values);
		$this->result = $stmt->fetchAll();
       	
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get images.');
      	}

        return resultArray(true, $this->result[0]);
	}
	/*
	public function get_product_features($params) {
		if($params['conditions']['id_shop'] == '' ){
		return resultArray(false, NULL, 'Please pass id_shop in parameter!');
		}
		if($params['conditions']['id_lang'] == '' ){
		return resultArray(false, NULL, 'Please pass id_lang in parameter!');
		}
        if(($params['conditions']['id_feature'] == '' ) && ($params['conditions']['id_product'] == '' ) ){
		return resultArray(false, NULL, 'Please pass id_feature or id_product in parameter!');
		}

		$values = array(
			':id_lang' => $params['conditions']['id_lang']
			, ':id_shop' => $params['conditions']['id_shop']
		);
		if($params['conditions']['id_feature'] > 0 ){
			$addToQuery = " AND feature.id_feature = :id_feature ";
			$values[':id_feature'] = $params['conditions']['id_feature'];
		}
		if($params['conditions']['id_product'] > 0 ){
			$addToQuery = " AND image.id_product = :id_product ";
			$values[':id_product'] = $params['conditions']['id_product'];
		}
		
		$query = '
			SELECT
				feature.id_feature
				, feature.position
				 
				, feature_lang.id_feature_lang
				, feature_lang.id_feature
				, feature_lang.id_lang
				, feature_lang.name
				
				, feature_shop.id_feature_shop
				, feature_shop.id_feature
				, feature_shop.id_shop
				
				, feature_value.id_feature_value
				, feature_value.id_feature
				, feature_value.custom
				
				, feature_value_lang.id_feature_value_lang
				, feature_value_lang.id_feature_value
				, feature_value_lang.id_lang
				, feature_value_lang.value
			FROM feature
				INNER JOIN feature_lang ON feature.id_feature = feature_lang.id_feature
				INNER JOIN feature_shop ON feature.id_feature = feature_shop.id_feature
				
				INNER JOIN feature_value ON feature.id_feature = feature_value.id_feature
				INNER JOIN feature_value_lang ON feature_value.id_feature_value = feature_value_lang.id_feature_value
				
			WHERE 
				feature_lang.id_lang = :id_lang
				feature_value_lang.id_lang = :id_lang
				AND feature_shop.id_shop = :id_shop
		'. $addToQuery;
		
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product features.');
      	}

        return resultArray(true, $this->result);
	}
	*/
	public function get_product_price($params) {
		$query = '
			SELECT * 
			FROM product INNER JOIN product_shop ON product.id_product = product_shop.id_product 
			WHERE product.id_product = :id_product
				AND product_shop.id_shop = :id_shop
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_shop' => $params['conditions']['id_shop']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_price_combination($params) {
		$query = '
			SELECT * 
			FROM product INNER JOIN product_lang ON product.id_product = product_shop.id_product 
			WHERE product.id_product = :id_product
				AND product_shop.id_shop = :id_shop
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_shop' => $params['conditions']['id_shop']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_attachment($params) {
		$query = '
			SELECT * 
			FROM product INNER JOIN product_attachment ON product.id_product = product_attachment.id_product_attachment 
			WHERE product.id_product = :id_product
				AND product_attachment.id_attachment = :id_attachment
				AND product_attachment.id_product_attachment = :id_product_attachment
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_attachment' => $params['conditions']['id_attachment']
			, ':id_product_attachment' => $params['conditions']['id_product_attachment']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_carrier($params) {
		$query = '
			SELECT * 
			FROM product INNER JOIN product_lang ON product.id_product = product_carrier.id_product 
			WHERE product.id_product = :id_product
				AND product_lang.id_lang = :id_lang
				AND product_carrier.id_shop = :id_shop
				AND product_carrier.id_product_carrier = :id_product_carrier
				AND product_carrier.id_carrier_reference = :id_carrier_reference
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_lang' => $params['conditions']['id_lang']
			, ':id_shop' => $params['conditions']['id_shop']
			, ':id_product_carrier' => $params['conditions']['id_product_carrier']
			, ':id_carrier_reference' => $params['conditions']['id_carrier_reference']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_comment($params) {
		$query = '
			SELECT * 
			FROM product INNER JOIN product_comment ON product.id_product = product_comment.id_product 
			WHERE product.id_product = :id_product
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_product_comment' => $params['conditions']['id_product_comment']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_tax($params) {
		$query = '
			SELECT * 
			FROM product INNER JOIN product_lang ON product.id_product = product_lang.id_product 
			WHERE product.id_product = :id_product
				AND product_lang.id_lang = :id_lang
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_lang' => $params['conditions']['id_lang']
			, ':id_shop' => $params['conditions']['id_shop']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_country_tax($params) {
		$query = '
			SELECT * 
			FROM product 
			INNER JOIN product_lang ON product.id_product = product_lang.id_product 
			INNER JOIN product_country_tax ON product.id_product = product_country_tax.id_product 
			WHERE product.id_product = :id_product
				AND product_lang.id_lang = :id_lang
				AND product_lang.id_country = :id_country
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_lang' => $params['conditions']['id_lang']
			, ':id_country' => $params['conditions']['id_country']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_supplier($params) {
		$query = '
			SELECT * 
			FROM product 
			INNER JOIN product_lang ON product.id_product = product_lang.id_product 
			INNER JOIN product_supplier ON product.id_product = product_supplier.id_product 

			WHERE product.id_product = :id_product
				AND product_lang.id_lang = :id_lang
				AND product_supplier.id_product_supplier = :id_product_supplier
				AND product_supplier.id_product_attribute = :id_product_attribute
				AND product_supplier.id_supplier = :id_supplier
				AND product_lang.id_shop = :id_shop
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_lang' => $params['conditions']['id_lang']
			, ':id_product_supplier' => $params['conditions']['id_product_supplier']
			, ':id_product_attribute' => $params['conditions']['id_product_attribute']
			, ':id_supplier' => $params['conditions']['id_supplier']
			, ':id_shop' => $params['conditions']['id_shop']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_manufacturer($params) {
		$query = '
			SELECT * 
			FROM product INNER JOIN product_lang ON product.id_product = product_lang.id_product 
			WHERE product.id_product = :id_product
				AND product_lang.id_lang = :id_lang
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_lang' => $params['conditions']['id_lang']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_stock($params) {
		$query = '
			SELECT * 
			FROM product
			INNER JOIN warehouse_product_location ON product.id_product = warehouse_product_location.id_product
			INNER JOIN product_lang ON product.id_product = product_lang.id_product
			INNER JOIN stock ON product.id_product = stock.id_product
			WHERE product.id_product = :id_product
				AND warehouse_product_location.id_product = :id_product
				AND stock.id_stock = :id_stock
				AND warehouse_product_location.id_warehouse = :id_warehouse
				AND warehouse_product_location.id_product_attribute = :id_product_attribute
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_stock' => $params['conditions']['id_stock']
			, ':id_warehouse' => $params['conditions']['id_warehouse']
			, ':id_product_attribute' => $params['conditions']['id_product_attribute']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_tag($params) {
		$query = '
			SELECT * 
			FROM product 
			INNER JOIN product_lang ON product.id_product = product_lang.id_product
			INNER JOIN product_tag ON product.id_product = product_tag.id_product 
			WHERE product.id_product = :id_product
				AND product_lang.id_lang = :id_lang
				AND product_tag.id_product_tag = :id_product_tag
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_lang' => $params['conditions']['id_lang']
			, ':id_product_tag' => $params['conditions']['id_product_tag']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_sale($params) {
		$query = '
			SELECT * 
			FROM product
			WHERE product.id_product = :id_product
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
	
	public function get_product_download($params) {
		$query = '
			SELECT * 
			FROM product 
			INNER JOIN  product_download ON product.id_product = product_download.id_product 
			INNER JOIN product_lang ON product.id_product = product_lang.id_product 
			WHERE product.id_product = :id_product
				AND product_download.id_product_download = :id_product_download
		';
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_product_download' => $params['conditions']['id_product_download']
		);
		$stmt = $this->run($query, $values);
		
		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
	}
}
?>