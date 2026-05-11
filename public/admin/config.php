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

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// HTTP
define('HTTP_SERVER', $protocol . $host . '/admin/');
define('HTTP_CATALOG', $protocol . $host . '/');

// HTTPS
define('HTTPS_SERVER', $protocol . $host . '/admin/');
define('HTTPS_CATALOG', $protocol . $host . '/');

// DIR
define('DIR_APPLICATION', __DIR__ . '/');
define('DIR_SYSTEM', dirname(__DIR__) . '/system/');
define('DIR_IMAGE', dirname(__DIR__) . '/image/');
define('DIR_STORAGE', dirname(__DIR__, 2) . '/storage/');
define('DIR_CATALOG', dirname(__DIR__) . '/catalog/');
define('DIR_LANGUAGE', DIR_APPLICATION . 'language/');
define('DIR_TEMPLATE', DIR_APPLICATION . 'view/template/');
define('DIR_CONFIG', DIR_SYSTEM . 'config/');
define('DIR_CACHE', DIR_STORAGE . 'cache/');
define('DIR_DOWNLOAD', DIR_STORAGE . 'download/');
define('DIR_LOGS', DIR_STORAGE . 'logs/');
define('DIR_MODIFICATION', DIR_STORAGE . 'modification/');
define('DIR_SESSION', DIR_STORAGE . 'session/');
define('DIR_UPLOAD', DIR_STORAGE . 'upload/');

// DB
require_once DIR_CONFIG . 'database.php';

// OpenCart API
define('OPENCART_SERVER', 'https://www.opencart.com/');
define('OPENCARTFORUM_SERVER', 'https://opencartforum.com/');
