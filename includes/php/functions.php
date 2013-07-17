<?

// Check if array is associative or indexed
function is_assoc($array) {
	if (empty($array) && is_array($array)) {
		return false;
	}
	//return array_keys($array) !== range(0, count($array) - 1);
	return (bool)count(array_filter(array_keys($array), 'is_string'));
}

// Group a set of rows into several sets of rows using key_field as the index
function rows_to_groups($rows, $key_field) {
	if (!is_array($rows)) {
		trigger_error(__FUNCTION__ . '() 1st argument needs to be an array');
	}
	
	$array = array();
	foreach ($rows as $row){
		$key_field_value = $row[$key_field];
		if (!isset($array[$key_field_value])) {
			$array[$key_field_value] = array();
		}
		array_push($array[$key_field_value], $row);
	}
	
	return $array;
}

// Convert db rows to array
function rows_to_array($rows, $key_field, $value_field) {
	$array = array();
	if (is_array($rows) && !empty($rows)) {
		$useKeys = !empty($key_field) ? true : false;
		foreach ($rows as $row) {
			if ($useKeys == true) {
				$array[$row[$key_field]] = $row[$value_field];
			}
			else {
				array_push($array, $row[$value_field]);
			}
		}
	}
	
	return $array;
}

function group_rows_by_primary_key($rows, $key_field) {
	if (!is_array($rows)) {
		trigger_error(__FUNCTION__ . '() 1st argument needs to be an array');	
	}
	
	$array = array();
	foreach ($rows as $row){
		$array[$row[$key_field]] = $row;
	}
	
	return $array;
}

function get_request_methods() {
	$request_methods = array(
		'json' => 'REST'
		, 'jsonp' => 'REST'
		, 'xml' => 'SOAP'
		, 'php' => 'SOAP'
	);
	
	return $request_methods;
	
}

function log_error($msg, $error_type) {
	
	$log_files = array(
		'user'      => 'error_log_user',
		'database'  => 'error_log_database',
		'system'    => 'error_log_system',
        'shipping'  => 'shipping_log',
	);
	
	if (empty($log_files[$error_type])) {
		trigger_error('Invalid error type', E_USER_NOTICE);
	}
	
	$user_info = "DATE: " . date("Y-m-d H:i:s");
	$user_info .= "\nSERVER_NAME: " . $_SERVER['SERVER_NAME'];
	$user_info .= "\nREQUEST_URI: " . $_SERVER['REQUEST_URI'];
	$user_info .= "\nSCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'];
	$user_info .= "\nREMOTE_ADDR: " . $_SERVER['REMOTE_ADDR'];
	$user_info .= "\nBROWSER: " . (!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
	
	// Append Error To Log File
	error_log($user_info . "\nMESSAGE: " . $msg . "\n\n", 3, LOG_DIR . '/' . $log_files[$error_type]);
	
	// Send Error To Email Address
	if ($error_type != 'user') {
		// disable for now
		//error_log($user_info . " MESSAGE:" . $msg . "\n\n", 1, ERROR_NOTIFICATION_EMAIL, 'From: ' . ERROR_NOTIFICATION_EMAIL);
	}
}

function error_handler($errno, $errmsg, $filename, $linenum, $vars) {
	switch ($errno) {
		case E_ERROR:
		case E_WARNING:
		case E_PARSE:
		case E_NOTICE:
		case E_CORE_ERROR:
		case E_CORE_WARNING:
		case E_COMPILE_ERROR:
		case E_COMPILE_WARNING:
		case E_STRICT:
		case E_RECOVERABLE_ERROR:
			log_error($filename . ' ' . $linenum . ' ' . $errmsg, 'system');
			break;
		
		case E_USER_ERROR:
			log_error($filename . ' ' . $linenum . ' ' . $errmsg, 'database');
			break;
		
		case E_USER_WARNING:
		case E_USER_NOTICE:
			log_error($filename . ' ' . $linenum . ' ' . $errmsg, 'user');
			break;
	}
	
	return true; 
}

function get_row_key_values($rows, $key) {
	$values = array();
	foreach ($rows as $row) {
		array_push($values, $row[$key]);
	}
	return $values;
}


function array_filter_by_key_value($items, $key, $value) {
	$filtered = array();
	
	if (!empty($items)) {
		foreach ($items as $item) {
			if (isset($item[$key]) && $item[$key] == $value) {
				array_push($filtered, $item);
			}
		}
	}
	
	return $filtered;
}

class Row_Value_Comparison_Closure {
	private static $key;
	
	static function sort(&$array, $key) {
		self::$key = $key;
		echo 'sorting' . self::$key;
		usort($array, array('Row_Value_Comparison_Closure', 'compare'));
	}
	
	private static function compare($a, $b) {
		if ($a[self::$key] == $b[self::$key]) {
			return 0;
		}
		
		return ($a[self::$key] < $b[self::$key]) ? -1 : 1;
		//return strcmp($a[self::$key], $b[self::$key]);
	}
}

function email($from, $fromEmail, $to, $toEmail, $subject, $htmlBody, $plainBody = '', $ccEmail = '', $bccEmail = '', $replyToEmail = '', $attachments = array()){
	
	$mail = new PHPMailer(true); // the true param means it will throw exceptions on errors, which we need to catch
	
	$mail->IsSendmail(); // telling the class to use SendMail transport
	
	try {
		
		// Set To
		$mail->AddAddress($toEmail, $to);
		
		// Set From
		$mail->SetFrom($fromEmail, $from);
		
		// Set CC
		$mail->ClearCCs();
		if ($ccEmail != '') {
			$mail->AddCC($ccEmail);
		}
		
		// Set Bcc
		$mail->ClearBCCs();
		if ($bccEmail != '') {
			$mail->AddBCC($bccEmail);
		}
		
		// Set Reply To
		if ($replyToEmail != '') {
			$mail->ClearReplyTos();
			$mail->AddReplyTo($replyToEmail);
		}
		
		// Set Subject
		$mail->Subject = $subject;
		
		// Set Text Body
		$mail->AltBody = $plainBody != '' ? $plainBody : '';// : 'To view the message, please use an HTML compatible email viewer.'; // optional - MsgHTML will create an alternate automatically
		
		// Set HTML Body
		$mail->MsgHTML($htmlBody);
		
		// Set Attachments
		if (!empty($attachments)) {
			foreach ($attachments as $attachment) {
				if (!is_file($attachment['path'])) {
					continue;
				}
				
				if (isset($attachment['type']) && $attachment['type'] != '') {
					$mail->AddAttachment($attachment['path'], $attachment['filename'], 'base64', $attachment['type']);
				}
				else {
					$mail->AddAttachment($attachment['path'], $attachment['filename']);
				}
			}
		}
		
		// Send Email
		$mail->Send();
		
		//echo "Message Sent OK</p>\n";
		return array('sent' => true, 'error' => NULL);
		//return true;
	} 
	catch (phpmailerException $e) {
		$errorMsg = $e->errorMessage();
	  	return array('sent' => false, 'error' => $errorMsg);
	  	//return false;
		//echo $e->errorMessage(); //Pretty error messages from PHPMailer
	
	} 
	catch (Exception $e) {
		$errorMsg = $e->getMessage();
		return array('sent' => false, 'error' => $errorMsg);
	  	//return false;
		//echo $e->getMessage(); //Boring error messages from anything else!
	
	}
}

function getEmailBody($name, $type, $variables){
	$_emailast_name = $name;
	$_variables = $variables;
	ob_start();
	if (!defined('RD')) {
		define('RD', DR);
	}
	require RD . '/emails/' . $type . '.php';
	$body = ob_get_contents();
	ob_end_clean();
	return $body;
}

function getSecretKey() {
	/*
	if(defined('MYSQLPASS')){
		return MYSQLPASS;
	}
	if(defined('SITENAME')){
		return SITENAME;
	}
	*/
	return 'turtles-are-green';
}
?>