<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
    $p = new PayPalExpressCheckout();
    $response = $p->cancelSubscription("I-XU8P191K85JJ",  "testing a cancelled subscription");
    echo "<pre>";
    print_r($response);
    echo "</pre>" ;
?>
<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>
