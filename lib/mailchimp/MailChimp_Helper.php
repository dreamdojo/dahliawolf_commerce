<?php
/**
 * User: JDorado
 * Date: 11/21/13
 */
 
class MailChimp_Helper
{
    /** @var $logger Jk_Logger  */
    protected static $logger = null;
    protected static $api_key = "3476bd277ea267d6b3be6a3ec186b095-us1";

    protected static function setApiKey($api_key)
    {
        self::$api_key = $api_key;
    }


    protected static function trace($m)
    {
        if(!self::$logger) self::$logger = new Jk_Logger(APP_PATH.'logs/mailchimp.log');
        if(is_object($m) || is_array($m)) $m = json_pretty($m);
        self::$logger->LogInfo($m);
    }


    public static function addSubscriber($user_obj, $list_id = "800b6e2485")
    {
        $user_obj = (object) $user_obj;

        $mailchimp = new MailChimp( self::$api_key );
        $result = $mailchimp->call('lists/subscribe', array(
            'id'                => "$list_id",
            'email'             => array('email'=> $user_obj->email ),
            'merge_vars'        => array('FNAME'=> $user_obj->first_name , 'LNAME'=> $user_obj->last_name ),
            'double_optin'      => false,
            'update_existing'   => true,
            'replace_interests' => false,
            'send_welcome'      => true,
        ));

        self::trace( "mailchimp result, addSubscriber(): " . json_pretty($result) );

        return  $result;
    }

    public static function getLists()
    {
        $mailchimp_ = new MailChimp(self::$api_key);
        $result = $mailchimp_->call('lists/list');
        self::trace($result);

        return  $result;
    }




}

?> 