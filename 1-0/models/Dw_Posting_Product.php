<?
class Dw_Posting_Product extends _Model {
	const TABLE = 'posting_product';
	const PRIMARY_KEY_FIELD = 'posting_product_id';

	protected $fields = array(
		'product_id'
		, 'posting_id'
		, 'vote_period_id'
		, 'active'
		, 'is_primary'
	);

	public function get_posting($posting_product_id) {
		$query = '
			SELECT posting_product.*
				, posting.user_id
			FROM posting_product
				INNER JOIN posting ON posting_product.posting_id = posting.posting_id
			WHERE posting_product.posting_product_id = :posting_product_id
		';
		$values = array(
			':posting_product_id' => $posting_product_id
		);

		try {
			$posting = self::$dbs[$this->db_host][$this->db_name]->select_single($query, $values);

			return $posting;
		} catch (Exception $e) {
			self::$Exception_Helper->server_error_exception('Unable to get posting.');
		}
	}
}
?>