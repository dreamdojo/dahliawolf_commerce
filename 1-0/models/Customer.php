<?php

class Customer extends _Model {

	const TABLE = 'customer';
	const PRIMARY_KEY_FIELD = 'id_customer';
	
	protected $fields = array(
		'user_id'
		, 'id_shop_group'
		, 'id_shop'
		, 'id_gender'
		, 'id_default_group'
		, 'id_risk'
		, 'company'
		, 'siret'
		, 'ape'
		, 'firstname'
		, 'lastname'
		, 'username'
		, 'email'
		, 'passwd'
		, 'last_passwd_gen'
		, 'birthday'
		, 'newsletter'
		, 'ip_registration_newsletter'
		, 'newsletter_date_add'
		, 'optin'
		, 'website'
		, 'outstanding_allow_amount'
		, 'show_public_prices'
		, 'max_payment_days'
		, 'secure_key'
		, 'note'
		, 'active'
		, 'is_guest'
		, 'deleted'
		, 'date_add'
		, 'date_upd'
	);
	
	/*
	public function insert_to_customer() {
		$query = 'SELECT user.* FROM btg_hq.user WHERE exists(SELECT user_user_group_link_id FROM user_user_group_link WHERE user_user_group_link.user_id = user.user_id and user_user_group_link.user_group_portal_id = 2) group by user.user_id ORDER BY user.user_id ASC';
		
		try {
			
			$settings = array(
				'host' => '10.51.98.70'
				, 'user' => 'hqadmin'
				, 'password' => '6MPWxGSP2eerWwxJ'
				, 'db_name' => 'btg_hq'
			);
			self::$dbs['a']['b'] = new Database_Helper();
			self::$dbs['a']['b']->open_connection($settings);
			$users = self::$dbs['a']['b']->exec($query);
			
			foreach ($users as $user) {
				$info = array(
					'user_id' => $user['user_id']
					, 'firstname' => $user['first_name']
					, 'lastname' => $user['last_name']
					, 'email' => $user['email']
					, 'active' => $user['active']
					, 'date_add' => $this->get_datetime()
				);
				
				$this->db_insert($info);
			}
			
			return $data;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get insert customers.'. $e->getMessage());
		}
	}
	*/
}
?>
