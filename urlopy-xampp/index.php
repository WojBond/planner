<?php
require_once __DIR__ . '/bootstrap.php';
header('Location: ' . (current_user() ? 'calendar.php' : 'login.php'));
exit;
