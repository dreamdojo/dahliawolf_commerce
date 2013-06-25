<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Web Page Tutorials</h1>
<?php
$a = array(
    "name" => "subscription name",
    "intervalLength" => "1",
    "intervalUnit" => "months",//days or months
    "startDate" => "2012-04-30",
    "totalOccurrences" => "9999",
    "trialOccurrences" => "0",
    "amount" => "129.95",
    "trialAmount" => "0",
    "creditCardCardNumber" => "4007000000027",
    "creditCardExpirationDate" => "01/15",
    "creditCardCardCode" => "355",
//    "bankAccountAccountType" => "",
//    "bankAccountRoutingNumber" => "",
//    "bankAccountAccountNumber" => "",
//    "bankAccountNameOnAccount" => "",
//    "bankAccountEcheckType" => "WEB",
//    "bankAccountBankName" => "",
    "orderInvoiceNumber" => "",
    "orderDescription" => "",
    "customerId" => "",
    "customerEmail" => "",
    "customerPhoneNumber" => "",
    "customerFaxNumber" => "",
    "billToFirstName" => "John",
    "billToLastName" => "Doe",
    "billToCompany" => "",
    "billToAddress" => "123 Main St.",
    "billToCity" => "Los Angeles",
    "billToState" => "CA",
    "billToZip" => "90025",
    "billToCountry" => "",
    "shipToFirstName" => "",
    "shipToLastName" => "",
    "shipToCompany" => "",
    "shipToAddress" => "",
    "shipToCity" => "",
    "shipToState" => "",
    "shipToZip" => "",
    "shipToCountry" => ""
    );
    
    echo "<pre>";
    $s = new AuthorizeNetARB("www.legalteller.com","", true);
    $r = $s->createSubscription($a);
    print_r($r);

?>
<!--We must open the php tags to finish rendering the page.-->

<?php
$wp->renderFooter();
?>
