<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/payments/authorizenet/arb.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_header.php';
$server->register('createSubscription');
function createSubscription($apikey, $host, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $useApiDb, $subscriptionArray)
    {
    $a = new AuthNetARB($apikey, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $useApiDb);
    $result = $a->createSubscription($subscriptionArray);
    return $result;
    }
$server->register('updateSubscription');
function updateSubscription($apikey, $host, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $useApiDb, $subscriptionId, $subscriptionArray)
    {
    $a = new AuthNetARB($apikey, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $useApiDb);
    $result = $a->updateSubscription($subscriptionId, $subscriptionArray);
    return $result;
    }
$server->register('subscriptionStatus');
function subscriptionStatus($apikey, $host, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $useApiDb, $subscriptionId, $referenceId)
    {
    $a = new AuthNetARB($apikey, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $useApiDb);
    $result = $a->getSubscriptionStatus($subscriptionId, $referenceId);
    return $result;
    }
$server->register('cancelSubscription');
function cancelSubscription($apikey, $host, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $useApiDb, $subscriptionId, $referenceId)
    {
    $a = new AuthNetARB($apikey, $authnetApiId, $authnetTransKey, $useSandbox, $userDomainName, $logDbName, $useApiDb);
    $result = $a->cancelSubscription($subscriptionId, $referenceId);
    return $result;
    }
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_footer.php';
?>