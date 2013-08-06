<?
error_reporting(E_ALL);
ini_set('display_errors', '0');
session_start();

require_once 'config/config.php';

//require_once 'controllers/_Controller.php';
//require_once 'controllers/Account_Controller.php';



define('APP_PATH', realpath('./')."/");
$include_paths = explode(":", get_include_path());
$include_paths[] = realpath('../lib/jk07');
$include_paths[] = realpath('../');
set_include_path(implode(":", $include_paths));


require_once 'Jk_Root.php';
require_once 'Jk_Base.php';
require_once 'Jk_Logger.php';
require_once 'utils/Error_Handler.php';


$error_handler = new Error_Handler();
$error_handler->registerShutdownHandler();
$error_handler->registerErrorHandler();



$endpoint = !empty($_GET['endpoint']) ? $_GET['endpoint'] : NULL;
$controller_name = str_replace(' ', '_', ucwords(str_replace('_', ' ', $endpoint))) . '_Controller';

if (empty($endpoint)) {
	die('Endpoint is not set.');
}

try {
	$controller = new $controller_name();

	$request = !empty($_POST) ? $_POST : $_GET;
	$response_format = !empty($_GET['response_format']) ? $_GET['response_format'] : NULL;
	$request_methods = get_request_methods();
	
	// Validate Response Format
	if (empty($response_format) || empty($request_methods[$response_format])) {
		die('Invalid response format.');
	}
	
	// Do Request
	$request_method = !empty($request_methods[$response_format]) ? $request_methods[$response_format] : NULL;
	
	// SOAP Call
	if ($request_method == 'SOAP') {
		$SoapServer = new SoapServer(
			NULL
			, array(
				'uri' => 'http://dev.jewelsthatgive.com/'
			)
		);
		$SoapServer->setClass($controller_name); //$server->addFunction(SOAP_FUNCTIONS_ALL); // bad for security
		$SoapServer->handle();
		die();
	}
	
	// REST Call
	else if ($request_method == 'REST' && !empty($request['calls'])) {
        /** @var  $controller _Controller */
		$result = $controller->process_request($request);
		
		// JSON
		if ($response_format == 'json') {
			echo json_encode($result);
		}
		// JSONP
		else if ($response_format == 'jsonp') {
			echo '?(' . json_encode($result) . ')';
		}
		
		die();
	}
	
} catch (Exception $e) {
	die($e->getMessage());	
}


?>