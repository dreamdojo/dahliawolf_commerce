<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
$paymentArray = array
    ("first_name"=> "buba",
    "last_name"=> "fett",
    "address"=> "123 fett rd",
    "city"=> "los angeles",
    "state"=> "ca",
    "zip"=> "90017",
    "phone"=> "2139555522",
    "email"=> "hola@hotmail.com",
    "card_num"=> "4007000000027",
    "card_code"=>"355",
    "exp_date"=> "01/15",
    "amount"=> "99.79",
    "description" => "an item for sale"    
    );
//call function
$auth = new AuthorizeNetAIM();
$result = $auth->authorizeCapture($paymentArray);
//view results
echo "Results:";
echo "<pre>";
print_r($result);
echo "</pre>";?>
<!--We must open the php tags to finish rendering the page.-->
<?php
$wp->renderFooter();
?>