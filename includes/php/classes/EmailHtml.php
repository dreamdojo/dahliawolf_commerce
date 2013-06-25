<?
class EmailHtml {
	public $websiteUrl = '';
	public $imageDir = '';
	
	// Main email body
	public $bgColor = 'ffffff';
	public $width = 600;
	public $align = 'left';
	
	// Font
	// Use pixel sizes
	public $color = '545454';
	public $anchorColor = '3366cc';
	public $fontSize = '13';
	public $lineHeight = '20';
	public $fontFamily = 'Arial, Helvetica, sans-serif';
	
	// Start a paragraph
	public function p($styles = array(), $customStyle = '') {
		$fontSize = !empty($styles['font-size']) ? $styles['font-size'] : $this->fontSize;
		$lineHeight = !empty($styles['line-height']) ? $styles['line-height'] : $this->lineHeight;
		$fontFamily = !empty($styles['font-family']) ? $styles['font-family'] : $this->fontFamily;
		$color = !empty($styles['color']) ? $styles['color'] : $this->color;
		
		$fontWeight = !empty($styles['font-weight']) ? $styles['font-weight'] : 'normal';
		$fontStyle = !empty($styles['font-style']) ? $styles['font-style'] : 'normal';
		$textAlign = !empty($styles['text-align']) ? $styles['text-align'] : 'left';
		?><div style="font-size: <?= $fontSize ?>px; line-height: <?= $lineHeight ?>px; font-family: <?= $fontFamily ?>; font-weight: <?= $fontWeight ?>; font-style: <?= $fontStyle ?>; color: #<?= $color ?>; text-align: <?= $textAlign ?>;<?= !empty($customStyle) ? ' ' . $customStyle : '' ?>"><?
	}
	
	// End a paragraph
	public function pp($marginBottom = 0) {
		?></div><?
		if ($marginBottom > 0) {
			$this->verticalSpacer($marginBottom);
		}
	}
	
	// Anchor link
	public function a($url, $text, $anchorColor = '', $customStyle = '') {
		$anchorColor = empty($anchorColor) ? $this->anchorColor : $anchorColor;
		?><a href="<?= $url ?>" target="_blank" style="color: #<?= $anchorColor ?>;<?= !empty($customStyle) ? ' ' . $customStyle : '' ?>"><span style="color: #<?= $anchorColor ?>;"><?= $text ?></span></a><?
	}
	
	// Image
	public function img($src, $alt, $width, $height) {
		if (strpos($src, 'http://') !== 0) {
			$src = $this->imageDir . '/' . $src;
		}
		?><img src="<?= $src ?>" alt="<?= $alt ?>" border="0" style="dislay: block;" width="<?= $width ?>" height="<?= $height ?>" /><?
	}
	
	// Image link
	public function aimg($url, $src, $alt, $width, $height) {
		ob_start();
		$this->img($src, $alt, $width, $height);
		$text = ob_get_contents();
		ob_end_clean();
		$this->a($url, $text);
	}
	
	// Vertical spacer
	public function verticalSpacer($height) {
		?><table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td height="<?= $height ?>"></td></tr></table><?
	}
	
	public function colorBlock($width, $height, $color) {
		?><table width="<?= $width ?>" border="0" cellpadding="0" cellspacing="0" bgcolor="#<?= $color ?>"><tr><td valign="top" height="<?= $height ?>"></td></tr></table><?
	}
}
?>