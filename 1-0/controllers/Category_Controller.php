<?
/**
 * @property Product Product
 * @property Category Category
 */
class Category_Controller extends _Controller {

	
	public function get_shop_categories($params) {
		$this->load('Category');

        if($params['id_shop']=='' || !$params['id_shop']) $params['id_shop'] = 3;
        if($params['id_lang']=='' || !$params['id_lang']) $params['id_lang'] = 1;
		
		$data = $this->Category->getShopCategories($params);
		return static::wrap_result(true, $data);
	}
	
	public function get_category($params) {
		$this->load('Category');
		
		// Validations
		$input_validations = array(
			'id_category' => array(
				'label' => 'Category ID'
				, 'rules' => array(
					'is_int' => NULL
				)
			)
		);
		$this->Validate->add_many($input_validations, $params, true);
		$this->Validate->run();
		
		$data = $this->Category->get_shop_categories($params);
		return static::wrap_result(true, $data);
	}


    public function get_categories($params=array())
    {
        $category = new Category();

        if($params['id_shop']=='' || !$params['id_shop']) $params['id_shop'] = 3;
        if($params['id_lang']=='' || !$params['id_lang']) $params['id_lang'] = 1;

        $data = $category->getCategories($params);


        return $data;
    }
	
	public function get_products_in_category($params) {
		$this->load('Product');
		
		if($params['id_shop']=='' ){
		return static::wrap_result(false, NULL, 'Please pass id_shop in parameter!');
		}
        if($params['id_category']=='' ){
		return static::wrap_result(false, NULL, 'Please pass id_category in parameter!');
		}
        if($params['id_lang']=='' ){
		return static::wrap_result(false, NULL, 'Please pass id_lang in parameter!');
		}
		
		if (empty($params['id_category'])) $params['id_category'] = 0;
		$data = $this->Product->get_products_in_category($params);
		return static::wrap_result(true, $data);
	}
	
	public function get_number_of_products_in_category($params) {
		$this->load('Product');
		
        if($params['conditions']['id_shop']=='' ){
		return static::wrap_result(false, NULL, 'Please pass id_shop in parameter!');
		}
        if($params['conditions']['id_category']=='' ){
		return static::wrap_result(false, NULL, 'Please pass id_category in parameter!');
		}
        if($params['conditions']['id_lang']=='' ){
		return static::wrap_result(false, NULL, 'Please pass id_lang in parameter!');
		}
		
		if (empty($params['biz_site_id'])) $params['biz_site_id'] = 0;
		$data = $this->Product->get_number_of_products_in_category($params['biz_site_id']);
		return static::wrap_result(true, $data);
	}
}

?>