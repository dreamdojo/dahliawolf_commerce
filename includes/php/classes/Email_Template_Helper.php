<?
/**
    * Sends email template
    *
    * @access public
	* @param string $emailTemplateName - name of email template (file must exist)
	* @param array $customVariables - variables to be plugged into custom email template
	* @param array $templateVariables - variables to be plugged into main email template
	* @param string $from - name of sender
	* @param string $fromEmail - email address of sender
	* @param string $to - name of recipient
	* @param string $toEmail - email address of recipient
	* @param string $subject
	* @param string $replyToEmail
	* @param string $ccEmail
	* @param string $bccEmail
	* @param array $attachments - associative array of attachments
	* @return array - associative array of results
    
	
Usage:
// Send Email
$customVariables = array(
	'userid' => $userid
	, 'username' => $userName
	, 'first_name' => $firstName
	, 'last_name' => $lastName
	, 'promo_amount' => $promoAmount
	, 'code' => $code
	, 'term_in_days' => $termInDays
	, 'required_bonus_points' => $requiredBonusPoints
);

$templateVariables = array(
	'first_name' => $email
	, 'email' => $email
);

// Send Email
$emailTemplateHelper = new EmailTemplateHelper();
$emailResults = $emailTemplateHelper->sendEmail('promo-certificate', $customVariables, $templateVariables, $from, $fromEmail, $to, $toEmail, $subject, $replyToEmail);
*/
class Email_Template_Helper {
	public function sendEmail($emailTemplateName, $customVariables, $templateVariables, $from, $fromEmail, $to, $toEmail, $subject, $replyToEmail = '', $ccEmail = '', $bccEmail = '', $attachments = array()) {
		
		$customVariables = array_merge(
			$customVariables
			, array(
				'domain' => $templateVariables['domain']
				, 'site_name' => $templateVariables['site_name']
			)
		);
		
		$htmlBody = getEmailBody($emailTemplateName, 'html', $customVariables);
		$textBody = getEmailBody($emailTemplateName, 'text', $customVariables);
		
		$secretKey = getSecretKey();
		$templateVariables = array_merge(
			$templateVariables
			, array(
				'text_body' => $textBody
				, 'html_body' => $htmlBody
				, 'domain' => $templateVariables['domain']
				, 'site_name' => $templateVariables['site_name']
				, 'key' => md5($templateVariables['email'] . $secretKey)
				, 'cdn_domain' => $templateVariables['cdn_domain']
			)
		);
		
		$htmlEmail = getEmailBody('email-template', 'html', $templateVariables);
		$textEmail = getEmailBody('email-template', 'text', $templateVariables);
		
		$emailResults = email($from, $fromEmail, $to, $toEmail, $subject, $htmlEmail, $textEmail, $ccEmail, $bccEmail, $replyToEmail, $attachments);
		
		return $emailResults;
	}
}

?>