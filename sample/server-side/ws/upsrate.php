<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/shipping/ups/rate.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_header.php';
$server->register('getRate');
function getRate($apikey, $host, $accessKey, $username, $password, $accountNumber, $shipFromZip, $shipToZip, $service, $weight, $length, $width, $height, $useTestServer = false)
    {
    $u = new UpsRate($accessKey, $username, $password, $accountNumber, $shipFromZip, $useTestServer);
    return $u->getRate($shipToZip, $service, $weight, $length, $width, $height);
    }
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_footer.php';
?>