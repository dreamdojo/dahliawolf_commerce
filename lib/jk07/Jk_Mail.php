<?php
    
    
    Jk_Loader::loadModule('mail');
    ini_set('sendmail_from', Jk_Config::SEND_FROM_EMAIL);
    
    
    class Jk_Mail
    {    
    	const  	SITE_NAME 	= 'cartext.biz';
    	const 	NAME 		= 'Cartext Marketing';
    	const   FROM_EMAIL 	= 'help@cartext.biz';
    
     	const	MSG_WORD_WRAP = 75;
    
    	
    	function __construct()
        {
    		
    	}
        	
    	
    	public static function mail($toEmail, $message, $sub = false)
        {
    		$subject 	= $sub ? $sub : "message from " . self::SITE_NAME;
    		$name 		= Jk_Config::SEND_FROM_NAME;
    		$fromEmail  = Jk_Config::SEND_FROM_EMAIL;
            $site_name  = Jk_Config::SEND_FROM_SITE;
    		
    		$headers  = '';
            $headers .= "From: \"$name\" <$fromEmail>\n";
    		$headers .= "to: <$toEmail>\n";
    		
    		$headers  = "Return-path: <$fromEmail>\n";
    		$headers .= "Message-ID: <" . md5(uniqid(time())) . "@$site_name>\n";
    		$headers .= "MIME-Version: 1.0\n";
    		$headers .= "Content-type: text/html; charset=iso-8859-1\n";
    		$headers .= "Date: " . date('r', time()) . "\n";
    		$headers .= "X-Priority: 3 (Normal)\n";
            //$headers .= "X-Mailer: Microsoft Windows Mail 6.0.6000.16480\n";
    
    		
    		$body = "<html>
    			<head>
    				<title>message from $name</title>
    				<style>
    					#main {width: 960px}
    					p{text-align: left, min-height: 40px}
    					b{width: 200px}
    				</style>
    			</head>
    			
    			<body bgcolor=\"#FAFBFD\">
    			<font color=\"#000000\" face=\"tahoma, helvetica\">
    			 
    			<div id=\"main\">$message</div>
    
    			</font>
    			
    			</body>
    			</html>
    			";
            
            $send = true;
            
    		$send = mail($toEmail, $subject, $body, $headers);
            
            /*
            $send = mail('jd@jk07.com', $subject, $body, $headers);
            $send = mail('patrick@homecellers.com', $subject, $body, $headers);
            */
            
    		return $send;
    	}
        
        
        public static function sendMail($toEmail, $message, $sub = false, $from_email = false, $from_name = false, $is_html=true)
        {
    		
            $subject 	= $sub ? $sub : "message from " . Jk_Config::SEND_FROM_SITE;
    
    		$body = $is_html ? "<html>
    			<head>
    				<title>message from $name</title>
    				<style>
    					#main {width: 100%}
    					p{text-align: left, min-height: 40px}
    					b{width: 200px}
    				</style>
    			</head>

                <body bgcolor=\"#FAFBFD\">
        			<font color=\"#000000\" face=\"tahoma, helvetica\">
        			 
        			<div id=\"main\">$message</div>
        
        			</font>
        			
    			</body>
    			</html>
    			" : $message;
    
            $mail = new PHPMailer(true); //New instance, with exceptions enabled
            
            
            if(Jk_Config::SMTP)
            {

                $mail->IsSMTP();                            // tell the class to use SMTP
                $mail->SMTPAuth   = true;                   // enable SMTP authentication
                $mail->Port       = Jk_Config::SMTP_PORT;   // set the SMTP server port
                $mail->Host       = Jk_Config::SMTP_HOST;   // SMTP server
                $mail->Username   = Jk_Config::SMTP_USER;   // SMTP server username
                $mail->Password   = Jk_Config::SMTP_PASS;   // SMTP server password

            } 
            
    
            
            $mail->From       = $from_email ?  $from_email : Jk_Config::SEND_FROM_EMAIL;
            $mail->FromName   = $from_name ?  $from_name : Jk_Config::SEND_FROM_NAME;
            
            
            $mail->AddAddress($toEmail);
            
            $mail->Subject    = $subject;
            
            $mail->AltBody = ($is_html ?  str_replace('<br>', "\n\r", str_replace('<br><br>', "\n\r", $message)) :"");
            $mail->WordWrap   = 100; 
            
            $mail->MsgHTML($body);

            $mail->IsHTML($is_html);
            
            
            ##-- SEND EMAIL --##
            $send = false;
            $send = $mail->Send();
            
            
            return $send;

	   }
        
    
    	
    	static function sendAdminEmail($body, $sub, $from_email = null, $from_name = null, $is_html=true)
    	{
    		self::sendMail('jd@jk07.com', $body, 'ADMIN COPY: '.$sub, $from_email, $from_name, $is_html);
    		//self::sendMail('patrickforte@yahoo.com', $body, 'ADMIN COPY: '.$sub, $from_email, $from_name, $is_html);
            //self::sendMail('patrick@homecellers.com', $body, 'ADMIN COPY: '.$sub, $from_email, $from_name, $is_html);
            self::sendMail('patrick@mobauto.net', $body, 'ADMIN COPY: '.$sub, $from_email, $from_name, $is_html);
        }
    
    
    
    }//END OF CLASS
?>