<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$p = new PayPalExpressCheckout();
 $refundArray = array();
    $refundArray['transaction_id'] = "1HC0702286198651U";
    $refundArray['refund_type'] = "partial";
    $refundArray['currency'] = "US";
    $refundArray['amount'] = "14.95";
    $refundArray['note'] = "a partial refund";
   $stat = $p->issueRefund($refundArray);
   echo "<pre>";
   print_r($stat);
?>
