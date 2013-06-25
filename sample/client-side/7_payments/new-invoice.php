<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/flexutil/basepage.php';
$wp = new BasePage();
$wp->renderHeader();
?>
<!--we are closing the php tags to use some standard html.-->
<h1>Add Collections</h1>
<?php
$i = new Invoice();
//begin by adding a new invoice, it will return the invoice number  and you can add items.
$userId = 1;//normally this would be pulled from the session, here we are setting it manually.
$name = "Customer Invoice";
$description = "Invoice from my business";
$amount = 99.95;
$iNo = $i->newInvoice($userId, $name, $description, $amount);
echo "The invoice no is: $iNo <br />";
//once you have the invoice number you can add a single item by calling the following function
$itemId = 157;
$itemType = "an item";
$itemName = "my item";
$itemDescription = "an item that is collected";
$iId = 0;
$iId = $i->addItem($iNo, $itemId, $itemType, $itemName, $itemDescription);
echo "The item id is: $iId";//
//to add multiple items first build an array with item_id, item_type, item_name and item_description
$itemArray = array();
$itemArray[] = array("item_id"=>"10", "item_type" => "array item", "item_name" => "my array item", "item_description" => "an array item that is collected");
$itemArray[] = array("item_id"=>"11", "item_type" => "array item", "item_name" => "my array item", "item_description" =>  "an array item that is collected");
$itemArray[] = array("item_id"=>"12", "item_type" => "array item", "item_name" => "my array item", "item_description" =>  "an array item that is collected");
$itemArray[] = array("item_id"=>"13", "item_type" => "array item", "item_name" => "my array item", "item_description" =>  "an array item that is collected");
$itemArray[] = array("item_id"=>"14", "item_type" => "array item", "item_name" => "my array item", "item_description" =>  "an array item that is collected");
//pass in the invoice number and an item array to the following function
$iIds = $i->addItems($iNo, $itemArray);
// the function will return the item Ids in an array
echo "<pre>";
print_r($iIds);
echo "</pre>";
?>
<!--We must open the php tags to finish rendering the page.-->
<?php
$wp->renderFooter();
?>