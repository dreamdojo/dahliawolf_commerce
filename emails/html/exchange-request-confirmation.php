<?
require_once DR . '/emails/html/_config.php';
?>
<div style="font-size: <?= $_e->fontSize ?>px; line-height: <?= $_e->lineHeight ?>px; font-family: <?= $_e->fontFamily ?>; color: <?= $_e->color ?>">
	<p>Please print the attached return request and include it with your return. For full return instructions visit <a href="http://www.dahliawolf.com/returns/">http://www.dahliawolf.com/returns/</a>. You may generate a free shipping return label from the order return page. Print it and attach to package. Sorry you didn't like it. Check out the shop for something better.</p>
	<table width="100%" style="border-collapse: collapse; margin: 20px 0;">
		<thead>
			<tr>
            	<th scope="col" style="text-align: left;" width="36%">Product Name</th>
				<th scope="col" style="text-align: center;" width="16%">Unit Price</th>
				<th scope="col" style="text-align: center;" width="16%">Quantity</th>
				<th scope="col" style="text-align: right;" width="32%">Exchange For</th>
			</tr>
		</thead>
		<tfoot>
         <?
			/*
			<tr>
				<th scope="row" colspan="4" style="text-align: right;">Subtotal</th>
				<td style="text-align: right;">$<?= number_format($variables['product_subtotal'], 2) ?></td>
			</tr>
			<tr>
				<th scope="row" colspan="4" style="text-align: right;">Tax</th>
				<td style="text-align: right;">$<?= number_format($variables['product_taxes'], 2) ?></td>
			</tr>
            
           
			<tr>
				<th scope="row" colspan="3" style="text-align: right;">Shipping</th>
				<td style="text-align: right;">$<?= number_format($variables['cart']['cart']['totals']['shipping'], 2) ?></td>
			</tr>
			<tr>
				<th scope="row" colspan="3" style="text-align: right;">Shipping Tax</th>
				<td style="text-align: right;">$<?= number_format($variables['cart']['cart']['totals']['shipping_tax'], 2) ?></td>
			</tr>
			<tr>
				<th scope="row" colspan="3" style="text-align: right;">Discounts</th>
				<td style="text-align: right;">- $<?= number_format($variables['cart']['cart']['totals']['discounts'], 2) ?></td>
			</tr>
			<tr>
				<th scope="row" colspan="3" style="text-align: right;">Discount Tax</th>
				<td style="text-align: right;">$<?= number_format($variables['cart']['cart']['totals']['discount_tax'], 2) ?></td>
			</tr>
			
			<tr>
				<th scope="row" colspan="4" style="text-align: right;">Grand Total</th>
				<td style="text-align: right;">$<?= number_format($variables['return_grand_total'], 2) ?></td>
			</tr>
			*/
            ?>
		</tfoot>
		
		<tbody>
			<?
			foreach ($variables['products'] as $i => $product) {
				$attributes = '';
				if ($product['attributes'] != '') {
					 $attributes = explode(chr(0x1D), $product['attributes']);
					 $attributes = implode(', ', $attributes);
				}
				
				$exchange_attributes = '';
				
				if ($product['exchange_attribute'] != '') {
					 $exchange_attributes = explode(chr(0x1D), $product['exchange_attribute']);
					 $exchange_attributes = implode(', ', $exchange_attributes);
				}
				
				?>
				<tr>
                	<td style="text-align: left;"><?= $product['product_name'] . ($attributes != '' ? '<br />' . $attributes : '') ?></td>
					<td style="text-align: center;">$<?= number_format($product['product_price'], 2) ?></td>
					<td style="text-align: center;"><?= $product['return_quantity'] ?></td>
					<td style="text-align: right;"><?= $exchange_attributes ?></td>
				</tr>
				<?
			}
			?>
		</tbody>
	</table>
</div>