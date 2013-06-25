<?php
// session start
session_start();

require_once 'includes/config.php';
require_once 'models/Shop.php';
require_once 'models/Tax.php';
require_once 'models/Category.php';
require_once 'models/Product.php';
/*
require_once 'models/Cart.php';
require_once 'models/shipping.php';
require_once 'models/order.php';
require_once 'models/invoice.php';
require_once 'models/estimate.php';
require_once 'models/manufacturer.php';
require_once 'models/supplier.php';
require_once 'models/inventory.php';
require_once 'models/analytics.php';
require_once 'models/report.php';
*/
header('Content-type: application/json');
$calls = json_decode($_REQUEST['calls'], true);

//echo "<pre>";
//print_r($_REQUEST);
//exit();

$_REQUEST['api'] = $_REQUEST['endpoint'];
foreach($calls AS $key => $val) {
	//$function = $key
	$_REQUEST['function'] = $key;
	foreach($val AS $key2 => $val2) {
		$_REQUEST[$key2] = $val2;
	}
}

/*
 * get_shopid_from_domain
 * get_shop_info
 */
if (isset($_REQUEST['api']) && $_REQUEST['api'] == 'shop') {
	$shop = new Shop();
	if ($_REQUEST['function'] == 'get_shopid_from_domain') {
		$params 	= array();
		$fields 	= array();
		$conditions = array();
		
		$fields[] = "id_shop";
		
		$conditions['domain'] = $_REQUEST['domain'];
		$conditions['physical_uri'] = $_REQUEST['physical_uri'];
		
		$params['fields'] 		= $fields;
		$params['conditions'] 	= $conditions;
		
		echo json_encode($shop->get_shopid_from_domain($params));
		return;
	} elseif ($_REQUEST['function'] == 'get_shop_info') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_shop'] = $_REQUEST['id_shop'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($shop->get_shop_info($params));
		return;
	}
	else {
		resultArray(FALSE, "Function doesn't exist!");
	}

} 
/*
 * get_shop_categories 
 * get_products_in_category
 * get_number_of_products_in_category
 */
 elseif(isset($_REQUEST['api']) && $_REQUEST['api'] == 'category') {
	$category = new Category();
	if ($_REQUEST['function'] == 'get_shop_categories') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_shop'] = $_REQUEST['id_shop'];
		$conditions['id_lang'] = $_REQUEST['id_lang'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($category->get_shop_categories($params));
		return;
	} elseif ($_REQUEST['function'] == 'get_products_in_category') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_shop'] = $_REQUEST['id_shop'];
		$conditions['id_lang'] = $_REQUEST['id_lang'];
		$conditions['id_category'] = $_REQUEST['id_category'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($category->get_products_in_category($params));
		return;
	} elseif ($_REQUEST['function'] == 'get_number_of_products_in_category') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_shop'] = $_REQUEST['id_shop'];
		$conditions['id_lang'] = $_REQUEST['id_lang'];
		$conditions['id_category'] = $_REQUEST['id_category'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($category->get_number_of_products_in_category($params));
		return;
	} else {
		resultArray(FALSE, "Function doesn't exist!");
	}
	
} 
/*
 * get_product_details
 * get_product_attributes
 * get_product_features
 * get_product_price
 * get_product_price_combination
 * get_product_attachment
 * get_product_carrier
 * get_product_comment
 * get_product_tax
 * get_product_country_tax
 * get_product_supplier
 * get_product_manufacturer
 * get_product_stock
 * get_product_tag
 * get_product_sale
 * get_product_download
 */
elseif(isset($_REQUEST['api']) && $_REQUEST['api'] == 'product') {
	$product = new Product();
	if ($_REQUEST['function'] == 'get_product_details') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_shop'] = $_REQUEST['id_shop'];
		$conditions['id_lang'] = $_REQUEST['id_lang'];
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_details($params));
	}
	else if ($_REQUEST['function'] == 'get_product_attributes') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_shop'] = $_REQUEST['id_shop'];
		$conditions['id_lang'] = $_REQUEST['id_lang'];
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_attributes($params));
	}
	else if ($_REQUEST['function'] == 'get_product_features') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_feature_product'
			, 'id_feature'
			, 'id_lang'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_features($params));
	}
	else if ($_REQUEST['function'] == 'get_product_price') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_shop'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_price($params));
	}
	else if ($_REQUEST['function'] == 'get_product_price_combination') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_shop'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_price_combination($params));
	}
	else if ($_REQUEST['function'] == 'get_product_attachment') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_product_attachment'
			, 'id_attachment'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_attachment($params));
	}
	else if ($_REQUEST['function'] == 'get_product_carrier') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_product_carrier'
			, 'id_carrier_reference'
			, 'id_shop'
			, 'id_lang'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_carrier($params));
	}
	else if ($_REQUEST['function'] == 'get_product_comment') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_product_comment'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_comment($params));
	}
	else if ($_REQUEST['function'] == 'get_product_tax') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_shop'
			, 'id_lang'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_tax($params));
	}
	else if ($_REQUEST['function'] == 'get_product_country_tax') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_country'
			, 'id_lang'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_country_tax($params));
	}
	else if ($_REQUEST['function'] == 'get_product_supplier') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_product_supplier'
			, 'id_product_attribute'
			, 'id_supplier'
			, 'id_shop'
			, 'id_lang'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_supplier($params));
	}
	else if ($_REQUEST['function'] == 'get_product_manufacturer') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_lang'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_manufacturer($params));
	}
	else if ($_REQUEST['function'] == 'get_product_stock') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_stock'
			, 'id_warehouse'
			, 'id_product_attribute'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_stock($params));
	}
	else if ($_REQUEST['function'] == 'get_product_tag') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_product_tag'
			, 'id_tag'
			, 'id_lang'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_tag($params));
	}
	else if ($_REQUEST['function'] == 'get_product_sale') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_sale($params));
	}
	else if ($_REQUEST['function'] == 'get_product_download') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$opt_fields = array(
			'id_product_download'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_download($params));
	}
}

/*
 * get_tax_rate_for_zip
 * get_tax_rate_for_state
 * get_tax_rate_for_country
 */
else if (isset($_REQUEST['api']) && $_REQUEST['api'] == 'tax') {
	$tax = new Tax();
	
	if ($_REQUEST['function'] == 'get_tax_rate_for_zip') {
		$params 	= array();
		$conditions = array();
		
		$conditions['zip_code'] = $_REQUEST['zip_code'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($tax->get_tax_rate_for_zip($params));
	}
	else if ($_REQUEST['function'] == 'get_tax_rate_for_state') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_state'] = $_REQUEST['id_state'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($tax->get_tax_rate_for_state($params));
	}
	else if ($_REQUEST['function'] == 'get_tax_rate_for_country') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_country'] = $_REQUEST['id_country'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($tax->get_tax_rate_for_country($params));
	}
}

/* 
 * get_cart_item_cookie
 * get_cart_item_db
 * update_cart_item_cookie
 * update_cart_item_db
 * delete_cart_item_cookie
 * delete_cart_item_db
 * add_item_in_cart_cookie
 * add_item_in_cart_db
 * get_cart_from_cookie
 * get_cart_from_db
 * update_cart_cookie
 * update_cart_db
 * delete_cart_cookie
 * delete_cart_db
 * get_number_of_items_cart_cookie
 * get_number_of_items_cart_db
 */

else if (isset($_REQUEST['api']) && $_REQUEST['api'] == 'cart') {
	$cart = new Cart();
	$product = new Product();
	
	if ($_REQUEST['function'] == 'get_cart_item_cookie') {
		$product_ids = array_keys($_REQUEST['cart']['products']);
		$products = array();
		foreach ($product_ids as $product_id) {
			$params 	= array();
			$conditions = array();
			
			$conditions['id_product'] = $product_id;
			
			$params['conditions'] 	= $conditions;
			$products[$product_id] = $product->get_product($params);
		}
		
		echo json_encode($products);
	}
	else if ($_REQUEST['function'] == 'get_cart_item_db') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_cart'] = $_REQUEST['id_cart'];
		
		$opt_fields = array(
			'id_product'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($cart->get_cart_item_db($params));
	}
	else if ($_REQUEST['function'] == 'update_cart_item_cookie') {
		$product_id = $_REQUEST['product_id'];
		$info = $_REQUEST['product_info'];
		
		$cookie = $_REQUEST;
		$cookie['cart'][$product_id] = $info;
		
		echo json_encode($cookie);
	}
	else if ($_REQUEST['function'] == 'update_cart_item_db') {
		$params 	= array();
		$conditions = array();
		$info = array();
		
		$conditions['id_cart'] = $_REQUEST['id_cart'];
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$update_fields = array(
			'id_address_delivery'
			, 'id_shop'
			, 'id_product_attribute'
			, 'quantity'
			, 'date_add'
		);
		
		foreach ($update_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$info[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] = $conditions;
		$params['info'] = $info;
		echo json_encode($cart->update_cart_item_db($params));
	}
	else if ($_REQUEST['function'] == 'delete_cart_item_cookie') {
		$product_id = $_REQUEST['product_id'];
		$cookie = $_REQUEST;
		unset($cookie['cart'][$product_id]);
		
		echo json_encode($cookie);
	}
	else if ($_REQUEST['function'] == 'delete_cart_item_db') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_cart'] = $_REQUEST['id_cart'];
		
		$opt_fields = array(
			'id_product'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($cart->delete_cart_item_db($params));
	}
	else if ($_REQUEST['function'] == 'add_item_in_cart_cookie') {
		$product_id = $_REQUEST['product_id'];
		$info = $_REQUEST['product_info'];
		
		$cookie = $_REQUEST;
		$cookie['cart'][$product_id] = $info;
		
		echo json_encode($cookie);
	}
	else if ($_REQUEST['function'] == 'add_item_in_cart_db') {
		$params 	= array();
		$conditions = array();
		$info = array();
		
		$info['id_cart'] = $_REQUEST['id_cart'];
		$info['id_product'] = $_REQUEST['id_product'];
		
		$update_fields = array(
			'id_address_delivery'
			, 'id_shop'
			, 'id_product_attribute'
			, 'quantity'
			, 'date_add'
		);
		
		foreach ($update_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$info[$field] = $_REQUEST[$field];
			}
		}
		
		$params['info'] = $info;
		echo json_encode($cart->add_item_in_cart_db($params));
	}
	else if ($_REQUEST['function'] == 'get_cart_from_cookie') {
		$cookie = $_REQUEST;
		
		echo json_encode($cookie['cart']);
	}
	else if ($_REQUEST['function'] == 'get_cart_from_db') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_cart'] = $_REQUEST['id_cart'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($cart->get_cart_from_db($params));
	}
	else if ($_REQUEST['function'] == 'update_cart_cookie') {
		$products = $_REQUEST['products'];
		
		$cookie = $_REQUEST;
		$cookie['cart'] = $products;
		
		echo json_encode($cookie);
	}
	else if ($_REQUEST['function'] == 'update_cart_db') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_cart'] = $_REQUEST['id_cart'];
		
		$params['conditions'] 	= $conditions;
		$params['products'] 	= $_REQUEST['products'];
		echo json_encode($cart->update_cart_db($params));
	}
	else if ($_REQUEST['function'] == 'delete_cart_cookie') {
		$cookie = $_REQUEST;
		unset($cookie['cart']);
		
		echo json_encode($cookie);
	}
	else if ($_REQUEST['function'] == 'delete_cart_db') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_cart'] = $_REQUEST['id_cart'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($cart->delete_cart_db($params));
	}
	else if ($_REQUEST['function'] == 'get_number_of_items_cart_cookie') {
		$cookie = $_REQUEST;
		$products = $cookie['cart']['products'];
		
		$num_products = 0;
		foreach ($products as $product) {
			$num_products += $product['quantity'];
		}
		
		echo json_encode($num_products);
	}
	else if ($_REQUEST['function'] == 'get_number_of_items_cart_db') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_cart'] = $_REQUEST['id_cart'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($cart->get_number_of_items_cart_db($params));
	}
} 

/*
 * get_shipping_rate
 * get_shipping_handling
 * print_shipping_label
 */
elseif(isset($_REQUEST['api']) && $_REQUEST['api'] == 'shipping'){
	$shipping = new Shipping();
	
	if ($_REQUEST['function'] == 'get_shipping_rate') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_shipping'] = $_REQUEST['id_shipping'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($shipping->get_shipping_rate($params));
	}
	else if ($_REQUEST['function'] == 'get_shipping_handling') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_shipping'] = $_REQUEST['id_shipping'];
		
		$opt_fields = array(
			'id_order'
			, 'id_cart'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($shipping->get_shipping_handling($params));
	}
	else if ($_REQUEST['function'] == 'print_shipping_label') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_shipping'] = $_REQUEST['id_shipping'];
		
		$opt_fields = array(
			'id_order'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($shipping->print_shipping_label($params));
	}
} 

/*
 * place_new_order
 * get_orders
 * get_order_info
 * cancel_order
 * return_order
 * get_order_shipping_info
 * get_order_estimate
 * get_order_invoice
 * get_order_messages
 * create_order_message
 * reply_to_order_message
 * get_order_payment_method
 * get_order_status
 */
else if (isset($_REQUEST['api']) && $_REQUEST['api'] == 'order'){
	$order = new Order();
	
	if ($_REQUEST['function'] == 'place_new_order') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_country'] = $_REQUEST['id_country'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($order->place_new_order($params));
	}
	else if ($_REQUEST['function'] == 'get_orders') {
		$params 	= array();
		$conditions = array();
		
		$opt_fields = array(
			'id_order'
			, 'id_shop_group'
			, 'id_shop'
			, 'id_carrier'
			, 'id_lang'
			, 'id_customer'
			, 'id_cart'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] = $conditions;
		echo json_encode($order->get_orders($params));
	}
	else if ($_REQUEST['function'] == 'get_order_info') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order'] = $_REQUEST['id_order'];
		
		$opt_fields = array(
			'id_shop'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] = $conditions;
		echo json_encode($order->get_order_info($params));
	}
	else if ($_REQUEST['function'] == 'cancel_order') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order'] = $_REQUEST['id_order'];
		
		$params['conditions'] = $conditions;
		echo json_encode($order->cancel_order($params));
	}
	else if ($_REQUEST['function'] == 'return_order') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order'] = $_REQUEST['id_order'];
		
		$params['conditions'] = $conditions;
		echo json_encode($order->return_order($params));
	}
	else if ($_REQUEST['function'] == 'get_order_shipping_info') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order'] = $_REQUEST['id_order'];
		
		$params['conditions'] = $conditions;
		echo json_encode($order->get_order_shipping_info($params));
	}
	else if ($_REQUEST['function'] == 'get_order_estimate') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order'] = $_REQUEST['id_order'];
		
		$params['conditions'] = $conditions;
		echo json_encode($order->get_order_estimate($params));
	}
	else if ($_REQUEST['function'] == 'get_order_invoice') {
		$params 	= array();
		$conditions = array();
		
		$opt_fields = array(
			'id_order_invoice'
			, 'id_order'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] = $conditions;
		echo json_encode($order->get_order_invoice($params));
	}
	else if ($_REQUEST['function'] == 'get_order_messages') {
		$params 	= array();
		$conditions = array();
		
		$opt_fields = array(
			'id_order_message'
			, 'id_lang'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] = $conditions;
		echo json_encode($order->get_order_messages($params));
	}
	else if ($_REQUEST['function'] == 'create_order_message') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_lang'] = $_REQUEST['id_lang'];
		$conditions['name'] = $_REQUEST['name'];
		$conditions['message'] = $_REQUEST['message'];
		
		$params['conditions'] = $conditions;
		echo json_encode($order->create_order_message($params));
	}
	else if ($_REQUEST['function'] == 'reply_to_order_message') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order_message'] = $_REQUEST['id_order_message'];
		$conditions['message'] = $_REQUEST['message'];
		
		$params['conditions'] = $conditions;
		echo json_encode($order->reply_to_order_message($params));
	}
	else if ($_REQUEST['function'] == 'get_order_payment_method') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order'] = $_REQUEST['id_order'];
		
		$params['conditions'] = $conditions;
		echo json_encode($order->get_order_payment_method($params));
	}
	else if ($_REQUEST['function'] == 'get_order_status') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order'] = $_REQUEST['id_order'];
		
		$opt_fields = array(
			'id_lang'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] = $conditions;
		echo json_encode($order->get_order_status($params));
	}
} 

/*
 * get_invoices
 * get_invoice_detail
 * send_invoice
 * print_invoice
 */
else if (isset($_REQUEST['api']) && $_REQUEST['api'] == 'invoice') {
	$invoice = new Invoice();
	
	if ($_REQUEST['function'] == 'get_invoices') {
		$params 	= array();
		$conditions = array();
		
		$opt_fields = array(
			'id_shop'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		
		echo json_encode($invoice->get_invoices($params));
	}
	else if ($_REQUEST['function'] == 'get_invoice_detail') {
		$params 	= array();
		$conditions = array();
		
		$opt_fields = array(
			'id_order_invoice'
			, 'id_order'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] = $conditions;
		echo json_encode($invoice->get_invoices($params));
	}
	else if ($_REQUEST['function'] == 'send_invoice') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order_invoice'] = $_REQUEST['id_order_invoice'];
		$conditions['email'] = $_REQUEST['email'];
		
		$params['conditions'] = $conditions;
		echo json_encode($invoice->send_invoice($params));
	}
	else if ($_REQUEST['function'] == 'print_invoice') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order_invoice'] = $_REQUEST['id_order_invoice'];
		
		$params['conditions'] = $conditions;
		echo json_encode($invoice->print_invoice($params));
	}
} 

/*
 * get_estimates
 * get_estimate_detail
 * update_estimate
 * make_a_copy_estimate
 * convert_estimate_to_invoice
 * send_estimate
 * print_estimate
 */
elseif(isset($_REQUEST['api']) && $_REQUEST['api'] == 'estimate'){
	$estimate = new Estimate();
	
	if ($_REQUEST['function'] == 'get_estimates') {
		$params 	= array();
		$conditions = array();
		
		$opt_fields = array(
			'id_shop'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		
		echo json_encode($estimate->get_estimates($params));
	}
	else if ($_REQUEST['function'] == 'get_estimate_detail') {
		$params 	= array();
		$conditions = array();
		
		$opt_fields = array(
			'id_order_estimate'
			, 'id_order'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] = $conditions;
		echo json_encode($estimate->get_estimate_detail($params));
	}
	else if ($_REQUEST['function'] == 'update_estimate') {
		$params 	= array();
		$conditions = array();
		$info = array();
		
		$conditions['id_order_estimate'] = $_REQUEST['id_order_estimate'];
		
		$update_fields = array(
			'id_order'
			, 'number'
			, 'delivery_number'
			, 'delivery_date'
			, 'total_discount_tax_excl'
			, 'total_discount_tax_incl'
			, 'total_paid_tax_excl'
			, 'total_paid_tax_incl'
			, 'total_products'
			, 'total_products_wt'
			, 'total_shipping_tax_excl'
			, 'total_shipping_tax_incl'
			, 'shipping_tax_computation_method'
			, 'total_wrapping_tax_excl'
			, 'total_wrapping_tax_incl'
			, 'note'
			, 'date_add'
		);
		
		foreach ($update_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$info[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] = $conditions;
		$params['info'] = $info;
		
		echo json_encode($estimate->update_estimate($params));
	}
	else if ($_REQUEST['function'] == 'make_a_copy_estimate') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order_estimate'] = $_REQUEST['id_order_estimate'];
		
		$params['conditions'] = $conditions;
		echo json_encode($estimate->make_a_copy_estimate($params));
	}
	else if ($_REQUEST['function'] == 'convert_estimate_to_invoice') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order_estimate'] = $_REQUEST['id_order_estimate'];
		
		$params['conditions'] = $conditions;
		echo json_encode($estimate->convert_estimate_to_invoice($params));
	}
	else if ($_REQUEST['function'] == 'send_estimate') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order_estimate'] = $_REQUEST['id_order_estimate'];
		$conditions['email'] = $_REQUEST['email'];
		
		$params['conditions'] = $conditions;
		echo json_encode($estimate->send_estimate($params));
	}
	else if ($_REQUEST['function'] == 'print_estimate') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_order_estimate'] = $_REQUEST['id_order_estimate'];
		
		$params['conditions'] = $conditions;
		echo json_encode($estimate->print_estimate($params));
	}
} 

/*
 * get_manufacturers
 * get_product_manufacturer
 * get_manufacturer_info
 */
 else if(isset($_REQUEST['api']) && $_REQUEST['api'] == 'manufacturer'){
	$manufacturer = new Manufacturer();
	$product = new Product();
	
	if ($_REQUEST['function'] == 'get_manufacturers') {
		$params 	= array();
		$conditions = array();
		
		$opt_fields = array(
			'id_lang'
			, 'id_shop'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		
		echo json_encode($manufacturer->get_manufacturers($params));
	}
	else if ($_REQUEST['function'] == 'get_product_manufacturer') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_product'] = $_REQUEST['id_product'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($product->get_product_manufacturer($params));
	}
	else if ($_REQUEST['function'] == 'get_manufacturer_info') {
		$params 	= array();
		$conditions = array();
		
		$conditions['id_manufacturer'] = $_REQUEST['id_manufacturer'];
		
		$params['conditions'] 	= $conditions;
		echo json_encode($manufacturer->get_product_manufacturer($params));
	}
} 

/*
 * get_supplier
 * get_supplier_of_product
 */
else if(isset($_REQUEST['api']) && $_REQUEST['api'] == 'supplier') {
	$supplier = new Supplier();
	
	if ($_REQUEST['function'] == 'get_supplier_of_product') {
		$params 	= array();
		$conditions = array();
		
		$opt_fields = array(
			'id_supplier'
			, 'id_lang'
			, 'id_shop'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($supplier->get_supplier($params));
		return;
	}
	else if ($_REQUEST['function'] == 'get_supplier') {
		$params 	= array();
		$conditions = array();
		
		$opt_fields = array(
			'id_supplier'
			, 'id_lang'
			, 'id_shop'
		);
		
		foreach ($opt_fields as $field) {
			if (array_key_exists($field, $_REQUEST)) {
				$conditions[$field] = $_REQUEST[$field];
			}
		}
		
		$params['conditions'] 	= $conditions;
		echo json_encode($supplier->get_supplier($params));
		return;
	} 
	else {
		resultArray(FALSE, "Function doesn't exist!");
	}
} 

/*
 *
 */
elseif(isset($_REQUEST['api']) && $_REQUEST['api'] == 'inventory'){
	$cart = new Inventory();
	if ($_REQUEST['function'] == '') {
	} else {
		resultArray(FALSE, "Function doesn't exist!");
	}
} elseif(isset($_REQUEST['api']) && $_REQUEST['api'] == 'analytics'){
	$cart = new Analytics();
	if ($_REQUEST['function'] == '') {
	} else {
		resultArray(FALSE, "Function doesn't exist!");
	}
} elseif(isset($_REQUEST['api']) && $_REQUEST['api'] == 'report'){
	$cart = new Report();
	if ($_REQUEST['function'] == '') {
	} else {
		resultArray(FALSE, "Function doesn't exist!");
	}
}
else {
		outputResult(
			false
			, NULL
			, array(
				'Invalid function call.'
			)
		);
	
	die();
}

?>
