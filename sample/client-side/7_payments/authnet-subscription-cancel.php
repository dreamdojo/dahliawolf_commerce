<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
    echo "<pre>";
    $s = new AuthorizeNetARB("www.legalteller.com","", true);
    $r = $s->cancelSubscription("1330290");
    print_r($r);
    echo "</pre>";
?>
<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>
