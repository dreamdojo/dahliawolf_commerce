<?php
require_once 'PasswordHash.php';
//requires mySqlV6.php and session start()

/*
MYSQL

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` mediumint(8) unsigned NOT NULL auto_increment,
  `login` varchar(255) default NULL,
  `password` char(32) default NULL,
  `user_group_id` mediumint(8) unsigned default NULL,
  `token` varchar(255) default NULL,
  `counter` mediumint(8) unsigned default NULL,
  `last_login` int(10) unsigned default NULL,
  `last_failed_login` int(10) unsigned default NULL,
  `failed_login_count` mediumint(8) unsigned default NULL,
  `lockout_start` int(10) unsigned default NULL,
  `created` timestamp NULL default CURRENT_TIMESTAMP,
  `active` tinyint(1) default '1',
  `first_name` varchar(255) default NULL,
  `last_name` varchar(255) default NULL,
  `username` varchar(255) default NULL,
  PRIMARY KEY  (`user_id`),
  UNIQUE KEY `login` (`login`),
  KEY `token` (`token`),
  KEY `password` (`password`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO `user` (`user_id`, `login`, `password`, `user_group_id`, `token`, `counter`, `last_login`) VALUES 
(1, 'eckxmediagroup', 'f3e1595824406db27049205cd8fdc14c', 1, NULL, NULL, NULL),
(2, 'administrator', 'dbe348bdd3084d2613c2bedd3b7a03e6', 1, NULL, NULL, NULL)
;


CREATE TABLE IF NOT EXISTS `group` (
  `user_group_id` mediumint(8) unsigned NOT NULL auto_increment,
  `name` varchar(255) default NULL,
  `portal` enum('Public','Admin') default NULL,
  PRIMARY KEY  (`user_group_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

INSERT INTO `group` (`user_group_id`, `name`) VALUES 
(1, 'Administrator');
*/
class login {

	public $timeOut = 1500; // maximum number seconds the user can be idle
	private $cookieTime = 1209600; // max number of seconds the cookie information will be stored
	private $lockoutSec = 3600; // 60 minutes
	private $maxFailedLogins = 100; //max number of failed logins before the login is locked
	private	$failedLoginSec = 3600; // the interval used to check maxFailedLogins: ie, num failed logins in the last $failedLoginSec seconds
	
	//public
	//public $mysql;	
	public $user = array();
	public $bad = false; //false: none,  1: no login, 2: timeout, 3: bad group
	
	//private
	private $key = 'I like red pickled apples!';
	private $ckiePassIndex = 'login-v4-password'; //for the associative index, CR is used for multiple portals
	private $ckieLoginIndex = 'login-v4-login'; //for the associative index, CR is used for multiple portals
	private $sessionTokenIndex = 'login-v4-token';
	private $validGroupIds = array();
	private $invalidGroupIds = array();
	
	private $pHash;

	/**
	* Constructor
	*
	* Authenticates user if: $userInputedToken is provided or if cookies are available
	*
	* @access public
	* @param array $validGroupIds - A list of valid group ids (optional); if provided, login will verify
	   that user is within one of these
	  groups before authenticating
	* @param array $invalidGroupIds - A list of invalid group ids (optional); if provided, login wil
	   verify that user is not within one of these groups before authenticating
	* @param string $userInputedToken - Key to authenticate user (optional)
	*
	*/
	public function __construct($validGroupIds = array(), $invalidGroupIds = array(), $userInputedToken = NULL) {
		global $_mysql;
		
		// PasswordHasher
		$this->pHash = new PasswordHash(8, FALSE);
		
		if (!empty($validGroupIds)) {
			$this->validGroupIds = array_filter($validGroupIds, 'is_numeric');
		}
		
		if (!empty($invalidGroupIds)) {
			$this->invalidGroupIds = array_filter($invalidGroupIds, 'is_numeric');
		}
		
		//have to concat CR here because cant concat during declaration
		$this->key = CR . $this->key; // CR is used for multiple portals
		$this->ckiePassIndex = CR . $this->ckiePassIndex; // CR is used for multiple portals
		$this->ckieLoginIndex = CR . $this->ckieLoginIndex; //  CR is used for multiple portals
		
		$token = $this->getToken();
		$cookieLogin = false;
		
		//get login info
		if ($token !== false) {
			$query = '
				SELECT user.*, user_user_group_link.user_group_id, user_user_group_link.user_group_portal_id, login_instance.token
				FROM user 
				INNER JOIN user_user_group_link ON user.user_id = user_user_group_link.user_id 
				INNER JOIN login_instance ON login_instance.user_id = user.user_id
				WHERE token = :token AND active = :active
			';
			$values = array(
				':token' => $token
				, ':active' => '1'
			);
			
			if (!empty($this->validGroupIds)) {
				$query .= ' AND user_user_group_link.user_group_id IN(' . implode(',', $this->validGroupIds) . ')';
			}
			
			if (!empty($this->invalidGroupIds)) {
				$query .= ' AND user_user_group_link.user_group_id NOT IN(' . implode(',', $this->invalidGroupIds) . ')';
			}
			
			$this->user = $_mysql->getSingle($query, $values);
			if (empty($this->user)) {
				cookieUnset($this->ckiePassIndex); //clear cookie from local computer
				$this->unsetToken(); //reduce damange from hijacking
				session_regenerate_id(true); //reduce damange from hijacking
				$_SESSION[CR]['user-error-single'] = 'You have logged in at another location.';
			}
		}	
		else {
			$login = $this->getCookieLogin();
			if ($login) { // if login is saved
				$password = $this->getCookiePassword();
				if ($password && STOREPASSWORDCOOKIE === true) { // if password is saved and STOREPASSWORDCOOKIE flag is set
					$query = '
						SELECT user.*, user_user_group_link.user_group_id, user_user_group_link.user_group_portal_id, login_instance.token 
						FROM user 
						LEFT JOIN login_instance ON login_instance.user_id = user.user_id
						INNER JOIN user_user_group_link ON user.user_id = user_user_group_link.user_id 
						WHERE user.email = :email AND user.active = :active
					';
					$values = array(
						':email' => $login
						, ':active' => '1'
					);
					
					if ($userInputedToken != NULL) {
						$query .= ' AND login_instance.token = :token';
						$values[':token'] = $userInputedToken;
					}
					
					if (!empty($this->validGroupIds)) {
						$query .= ' AND user_user_group_link.user_group_id IN(' . implode(',', $this->validGroupIds) . ')';
					}
					
					if (!empty($this->invalidGroupIds)) {
						$query .= ' AND user_user_group_link.user_group_id NOT IN(' . implode(',', $this->invalidGroupIds) . ')';
					}
					
					$this->user = $_mysql->getSingle($query, $values);
					if (!empty($this->user)) {
						if ($password != $this->user['hash']) {
							$this->user = false;
						}
						else { //all checks are good, user is now considered loggedin
							//update token
							if ($userInputedToken != NULL) {
								$token = $userInputedToken;
							}
							else {
								$token = $this->generateToken($this->user['user_id']);
							}
							
							$this->setToken($token);
							
							// Create Login Instance
							if ($userInputedToken != NULL) {
								$values = array(
									'last_login' => date('Y-m-d H:i:s', TIME)
								);
								$wherestr = 'token = :token';
								$wherevals = array(
									':token' => $token
								);
								$_mysql->update('login_instance', $values, $wherestr, $wherevals);
							}
							else {
								$values = array(
									'user_id' => $this->user['user_id']
									, 'token' => $token
									, 'last_login' => date('Y-m-d H:i:s', TIME)
								);
								
								$tryLimit = 100;
								$tryCount = 0;
								$inserted = $_mysql->insert('login_instance', $values);
								while (!$inserted && $tryCount < $tryLimit) {
									$token = $this->generateToken($this->user['user_id']);
									$values['token'] = $token;
									$inserted = $_mysql->insert('login_instance', $values);
									$tryCount++;
								}
								
								if (!$inserted) {
									$error = 0;
									return false;
								}
							}
							
							// Update User Row
							$values = array(
								'counter' => $this->user['counter'] + 1
								, 'last_login' => date('Y-m-d H:i:s ', TIME)
								, 'last_failed_login' => NULL
								, 'failed_login_count' => NULL
								, 'lockout_start' => NULL
							);
							$wherestr = 'user_id = :user_id';
							$wherevals = array(
								':user_id' => $this->user['user_id']
							);
							$_mysql->update('user', $values, $wherestr, $wherevals);
							
							$this->keepUserLoggedIn();
							$cookieLogin = true;
						}
					}
				}
			} 
		}
		
		if (!empty($this->user)) { // found login
			if ((TIME - strtotime($this->user['last_login'])) > $this->timeOut && !$cookieLogin) { //timeout
				$this->bad = 2;
				$this->logout();
			}
			else { // good login
				$values = array(
					'last_login' => date('Y-m-d H:i:s ', TIME)
				);
				$wherestr = 'user_id = :user_id';
				$whereValues = array(
					':user_id' => $this->user['user_id']
				);
				$_mysql->update('user', $values, $wherestr, $whereValues);
			}
			
			return;
		}
		
		//no login
		$this->bad = 1;
	}
	
	/**
	*
	* Authenticates user
	*
	* @access public
	* @param string $login
	* @param string $password
	* @param string $cookieType - Can be: 0 - Do not store cookies; 1 - Store login only; 2 - Store
	   login and password
	* @param string $error - Key to authenticate user
	* @return bool - True if authentication was successful, false otherwise
	*
	*/
	public function authen($login, $password, $cookieType = 0, &$error) {
		global $_mysql;
		
		//make $login safe
		$this->clean($login);
		
		if ($login == '' || $password == '') {
			return false;
		}
		
		// Login via username or email
		if (strpos($login, '@') !== false) {
			$where_str = 'user.email = :email';
			$values = array(
				':email' => $login
			);
		}
		else {
			$where_str = 'user.username = :username';
			$values = array(
				':username' => $login
			);
		}
		
		$query = '
			SELECT user.*, user_user_group_link.user_group_id, user_user_group_link.user_group_portal_id 
			FROM user 
			INNER JOIN user_user_group_link ON user.user_id = user_user_group_link.user_id
			WHERE ' . $where_str . '
		';
		
		
		if (!empty($this->validGroupIds)) {
			$query .= ' AND user_user_group_link.user_group_id IN(' . implode(',', $this->validGroupIds) . ')';
		}
		
		if (!empty($this->invalidGroupIds)) {
			$query .= ' AND user_user_group_link.user_group_id NOT IN(' . implode(',', $this->invalidGroupIds) . ')';
		}
			
		$this->user = $_mysql->getSingle($query, $values);
		
		if (empty($this->user)) { // make sure the login exists
			$error = 0;
			
			return false;
		}
		
		// if they are currently locked out return false
		$lockedOut = $this->user['lockout_start'] >= (TIME - $this->lockoutSec) ? true : false;
		if ($lockedOut) {
			$error = 2;
			
			return false;
		}
		
		$isCorrectPassword = $this->checkPassword($password, $this->user['hash']);
		
		// record failed login attempt
		if (!$isCorrectPassword) {
			$this->recordFailedLogin();
			$error = 0;
			
			return false;
		}
		else {
			//make sure user is active
			$active = $this->user['active'] == '1' ? true : false;
			if (!$active) {
				$error = 1;
				
				return false;
			}
			
			session_regenerate_id(true); //reduce session hijacking dmg
			
			$token = $this->generateToken($this->user['user_id']);
			$this->setToken($token);
			
			// Create Login Instance
			$values = array(
				'user_id' => $this->user['user_id']
				, 'token' => $token
				, 'last_login' => date('Y-m-d H:i:s', TIME)
			);
			
			$tryLimit = 100;
			$tryCount = 0;
			$inserted = $_mysql->insert('login_instance', $values);
			while (!$inserted && $tryCount < $tryLimit) {
				$token = $this->generateToken($this->user['user_id']);
				$values['token'] = $token;
				$inserted = $_mysql->insert('login_instance', $values);
				$tryCount++;
			}
			
			if (!$inserted) {
				$error = 0;
				return false;
			}
			
			// Update User Row
			$values = array(
				'counter' => $this->user['counter'] + 1
				, 'last_login' => date('Y-m-d H:i:s ', TIME)
				, 'last_failed_login' => NULL
				, 'failed_login_count' => NULL
				, 'lockout_start' => NULL
			);
			$wherestr = 'user_id = :user_id';
			$wherevals = array(
				':user_id' => $this->user['user_id']
			);
			$_mysql->update('user', $values, $wherestr, $wherevals);
			
			if ($cookieType == 1 || $cookieType == 2) { // save login
				$this->saveCookieLogin($login);
			}
			
			if ($cookieType == 2 && STOREPASSWORDCOOKIE) { // save login & password
				$this->saveCookiePassword($this->user['hash']);
				$this->keepUserLoggedIn();
			}
			
			return $token;
		}
	}
	
	/**
	*
	* Creates a new user with the values provided
	*
	* @access public
	* @param string $login
	* @param string $password
	* @param string $user_group_id - User group id
	* @param string $username - Optional
	* @return int - New user id
	*
	*/
	public function create($login, $password, $user_group_ids = array(), $username = NULL) { //need to move away from createLogin to create
		global $_mysql;
		
		$this->clean($login);

		if ($login == '' || $password == '' || (!empty($user_group_ids) && !is_array($user_group_ids))) {
			trigger_error('function $login->createLogin() :: missing argument', E_USER_ERROR);
		}
		
		//check if login exist
		$exists = $this->exists($login, true);
		if ($exists) {
			return false;
		}
		
		if ($username != NULL) {
			$exists = $this->exists($username, false, 'username');
			if ($exists) {
				return false;
			}
		}
		
		$hashPassword = $this->hashPassword($login, $password);
		$values = array(
			'email' => $login
			, 'hash' => $hashPassword
			, 'username' => $username
		);
		
		$_mysql->insert('user', $values);
		$user_id = $_mysql->lastInsertId();
		
		if (!empty($user_group_ids)) {
			foreach ($user_group_ids as $user_group_portal_id => $user_group_id) {
				$values = array(
					'user_id' => $user_id
					, 'user_group_id' => $user_group_id
					, 'user_group_portal_id' => $user_group_portal_id
				);
				
				$_mysql->insert('user_user_group_link', $values);
			}
		}
		
		return $user_id;
	}
	
	/**
	*
	* Delete user
	*
	* @access public
	* @param string $login - The login to identify which user to delete
	*
	*/
	public function deleteLogin($login) {
		global $_mysql;
		
		$this->clean($login);
		
		$wherestr = 'login = :login';
		$wherevals = array(
			':login' => $login
		);
		$_mysql->delete('user', $wherestr, $wherevals);
	}
	
	/**
	*
	* Check whether user exists based on either "login" or "username"
	*
	* @access public
	* @param string $needle - The value to check
	* @param bool $clean - If set to true, will trim and convert $needle to lowercase before
	   comparing
	* @param string $check - The field to check $needle against; can be either "login" or
	   "username"
	* @return bool - True if user exists, false otherwise
	*/
	public function exists($needle, $clean = false, $check = 'email') {
		global $_mysql;

		if (empty($needle)) {
			return false;	
		}
		
		if (!$clean) {
			$this->clean($needle);
		}
		
		$query = '
			SELECT user_id 
			FROM user 
			WHERE ' . $check . ' = :' . $check;
		$values = array(
			':' . $check => $needle
		);
		
		$user = $_mysql->getSingle($query, $values);
		
		return !empty($user);
	}
	
	/**
	*
	* Returns the login $_COOKIE value
	*
	* @access public
	* @return string - The value of the login $_COOKIE
	*/
	public function getCookieLogin() {
		if (!isset($_COOKIE[$this->ckieLoginIndex])) {
			return false;	
		}
		
		return decrypt($_COOKIE[$this->ckieLoginIndex]);
	}
	
	/**
	*
	* Returns the Token stored in the $_SESSION
	*
	* @access public
	* @return string - The value of Token
	*/
	public function getToken() {
		$token = isset($_SESSION[CR][$this->sessionTokenIndex]) ? $_SESSION[CR][$this->sessionTokenIndex] : '';
		if ($token == '') {
			return false;
		}
		
		return $token; //CR is added to allow multiple portals
	}
	
	/**
	*
	* Logs out user by unsetting $_SESSION and $_COOKIE
	*
	* @access public
	*/
	public function logout() {
		global $_mysql;
		
		if (!is_numeric($this->user['user_id'])) {
			trigger_error('$login->logout(), user_id is not numeric', E_USER_ERROR);	
		}
		
		cookieUnset($this->ckiePassIndex); //clear cookie from local computer
		$this->unsetToken(); //reduce damange from hijacking
		session_regenerate_id(true); //reduce damange from hijacking
		
		$whereStr = 'user_id = :user_id AND token = :token';
		$whereVals = array(
			':user_id' => $this->user['user_id']
			, ':token' => $_SESSION[CR][$this->sessionTokenIndex]
		);
		$_mysql->delete('login_instance', $whereStr, $whereVals);
		
		return true;
	}
	
	/**
	*
	* Checks whether the user group is included in $user_group_ids; used primarily in permission class
	*
	* @access public
	* @param array $user_group_ids - A list of group ids
	* @return bool - True if user is logged in and user group is included in the list of user_group_ids, false
	   otherwise
	*/
	public function protect($user_group_ids = array()) {
		
		if ($this->bad) { // has some type of problem, during authen
			return false;
		}
		
		// Check for group
		if (count($user_group_ids) > 0) {
			if (!in_array($this->user['user_group_id'], $user_group_ids)) {
				//$this->bad = 3; //causing problems when using $this->protect() twice
				return false;
			}
		}
		
		return true;
	}
	
	/**
	*
	* Records a failed login attempt. If user has exceeded the number of allowed failed login
	  attempts, function will lock their account from further attempts for 1 hour.
	*
	* @access public
	*/
	public function recordFailedLogin() {
		global $_mysql;
		
		if (!is_numeric($this->user['user_id'])) {
			trigger_error('$login->recordFailedLogin(), user_id is not numeric', E_USER_ERROR);	
		}
		
		$numFailedLogins = empty($this->user['last_failed_login']) || ($this->user['last_failed_login'] < (TIME - $this->failedLoginSec)) ? 1 : $this->user['failed_login_count'] + 1;
		$failedLoginTime = empty($this->user['last_failed_login']) || ($this->user['last_failed_login'] < (TIME - $this->failedLoginSec)) ? TIME : $this->user['last_failed_login'];
		$lockoutStart = NULL;
		
		if ($numFailedLogins >= $this->maxFailedLogins) {
			$lockoutStart = TIME;
			email(SITENAME, FROMEMAIL, CONTACTEMAIL, CONTACTEMAIL, PORTAL . ' Portal Locked Out', $this->user['email'], $this->user['email']);
		}
		
		$values = array(
			'last_failed_login' => $failedLoginTime
			, 'failed_login_count' => $numFailedLogins
			, 'lockout_start' => $lockoutStart
		);
		$wherestr = 'user_id = :user_id';
		$wherevals = array(
			':user_id' => $this->user['user_id']
		);
		$_mysql->update('user', $values, $wherestr, $wherevals);
	}
	
	/**
	*
	* Resets failed login stats (date/time of last failed login, number of failed logins, lockout start
	  date/time)
	*
	* @access public
	* @param string $login - The login to identify which user to reset
	*/
	public function resetFailedLogin($login) {
		global $_mysql;
		
		$this->clean($login);
		
		$values = array(
			'last_failed_login' => NULL
			, 'failed_login_count' => NULL
			, 'lockout_start' => NULL
		);
		$wherestr = 'login = :login';
		$wherevals = array(
			':login' => $login
		);
		$_mysql->update('user', $values, $wherestr, $wherevals);
	}
	
	/**
	*
	* Resets a users password to a random string
	*
	* @access public
	* @param string $login - The login to identify which user to update
	* @return string - The random password assigned to the user
	*/
	public function resetPass($login) {
		global $_mysql;
		
		if (!is_numeric($this->user['user_id'])) {
			trigger_error('$login->resetPass(), user_id is not numeric', E_USER_ERROR);	
		}
		
		$this->clean($login);
		
		// check if login exist
		$userExist = $this->exists($login, true);
		if (!$userExist) {
			return false;
		}
		
		// create random password
		$rand = md5(rand(100, 1000000) . TIME);
		$tempPass = '';
		for ($i = 0; $i < 8; $i++) {
			$tempPass .= $rand[$i];
		}
		
		$values = array(
			'hash' => $this->hashPassword($login, $tempPass)
		);
		$wherestr = 'user_id = :user_id';
		$wherevals = array(
			':user_id' => $loginInfo['user_id']
		);
		$_mysql->update('user', $values, $wherestr, $wherevals);
		
		return $tempPass;
	}
	
	/**
	*
	* Updates a users password and, if provided, also updates a users login and username
	*
	* @access public
	* @param int $user_id - The user id to identify which user to update
	* @param string $newPass - The new password
	* @param string $oldPass - The users old password (optional); if provided, will be used to
	   protect the user from unauthorized changes to their login information
	* @param string $newLogin - The new login (optional)
	* @param string $newUsername - The new login (optional)
	* @return bool - True if update was successful, false otherwise
	*/
	public function updatePass($user_id, $newPass, $oldPass = NULL, $newLogin = NULL, $newUsername = NULL) {
		global $_mysql;
		
		if (!is_numeric($user_id)) {
			trigger_error('$login->updatePass() : updatePass - invalid user_id', E_USER_ERROR);
		}
		
		$query = '
			SELECT email, username, hash
			FROM user 
			WHERE user_id = :user_id
		';
		$values = array(
			':user_id' => $user_id
		);
		$user = $_mysql->getSingle($query, $values);
		
		if (empty($user)) {
			return false;
		}
		
		$login = $user['email'];
		$username = $user['username'];
		
		// handle cleaning
		$this->clean($login); 
		
		if ($newLogin != NULL) {
			$this->clean($newLogin);	
		}
		
		if ($oldPass != NULL) { 
			$isCorrectPassword = $this->checkPassword($oldPass, $user['hash']);
			
			//verify old password
			if (!$isCorrectPassword) {
				return false;
			}
		}
		
		$values = array();
		
		if ($newLogin != NULL && $newLogin != $login) { //new login is diferent, need to check for dups
			$exists = $this->exists($newLogin, false);
			if ($exists) {
				return false;	
			}
			$values['email'] = $newLogin;
			$values['hash'] = $this->hashPassword($newLogin, $newPass);
		}
		else {
			$values['hash'] = $this->hashPassword($login, $newPass);
		}
		
		if ($newUsername != NULL) { //new username need to check for dups
			$values['username'] = $newUsername;
			$exists = $this->exists($newUsername, false, 'username');
			$this->clean($newUsername);
			$this->clean($username);
			
			if ($exists && $newUsername != $username) {
				return false;	
			}
		}
		
		$wherestr = 'user_id = :user_id';
		$wherevals = array(
			':user_id' => $user_id
		);
		$_mysql->update('user', $values, $wherestr, $wherevals);
		
		return true;
	}
	
	public function update_groups($user_id, $user_group_ids) {
		global $_mysql;
		
		// delete current groups
		$whereStr = 'user_id = :user_id';
		$whereVals = array(':user_id' => $user_id
						   );
		$_mysql->delete('user_user_group_link', $whereStr, $whereVals);
		
		if (!empty($user_group_ids)) {
			foreach ($user_group_ids as $user_group_portal_id => $user_group_id) {
				$values = array(
					'user_id' => $user_id
					, 'user_group_id' => $user_group_id
					, 'user_group_portal_id' => $user_group_portal_id
				);
				
				$_mysql->insert('user_user_group_link', $values);
			}
		}
		
		return $user_id;
	}
	
	/**
	*
	* Updates a users username
	*
	* @access public
	* @param int $user_id - The user id to identify which user to update
	* @param string $newUsername - The new username
	* @param string $password - The users current password (optional); if provided, will be used to
	   protect the user from unauthorized changes to their username
	* @return bool - True if update was successful, false otherwise
	*/
	public function updateUsername($user_id, $newUsername, $password = NULL) {
		global $_mysql;
		
		if (!is_numeric($user_id)) {
			trigger_error('$login->updateUsername() : updatePass - invalid user_id', E_USER_ERROR);
		}
		
		$query = '
			SELECT email, username, hash
			FROM user 
			WHERE user_id = :user_id
		';
		$values = array(
			':user_id' => $user_id
		);
		$user = $_mysql->getSingle($query, $values);
		
		if (empty($user)) {
			return false;
		}
		
		$login = $user['email'];
		$username = $user['username'];
		
		if ($password != NULL) { 
			$isCorrectPassword = $this->checkPassword($password, $user['hash']);
			
			//verify password
			if (!$isCorrectPassword) {
				return false;
			}
		}
		
		$values = array(
			'username' => $newUsername
		);
		
		// need to check for dups
		$exists = $this->exists($newUsername, false, 'username');
		$this->clean($newUsername);
		$this->clean($username);
		
		if ($exists && $newUsername != $username) { 
			return false;	
		}
		
		$wherestr = 'user_id = :user_id';
		$wherevals = array(
			':user_id' => $user_id
		);
		$_mysql->update('user', $values, $wherestr, $wherevals);
		
		return true;
	}
	
	/*
	public function verifyOldPass($login, $oldPass) {
		global $_mysql;
		
		$this->clean($login);
		
		$hashedPassword = $this->hashPassword($login, $oldPass);
		
		$query = '
			SELECT user_id 
			FROM user 
			WHERE login = :login AND password = :password
		';
		$values = array(
			':login' => $login
			, ':password' => $hashedPassword
		);
		$user = $_mysql->getSingle($query, $values);
		
		if (empty($user)) {
			return false;
		}
		
		return true;
	}
	*/
	
	private function clean(&$str) {
		$str = strtolower($str);
		$str = trim($str);
	}
	
	//generates a unique token based on a random hasing algorithm, user_id, time, rand(0, 1000000)
	private function generateToken($user_id) {
		
		$rand = rand(0, 1000000); //so token changes, reduce hijacking dmg
		
		$token = sha1($user_id . $rand . TIME . (uniqid(mt_rand(), true)));
		
		return $token;
	}
	
	private function getCookiePassword() {
		if (!isset($_COOKIE[$this->ckiePassIndex])) {
			return false;	
		}
		
		return decrypt($_COOKIE[$this->ckiePassIndex]);
	}
	
	private function hashPassword($login, $password) {
		//return md5($login . $password);
		
		return $this->pHash->HashPassword($password);
	}
	
	private function checkPassword($password, $hash) {
		return $this->pHash->CheckPassword($password, $hash);
	}
	
	private function keepUserLoggedIn() {
		$_SESSION[CR]['keep_logged_in'] = true;	
	}
	
	private function saveCookieLogin($login) {
		cookie($this->ckieLoginIndex, encrypt($login), $this->cookieTime);
	}
	
	private function saveCookiePassword($hashedPassword) {
		$encryptedPassword = encrypt($hashedPassword);
		cookie($this->ckiePassIndex, $encryptedPassword, $this->cookieTime);
	}
	
	private function setToken($token) {
		$_SESSION[CR][$this->sessionTokenIndex] = $token;
	}
	
	private function unsetToken() {
		unset($_SESSION[CR][$this->sessionTokenIndex]);	
	}
	
	
}
?>