<?
function cookie($name, $val, $time) {
	return setcookie($name, $val, time() + $time, '/', $_SERVER['SERVER_NAME']);
}

function encrypt($data) {
	$key = getSecretKey();
	
	return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $data, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND));
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

//no need to add stuff shorter than 32 length into ignoreList
function decrypt($encryptions, $ignoreList = array()){
	$key = getSecretKey();
	
		
	if(!is_array($encryptions)){
		$encryptions = array($encryptions);
		$wasArray = false;
	}
	else{
		$wasArray = true;
	}
	foreach($encryptions as $i => $encryption){
		if(in_array($i, $ignoreList) || !isset($encryption[31])){ //skip ignore list or string shorter than 31
			continue;
		}
		$encryptions[$i] = trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), $encryption, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
	}
	
	return $wasArray ?  $encryptions : $encryptions[0];
}

function truncateNum ($number, $decimals = 2) {
	return round(floor($number * 100) / 100, $decimals);	
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

?>