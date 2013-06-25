<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$p = new PayPalExpressCheckout();
    $response = $p->subscriptionStatus("I-XU8P191K85JJ");
    echo "<pre>";
    print_r($response);
?>
