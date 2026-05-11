<?php
// Auto-detect protocol
if ((isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) ||
    (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
    (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on') ||
    (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) {
    $protocol = 'https://';
} else {
    $protocol = 'http://';
}

// HTTP
define('HTTP_SERVER', $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/');

// HTTPS
define('HTTPS_SERVER', $protocol . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/');

// DIR
define('DIR_APPLICATION', __DIR__ . '/catalog/');
define('DIR_SYSTEM', __DIR__ . '/system/');
define('DIR_IMAGE', __DIR__ . '/image/');
define('DIR_STORAGE', dirname(__DIR__) . '/storage/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
require_once DIR_CONFIG . 'database.php';
