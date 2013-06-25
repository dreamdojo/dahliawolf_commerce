Dear <?= $variables['first_name'] ?>,

<?= $variables['text_body'] ?>

Thank you!

The <?= isset($variables['site_name']) ? $variables['site_name'] : SITENAME ?> Team
<?
/*
You are currently subscribed to our mailing list. To unsubscribe, please click or go to: http://<?= isset($variables['domain']) ? $variables['domain'] : DOMAIN ?>/mailing-list-unsubscribe?email=<?= base64_encode($variables['email']) ?>&key=<?= $variables['key'] ?>.
*/
?>