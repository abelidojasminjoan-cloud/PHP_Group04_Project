<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= saEsc($pageTitle) ?> | PureVia Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&amp;display=swap" rel="stylesheet"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<?php foreach(['sidemenu','responsive','form2','admin-skin'] as $css):?><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= $css ?>.css"><?php endforeach; ?>
<?php foreach($saStyles??[] as $css):?><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/<?= saEsc($css) ?>.css"><?php endforeach; ?>
</head><body><?php include __DIR__.'/../../includes/sidemenu.php'; ?><div class="admin-layout sa-layout"><header class="admin-topbar"><p class="admin-topbar-title"><?= saEsc($pageTitle) ?></p></header><main class="<?= saEsc($saMainClass??'sa-page') ?>">
