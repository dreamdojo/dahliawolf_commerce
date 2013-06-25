<?
// Convert css selector to html markup string
// Input:	ul#nav.nav.nav-2
// Output:	array(
//				'tag' => 'ul'
//				, 'id' => 'nav'
//				, 'class' => 'nav nav-2'
//			)
function css_2_html($selector) {
	// class1/class2 since it can come before or after id
	preg_match('/(?P<tag>\w+)?(?P<class1>[.\w-]+)?(?P<id>#[\w-]+)?(?P<class2>[.\w-]+)?/', $selector, $matches);
	
	// Clean up matches return values
	return array(
		'tag' => !empty($matches['tag']) ? $matches['tag'] : ''
		// Remove #
		, 'id' => str_replace('#', '', (!empty($matches['id']) ? $matches['id'] : ''))
		// Remove leading . and replace remaining with spaces
		, 'class' => str_replace('.', ' ', ltrim((!empty($matches['class1']) ? $matches['class1'] : '') . (!empty($matches['class2']) ? $matches['class2'] : ''), '.'))
	);
}

function array_2_css($css_array) {
	$str = '';
	if (!empty($css_array)) {
		foreach ($css_array as $property => $value) {
			$str .= $property . ': ' . $value . ';';
		}
	}
	return $str;
}

function clean_string($string, $delimiter = '-', $replace_one_to_one = false) {
	// Character encoding
	setlocale(LC_ALL, 'en_US.UTF8');
	if (function_exists('iconv')) {
		$string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);
	}
	
	$string = strip_tags($string);
	// Reverse HTML special chars (charset provided for &trade;, &hellip;, etc...)
	$string = html_entity_decode($string, ENT_COMPAT, 'cp1251');
	
	// Keep case insensitive alphanumeric & special chars to be replaced with delimiter (rather than removing)
	$chars = '.\/_|+ -';
	$string = preg_replace("/[^a-z0-9" . $chars . "]/i", '', $string);
	
	// Convert to lowercase, replace (repeating) special chars and trim
	$string = strtolower($string);
	$regex = "[" . $chars . "]";
	if (!$replace_one_to_one) {
		$regex .= "+";
	}
	
	$string = preg_replace("/" . $regex . "/", $delimiter, $string);
	
	if (!$replace_one_to_one) {
		$string = trim($string, $delimiter);
	}

	return $string;
}

function unclean_string($string, $delimiters = array('-', '_')) {
	return ucwords(str_replace($delimiters, ' ', $string));
}

function strip_whitespace($str) {
	return trim(preg_replace('/\s\s+/', ' ', ($str)));
}

function nl2p($str) {
	if (strpos($str, '<') !== 0) {
		return '<p>' . preg_replace('/(<br \/>[\r\n]*)+/', "</p><p>", nl2br($str)) . '</p>';
	}

	return $str;
}

function strip_prefix($str, $prefix) {
	$str_len = strlen($str);
	$prefix_len = strlen($prefix);
	if (substr($str, 0, $prefix_len) == $prefix) {
		$str = substr($str, $prefix_len, $str_len);
	}
	return $str;
}
function strip_suffix($str, $suffix) {
	$str_len = strlen($str);
	$suffix_len = strlen($suffix);
	if (substr($str, -$suffix_len) == $suffix) {
		$str = substr($str, -$str_len, -$suffix_len);
	}
	return $str;
}

function round_up($value, $precision=0) {
	$power = pow(10,$precision);
	return ceil($value*$power)/$power;
}
function round_down($value, $precision=0) {
	$power = pow(10,$precision);
	return floor($value*$power)/$power;
}
?>