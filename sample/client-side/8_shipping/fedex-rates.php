<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
$r = new FedexRate();
//the ship from address is set in config.php
$r->addSendToAddress("601 S. Figueroa St.", "Los Angeles", "CA", "90017");
//These are the values accepted for service types by FedEx API
//FIRST_OVERNIGHT
//PRIORITY_OVERNIGHT
//STANDARD_OVERNIGHT
//FEDEX_2_DAY_AM
//FEDEX_2_DAY
//FEDEX_EXPRESS_SAVER
//FEDEX_GROUND 
echo "shipping rate is $" . $r->getRate("FEDEX_GROUND", 2, 5, 7, 4);
?>
<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>