<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
//build payment array
$paymentArray = array
    ("first_name"=> "buba",
    "last_name"=> "fett",
    "address"=> "123 fett rd",
    "city"=> "los angeles",
    "state"=> "california",
    "zip"=> "90017",
    "phone"=> "2139555522",
    "email"=> "hola@hotmail.com",
    'bank_aba_code' => "121042882",
    'bank_acct_num' => "123456789",
    'bank_acct_type' => "checking",
    'bank_name' => "Wells Fargo",
    'bank_acct_name' => "my checking",
    "amount"=> "9.99",
    "description" => "an item for sale"
     );
//call function
$auth = new AuthorizeNetAIM();
$result = $auth->authorizeCapture($paymentArray);
//view results
echo "Results:";
echo "<pre>";
print_r($result);
echo "</pre>";
?>
<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>