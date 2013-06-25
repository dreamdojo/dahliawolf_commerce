<?
require_once DR . '/emails/html/_config.php';
?>
<div style="font-size: <?= $_e->fontSize ?>px; line-height: <?= $_e->lineHeight ?>px; font-family: <?= $_e->fontFamily ?>; color: <?= $_e->color ?>">
	<p>Thank you for placing your order.</p>
	
	<table width="100%" style="border-collapse: collapse; margin: 20px 0;">
		<thead>
			<tr>
				<th scope="col" style="text-align: left;" width="52%">Product Name</th>
				<th scope="col" style="text-align: right;" width="16%">Unit Price</th>
				<th scope="col" style="text-align: center;" width="16%">Quantity</th>
				<th scope="col" style="text-align: right;" width="16%">Subtotal</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th scope="row" colspan="3" style="text-align: right;">Subtotal</th>
				<td style="text-align: right;">$<?= number_format($variables['cart']['cart']['totals']['products'], 2) ?></td>
			</tr>
			<tr>
				<th scope="row" colspan="3" style="text-align: right;">Tax</th>
				<td style="text-align: right;">$<?= number_format($variables['cart']['cart']['totals']['product_tax'], 2) ?></td>
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
				<th scope="row" colspan="3" style="text-align: right;">Grand Total</th>
				<td style="text-align: right;">$<?= number_format($variables['cart']['cart']['totals']['grand_total'], 2) ?></td>
			</tr>
		</tfoot>
		<tbody>
			<?
			foreach ($variables['cart']['products'] as $i => $product) {
				?>
				<tr>
					<td style="text-align: left;"><?= $product['product_info']['product_lang_name'] ?></td>
					<td style="text-align: right;">$<?= number_format($product['product_info']['price'], 2) ?></td>
					<td style="text-align: center;"><?= $product['quantity'] ?></td>
					<td style="text-align: right;">$<?= number_format($product['quantity'] * $product['product_info']['price'], 2) ?></td>
				</tr>
				<?
			}
			?>
		</tbody>
	</table>
</div>