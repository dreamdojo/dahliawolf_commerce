<?
class Dw_User extends _Model
{

    const TABLE = 'user_username';
   	const PRIMARY_KEY_FIELD = 'user_username_id';

    private $table = 'user_username';

    public function __construct()
    {
        parent::__construct($db_host = DW_API_HOST, $db_user = DW_API_USER, $db_password = DW_API_PASSWORD, $db_name = DW_API_DATABASE);
    }



    public function getUser($params = array())
    {
        $error = NULL;

        $where_str = '';
        $params = array();
        // user_id or username
        if (!empty($params['user_id'])) {
            $where_str = 'user_username.user_id = :user_id';
            $params[':user_id'] = $params['user_id'];
        }
        else {
            $where_str = "{$this->table}.username = :username";
            $params[':username'] = !empty($params['username']) ? $params['username'] : '';
        }

        $select_str = '';
        $join_str = '';
        // Optional viewer_user_id
        if (!empty($params['viewer_user_id'])) {
            $select_str = ', IF(f.user_id IS NULL, 0, 1) AS is_followed';
            $join_str = 'LEFT JOIN follow AS f ON user_username.user_id = f.user_id AND f.follower_user_id = :viewer_user_id';
            $params[':viewer_user_id'] = $params['viewer_user_id'];
        }

        $active_limit = (60*60*24)*30;

        $model_db = DW_API_DATABASE;

        $query = "SELECT
          {$this->table}.*
            , (
                SELECT COUNT(*)
                FROM {$model_db}.user_username AS u
                WHERE
                    u.points > user_username.points
            ) + 1 AS rank
            , (
                SELECT COUNT(*)
                FROM {$model_db}.follow
                WHERE follow.follower_user_id = user_username.user_id
            ) AS following
            , (
                SELECT COUNT(*)
                FROM {$model_db}.follow
                WHERE follow.user_id = user_username.user_id
            ) AS followers
            , (
                SELECT COUNT(*)
                FROM {$model_db}.posting
                WHERE posting.user_id = user_username.user_id
            ) AS posts
            , (
                SELECT COUNT(*)
                FROM {$model_db}.comment
                WHERE comment.user_id = user_username.user_id
            ) AS comments
            , (
                SELECT COUNT(*)
                FROM {$model_db}.posting_like
                    INNER JOIN {$model_db}.posting ON posting_like.posting_id = posting.posting_id
                WHERE posting.user_id = user_username.user_id
            ) AS likes
            ,(
                  SELECT
                  ml.name
                  FROM {$model_db}.membership_level ml, {$model_db}.user_username user
                  WHERE user.user_id = user_username.user_id
                  AND ABS(CAST(ml.points AS SIGNED) - CAST(user.points AS SIGNED) ) / ml.points > 1
                  order by ABS(CAST(ml.points AS SIGNED) - CAST(user.points AS SIGNED) +1) ASC
                  LIMIT 1
              ) AS membership_level
              ,(
                  SELECT COUNT(*)
                  FROM {$model_db}.posting
                  WHERE posting.user_id = user_username.user_id
                    AND posting.deleted IS NULL
                    AND UNIX_TIMESTAMP(posting.created)+2592000 < UNIX_TIMESTAMP()

              )AS posts_expired
              ,(
                  SELECT COUNT(*)
                  FROM {$model_db}.posting
                  WHERE posting.user_id = user_username.user_id
                    AND posting.deleted IS NULL
                    AND UNIX_TIMESTAMP(posting.created)+2592000 > UNIX_TIMESTAMP()

              ) AS posts_active
              ,(
                  SELECT COUNT(*)
                  FROM {$model_db}.posting
                   LEFT JOIN {$model_db}.like_winner ON posting.posting_id = like_winner.posting_id
                  WHERE posting.user_id = user_username.user_id  AND like_winner.like_winner_id IS NOT NULL
              ) AS winner_posts
              ,(
                SELECT COUNT(*)
                FROM {$model_db}.posting
                WHERE posting.user_id = user_username.user_id
                    AND posting.deleted IS NULL
            ) AS posts_total

                {$select_str}
            FROM {$model_db}.user_username as {$this->table}
                {$join_str}
            WHERE {$where_str}
            LIMIT 1";


        if(isset($_GET['t']))
        {
            echo sprintf("query: \n%s\n", $query);
        }

        /*
        $result = $this->run($query, $params);
        $rows = $result->fetchAll();
        */
        try{
            $data = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $params);

            if (empty($data)) {
                return resultArray(false, NULL, 'Could not get user.');
            }
            return  $data;
        }catch (Exception $e)
        {
            _Model::$Exception_Helper->request_failed_exception('User could not be found.');
            return null;
        }
    }

	public function get_membership_level($user_id) {
		$query = '
			SELECT user.points, user.points_threshold
				, membership_level.name, membership_level.commerce_id_cart_rule
			FROM
				(
					SELECT user_username.points
						, (
							SELECT MAX(points)
							FROM dahliawolf_v1_2013.membership_level
							WHERE membership_level.points <= user_username.points
							LIMIT 1
						) AS points_threshold
					FROM dahliawolf_v1_2013.user_username
					WHERE user_username.user_id = :user_id
				) AS user
				INNER JOIN dahliawolf_v1_2013.membership_level ON user.points_threshold = membership_level.points
		';
		$values = array(
			':user_id' => $user_id
		);

		try {
			$user = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $user;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get user membership level.');
		}
	}

	/*public function get_primary_posting_product_user_id($product_id) {
		$query = '
			SELECT posting.user_id
			FROM posting_product
				INNER JOIN posting ON posting_product.posting_id = posting.posting_id
			WHERE posting_product.product_id = :product_id
			ORDER BY posting_product.created
		';
		$values = array(
			':product_id' => $product_id
		);

		try {
			$user = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $user;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get primary posting product user.');
		}
	}*/
}
?>