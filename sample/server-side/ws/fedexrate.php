<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/shipping/fedex/rate.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_header.php';
$server->register('getRate');
function getRate($apikey, $host, $accessKey, $password, $accountNumber, $meterNumber, $sendFromDetails, $sendToDetails, $service, $weight, $length, $width, $height, $useTestServer = false)
    {
    $r = new FedexRate($accessKey, $password, $accountNumber, $meterNumber, $useTestServer);
    return $r->getRate($sendFromDetails, $sendToDetails, $service, $weight, $length, $width, $height);
    }
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_footer.php';
?>