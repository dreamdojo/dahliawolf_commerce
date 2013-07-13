<?php
//require_once $_SERVER['DOCUMENT_ROOT'] . '/models/database.php';

/*
 * // INSERT, UPDATE, DELETE with prepared statements:
$db->insert( 'table', array( 'name'=>'John Doe', 'age'=>28 ) );
$db->update( 'table', array( 'age'=>29 ), array( 'name'=>'John Doe' ) );
$db->delete( 'table', array( 'name'=>'John Doe' ) );

// SELECT all, single row, single column and single variable:
$db->get_all( 'table', array( 'age'=>22 ) ); $table=null, $where=array(), $fields='*'
$db->get_row( 'table', array( 'name'=>'John Doe' ), array( 'name', 'email' ) );  $table=null, $where=array(), $fields='*'
$db->get_col( 'table', array( 'age'=>28 ), 'name' ); $table=null, $where=array(), $fields='*'
$db->get_var( 'table', array( 'name'=>'John Doe' ) ); $table=null, $where=array(), $field=null

// Check if a record exists:
if( $db->exists( 'table', array( 'name'=>'John Doe' ) ) ) echo 'Record exists!';

// Get the count of matching records:
$db->get_count( 'table', array( 'age'=>22 ) );

// Debug:
$db->sql; // Holds the SQL query executed.
$db->bind; // Holds the bind parameters of a Prepared Statement.
$db->insert_id; // Holds the ID of last inserted row.
$db->num_rows; // Holds the number of rows affected by last query.
$db->result; // Holds the result of the last query executed.

$db->debug(); // Print out all necessary properties.
*/

class Shop extends db {

	private $table 			= 'shop';
	private $table_group	= 'shop_group';
	private $table_url		= 'shop_url';

    public function __construct() {
            parent::__construct();
    }

    // ?api=shop&function=add-shop-group&params={"conditions":{"id":"4"}}
    public function get_shopid_from_domain($params = array()) {
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

		$fields = array('id_shop');
     	$row = $this->get_row($this->table_url, $params['conditions'], $fields);
       	if ($row === false) {
          	return resultArray(false, NULL, 'Could not get shop groups.');
      	}

        return resultArray(true, $row);
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


	// ?api=shop&function=get_shop_info
	public function get_shop_info($params = array()) {
		$query = '
			SELECT shop.name,
				shop.id_category,
				shop.active,
				shop_group.share_customer,
				shop_group.share_order,
				shop_url.domain,
				shop_url.domain_ssl,
				shop_url.physical_uri,
				shop_url.virtual_uri,
				shop_url.main,
				shop_url.active
			FROM shop
				INNER JOIN shop_group ON shop.id_shop_group = shop_group.id_shop_group
				INNER JOIN shop_url ON shop.id_shop = shop_url.id_shop
			WHERE shop.id_shop = :id_shop
			LIMIT 1
		';
		$values = array(
			':id_shop' => $params['conditions']['id_shop']
		);

		$stmt = $this->run($query, $values);

		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return resultArray(false, NULL, 'Could not get shop info.');
      	}

        return resultArray(true, $this->result[0]);
	}

	public function get_primary_shop_store_address($id_shop) {
		$query = '
			SELECT store.address1, store.address2, store.city, store.postcode
				, country.iso_code AS country
				, state.iso_code AS state
			FROM shop
				INNER JOIN store_shop ON shop.id_shop = store_shop.id_shop
				INNER JOIN store ON store_shop.id_store = store.id_store
				INNER JOIN country ON store.id_country = country.id_country
				INNER JOIN state ON store.id_state = state.id_state
			WHERE shop.id_shop = :id_shop
			ORDER BY store.date_add ASC
			LIMIT 1
		';
		$values = array(
			':id_shop' => $id_shop
		);

		$stmt = $this->run($query, $values);

		$this->result = $stmt->fetchAll();
       	if (empty($this->result)) {
          	return NULL;
      	}

        return $this->result[0];
	}
}
?>
