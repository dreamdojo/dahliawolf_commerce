<?
class Commission extends _Model {
	const TABLE = 'commission';
	const PRIMARY_KEY_FIELD = 'id_commission';

	protected $fields = array(
		'user_id'
		, 'id_order'
		, 'id_product'
		, 'id_order_detail'
		, 'commission'
		, 'product_quantity'
		, 'note'
	);

    private function subtract($params = array()) {
        $values = array(
            ':user_id'=>$params['user_id'],
            ':amount'=>$params['amount'],
            ':note'=>$params['note']
        );

        $q = "INSERT INTO commission (user_id, commission, note) VALUES (:user_id, :amount, :note)";

        try {
            $ret = $this->query($q, $values);

            return $ret;
        } catch (Exception $e) {
            self::$Exception_Helper->server_error_exception('Unable to subtract user commissions.');
        }
    }

    private function addStoreCredit($params = array()) {
        $values = array(
            ':user_id'=>$params['user_id'],
            ':amount'=>floatval($params['amount'])*1.1,
            ':note'=>$params['note']
        );

        $q = "INSERT INTO store_credit (user_id, amount, note) VALUES (:user_id, :amount, :note)";

        try {
            $ret = $this->query($q, $values);

            return $ret;
        } catch (Exception $e) {
            self::$Exception_Helper->server_error_exception('Unable to add user commissions.');
        }
    }

    private function add($params = array()) {

    }

	public function get_user_total($user_id) {
		$query = '
			SELECT IFNULL(SUM(commission.commission), 0) AS total_commissions
			FROM commission
			WHERE user_id = :user_id
		';

		$values = array(
			':user_id' => $user_id
		);

		try {
			$total_commissions = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $total_commissions;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get total user commissions.');
		}
	}

    public function convertToStoreCredit($user_id) {
        $commissions = $this->get_user_total($user_id)['total_commissions'];
        if(floatval($commissions)) {
            $this->subtract(array('user_id'=>$user_id, 'amount'=>'-'.$commissions, 'note'=>'Transfer commission to Store Credit'));
            $this->addStoreCredit(array('user_id'=>$user_id, 'amount'=>$commissions, 'note'=>'Store Credit received from commission exchange'));
            return array('data'=>$commissions.' converted successfully');
        } else {
            _Model::$Exception_Helper->request_failed_exception('No commission available');
        }

    }
}
?>