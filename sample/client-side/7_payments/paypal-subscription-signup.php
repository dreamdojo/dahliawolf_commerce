<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
//we are overiding the return uri in config.php since subscription requires different logic on confirmation.
    $returnUri = "http://sample.companycheckout.com/tutorials/7_payments/paypal-subscription-complete.php";
    $cancelUri = "http://sample.companycheckout.com/tutorials/7_payments/paypal-subscription-cancel.php";
    $paymentArray = array("amount" => "49.95", "currency" => "USD", "description"=>"an item subscribed to on PayPal",
        "start_date"=>"2012-04-15T00:00:00", "billing_period"=>"Day", "billing_frequency"=>"30","item_category"=>"digital");    
    $p = new PayPalExpressCheckout();
    $p->beginSubscription($paymentArray, $returnUri, $cancelUri);
?>