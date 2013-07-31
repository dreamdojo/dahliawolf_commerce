<?php

class Cart extends _Model {

	const TABLE = 'cart';
	const PRIMARY_KEY_FIELD = 'id_cart';

	protected $fields = array(
		'id_shop_group'
		, 'id_shop'
		, 'id_carrier'
		, 'delivery_option'
		, 'id_lang'
		, 'id_address_delivery'
		, 'id_address_invoice'
		, 'id_currency'
		, 'id_customer'
		, 'id_guest'
		, 'id_payment_term'
		, 'id_estimate'
		, 'secure_key'
		, 'recyclable'
		, 'gift'
		, 'gift_message'
		, 'allow_seperated_package'
		, 'date_add'
		, 'date_upd'
		, 'paypal_token'
		, 'user_id_cart_rule'
	);

	// 3/1/2013
	public function get_user_cart($user_id, $id_shop, $id_cart) {
		$sql = '
			SELECT cart.*
				, membership_level.points
			FROM cart
				INNER JOIN customer ON cart.id_customer = customer.id_customer
				LEFT JOIN dahliawolf_v1_2013.membership_level ON cart.user_id_cart_rule = membership_level.commerce_id_cart_rule
			WHERE customer.user_id = :user_id AND cart.id_shop = :id_shop AND cart.id_cart = :id_cart
			ORDER BY cart.id_cart DESC
		';

		$params = array(
			':user_id' => $user_id
			, ':id_shop' => $id_shop
			, ':id_cart' => $id_cart
		);

		try {
			$data = self::$dbs[$this->db_host][$this->db_name]->select_single($sql, $params);

			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get cart.');
		}
	}

	public function add_cart_in_cookie($data=array()){

					$cookie_data=json_encode($data);
					setcookie('_cart', $cookie_data);
					return json_encode(resultArray(true, 'Cart added in Cookie!'));


	}


	public function add_item_in_cart_cookie($data=array()){
		/*$arr=array('shop_id'=>'test4','id_product'=>'test5'..... and so on);*/

		if(!isset($_COOKIE['_cart'])) {
			return resultArray(false, 'Cart is not ready yet!');
		} else {
			if (empty($data['id_cart'])) {
				$error = 'Cart id is required!';
			}
					else if (!is_array($data['id_product'])) {

						$error = 'Product id is required!';

					}else if (!is_array($data['id_shop'])) {

						$error = 'Shop id is required!';
					}else if (!is_array($data['quantity'])) {

						$error = 'Quantity id is required!';
					}

					if (!empty($error)) {

						return json_encode(resultArray(false,$error));

					}else{	$cookieData=array();

							$cart=json_decode($_COOKIE["_cart"]);

							$cookieData=(array)$cart;

							$count=count($cart->_cartItems);

							$cookieData['_cartItems'][$count]=$data;

							$cookie_data=json_encode($cookieData);

							setcookie('_cart', $cookie_data);

							return json_encode(resultArray(true, 'Item added to cart!'));

					}

			}


		}


	public function add_cart_in_db($COOKIE=null){
		/*pass in $COOKIE=$_COOKIE['_cart']*/

			$cart = array();
			$cartItems = array();

			if (isset($COOKIE)) {
				foreach (json_decode($COOKIE) as $key => $value) {
					if(is_array($value)) {
						$cartItems = $value;
					} else {
						$cart[$key] = $value;
					}
				}

				$this->insert($this->cart,$cart);
				$cart_id = $this->insert_id;

				foreach($cartItems as $Itemkey=>$Itemvalue) {
					$Itemvalue['id_cart'] = $cart_id;
					$this->insert($this->cart_product,$Itemvalue);
				}

				return json_encode(resultArray(TRUE, $cart_id));
			}else{

				json_encode(resultArray(TRUE,'Cart is empty!'));

			}

	}
	public function add_item_in_cart_db($data=array()) {

		if (empty($data['id_cart'])) {
			$error = 'Cart id is required!';
		} else if (!is_array($data['id_product'])) {
			$error = 'Product id is required!';
		} else if (!is_array($data['id_shop'])) {
			$error = 'Shop id is required!';
		} else if (!is_array($data['quantity'])) {
			$error = 'Quantity id is required!';
		}

		if (!empty($error)) {
			return json_encode(resultArray(false, $error));
		} else {
			$this->insert($this->cart_product,$data);
			return json_encode(resultArray(TRUE, "Item successfully added to cart!"));
		}
	}



		public function get_cart_item_db($cart_id=null){
		//get_cart_item_db
				$sql = 'SELECT * from '.$this->cart_product.' where id_cart="'.$cart_id.'"';
				$stmt = $this->run($sql);
				$this->result = $stmt->fetchAll();

				if (!empty($this->result)) {
					return resultArray(true, $this->result);
				}else{
					return resultArray(false, NULL, 'No record found');
				}

		}

		public function get_cart_item_cookie(){

			if (isset($_COOKIE["_cart"])){
					foreach (json_decode($_COOKIE["_cart"]) as $key => $value){
											if(is_array($value)){
													$cartItems=$value;

											}
						}
						if(!empty($cartItems)){

								return json_encode(resultArray(true, $cartItems));
						}else{
								return json_encode(resultArray(false, 'No Items found in cart'));
						}


			}
		}


		public function get_cart_from_cookie(){
			$_cart=array();

			if(isset($_COOKIE["_cart"])){

			$_cart=(array)json_decode($_COOKIE["_cart"]);

				return json_encode(resultArray(true, $_cart));

			}else{

				return json_encode(resultArray(false, 'Cart is not set in cookie yet!'));

			}



		}



		public function get_number_of_items_cart_db($cart_id=null){

			if(!isset($cart_id)){

					return json_encode(resultArray(false, 'Cart id required!'));

				}else{

					$sql="select count(*) from ".$this->cart_product." where id_cart='".$cart_id."'";
					$stmt=$this->run($sql);
					$this->result=$stmt->fetchColumn();
					$count= (int) $this->result;

					return json_encode(resultArray(true, $count));
				}

		}


		public function get_number_of_items_cart_cookie(){

			if(isset($_COOKIE["_cart"])){

							$cart=json_decode($_COOKIE["_cart"]);
							$count=count($cart->_cartItems);
							return json_encode(resultArray(true, $count));
			}else{
					return json_encode(resultArray(false, "Cart does set yet!"));
			}


		}


		public function delete_cart_item_db($id_cart=null){

				if(!isset($id_cart)){

					return json_encode(resultArray(false, 'Cart id required!'));

				}else{

					$sql="delete from ".$this->cart_product." where id_cart='".$id_cart."'";
					$this->run($sql);
					return json_encode(resultArray(true, 'Cart items deleted!'));
				}

		}


		public function delete_cart_item_cookie($item_id=null){


			if(empty($item_id)){

						return json_encode(resultArray(false, 'Item id required!'));
			}else{

							$cookieData=array();

							$cart=json_decode($_COOKIE["_cart"]);

							$cookieData=(array)$cart;

							unset($cookieData['_cartItems'][$item_id-1]);

							$cookie_data=json_encode($cookieData);

							setcookie('_cart', $cookie_data);

							return json_encode(resultArray(TRUE, 'Item deleted from cart!'));
			}

		}




	public function delete_cart_cookie(){

		if(isset($_COOKIE['_cart'])){

		    setcookie('_cart', '', time() - 3600);
		    return json_encode(resultArray(TRUE, 'Cart is deleted from cookie'));

			}else{

			return json_encode(resultArray(TRUE, 'Cart is empty!'));
		}

	}

	public function delete_cart_db($cartd_id=null){

		if(!isset($cart_id)){

			return json_encode(resultArray(TRUE, 'Cart id is required'));

		}else{

			$sql="delete from ".$this->cart." where id_cart='".$id_cart."'";
			$this->run($sql);

			$sql="delete from ".$this->cart_product." where id_cart='".$id_cart."'";
			$this->run($sql);

			return json_encode(resultArray(TRUE, 'Cart is deleted from db'));

		}


	}





}
?>
