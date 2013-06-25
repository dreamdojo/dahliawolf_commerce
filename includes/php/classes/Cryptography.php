<?php
class Cryptography {
	private $AES_key = 'bEFqVnRoMWFvSTR6QmxTNlkzaE4';
	private $iv = 'xEtgZUxWOfAwxNNUml4XFzJrbEEgtLtaCoBsaxqYZvs';
	private static $Exception_Helper;
	
	public function __construct() { 
		self::$Exception_Helper = new Exception_Helper();
	}
	
	public function get_random_string($len = NULL, $chars = NULL) {
		if (is_null($len)) {
			$len = 10;
		}
		
		if (is_null($chars)) {
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		}
		
		// Seed the better random number generator
		mt_srand(10000000 * (double)microtime());
		
		for ($i = 0, $str = '', $lc = strlen($chars) - 1; $i < $len; $i++) {
			$str .= $chars[mt_rand(0, $lc)];
		}
		
		return $str;
	}
	
	public function &rot13($str) {
		if (!function_exists('str_rot13')) {
			$from = 'ahijklCDEFGHIbcdefgJKLMmnopqrstuvPQRwxyzABNOSTUVWXYZ';
			$to = 'nouvwxyzhijklmNOYZABCPQabcdefgRSTUVpqrstWXDEFGHIJKLM';
			$rot13_str = strtr($str, $from, $to);
		}
		else {
			$rot13_str = str_rot13($str);
		}
		
		return $rot13_str;
	}
	
	public function urlsafe_b64encode($string) {
		$data = base64_encode($string);
		$data = str_replace(array('+', '/', '='), array('-', '_', ','), $data);
		
		return $data;
	}
	
	public function urlsafe_b64decode($encoded_string) {
		$data = str_replace(array('-', '_', ','), array('+', '/', '='), $encoded_string);
		$mod4 = strlen($data) % 4;
		if ($mod4) {
			$data .= substr('====', $mod4);
		}
		
		if (base64_decode($data, true)) {
			return base64_decode($data);
		}
		else {
			self::$Exception_Helper->bad_request_exception('String was not a valid base64 encoded string.');
		}
	}
	
	public function get_hash($password, $salt = NULL, $use_random_salt = false) {
		if ($use_random_salt) {
			$salt = $this->get_random_string($salt);
		}
		
		return $salt === NULL ? md5($password) : md5($salt . $password) . ':' . $salt;
	}
	
	private function add_padding($string, $blocksize = 32) {
		$len = strlen($string);
		$pad = $blocksize - ($len % $blocksize);
		$string .= str_repeat(chr($pad), $pad);
		return $string;
	}
	
	private function strip_padding($string) {
		$slast = ord(substr($string, -1));
		$slastc = chr($slast);
		$pcheck = substr($string, -$slast);
		if (preg_match("/$slastc{" . $slast . "}/", $string)){
			$string = substr($string, 0, strlen($string) - $slast);
			return $string;
		}
		else {
			return false;
		}
	}
	
	public function AES_encrypt($string = '') {
		return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $this->AES_key, $this->add_padding($string), MCRYPT_MODE_CBC, base64_decode($this->iv)));
	}
	
	public function AES_decrypt($string = '') {
		if (base64_decode($string, true) === false) {
			self::$Exception_Helper->bad_request_exception('String was not a valid base64 encoded string.');
		}
		
		return $this->strip_padding(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $this->AES_key, base64_decode($string), MCRYPT_MODE_CBC, base64_decode($this->iv)));
	}
}
?>