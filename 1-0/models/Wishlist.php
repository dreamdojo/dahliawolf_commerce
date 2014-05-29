<?php
class Wishlist extends _Model {

	const TABLE = 'favorite_product';
	const PRIMARY_KEY_FIELD = 'id_favorite_product';
	
	protected $fields = array(
		'id_favorite_product'
		, 'id_product'
		, 'id_customer'
		, 'id_shop'
		, 'date_add'
		, 'date_upd'
	);
	
	public function does_product_exist_in_wishlist($id_shop, $id_customer, $id_product) {
		$sql = '
			SELECT id_favorite_product FROM favorite_product
			WHERE 
				id_shop = :id_shop AND id_customer = :id_customer AND id_product = :id_product
		';
	
		$params = array(
			':id_shop' 			=> $id_shop
			, ':id_product' 	=> $id_product
			, ':id_customer' 	=> $id_customer
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->select_single($sql, $params);
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get wishlist.');
		}
	}
	
	public function remove_from_wishlist($id_favorite_product, $id_customer) {
		
		try {
			$params = array(
				':id_favorite_product' => $id_favorite_product,
                ':id_customer' => $id_customer
			);
            $ids = explode(",", $id_customer);

            $logger = new Jk_Logger(APP_PATH . 'logs/wishlist.log');
            $logger->LogInfo( "customer ids: " . var_export($ids, true));

            $delete_count = 0;

            if(count($ids)> 1)
            {
                foreach($ids as $id) {
                    $params = array(
                        ':id_favorite_product' => $id_favorite_product,
                        ':id_customer' => $id,
                    );
                    $logger->LogInfo( "deleting wishlist with params: " . var_export($params, true));

                    try {
                        $this->db_delete('id_favorite_product = :id_favorite_product AND id_customer = :id_customer', $params);
                        $delete_count += $this->db_row_count();
                    } catch(Exception $e) {
                        $error = $e->getMessage();
                        $logger->LogInfo( "query params: " . var_export($params, true));
                    }
                }
            }else{
                $logger->LogInfo( "deleting wishlist with params: " . var_export($params, true));
                $this->db_delete('id_favorite_product = :id_favorite_product AND id_customer = :id_customer', $params);
                $delete_count += $this->db_row_count();
            }

            $logger->LogInfo( "delete count: $delete_count" );

			return $delete_count;
			
		} catch(Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to delete row.');
		}
	}

    public function get_wishlist_by_item($product_id) {
        $sql = "
            SELECT favorite_product.id_customer,
            user_username.*
            FROM offline_commerce_v1_2013.favorite_product
            INNER JOIN dahliawolf_v1_2013.user_username ON user_username.user_id = favorite_product.id_customer
            WHERE favorite_product.id_product = :product_id
        ";

        $params = array(
            ':product_id' => $product_id
        );

        try {
            $data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);
            return $data;
        } catch (Exception $e) {
            self::$Exception_Helper->server_error_exception('Unable to get wishlist.');
        }
    }
	
	public function get_wishlist($id_shop, $id_lang, $id_customer) {
		$sql = "
		SELECT favorite_product.id_favorite_product, 
         product.*,
         product_lang.name AS product_lang_name,
         product_lang.name AS product_name,
         shop.name AS shop_name,
         lang.name AS lang_name,
         supplier.name AS supplier,
         manufacturer.name AS manufacturer,
         default_shop.name AS default_shop_name,
         tax_rules_group.name AS tax_rules_group,
         product_lang.description,
         product_lang.description_short,
         product_lang.meta_description,
         product_lang.meta_keywords,
         product_lang.meta_title,
         customer.username,
         (SELECT product_file.product_file_id FROM product_file WHERE product_file.product_id = product.id_product ORDER BY product_file.product_file_id ASC LIMIT 1) AS product_file_id,
         IF(EXISTS(SELECT category_product.id_category_product FROM category_product WHERE category_product.id_category = 1 AND category_product.id_product = product.id_product),
         1,
         0) AS is_new
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
			LEFT JOIN favorite_product ON product.id_product = favorite_product.id_product AND favorite_product.id_shop = product_shop.id_shop
        WHERE 
        	shop.id_shop 						= :id_shop 
        	AND lang.id_lang 					= :id_lang 
        	AND product_shop.active 			= :active
         	AND favorite_product.id_customer 	= :id_customer
		ORDER BY favorite_product.date_add
		";
		
		$params = array(
			':id_shop' => $id_shop
			, ':id_lang' => $id_lang
			, ':active' => '1'
			, ':id_customer' => $id_customer
		);
		
		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get wishlist.');
		}
	}
}	
?>