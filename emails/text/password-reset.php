Please click or go to the following link to reset your password:

http://<?= $variables['domain'] ?><?= (isset($variables['language']) && !empty($variables['language']) ? '/' . $variables['language']['abbreviation'] : '') ?>/reset-password?email=<?= base64_encode($variables['email']) ?>&key=<?= $variables['key'] ?>