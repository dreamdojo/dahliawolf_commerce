<?
$customerName = $variables['first_name'] == '' ? 'Customer' : $variables['first_name'];
$domain = isset($variables['domain']) ? $variables['domain'] : DOMAIN;
$siteName = isset($variables['site_name']) ? $variables['site_name'] : SITENAME;

//require_once DR . '/php/email-html.php';
require DR . '/emails/html/_config.php';
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0" style="border-spacing: 0; border-collapse: collapse; font: <?= $_e->fontSize ?>px/<?= $_e->lineHeight ?>px <?= $_e->fontFamily ?>; color: #<?= $_e->color ?>; -webkit-text-size-adjust: none;" align="center" bgcolor="#<?= $_e->bgColor ?>">
	<tr>
		<td valign="top">
			<?
			$_e->verticalSpacer(20);
			?>
			<table width="600" align="center" cellpadding="0" cellspacing="0" bgcolor="#ffffff" style="border: 1px solid #e5e5e5;">
				<tr>
					<td valign="top">
						<?
						// Header
						$_e->verticalSpacer(5);
						?>
						<table width="100%" border="0" cellpadding="5" cellspacing="0" style="border-bottom: 1px dotted #d9d9d9;">
							<tr>
								<td width="5">&nbsp;</td>
								<td valign="top" height="110">
									<a href="<?= $_e->websiteUrl ?>" target="_blank"><img src="<?= $_e->imageDir ?>/logo.png" alt="" border="0" style="display: block;" /></a>
								</td>
								<td width="5">&nbsp;</td>
							</tr>
						</table>
						<?
						$_e->verticalSpacer(5);
						?>
						
						
						<table width="100%" border="0" cellpadding="20" cellspacing="0">
							<tr>
								<td valign="top">
									<?
									$_e->p();
									?>
									Dear <?= $customerName ?>,
									<?
									$_e->pp(20);
									?>
									
									<?= $variables['html_body'] ?>
									
									<?
									$_e->verticalSpacer(10);
									$_e->p();
									?>
									Thank you!
									<?
									$_e->pp(10);
									$_e->p(
										array(
											'font-weight' => 'bold'
											, 'color' => '000000'
										)
									);
									?>
									The <?= $siteName ?> Team
									<?
									$_e->pp();
									?>									
								</td>
							</tr>
						</table>
						
					</td>
				</tr>
			</table>
			<table width="600" align="center" cellpadding="10" cellspacing="0">
				<tr>
					<td>
						<?
						/*
						$_e->p(array('font-size' => '11'
									)
								);
						?>
						You are currently subscribed to our mailing list.
						<?
						$_e->a('http://' . $domain . '/mailing-list-unsubscribe?email=' . base64_encode($variables['email']) . '&amp;key=' . $variables['key'], 'Click here');
						?>
						to unsubscribe.</div>
						<?
						$_e->pp();
						*/
						?>		
					</td>
				</tr>
			</table>
			<?
			$_e->verticalSpacer(20);
			?>
		</td>
	</tr>
</table>
