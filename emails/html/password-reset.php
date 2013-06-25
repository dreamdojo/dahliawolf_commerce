<?
require_once DR . '/emails/html/_config.php';

$_e->p();
?>
Please click or go to the following link to reset your password:
<?
$_e->pp(20);

$_e->p();
?>
<a href="http://<?= $variables['domain'] ?><?= (isset($variables['language']) && !empty($variables['language']) ? '/' . $variables['language']['abbreviation'] : '') ?>/reset-password?email=<?= base64_encode($variables['email']) ?>&key=<?= $variables['key'] ?>">http://<?= $variables['domain'] ?><?= (isset($variables['language']) && !empty($variables['language']) ? '/' . $variables['language']['abbreviation'] : '') ?>/reset-password?email=<?= base64_encode($variables['email']) ?>&key=<?= $variables['key'] ?></a>
<?
$_e->pp(20);
?>