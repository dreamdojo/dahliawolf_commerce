<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/models/database.php';

class Carts extends db {

private $cart_product='cart_product';

private $cart='cart';

		public function __construct() { 
			parent::__construct();
		}


		public function AddItemToCartDB($data=array()){
		
				$this->insert($this->cart_product,$data);
						
				return json_encode(resultArray(TRUE, "Item successfully added to cart!"));
					
				

		}

		public function AddItemToCartCookie($data=array()){
		
		
		}

		public function SaveCartToDB(){
				$cart=array();
				$cartItems=array();

			if (isset($_COOKIE["_cart"])){

					foreach (json_decode($_COOKIE["_cart"])->_cart as $key => $value){

											if(is_array($value)){
													$cartItems=$value;
											
											}else{
													$cart[$key]=$value;
												
											
											}

						}
						$this->insert($this->cart,$cart);
						$cart_id=$this->insert_id;	
						

						foreach($cartItems as $Itemkey=>$Itemvalue){
						
						$Itemvalue['id_cart']=$cart_id;

						$this->insert($this->cart_product,$Itemvalue);
						
						}

			return json_encode(resultArray(TRUE, $cart_id));
			
			}
		
		
		}

		public function SaveCartToCookie($cart_id=null){

				$_cart=array();

				$sql = 'SELECT * from '.$this->cart.' where id_cart="'.$cart_id.'"';
				$stmt = $this->run($sql);
				$row = $stmt->fetchAll();

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
	
		
		}

		public function GetItemFromDBCart($cart_id=null){
		
				$sql = 'SELECT * from '.$this->cart_product.' where id_cart="'.$cart_id.'"';
				$stmt = $this->run($sql);
				$this->result = $stmt->fetchAll();

				if (!empty($this->result)) {
					return resultArray(true, $this->result);
				}else{
					return resultArray(false, NULL, 'No record found');
				}
		
		}

		public function GetItemFromCookieCart(){

			if (isset($_COOKIE["_cart"])){
					foreach (json_decode($_COOKIE["_cart"])->_cart as $key => $value){
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

		public function GetCartFromDB($cart_id=null){
		
				$_cart=array();

				$sql = 'SELECT * from '.$this->cart.' where id_cart="'.$cart_id.'"';
				$stmt = $this->run($sql);
				$row = $stmt->fetchAll();

				foreach($row as $key=>$value){
					$_cart[$key]=$value;
				
				}

				$sql = 'SELECT * from '.$this->cart_product.' where id_cart="'.$cart_id.'"';
				$stmt = $this->run($sql);
				$rows = $stmt->fetchAll();

				if(!empty($rows)){

					$_cart['_cartItems']=$rows;
				
				
				}

				$cookie_data=json_encode($_cart);
				setcookie('_cart', $cookie_data);

			return json_encode(resultArray(TRUE, $_cart));
		}

		public function DeleteItemFromCart(){
		
		
		}

		public function UpdateItemQuantity(){
		
		
		}

		public function AddItemToCartCookie(){
		
		
		}


	
}
?>