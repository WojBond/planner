<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$color = $_POST['color'] ?? '';
if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
    header('Location: profile.php?error=' . urlencode('Nieprawidlowy format koloru.'));
    exit;
}

$pdo = get_pdo();
$stmt = $pdo->prepare('UPDATE users SET color = ? WHERE id = ?');
$stmt->execute([$color, $user['id']]);

header('Location: profile.php?saved=1');
exit;
