<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$p = new PayPalExpressCheckout();
echo "<pre> hi <br>";
$response = $p->completeSubscription($_GET);
$c = new Cryptography();
$subscriptionInfo = $c->urlsafe_b64decode($_GET['si']);
echo $subscriptionInfo;
print_r($subscriptionInfo);
print_r($response);
?>
