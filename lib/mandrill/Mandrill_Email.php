<?php
/**
 * User: JDorado
 * Date: 11/19/13
 */
 
class Mandrill_Email {

    protected $global_merge_vars = null;
    protected $email_type = 'transactional';
    protected static $api_key = "5U1DlZOtZMM6LbKDdse-JA";


    function setMergeVars($vars_arr)
    {
        if(is_array( $vars_arr)) $this->global_merge_vars = $vars_arr;

    }

    function setEmailType($email_type)
    {
        if($email_type) $this->email_type = $email_type;

    }

    public function send($from, $fromEmail, $to, $toEmail, $subject, $htmlBody, $plainBody = '', $send_at=null)
    {
        $logger = new Jk_Logger(APP_PATH . 'logs/mandrill_mail.log');

        $send_at = strtotime($send_at) > 0 ? $send_at :  null;

        try {
            $mandrill = new Mandrill(self::$api_key);
            $message = array(
                'html' => $htmlBody,
                'text' => $plainBody,
                'subject' => $subject,
                'from_email' => $fromEmail,
                'from_name' => $from,
                'to' => array(
                    array(
                        'email' => $toEmail,
                        'name' => $to,
                        'type' => 'to'
                    )
                ),
                'headers' => array('Reply-To' => $fromEmail),
                'important' => false,
                'track_opens' => null,
                'track_clicks' => null,
                'auto_text' => null,
                'auto_html' => null,
                'inline_css' => null,
                'url_strip_qs' => null,
                'preserve_recipients' => null,
                'view_content_link' => null,
                //'bcc_address' => 'message.bcc_address@example.com',
                'tracking_domain' => null,
                'signing_domain' => null,
                'return_path_domain' => null,
                'merge' => true,
                'global_merge_vars' => array(
                    $this->global_merge_vars
                ),
                'merge_vars' => array(
                    array(
                        'rcpt' => $toEmail,
                        'vars' => array(
                            $this->global_merge_vars
                        )
                    )
                ),
                'tags' => array(($this->email_type?$this->email_type:null)),
                'metadata' => array('website' => 'www.dahliawolf.com'),
            );
            $async = false;
            $ip_pool = 'Main Pool';
            $result = $mandrill->messages->send($message, $async, $ip_pool, $send_at);
            //print_r($result);


            if($result) $logger->LogInfo("mail sent.. for delivery at: [UTC: $send_at]\nmessage: " . json_pretty($message));
            if($result) $logger->LogInfo("mandrill result: " . json_pretty($result) );

        } catch(Mandrill_Error $e) {
            // Mandrill errors are thrown as exceptions
            //echo 'A mandrill error occurred: ' . get_class($e) . ' - ' . $e->getMessage();
            $logger->LogInfo("MANDRILL ERROR.. message: " . $e->getMessage() );
            // A mandrill error occurred: Mandrill_Unknown_Subaccount - No subaccount exists with the id 'customer-123'
            //throw $e;
        }

    }



}

?> 