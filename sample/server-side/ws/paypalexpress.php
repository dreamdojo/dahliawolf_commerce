<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/classes/payments/paypal/expresscheckout.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_header.php';
$server->register('beginPurchase');
function beginPurchase($apikey, $host, $developerMode, $username, $password, $signature, $paymentArray, $returnUrl, $cancelUrl, $logDb = "", $logDbIsHosted = true, $sandboxEnvironment = "sandbox")
    {
    $useSandbox = false;
    if ($developerMode == "true")
        {$useSandbox = true;}
    if ($developerMode == "false")
        {$useSandbox = false;}
    $p = new PayPalExpressCheckout($apikey, $useSandbox, $username, $password, $signature, $logDb, $logDbIsHosted, $sandboxEnvironment);
    $redirectUri = $p->beginPurchase($paymentArray, $returnUrl, $cancelUrl);
    return $redirectUri;
    }
$server->register('completePurchase');
function completePurchase($apikey, $host, $developerMode, $username, $password, $signature, $getData, $logDb = "", $logDbIsHosted = true, $sandboxEnvironment = "sandbox")
    {
    $useSandbox = false;
    if ($developerMode == "true")
        {$useSandbox = true;}
    if ($developerMode == "false")
        {$useSandbox = false;}
    $p = new PayPalExpressCheckout($apikey, $useSandbox, $username, $password, $signature, $logDb, $logDbIsHosted, $sandboxEnvironment);
    $responseArray = $p->completePurchase($getData);
    return $responseArray;
    }
$server->register('beginSubscription');
function beginSubscription($apikey, $host, $developerMode, $username, $password, $signature, $paymentArray, $returnUrl, $cancelUrl, $logDb = "", $logDbIsHosted = true, $sandboxEnvironment = "sandbox")
    {
    $useSandbox = false;
    if ($developerMode == "true")
        {$useSandbox = true;}
    if ($developerMode == "false")
        {$useSandbox = false;}
    $p = new PayPalExpressCheckout($apikey, $useSandbox, $username, $password, $signature, $logDb, $logDbIsHosted, $sandboxEnvironment);
    $redirectUri = $p->beginSubscription($paymentArray, $returnUrl, $cancelUrl);
    return $redirectUri;
    }
$server->register('completeSubscription');
function completeSubscription($apikey, $host, $developerMode, $username, $password, $signature, $getData, $logDb = "", $logDbIsHosted = true, $sandboxEnvironment = "sandbox")
    {
    $useSandbox = false;
    if ($developerMode == "true")
        {$useSandbox = true;}
    if ($developerMode == "false")
        {$useSandbox = false;}
    $p = new PayPalExpressCheckout($apikey, $useSandbox, $username, $password, $signature, $logDb, $logDbIsHosted, $sandboxEnvironment);
    $responseArray = $p->completeSubscription($getData);
    return $responseArray;
    }
$server->register('issueRefund');
function issueRefund($apikey, $host, $developerMode, $username, $password, $signature, $refundArray, $logDb = "", $logDbIsHosted = true, $sandboxEnvironment = "sandbox")
    {
    $p = new PayPalExpressCheckout($apikey, $useSandbox, $username, $password, $signature, $logDb, $logDbIsHosted, $sandboxEnvironment);
    $responseArray = $p->issueRefund($refundArray);
    return $responseArray;
    }
$server->register('cancelSubscription');
function cancelSubscription($apikey, $useSandbox, $username, $password, $signature, $logDb, $logDbIsHosted, $sandboxEnvironment, $profileId, $note)
    {
    $p = new PayPalExpressCheckout($apikey, $useSandbox, $username, $password, $signature, $logDb, $logDbIsHosted, $sandboxEnvironment);
    $response = $p->manageProfile($profileId, "Cancel", $note);
    return $response;
    }
$server->register('subscriptionStatus');
function subscriptionStatus($apikey, $host, $developerMode, $username, $password, $signature, $profileId, $logDb = "", $logDbIsHosted = true, $sandboxEnvironment = "sandbox")
    {
    $p = new PayPalExpressCheckout($apikey, $useSandbox, $username, $password, $signature, $logDb, $logDbIsHosted, $sandboxEnvironment);
    $response = $p->getProfileStatus($profileId);
    return $response;
    }
require_once $_SERVER['DOCUMENT_ROOT'] . '/ws/ws_footer.php';
?>