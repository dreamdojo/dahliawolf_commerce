<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
    $paymentArray = array("amount" => "29.95", "currency" => "USD", "description"=>"an item sold on PayPal");    
    $p = new PayPalExpressCheckout();
    $p->beginPurchase($paymentArray);
?>