<?php
define('APP_BOOTSTRAPPED', true);
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/event_types.php';
require_once __DIR__ . '/includes/settings.php';
require_once __DIR__ . '/includes/mailer.php';

start_app_session();
