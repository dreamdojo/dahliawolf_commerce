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
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$service = '01';
$length = '5';
$width = '5';
$height = '5';
$weight = '5';
$shipToZip = '92744';

$r = new UpsRate();
echo "the shipping rate is: " . $r->getRate($shipToZip, $service, $weight, $length, $width, $height);
?>
<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>