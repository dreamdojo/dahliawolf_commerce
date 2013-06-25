<?php
class Product extends db {

	private $table 			= 'product';
	/*
	 * condition (enum: new, used, refurbished)
	 * visibility (enum: both, catalog, search, none)
	 * */
	public function get_product_details($params) {
		$query = '
		SELECT product.*, supplier.name AS supplier, manufacturer.name AS manufacturer, shop.name AS default_shop, tax_rules_group.name AS tax_rules_group, product_lang_names.names
 		FROM product  
		LEFT JOIN supplier ON product.id_supplier = supplier.id_supplier
		LEFT JOIN manufacturer ON product.id_manufacturer = manufacturer.id_manufacturer
		LEFT JOIN category ON product.id_category_default = category.id_category
		LEFT JOIN shop ON product.id_shop_default = shop.id_shop
		LEFT JOIN tax_rules_group ON product.id_tax_rules_group = tax_rules_group.id_tax_rules_group
		INNER JOIN (
	    	SELECT 
				product.id_product, GROUP_CONCAT(CONCAT(product_lang.name, " (", lang.language_code, ")") ORDER BY lang.language_code ASC SEPARATOR 0x1D) AS names
				FROM product
					LEFT JOIN product_lang ON product.id_product = product_lang.id_product
					LEFT JOIN lang ON product_lang.id_lang = lang.id_lang
					WHERE product.id_product = :id_product AND product_lang.id_lang = :id_lang AND product_lang.id_shop = :id_shop
				GROUP BY product.id_product
		) AS product_lang_names ON product.id_product = product_lang_names.id_product
		';
		
		$values = array(
			':id_product' => $params['conditions']['id_product']
			, ':id_lang' => $params['conditions']['id_lang']
			, ':id_shop' => $params['conditions']['id_shop']
		);
		
			/*SELECT
				product.id_product
				, product.id_supplier
				, product.id_manufacturer
				, product.id_category_default
				, product.id_shop_default
				, product.id_tax_rules_group
				, product.on_sale
				, product.online_only
				, product.ean13
				, product.upc
				, product.ecotax
				, product.quantity
				, product.minimal_quantity
				, product.price
				, product.wholesale_price
				, product.unity
				, product.unit_price_ratio
				, product.additional_shipping_cost
				, product.reference
				, product.supplier_reference
				, product.location
				, product.width
				, product.height
				, product.depth
				, product.weight
				, product.out_of_stock
				, product.quantity_discount
				, product.customizable
				, product.uploadable_files
				, product.text_fields
				, product.active
				, product.available_for_order
				, product.available_date
				, product.condition
				, product.show_price
				, product.indexed
				, product.visibility
				, product.cache_is_pack
				, product.cache_has_attachments
				, product.is_virtual
				, product.cache_default_attribute
				, product.date_add
				, product.date_upd
				, product.advanced_stock_management
				 
				, product_lang.id_product_lang
				, product_lang.id_product
				, product_lang.id_shop,
				, product_lang.id_lang,
				, product_lang.name 
				, product_lang.description_short 
				, product_lang.description
				, product_lang.link_rewrite
				, product_lang.meta_description
				, product_lang.meta_keywords
				, product_lang.meta_title
				, product_lang.available_now
				, product_lang.available_later
				
				, product_shop.id_product_shop
				, product_shop.id_product
				, product_shop.id_shop
				, product_shop.id_category_default
				, product_shop.id_tax_rules_group
				, product_shop.on_sale
				, product_shop.online_only
				, product_shop.ecotax
				, product_shop.minimal_quantity
				, product_shop.price
				, product_shop.wholesale_price
				, product_shop.unity
				, product_shop.unit_price_ratio
				, product_shop.additional_shipping_cost
				, product_shop.customizable
				, product_shop.uploadable_files
				, product_shop.text_fields
				, product_shop.active
				, product_shop.available_for_order
				, product_shop.available_date
				, product_shop.condition
				, product_shop.show_price
				, product_shop.indexed
				, product_shop.visibility
				, product_shop.cache_default_attribute
				, product_shop.date_add
				, product_shop.date_upd
				, product_shop.advanced_stock_management
			FROM product
				INNER JOIN product_lang ON product.id_product = product_lang.id_product
				INNER JOIN product_shop ON product.id_product = product_shop.id_product
			WHERE product.id_product = :id_product
				AND product_lang.id_lang = :id_lang
				AND product_shop.id_shop = :id_shop
		';*/
		
		
		$stmt = $this->run($query, $values);
		$this->result = $stmt->fetchAll();
       	
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get product details.');
      	}

        return resultArray(true, $this->result);
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