<?
if (!empty($_e)) {
	$_e = new EmailHtml();
	$_e->websiteUrl = isset($domain) ? 'http://' . $domain : '';
	$_e->imageDir = isset($domain) ? 'http://' . $domain . '/images' : '';
	$_e->bgColor = 'f2f2f2';
	$_e->color = '545454';
	$_e->anchorColor = '3366cc';
	$_e->fontSize = '13';
	$_e->lineHeight = '20';
	$_e->fontFamily = 'Arial, Helvetica, sans-serif';
}
?>