<?php 
class Cart extends db {

	private $table_cart		= 'cart';
	private $table_pro		= 'product';
	private $table_cp 		= 'cart_product';
	private $table_cr 		= 'cart_rule';
	private $table_ccr 		= 'cart_cart_rule';
	private $table_crcr 	= 'cart_rule_carrier';
	private $table_crcmb 	= 'cart_rule_combination';
	private $table_crcnty 	= 'cart_rule_country';
	private $table_crg 		= 'cart_rule_group';
	private $table_crl 		= 'cart_rule_lang';
	private $table_crpr 	= 'cart_rule_product_rule';
	private $table_crprg 	= 'cart_rule_product_rule_group';
	private $table_crprv 	= 'cart_rule_product_rule_value';
	private $table_crs 		= 'cart_rule_shop';
	private $table_proatt 	= 'product_attribute';
	
	public function __construct() { 
	
		  parent::__construct();
	  }
	
	/**
	 * Get cart_product table data
	 *
	 *
	 * @param where
	 * @param fields
	 * @return json results 
	 */
	public function getCartItems($where=array(), $fields='*'){
	
		$error = NULL;
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$rows = $this->get_all($this->table_cp, $where, $fields);
		if ($rows === false) {
			 return resultArray(false, NULL, 'Could not get cart data.');
		}
		foreach($rows as $row){
			$row = array_shift($row);
			# get product details
			$pro_detail[] = $this->getProductDetails($row['id_product'], $row['id_product_attribute']);
		}
		return json_encode($pro_detail);	  
	}
	
	/**
	 * Update Cart product records
	 *
	 *
	 * @param where
	 * @param data
	 * @return updated array
	 */
	public function updateCartItem($data = array(), $where = array()){
		
		$error = NULL;
		if (empty($data) || empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data) || !is_array($where)) {
			$error = 'Invalid data.';
		}
		
		# Check product stock
		$check_quantity = $this->checkRemainingQuantity($where['id_product']);
		
		# Decode value and remove array keys
		$check_quantity = array_shift(json_decode($check_quantity, true));
		# If minimum quanity has been set then it reurns value else pass 1
		$min_quantity = ($data['quantity'])? $data['quantity'] : 1;
		if($check_quantity < $min_quantity){
			return resultArray(false, NULL, 'Quantity not in stock.');
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$res = $this->update($this->table_cp,$data,$where);
		
		if ($res === false) {
			 return resultArray(false, NULL, 'Could not update cart item.');
		}
		
		return json_encode(resultArray(TRUE, "Product updated successfully!"));
		
	}
	
	/**
	 * Delete cart item (Empty Cart)
	 *
	 *
	 * @param where
	 * @return updated array
	 */
	 public function deleteCartItem($where = array()){
	 
	 	$error = NULL;
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		# delete item - parameter product_id
		$res = $this->delete($this->table_cp,$where);
		if ($res === false) {
			return resultArray(false, NULL, 'Invalid data.');
		}
		return json_encode(resultArray(TRUE, "Product deleted successfully!"));
	 	
	 }
	 
	
	
	/**
	 * Check product quantiy
	 *
	 *
	 * @param where
	 * @param data
	 * @return updated array
	 */
	public function checkRemainingQuantity($where = array()){
	
		$error = NULL;
		
		if (empty($where)) {
			$error = 'product id is missing.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		$stock = $this->checkStock($where);
		
		# Decode value and remove array keys
		$stock = array_shift(json_decode($stock, true));
		
		# check out of stock
		if($stock == '1'){
			$error = 'Product is out of stock';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$row = $this->get_row($this->table_pro, $where, $fields);
		
		if ($row === false) {
			 return resultArray(false, NULL, 'Could not find any result.');
		}
		
		return json_encode($row);
	}
	
	/**
	 * In Stock or not
	 *
	 *
	 * @param where
	 * @param fields - out_of_stock
	 * @return updated array
	 */
	 public function checkStock($where = array(), $fields= 'out_of_stock'){
	
		$error = NULL;
		
		if (empty($where)) {
			$error = 'product id is missing.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$row = $this->get_row($this->table_pro, $where, $fields);
		
		if ($row === false) {
			 return resultArray(false, NULL, 'Could not find value.');
		}
		
		return json_encode($row);
	}
	/**
	 * Get Cart rules
	 *
	 *
	 * @param where
	 * @param fields 
	 * @return updated array
	 */	
	 
	public function getCartRules($where = array(), $fields){
		$error = NULL;
		
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$rows = $this->get_all($this->table_cr, $where, $fields);
		if ($rows === false) {
			 return resultArray(false, NULL, 'Could not get cart data.');
		}
		
		return json_encode($rows);
	}
	
	/**
	 * Add Cart rule
	 *
	 *
	 * @param data 
	 * @return updated array
	 */	
	 
	public function addCartRule($data=array()){
		$error = NULL;
		if (empty($data)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$this->insert($this->table_cr,$data);
		
		$insert_id = $this->insert_id;
		
		if (empty($insert_id)) {
			return resultArray(false, NULL, 'Could not add cart rule.');
		}
		return json_encode(resultArray(TRUE, "Rule added successfully!"));
	}
	
	/**
	 * Edit Cart rule
	 *
	 *
	 * @param where
	 * @param data 
	 * @return updated array
	 */	
	 
	public function updateCartRules($data = array(),$where = array()){
		$error = NULL;
		
		if (empty($data) || empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data) || !is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$res = $this->update($this->table_cr,$data,$where);
		
		if ($res === false) {
			 return resultArray(false, NULL, 'Could not update cart rule.');
		}
		
		return json_encode(resultArray(TRUE, "Rule updated successfully!"));
	}
	/**
	 * Delete Cart rule
	 *
	 *
	 * @param where 
	 */	
	 
	public function deleteCartRules($where = array()){
		$error = NULL;
		
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->delete($this->table_cr,$where);
		if ($res === false) {
			return resultArray(false, NULL, 'Could not delete cart rule.');
		}
		return json_encode(resultArray(TRUE, "Rule deleted successfully!"));
	}
	/**
	 * Cart Products Subtotal
	 *
	 *
	 * @param data
	 */	
	public function cartSubtotal($data = array()){
		$error = NULL;
		
		if (empty($data)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$subtotal = 0;
		foreach($data as $data_price){
			$subtotal = $subtotal + $data_price['price'];
		}
		return $subtotal;
	}
	/**
	 * Add Cart Country Rule
	 *
	 *
	 * @param data
	 * @return added json message
	 */	
	 public function addCartRuleCountry($data=array()){
	 	$error = NULL;
		if (empty($data)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$this->insert($this->table_crcnty,$data);
		
		$insert_id = $this->insert_id;
		
		if (empty($insert_id)) {
			return resultArray(false, NULL, 'Could not add country rule.');
		}
		return json_encode(resultArray(TRUE, "Country added successfully!"));
	 }
	 /**
	 * Edit Cart Country Rule
	 *
	 *
	 * @param data
	 * @param where
	 * @return updated json message
	 */	
	 public function updateCartRuleCountry($data = array(), $where = array()){
	 	$error = NULL;
		if (empty($data) || empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data) || !is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->update($this->table_crcnty,$data,$where);
		
		if ($res === false) {
			 return resultArray(false, NULL, 'Could not update cart country rule.');
		}
		return json_encode(resultArray(TRUE, "Country updated successfully!"));
	 }
	 /**
	 * delete cart country rule
	 *
	 *
	 * @param where
	 * @return json deleted message
	 */
	public function deleteTaxRule($where=array()){
		$error = NULL;
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->delete($this->table_crcnty,$where);
		if ($res === false) {
			return resultArray(false, NULL, 'Could not delete cart country rule.');
		}
		return json_encode(resultArray(TRUE, "Country deleted successfully!"));
	}
	 /**
	 * Add Cart Rule Group
	 *
	 *
	 * @param data
	 * return json added message
	 */	
	 public function addCartRuleGroup($data=array()){
		 $error = NULL;
		if (empty($data)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$this->insert($this->table_crg,$data);
		
		$insert_id = $this->insert_id;
		
		if (empty($insert_id)) {
			return resultArray(false, NULL, 'Could not add cart rule group.');
		}
		return json_encode(resultArray(TRUE, "Cart rule Group added successfully!"));
	 }
	 /**
	 * Update Cart Rule Group
	 *
	 *
	 * @param data
	 * @param where
	 * return updated group message
	 */	
	 public function updateCartRuleGroup($data = array(),$where = array()){
	 	$error = NULL;
		
		if (empty($data) || empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data) || !is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$res = $this->update($this->table_crg,$data,$where);
		
		if ($res === false) {
			 return resultArray(false, NULL, 'Could not update cart rule group.');
		}
		
		return json_encode(resultArray(TRUE, " Cart rule group updated successfully!"));
	 }
	 /**
	 * Delete Cart rule group
	 *
	 *
	 * @param where 
	 * @return json deleted message
	 */	
	 
	public function deleteCartRuleGroup($where = array()){
		$error = NULL;
		
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->delete($this->table_crg,$where);
		if ($res === false) {
			return resultArray(false, NULL, 'Could not delete cart rule.');
		}
		return json_encode(resultArray(TRUE, "Cart rule group deleted successfully!"));
	}
	 /**
	 * Add Cart Rule Language
	 *
	 *
	 * @param data
	 * return json added message
	 */	
	 public function addCartRuleLanguage($data=array()){
	 	$error = NULL;
		if (empty($data)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$this->insert($this->table_crl,$data);
		
		$insert_id = $this->insert_id;
		
		if (empty($insert_id)) {
			return resultArray(false, NULL, 'Could not add cart rule language.');
		}
		return json_encode(resultArray(TRUE, "Cart rule language added successfully!"));
	 }
	 /**
	 * Edit Cart Rule Language
	 *
	 *
	 * @param data
	 * @param where
	 * return json updated message
	 */	
	 public function updateCartRuleLanguage($data = array(),$where = array()){
	 	$error = NULL;
		if (empty($data) || empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data) || !is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->update($this->table_crl,$data,$where);
		
		if ($res === false) {
			 return resultArray(false, NULL, 'Could not update cart rule language.');
		}
		return json_encode(resultArray(TRUE, "Cart rule language updated successfully!"));
	 }
	 /**
	 * delete cart country rule
	 *
	 *
	 * @param where
	 * @return json deleted message
	 */
	public function deleteCartRuleLanguage($where=array()){
		$error = NULL;
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->delete($this->table_crl,$where);
		if ($res === false) {
			return resultArray(false, NULL, 'Could not delete cart rule language.');
		}
		return json_encode(resultArray(TRUE, "Cart rule language deleted successfully!"));
	}
	 /**
	 * Add Cart Product Rule
	 *
	 *
	 * @param data
	 * return json added message
	 */	
	 public function addCartRuleProductRule($data=array()){
	 	$error = NULL;
		if (empty($data)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$this->insert($this->table_crpr,$data);
		
		$insert_id = $this->insert_id;
		
		if (empty($insert_id)) {
			return resultArray(false, NULL, 'Could not add country rule.');
		}
		return json_encode(resultArray(TRUE, "Country added successfully!"));
	 }
	 /**
	 * Update Cart Product Rule
	 *
	 *
	 * @param data
	 * @param where
	 * return json updated message
	 */	
	 public function updateCartRuleProductRule($data = array(),$where = array()){
	 	$error = NULL;
		if (empty($data) || empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data) || !is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->update($this->table_crpr,$data,$where);
		
		if ($res === false) {
			 return resultArray(false, NULL, 'Could not update cart rule product rule  rule.');
		}
		return json_encode(resultArray(TRUE, "Cart rule product rule updated successfully!"));
	 }
	 /**
	 * delete cart country rule
	 *
	 *
	 * @param where
	 * @return json deleted record
	 */
	public function deleteCartRuleProductRule($where=array()){
		$error = NULL;
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->delete($this->table_crpr,$where);
		if ($res === false) {
			return resultArray(false, NULL, 'Could not delete cart rule product rule.');
		}
		return json_encode(resultArray(TRUE, "Cart rule product rule deleted successfully!"));
	}
	 /**
	 * Add Cart Rule Carrier
	 *
	 *
	 * @param data
	 * return json added message
	 */	
	 public function addCartRuleCarrier($data=array()){
	 	$error = NULL;
		if (empty($data)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$this->insert($this->table_crcr,$data);
		
		$insert_id = $this->insert_id;
		
		if (empty($insert_id)) {
			return resultArray(false, NULL, 'Could not add cart rule carrier.');
		}
		return json_encode(resultArray(TRUE, "Cart rule carrier added successfully!"));
	 }
	 /**
	 * Update Cart Rule Carrier
	 *
	 *
	 * @param data
	 * return json updated message
	 */	
	 public function updateCartRuleCarrier($data = array(),$where = array()){
	 	$error = NULL;
		
		if (empty($data) || empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data) || !is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$res = $this->update($this->table_crcr,$data,$where);
		
		if ($res === false) {
			 return resultArray(false, NULL, 'Could not update cart rule carrier.');
		}
		
		return json_encode(resultArray(TRUE, "Cart rule carrier updated successfully!"));
	 }
	 /**
	 * Delete Cart rule carrier
	 *
	 *
	 * @param where 
	 * @return json deleted record
	 */	
	 
	public function deleteCartRuleCarrier($where = array()){
		$error = NULL;
		
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->delete($this->table_crcr,$where);
		if ($res === false) {
			return resultArray(false, NULL, 'Could not delete cart rule carrier.');
		}
		return json_encode(resultArray(TRUE, "Cart rule carrier deleted successfully!"));
	}
	 /**
	 * Add Cart Cart Rule
	 *
	 *
	 * @param data
	 * @return addded json message
	 */	
	 public function addCartCartRule($data=array()){
	 	$error = NULL;
		if (empty($data)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$this->insert($this->table_ccr,$data);
		
		$insert_id = $this->insert_id;
		
		if (empty($insert_id)) {
			return resultArray(false, NULL, 'Could not add cart cart rule.');
		}
		return json_encode(resultArray(TRUE, "Cart cart rule added successfully!"));
	 }
	 /**
	 * Update Cart Cart Rule
	 *
	 *
	 * @param data
	 * @return updated json message
	 */	
	 public function updateCartCartRule($data = array(), $where = array()){
	 	$error = NULL;
		if (empty($data) || empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($data) || !is_array($where)) {
			$error = 'Invalid data.';
		}
		
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->update($this->table_ccr,$data,$where);
		
		if ($res === false) {
			 return resultArray(false, NULL, 'Could not update cart cart rule.');
		}
		return json_encode(resultArray(TRUE, "Cart cart rule updated successfully!"));
	 }
	 /**
	 * delete Cart Cart Rule
	 *
	 *
	 * @param where
	 * @return deleted record
	 */
	public function deleteCartCartRule($where=array()){
		$error = NULL;
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		
		$res = $this->delete($this->table_ccr,$where);
		if ($res === false) {
			return resultArray(false, NULL, 'Could not delete cart cart rule.');
		}
		return json_encode(resultArray(TRUE, "Cart cart rule deleted successfully!"));
	}
	/**
	 * Add cart item to cookie data.
	 * For guest user or not logged in user. So when they come next time on site their product will remain save in cookie.
	 *
	 * @param where
	 * @param fields default *
	 */	
	public function getCartToCookie($where=array(), $fields='*'){

		$error = NULL;
		if (empty($where)) {
			$error = 'Data is required.';
		}
		else if (!is_array($where)) {
			$error = 'Invalid data.';
		}
		if (!empty($error)) {
			return resultArray(false, NULL, $error);
		}
		$rows = $this->get_all($this->table_cp, $where, $fields);
		if ($rows === false) {
			 return resultArray(false, NULL, 'Could not get cart data.');
		}
		foreach($rows as $row){
			$row = array_shift($row);
			# get product details
			$pro_detail[] = $this->getProductDetails($row['id_product'], $row['id_product_attribute']);
		}
		return json_encode($pro_detail);

		$cart_cookie = json_encode($pro_detail);
		setcookie('_cartItems', $cart_cookie, $lifetime_cookie);

	}

	/**
	 * Grab product data from cookie.
	 * 
	 *
	 * 
	 */	
	public function getItemFromCookie(){

		if (isset($_COOKIE["_cartItems"])){
			foreach (json_decode($_COOKIE["_cartItems"])->_cart as $key => $value){
				if(is_array($value)){
					$cartItems=$value;
				}
			}
			if(!empty($cartItems)){
				return resultArray(true, $cartItems);						
			}else{						
				return resultArray(false, 'No Items found in cart');
			}
		}
	}
}
?>