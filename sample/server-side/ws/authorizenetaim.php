<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/payments/authorizenet/aim.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_header.php';
$server->register('authorizeCapture');
function authorizeCapture($apikey, $host, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $logToApiDb, $paymentArray,  $paymentType = "credit card")
    {
    $a = new AuthNetAIM($apikey, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $logToApiDb);
    $result = $a->authorizeCapture($paymentArray, $paymentType = "credit card");
    return $result;
    }
$server->register('authorizeOnly');
function authorizeOnly($apikey, $host, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $logToApiDb, $paymentArray,  $paymentType = "credit card")
    {
    $a = new AuthNetAIM($apikey, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $logToApiDb);
    $result = $a->authorizeOnly($paymentArray, $paymentType = "credit card");
    return $result;
    }
$server->register('capturePriorAuthorization');
function capturePriorAuthorization($apikey, $host, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logToApiDb, $logDbIsHosted, $transactionId, $amount = null)
    {
    $a = new AuthNetAIM($apikey, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logToApiDb, $logDbIsHosted);
    $result = $a->capturePriorAuthorization($transactionId, $amount);
    return $result;
    }
$server->register('voidTransaction');
function voidTransaction($apikey, $host, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $logToApiDb, $transactionId)
    {
    $a = new AuthNetAIM($apikey, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $logToApiDb);
    $result = $a->voidTransaction($transactionId);
    return $result;
    }
$server->register('issueRefund');
function issueRefund($apikey, $host, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $logToApiDb, $transactionId, $amount, $ccNumber)
    {
    $a = new AuthNetAIM($apikey, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $logToApiDb);
    $result = $a->issueRefund($transactionId, $amount, $ccNumber);
    return $result;
    }
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_footer.php';
?>