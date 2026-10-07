<?php
if (!defined('APP_BOOTSTRAPPED')) { http_response_code(403); exit; }
$pageTitle = $pageTitle ?? 'Planer urlopów';
$containerClass = $containerClass ?? 'container';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= e($pageTitle) ?></title>
  <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="<?= e($containerClass) ?>">
