<?php

class Order_Invoice extends _Model {

	const TABLE = 'order_invoice';
	const PRIMARY_KEY_FIELD = 'id_order_invoice';
	
	protected $fields = array(
		 'id_order'
		 , 'number'
		 , 'delivery_number'
		 , 'delivery_date'
		 , 'total_discount_tax_excl'
		 , 'total_discount_tax_incl'
		 , 'total_paid_tax_excl'
		 , 'total_paid_tax_incl'
		 , 'total_products'
		 , 'total_products_wt'
		 , 'total_shipping_tax_excl'
		 , 'total_shipping_tax_incl'
		 , 'shipping_tax_computation_method'
		 , 'total_wrapping_tax_excl'
		 , 'total_wrapping_tax_incl'
		 , 'note'
		 , 'date_add'
	);
}
?>