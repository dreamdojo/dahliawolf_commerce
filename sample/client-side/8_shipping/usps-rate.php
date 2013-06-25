<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
$r = new UspsRate();
    echo "<p> your rate is: " . $r->getRate("FIRST CLASS", "LETTER", 90017, 10001, 0, 2, "REGULAR", true);
?>
<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>