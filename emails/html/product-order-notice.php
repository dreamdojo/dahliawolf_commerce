<?
require_once DR . '/emails/html/_config.php';
?>
<div style="font-size: <?= $_e->fontSize ?>px; line-height: <?= $_e->lineHeight ?>px; font-family: <?= $_e->fontFamily ?>; color: <?= $_e->color ?>">
	<p>An order has been placed on your product: <?= $variables['product_name'] ?>.</p>
</div>