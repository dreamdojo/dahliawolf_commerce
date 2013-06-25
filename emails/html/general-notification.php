<?
if (isset($variables['general_description'])) {
	?>
	<p><?= $variables['general_description'] ?></p>
    <?
	unset($variables['general_description']);
}
?>

<table border="0" cellpadding="0" cellspacing="0" style="font-size: 14px; font-family: Arial, Helvetica, sans-serif;">
	<tr>
		<th align="right" valign="top" style="padding: 4px 0; text-align: right;">Info:</th>
		<td align="left" valign="top" style="padding: 4px 0 4px 8px;"></td>
	</tr>
	<?
	foreach ($variables as $th => $td) {
		?>
		<tr>
			<th align="right" valign="top" style="padding: 4px 0; text-align: right;"><?= uncleanUrl($th, '_') ?>:</th>
			<td align="left" valign="top" style="padding: 4px 0 4px 8px;"><?= $td ?></td>
		</tr>
		<?
	}
	?>
</table>