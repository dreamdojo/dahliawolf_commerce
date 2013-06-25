<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
$g = new Geography();
$result = $g->dropDownUSStateList();
echo "the result for dropdownUSStateList is: $result <br />";
//dropDownCountryList
$result = $g->dropDownCountryList();
echo "the result for dropdownCountryList is: $result <br />";
?>
<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>