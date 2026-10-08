<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

$eventId = $_GET['event'] ?? '';
$pdo = get_pdo();
$stmt = $pdo->prepare('
    SELECT e.*, u.name AS user_name
    FROM events e
    LEFT JOIN users u ON u.id = e.user_id
    WHERE e.id = ?
');
$stmt->execute([$eventId]);
$event = $stmt->fetch();

if (!$event) {
    http_response_code(404);
    render_error('Nie znaleziono zdarzenia.');
    exit;
}

if (!can_print_event($user, $event)) {
    http_response_code(403);
    render_error('Nie można wydrukować tego wniosku (brak uprawnień albo urlop już się zakończył).');
    exit;
}

$group = null;
if ($event['group_id']) {
    $stmt = $pdo->prepare('SELECT * FROM groups_ WHERE id = ?');
    $stmt->execute([$event['group_id']]);
    $group = $stmt->fetch() ?: null;
}

$startFormatted = date('d.m.Y', strtotime($event['start_date']));
$endFormatted = date('d.m.Y', strtotime($event['end_date']));
$days = (strtotime($event['end_date']) - strtotime($event['start_date'])) / 86400 + 1;
$daysFormatted = format_days($days);

$type = $event['type'];
$boxWypoczynkowy = $type === 'wypoczynkowy';
$boxNaZadanie = $type === 'na_zadanie';
$boxInne = !$boxWypoczynkowy && !$boxNaZadanie;
$inneLabel = $boxInne ? event_type_label($type) : '';

$companyName = get_setting('print_company_name', '');
$printCity = get_setting('print_city', '');
$todayFormatted = date('d.m.Y');
$logoFile = 'assets/img/logo-portos.png';
$logoExists = is_file(__DIR__ . '/' . $logoFile);

$pageTitle = 'Wydruk wniosku urlopowego';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?= e($pageTitle) ?></title>
<style>
  @page { size: 210mm 148mm; margin: 10mm; }
  * { box-sizing: border-box; }
  body { font-family: Arial, Helvetica, sans-serif; font-size: 12pt; color: #111; margin: 0; padding: 10mm; }
  .row { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 7mm; gap: 10mm; }
  .field { border-bottom: 1px dotted #333; min-width: 55mm; display: inline-block; padding: 0 2px 2px; }
  .label-small { font-size: 8.5pt; color: #555; display: block; }
  .logo { height: 20mm; display: block; }
  .company { font-weight: bold; font-size: 13pt; text-align: right; }
  .intro { margin: 4mm 0 2mm; }
  .types { margin: 2mm 0 4mm; }
  .type-row { display: flex; align-items: center; gap: 6px; margin-bottom: 2mm; }
  .box { display: inline-flex; align-items: center; justify-content: center; width: 4mm; height: 4mm; border: 1px solid #333; font-size: 10pt; line-height: 1; flex: none; }
  .dates-row { display: flex; gap: 10mm; margin: 4mm 0; flex-wrap: wrap; }
  .sig-row { display: flex; justify-content: space-between; margin-top: 7mm; gap: 10mm; }
  .no-print { margin-bottom: 8px; }
  .no-print button { margin-right: 6px; }
  @media print { .no-print { display: none; } body { padding: 0; } }
</style>
</head>
<body>
<div class="no-print">
  <button type="button" onclick="window.print()">Drukuj</button>
  <button type="button" onclick="window.close()">Zamknij</button>
</div>

<div class="row">
  <div>
    <span class="field"><?= e($event['user_name'] ?? '') ?></span>
    <span class="label-small">(Imię i nazwisko pracownika)</span>
  </div>
  <div>
    <?= e($printCity) ?><?= $printCity !== '' ? ', ' : '' ?>dnia <span class="field" style="min-width:20mm;"><?= e($todayFormatted) ?></span>
  </div>
</div>

<div class="row">
  <div>
    <span class="field"><?= e($group ? $group['name'] : '') ?></span>
    <span class="label-small">(Dział)</span>
  </div>
  <?php if ($logoExists): ?>
    <img class="logo" src="<?= e($logoFile) ?>" alt="<?= e($companyName !== '' ? $companyName : 'Logo firmy') ?>" />
  <?php elseif ($companyName !== ''): ?>
    <div class="company"><?= e($companyName) ?></div>
  <?php endif; ?>
</div>

<p class="intro">Proszę o udzielenie mi urlopu:</p>
<div class="types">
  <div class="type-row"><span class="box"><?= $boxNaZadanie ? '&times;' : '' ?></span> Na żądanie</div>
  <div class="type-row"><span class="box"><?= $boxWypoczynkowy ? '&times;' : '' ?></span> Wypoczynkowego</div>
  <div class="type-row"><span class="box"></span> Okolicznościowego</div>
  <div class="type-row">
    <span class="box"><?= $boxInne ? '&times;' : '' ?></span> Inne
    <span class="field" style="min-width:40mm;"><?= $boxInne ? e($inneLabel) : '' ?></span>
  </div>
</div>

<div class="dates-row">
  <div>Od dnia <span class="field"><?= e($startFormatted) ?></span></div>
  <div>Do dnia <span class="field"><?= e($endFormatted) ?></span></div>
  <div>Ilość dni <span class="field" style="min-width:12mm;"><?= e($daysFormatted) ?></span></div>
</div>

<p>Zastępstwo pełni: <span class="field" style="min-width:60mm;"></span></p>

<div class="sig-row">
  <div>Akceptuję: <span class="field" style="min-width:35mm;"></span></div>
  <div>Podpis pracownika: <span class="field" style="min-width:35mm;"></span></div>
</div>

</body>
</html>
