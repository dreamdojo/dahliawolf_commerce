<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
$a = array(
    "amount" => "129.95",
    );
    $s = new AuthorizeNetARB("www.legalteller.com","", true);
    //ONLY THING THAT CAN BE CHANGED IS THE AMOUNT AND BILLING DATE
    $r = $s->updateSubscription("1330290", $a);
    echo "<pre>";
    print_r($r);
    echo "</pre>";
?>

<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>

?>
