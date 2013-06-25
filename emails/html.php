<?
if (!defined('RD')) {
	define('RD', DR);
}

// Html helper class
// Require once for mass emails
//require_once RD . '/includes/classes/email-html.php';
if (empty($_e)) {
	$_e = new EmailHtml();
}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title></title>
</head>
<body style="padding: 0; margin: 0;">
<?
$emailFile = !empty($_emailast_name) ? RD . '/emails/html/' . $_emailast_name . '.php' : $_emailFile;
if (file_exists($emailFile)) {
	require $emailFile;
}
else {
	trigger_error('Invalid email file: ' . $emailFile, E_USER_ERROR);
}
?>
</body>
</html>