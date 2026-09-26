<?php
// Router script for PHP's built-in dev server only (`php -S`), emulating
// the .htaccess rewrite rules. Never used on real Apache/LiteSpeed
// hosting — this file is not part of the deployed application logic.
$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $path;
if ($path !== '/' && is_file($file)) {
    return false;
}
require __DIR__ . '/index.php';
