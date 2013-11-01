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



    public function get_commissions($user_id, $id_shop=3, $id_lang=1 )
    {
        $logger = new Jk_Logger(APP_PATH . 'logs/user.log');

        $params = array(
            ':id_shop' => $id_shop,
            ':id_lang' => $id_lang,
            ':user_id' => $user_id,
            //':active' => '1',
        );

        $sql = "
        SELECT SUM(order_detail.product_price) as sales_total
        FROM order_detail
        WHERE order_detail.product_id IN
            (
              SELECT products.product_id
              FROM
                (
                    SELECT  DISTINCT  product.id_product as 'product_id',
                    (
                            SELECT product_file.product_file_id FROM offline_commerce_v1_2013.product_file WHERE product_file.product_id = product.id_product ORDER BY product_file.product_file_id ASC LIMIT 1) AS product_file_id,
                            IF(EXISTS(SELECT category_product.id_category_product FROM offline_commerce_v1_2013.category_product WHERE category_product.id_category = 1 AND category_product.id_product = product.id_product), 1, 0) AS is_new,
                            user_username.username as username, IF(user_username.location IS NULL, '', user_username.location) AS 'location',
                            user_username.user_id

                            FROM offline_commerce_v1_2013.product

                                LEFT JOIN
                                (
                                    SELECT m.*, posting_product.posting_id, posting_product.product_id
                                    FROM
                                    (
                                        SELECT MIN(posting_product.created) AS pp_created, GROUP_CONCAT(posting_product.posting_id SEPARATOR '|') AS posting_ids
                                        FROM dahliawolf_v1_2013.posting
                                            INNER JOIN dahliawolf_v1_2013.posting_product ON posting.posting_id = posting_product.posting_id
                                        GROUP BY posting_product.product_id
                                    ) AS m
                                    INNER JOIN dahliawolf_v1_2013.posting_product ON posting_product.created = m.pp_created
                                ) AS mm ON product.id_product = mm.product_id


                                LEFT JOIN dahliawolf_v1_2013.posting AS posting ON mm.posting_id = posting.posting_id
                                INNER JOIN offline_commerce_v1_2013.product_shop ON product.id_product = product_shop.id_product
                                INNER JOIN offline_commerce_v1_2013.shop ON product_shop.id_shop = shop.id_shop
                                INNER JOIN offline_commerce_v1_2013.product_lang ON product.id_product = product_lang.id_product
                                INNER JOIN offline_commerce_v1_2013.lang ON product_lang.id_lang = lang.id_lang
                                LEFT JOIN offline_commerce_v1_2013.shop AS default_shop ON product.id_shop_default = default_shop.id_shop
                                LEFT JOIN dahliawolf_v1_2013.user_username ON user_username.user_id = posting.user_id

                            WHERE shop.id_shop = :id_shop AND lang.id_lang = :id_lang
                            AND product.user_id = :user_id

                    ) AS products

                )


        ";

        $logger->LogInfo("query params: " . var_export($params,true));

        if(isset($_GET['t'])) var_dump($sql);


        try {
            $data = self::$dbs[$this->db_host][$this->db_name]->exec($sql, $params);

            return $data;
        } catch (Exception $e) {
            self::$Exception_Helper->server_error_exception('Unable to get user comissions.');
        }

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