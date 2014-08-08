<?php
class Category extends _Model {

	const TABLE = 'category';
	const PRIMARY_KEY_FIELD = 'id_category';
	
	public function getShopCategories($params) {
		$query = '
			SELECT category.id_category, category_lang.name, category_lang.description, category_lang.link_rewrite
			FROM category
				INNER JOIN category_lang ON category.id_category = category_lang.id_category
			WHERE category_lang.id_lang = :id_lang
				AND category.active = :active';
				
		if (!empty($params['id_category'])) {
			$query .= ' AND category.id_category = :id_category';
		}
		
		//$query .= ' ORDER BY category_shop.position ASC';
		$values = array(
			':id_lang' => $params['id_lang']
			, ':active' => '1'
		);
		
		if (!empty($params['id_category'])) {
			$values[':id_category'] = $params['id_category'];
		}
		
		try {
			$query_result = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);
			if (empty($query_result)) return NULL;
			return $query_result;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Could not get shop categories');
		}
	}
	
	public function get_category($params) {
		$query = '
			SELECT category.id_category, category_lang.name, category_lang.description, category_lang.link_rewrite, category_shop.position
			FROM category
				INNER JOIN category_shop ON category.id_category = category_shop.id_category
				INNER JOIN category_lang ON category.id_category = category_lang.id_category
			WHERE category_shop.id_shop = :id_shop
				AND category_lang.id_lang = :id_lang
				AND category.id_category = :id_category
				AND category.active = :active
		';
		$values = array(
			':id_shop' => $params['id_shop']
			, ':id_lang' => $params['id_lang']
			, ':id_category' => $params['id_category']
			, ':active' => '1'
		);
		
		try {
			$query_result = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);
			if (empty($query_result)) return NULL;
			return $query_result;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Could not get category');
		}
	}

    public function getCategories($params=array())
    {
        $query = "SELECT
            c.id_category as 'category_id',
            cl.name
        FROM offline_commerce_v1_2013.category c
          JOIN category_lang cl ON cl.id_category = c.id_category
          JOIN category_shop cs ON cs.id_category = c.id_category

          WHERE c.active = :active
            AND cl.id_lang = :id_lang
            AND cs.id_shop = :id_shop
      ";

        $values = array(
            ':id_shop' => $params['id_shop'],
            ':id_lang' => $params['id_lang'],
            ':active' => '1',
        );

        try {
            $query_result = self::$dbs[$this->db_host][$this->db_name]->exec($query, $values);
            if (empty($query_result)) return NULL;
            return $query_result;
        } catch (Exception $e) {
            self::$Exception_Helper->server_error_exception('Could not get category');
        }

    }


}