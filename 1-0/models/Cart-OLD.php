<?php
/* NEEDED FOR FRONTEND WEBSITE / ALL OTHER FUNCTIONS WILL BE USED ON BACKEND ADMINISTRATION
 * add_cart_in_cookie done
 * add_item_in_cart_cookie  done
 * add_cart_in_db done
 * add_item_in_cart_db done

 * get_cart_item_cookie done
 * get_cart_item_db  done

 * get_cart_from_cookie Done
 * get_cart_from_db Done

 * get_number_of_items_cart_cookie DONE
 * get_number_of_items_cart_db DONE

 * delete_cart_item_cookie Done 
 * delete_cart_item_db DONE

 * delete_cart_cookie DONE
 * delete_cart_db DONE

 * update_cart_cookie
 * update_cart_db

 * update_cart_item_cookie
 * update_cart_item_db


 $data=array(
				'cart_id'=>'1',
				'id_shop'=>'1',
				'_cartItems'=>array(
									'0'=>array('sop_id'=>'test','product_id'=>'test1'),
									'1'=>array('sop_id'=>'test2','product_id'=>'test3')
								)
			);
 
 
 */
 
class Carts extends db {

	private $cart_product = 'cart_product';
	private $cart = 'cart';

	public function __construct() { 
		parent::__construct();
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
		

		public function get_cart_from_cookie(){
			$_cart=array();

			if(isset($_COOKIE["_cart"])){

			$_cart=(array)json_decode($_COOKIE["_cart"]);

				return json_encode(resultArray(true, $_cart));	

			}else{
			
				return json_encode(resultArray(false, 'Cart is not set in cookie yet!'));
			
			}
		
		
		
		}

		public function get_cart_from_db($cart_id=null){

				if(!isset($cart_id))return json_encode(resultArray(FALSE, "Please passed cart ID!"));
		
				$_cart=array();

				$sql = 'SELECT * from '.$this->cart.' where id_cart="'.$cart_id.'"';
				$stmt = $this->run($sql);
				$row = $stmt->fetchAll();

				if(!empty($row)){

				foreach($row as $key=>$value){
					$_cart[$key]=$value;
				
				}

				$sql = 'SELECT * from '.$this->cart_product.' where id_cart="'.$cart_id.'"';
				$stmt = $this->run($sql);
				$rows = $stmt->fetchAll();

				if(!empty()){

					$_cart['_cartItems']=$rows;
				
				
				}

				$cookie_data=json_encode($_cart);
				setcookie('_cart', $cookie_data);

				return json_encode(resultArray(TRUE, $_cart));

				}else{

					return json_encode(resultArray(false, "Cart does not found in DB!"));
				
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
