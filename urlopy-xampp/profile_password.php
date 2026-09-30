<?php
require_once __DIR__ . '/bootstrap.php';
$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

$currentPassword = $_POST['currentPassword'] ?? '';
$newPassword = $_POST['newPassword'] ?? '';

if (!password_verify($currentPassword, $user['password_hash'])) {
    header('Location: profile.php?error=' . urlencode('Aktualne haslo jest nieprawidlowe.'));
    exit;
}
if (strlen($newPassword) < 6) {
    header('Location: profile.php?error=' . urlencode('Nowe haslo musi miec co najmniej 6 znakow.'));
    exit;
}

$pdo = get_pdo();
$stmt = $pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?');
$stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $user['id']]);

header('Location: profile.php?saved=1');
exit;
