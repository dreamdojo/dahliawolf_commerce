<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$p = new PayPalExpressCheckout();
echo"<pre>";
$response = $p->completePurchase($_GET);
print_r($response);
echo "</pre>";
?>