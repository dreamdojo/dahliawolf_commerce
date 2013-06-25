<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
$g = new Geography();
//getDistance
$zip1 = 90017;
$zip2 = 91701;
$result = $g->getDistance($zip1, $zip2);
echo "the distance between $zip1 and $zip2 is $result <br />";
?>
<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>