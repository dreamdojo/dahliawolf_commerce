<?
error_reporting(E_ALL);
ini_set('display_errors', '1');

require $_SERVER['DOCUMENT_ROOT'] . '/1-0/lib/php/Commerce_API.php';

define('API_KEY_DEVELOPER', 'b968a167feba0990b283f0cd65757a60');
define('PRIVATE_KEY_DEVELOPER', '796323f65ce5f0178dc15e8181c17247');


function api_request($service, $calls, $return_array = false) {
	if (!class_exists('Commerce_API', false)) {
		require $_SERVER['DOCUMENT_ROOT'] . '/1-0/lib/php/Commerce_API.php';
	}
	
	// Instantiate library helper
	$api = new Commerce_API(API_KEY_DEVELOPER, PRIVATE_KEY_DEVELOPER);
	
	// Make request
	$result = $api->rest_api_request($service, $calls);
	
	if (!$return_array) {
		return $result;
	}
	
	$decoded = json_decode($result, true);
	if ($decoded) {
		return $decoded;
	}
	echo $result;
	return;
}

$items = '
<a href="#create-invoice">// Create Invoice</a>
';

echo nl2br($items);

echo '<a name="create-invoice">// Create Invoice</a>';
$calls = array(
	'create_estimate' => array(
		'id_shop_group' => NULL
		, 'id_shop' => 3
		, 'id_carrier' => 1
		, 'delivery_option'
		, 'id_lang' => 1
		, 'id_address_delivery' => 4
		, 'id_address_invoice' => 2
		, 'id_currency' => 1
		, 'id_customer' => 17
		, 'id_guest' => NULL
		, 'id_payment_term' => 4
		, 'secure_key' => NULL
		, 'recyclable' => 1
		, 'gift' => '1'
		, 'gift_message' => NULL
		, 'allow_seperated_package' => 0
		, 'products' => array(
			array(
				'id_product' => 76
				, 'id_product_attribute' => NULL
				, 'quantity' => 1
			)
			, array(
				'id_product' => 70
				, 'id_product_attribute' => NULL
				, 'quantity' => 1
			)
		)
	)
);

$data = api_request('invoice', $calls, true);
echo '<pre>';
print_r($data);

?>