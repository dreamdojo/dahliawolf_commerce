<?
class User extends _Model {
	const TABLE = 'user';
	const PRIMARY_KEY_FIELD = 'user_id';
	
	protected $fields = array(
		'user_id'
		, 'first_name'
		, 'last_name'
		, 'date_of_birth'
		, 'gender'
		, 'referrer_user_id'
		, 'username'
		, 'email'
		, 'hash'
		, 'active'
		, 'newsletter'
		, 'api_website_id'
	);
	
	protected $public_fields = array(
		'user_id'
		, 'first_name'
		, 'last_name'
		, 'date_of_birth'
		, 'gender'
		, 'username'
		, 'email'
		, 'newsletter'
		, 'api_website_id'
	);
	
	public function get_user($email) {
		$query = '
			SELECT user.*
			FROM user
			WHERE email = :email
		';
		$params = array(
			':email' => $email
		);

       
        $result = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $params);
		return $result;
	}

	public function getUserByUsername($username) {
		$query = '
			SELECT *
			FROM user
			WHERE username = :username
		';
		$params = array(
			':username' => $username
		);

       
        $result = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $params);
		return $result;
	}

	public function getUserById($user_id) {
		$query = '
			SELECT *
			FROM user
			WHERE user_id = :user_id
		';
		$params = array(
			':user_id' => $user_id
		);

       
        $result = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $params);
		return $result;
	}
	
	public function get_user_by_token($user_id, $token) {
		$query = '
			SELECT user.user_id, user.first_name, user.last_name, user.username, user.email
			FROM user
				INNER JOIN login_instance ON user.user_id = login_instance.user_id
			WHERE login_instance.user_id = :user_id
				AND login_instance.token = :token
				AND login_instance.logout IS NULL
		';
		$values = array(
			':user_id' => $user_id
			, 'token' => $token
		);
		
		try {
			$user = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);
			
			return $user;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get user by token.');
		}
	}
	
	public function filter_columns($user) {
		return array(
			'user_id' => $user['user_id']
			, 'first_name' => $user['first_name']
			, 'last_name' => $user['last_name']
			, 'username' => $user['username']
			, 'email' => $user['email']
		);
	}
	
	public function check_social_network_email_exists($email, $social_network_id) {
		$select_str = 'user.' . implode(', user.', $this->public_fields);
		
		$query = '
			SELECT ' . $select_str . '
			FROM user
				INNER JOIN user_social_network_link ON user.user_id = user_social_network_link.user_id
			WHERE user.email = :email
				AND user_social_network_link.social_network_id = :social_network_id
		';
		$values = array(
			':email' => $email,
			':social_network_id' => $social_network_id
		);
		
		try {
			$user = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);
			return $user;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to check user social network email.');
            return false;
		}

        return false;
	}
	
	public function get_regexp_username($username) {
		$query = '
			SELECT username
			FROM user
			WHERE username REGEXP :username
			ORDER BY LENGTH(username)
		';
		$values = array(
			':username' => '^' . $username . '[0-9]*$'
		);
		
		try {
			$usernames = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);
			
			return $usernames;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get regexp usernames.');
		}
	}



}