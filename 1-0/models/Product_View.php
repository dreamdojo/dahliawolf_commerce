<?php
/**
 * User: JDorado
 * Date: 11/18/13
 */
 
class Product_View extends _Model
{
    const TABLE = 'product_view';
   	const PRIMARY_KEY_FIELD = 'product_view_id';

    private $table = 'product_view';

    public function __construct($db_host = DW_API_HOST, $db_user = DW_API_USER, $db_password = DW_API_PASSWORD, $db_name = DW_API_DATABASE)
    {
        parent::__construct($db_host, $db_user, $db_password, $db_name );
    }

    public function addView($data = array())
    {
        $error = NULL;

        $values = array();

        $fields = array(
            'product_id',
            'user_id',
            'viewer_user_id',
            'created_at',
        );

        $data['created_at'] = date('Y-m-d h:i:s');

        foreach ($fields as $field) {
            if (array_key_exists($field, $data)) {
                $values[$field] = $data[$field];
            }
        }

        try {
            $insert_id = $this->do_db_save($values, $data);
            return array(
                    strtolower( self::PRIMARY_KEY_FIELD) => $insert_id,
                    //'model_data' => $data
                    );

        } catch(Exception $e) {
            self::$Exception_Helper->server_error_exception("Unable to save product view. ". $e->getMessage());
        }

    }


    public function getViews($params = array())
    {
        $error = NULL;

        if (empty($params['product_id'])) {
            $error = 'Invalid posting id.';
            return array('error' => $error );
        }

        $query = " SELECT
                    *
                    FROM {$this->table}
                    WHERE product_id = :product_id
        ";

        if(isset($params['t'])) echo $query;

        $values = array(
            ':product_id' => $params['product_id']
        );

        $data = $this->fetch($query, $values);

        if ($data === false) {
            return array('error' => 'Could not get product views.');
        }

        return array('product_views' => $data);
    }


    public function getTotalViews($params = array())
    {
        $error = NULL;
        $query = "
            SELECT
              COUNT(*) AS 'total'
            FROM {$this->table}
            WHERE product_id = :product_id
        ";
        $values = array(
            ':product_id' => $params['product_id']
        );

        if(!$params['product_id']) self::addError('invalid_product_id', 'posting id is invalid');

        $data = $this->fetch($query, $values);

        if($data) {
            return array(
                        'total' => $data[0]['total']
                    );
        }

    }

}

?>