<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
Enter the transaction_id of an unsettled payment
<form action="" method="post">
 Transaction Id: <input type="text" name="transaction_id" />
 <input type="submit" value="Void Transaction" />
 </form> 

<?php
//check if post data is set
if (isset ($_POST["transaction_id"]))
    {
    //call function
    $auth = new AuthorizeNetAIM();
    $result = $auth->voidTransaction($_POST["transaction_id"]);
    //view results
    echo "Results:";
    echo "<pre>";
    print_r($result);
    echo "</pre>";    
    }
?>

<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>
