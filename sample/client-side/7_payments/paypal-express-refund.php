<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
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
<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>