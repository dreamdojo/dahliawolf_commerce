<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
$paymentArray = array
    ("first_name"=> "george",
    "last_name"=> "smith",
    "address"=> "123 fett rd",
    "city"=> "los angeles",
    "state"=> "ca",
    "zip"=> "90017",
    "phone"=> "2139555522",
    "email"=> "hola@hotmail.com",
    "card_num"=> "4007000000027",
    "card_code"=>"355",
    "exp_date"=> "01/15",
    "amount"=> "28.95",
    "description" => "an item for sale"    
    );
//call function
$i = new Invoice();
$invoiceNumber = 1;//this would come from db or session
$result = $i->payWithAuthNet($invoiceNumber, $paymentArray);
//view results
echo "Results:<br />";//you will either get a payment confirmation code or the word failed
echo $result;?>
<!--We must open the php tags to finish rendering the page.-->
<?php
$wp->renderFooter();
?>