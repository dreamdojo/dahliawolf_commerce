<?
if (!defined('RD')) {
	define('RD', DR);
}

$emailFile = !empty($_emailast_name) ? RD . '/emails/text/' . $_emailast_name . '.php' : $_emailFile;
if (file_exists($emailFile)) {
	// If previewing, show monospaced font
	if (defined('CUSTOMTEMPLATE')) {
		?><pre style="font-family: 'Courier New', Courier, monospace;"><?
	}
	require $emailFile;
}
else {
	trigger_error('Invalid email file: ' . $emailFile, E_USER_ERROR);
}
?>