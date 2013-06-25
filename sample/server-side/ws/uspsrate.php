<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/shipping/usps/rate.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_header.php';
$server->register('testCall1');
function testCall1($apikey, $host, $username)
    {
    $r = new UspsRate($username);
    return $r->testCall1();
    }
$server->register('testCall2');
function testCall2($apikey, $host, $username)
    {
    $r = new UspsRate($username);
    return $r->testCall2();
    }
$server->register('testCall3');
function testCall3($apikey, $host, $username)
    {
    $r = new UspsRate($username);
    return $r->testCall3();
    }
$server->register('getRate');
function getRate($apikey, $host, $username, $service, $firstClassMailType, $sendFromZip, $sendToZip, $pounds, $ounces, $containerSize, $machinable = "true")
    {
    $r = new UspsRate($username);
    return $r->getRate($service, $firstClassMailType, $sendFromZip, $sendToZip, $pounds, $ounces, $containerSize, $machinable);
    }
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_footer.php';
?>