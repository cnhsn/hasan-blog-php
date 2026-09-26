<?php

declare(strict_types=1);

use App\Core\Config;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;

require __DIR__ . '/autoload.php';
require __DIR__ . '/helpers.php';

date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

if (!Config::isInstalled()) {
    header('Location: /install.php');
    exit;
}

if (Config::isProduction()) {
    error_reporting(0);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

set_exception_handler(static function (Throwable $e): void {
    error_log('[hasan-journal] Uncaught: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (Config::isProduction()) {
        echo 'Something went wrong. Please try again later.';
    } else {
        echo '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
    }
});

View::init(dirname(__DIR__) . '/app/Views');
Session::start();
Response::securityHeaders();
