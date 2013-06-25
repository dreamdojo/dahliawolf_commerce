<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
Enter the following information to issue a credit card refund
<form action="" method="post">
Transaction Id: <input type="text" name="transaction_id" />
Amount: <input type="text" name="amount" />
Credit Card #: <input type="text" name="cc_num" value="4007000000027" />
 
 <input type="submit" value="Void Transaction" />
 </form> 

<?php
//check if post data is set
if (isset ($_POST["transaction_id"]))
    {
    //call function
    $auth = new AuthorizeNetAIM();
    $result = $auth->issueRefund($_POST["transaction_id"],$_POST["amount"],$_POST["cc_num"]);
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