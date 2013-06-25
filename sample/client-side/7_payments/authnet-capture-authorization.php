<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
Enter the transaction_id of a previously authorized payment <br />
If amount is blank, exact authorized amount will be captured.
<form action="" method="post">
 Transaction Id: <input type="text" name="transaction_id" />
 Amount: <input type="text" name="amount" />
 <input type="submit" value="Capture Transaction" />
 </form> 
<?php
//check if post data is set
if (isset ($_POST["transaction_id"]))
    {
    $auth = new AuthorizeNetAIM();
    $result;
    //check if amount is set
    if (isset ($_POST["amount"]))
        {
        //call function
        $result = $auth->capturePriorAuthorization($_POST["transaction_id"], $_POST["amount"]);
        }
    else
        {
        //call function
        $result = $auth->capturePriorAuthorization($_POST["transaction_id"]);
        }
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