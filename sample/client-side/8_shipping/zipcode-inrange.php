<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
$g = new Geography();
$zip1 = 90017;
$result = $g->getZipsInRange($zip1, 10);
echo "the zips in range of $zip1 are <br /><pre>";
echo print_r($result) ."</pre>";
?>
<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>