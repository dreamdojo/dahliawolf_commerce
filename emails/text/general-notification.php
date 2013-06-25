<?
if (isset($variables['general_description'])) {
	?><?= $variables['general_description'] ?>
	
	<?
	unset($variables['general_description']);
}
?>Info:

<?
foreach ($variables as $key => $value) {
	?><?= uncleanUrl($key, '_') ?>: <?= $value ?>

<?
}
?>