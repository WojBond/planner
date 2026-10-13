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
  <link rel="manifest" href="manifest.json" />
  <link rel="icon" href="assets/img/icon-192.png" />
  <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png" />
  <meta name="theme-color" content="#2563eb" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-status-bar-style" content="default" />
  <meta name="apple-mobile-web-app-title" content="Urlopy" />
</head>
<body>
<?php include __DIR__ . '/nav.php'; ?>
<div class="<?= e($containerClass) ?>">
