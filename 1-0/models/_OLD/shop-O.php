<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/models/database.php';

class Shop extends db {

        private $table 		= 'shop';
	private $table_group	= 'shop_group';
	private $table_url	= 'shop_url';

        public function __construct() {
                parent::__construct();
        }

        // ?api=shop&function=add-shop-group&params={"data":{"title":"sdfsdbeka;sfcsd"}}
        //id_shop_group	name	share_customer	share_order	share_stock	active	deleted
	public function add_shop_group($params = array()) {
                //VALIDATION
		$error = NULL;

                if (empty($params['data'])) {
                        $error = 'Data is required.';
                }
                else if (!is_array($params['data'])) {
                        $error = 'Invalid data.';
                }

                if (!empty($error)) {
                        return resultArray(false, NULL, $error);
                }
		//END VALIDATION

                $this->insert($this->table_group, $params['data']);
                $insert_id = $this->insert_id;

                if (empty($insert_id)) {
                        return resultArray(false, NULL, 'Could not add shop group.');
                }

                return resultArray(true, $insert_id);
        }

	// ?api=shop&function=update-shop-group&params={"data":{"title":"my%20test@"},"where":{"id":"2"}}
        public function update_shop_group($params = array()) {
                //VALIDATION
		$error = NULL;

                if (empty($params['data'])) {
                        $error = 'Data is required.';
                }
                else if (!is_array($params['data'])) {
                        $error = 'Invalid data.';
                }
                else if (empty($params['where'])) {
                        $error = 'Where conditions are required.';
                }
                else if (!is_array($params['where'])) {
                        $error = 'Invalid conditions.';
                }

                if (!empty($error)) {
                        return resultArray(false, NULL, $error);
                }
		//END VALIDATION

                $res = $this->update($this->table_group, $params['data'], $params['where']);
                if ($res === false) {
                         return resultArray(false, NULL, 'Could not update shop group.');
                }

                return resultArray(true, $res);
        }

	// &params={"where":{"id":"1"}}
        public function delete_shop_group($params = array()) {
                //VALIDATION
		$error = NULL;

                if (empty($params['where'])) {
                        $error = 'Where conditions are required.';
                }
                else if (!is_array($params['where'])) {
                        $error = 'Invalid conditions.';
                }

                if (!empty($error)) {
                        return resultArray(false, NULL, $error);
                }
		//END VALIDATION

                $res = $this->delete($this->table_group, $params['where']);
                if ($res === false) {
                         return resultArray(false, NULL, 'Could not shop group.');
                }
                return resultArray(true, $res);
        }

	// ?api=shop&function=get-shop-group-detail&params={"conditions":{"id":"4"}}
        public function get_shop_group_detail($params = array()) {
                $error = NULL;

                if (empty($params['conditions'])) {
                        $error = 'Conditions are required.';
                }
                else if (!is_array($params['conditions'])) {
                        $error = 'Invalid conditions.';
                }

                if (!empty($error)) {
                        return resultArray(false, NULL, $error);
                }

                $row = $this->get_row($this->table_group, $params['conditions']);
                if ($row === false) {
                         return resultArray(false, NULL, 'Could not get shop groups.');
                }

                return resultArray(true, $row);
        }

        // ?api=shop&function=get-all-shop-groups
        public function get_all_shop_groups(){
                $rows = $this->get_all($this->table_group);
                if ($rows === false) {
                         return resultArray(false, NULL, 'Could not get shop groups.');
                }

                return resultArray(true, $rows);
        }
	
	//just example
	// ?api=shop&function=get-products
        public function custom-func() {
                $sql = 'SELECT * from `products`';
                $stmt = $this->run($sql);

                // Fetch everything.
                $this->result = $stmt->fetchAll();
                if ($rows === false) {
                         return resultArray(false, NULL, 'Could not get products.');
                }

                if (!empty($this->result)) {
                        return resultArray(true, $this->result);
                }
                else {
                        return resultArray(false, NULL, 'No record found');
                }
        }

}
?>
