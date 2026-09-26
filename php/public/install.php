<?php

declare(strict_types=1);

/**
 * Web-based installation wizard (WordPress-style): requirements check,
 * database connection + schema setup, owner account creation, writes
 * app/config.php. No shell/CLI access required — this is the whole
 * point, for shared hosting where SSH may not exist.
 *
 * Refuses to run again once app/config.php exists (mirrors
 * src/hooks/restrictUserCreation.ts in the Next.js build: bootstrap
 * must be a one-time, explicit, authenticated action).
 */

require dirname(__DIR__) . '/app/autoload.php';

use App\Core\Database;
use App\Core\Migrator;
use App\Models\SiteSetting;
use App\Models\User;
use App\Security\Slug;

session_start();

$configPath = dirname(__DIR__) . '/app/config.php';

if (is_file($configPath)) {
    http_response_code(403);
    echo '<!doctype html><meta charset="utf-8"><body style="font-family:sans-serif;max-width:640px;margin:60px auto">'
        . '<h1>Already installed</h1>'
        . '<p>This site is already set up. Delete <code>app/config.php</code> manually first if you really need to run the installer again — doing so does not reset your database or media.</p>'
        . '</body>';
    exit;
}

function wizard_csrf_token(): string
{
    if (empty($_SESSION['install_csrf'])) {
        $_SESSION['install_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['install_csrf'];
}

function wizard_csrf_check(): bool
{
    return isset($_POST['csrf']) && isset($_SESSION['install_csrf']) && hash_equals($_SESSION['install_csrf'], $_POST['csrf']);
}

function wizard_render(string $step, array $vars = []): void
{
    extract($vars);
    $csrf = wizard_csrf_token();
    require __DIR__ . '/../app/Views/install/layout.php';
}

// --- Step 0: requirements check --------------------------------------
function check_requirements(): array
{
    $checks = [
        'PHP 8.1 or newer' => version_compare(PHP_VERSION, '8.1.0', '>='),
        'PDO MySQL extension' => extension_loaded('pdo_mysql'),
        'GD extension (image processing)' => extension_loaded('gd'),
        'fileinfo extension (upload validation)' => extension_loaded('fileinfo'),
        'mbstring extension' => extension_loaded('mbstring'),
        'openssl extension' => extension_loaded('openssl'),
        'app/ directory is writable' => is_writable(dirname(__DIR__) . '/app'),
        'storage/uploads/ directory is writable' => is_writable(dirname(__DIR__) . '/storage/uploads'),
    ];
    return $checks;
}

$step = $_POST['step'] ?? 'requirements';

// --- Step: requirements ------------------------------------------------
if ($step === 'requirements' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $checks = check_requirements();
    $allPass = !in_array(false, $checks, true);
    wizard_render('requirements', ['checks' => $checks, 'allPass' => $allPass]);
    exit;
}

// --- Step: database -----------------------------------------------------
if ($step === 'database') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        wizard_render('database', ['error' => null, 'old' => []]);
        exit;
    }

    if (!wizard_csrf_check()) {
        wizard_render('database', ['error' => 'Your session expired. Please try again.', 'old' => []]);
        exit;
    }

    $host = trim((string) ($_POST['db_host'] ?? ''));
    $port = (int) ($_POST['db_port'] ?? 3306);
    $name = trim((string) ($_POST['db_name'] ?? ''));
    $user = trim((string) ($_POST['db_user'] ?? ''));
    $pass = (string) ($_POST['db_pass'] ?? '');
    $old = ['db_host' => $host, 'db_port' => $port, 'db_name' => $name, 'db_user' => $user];

    if ($host === '' || $name === '' || $user === '') {
        wizard_render('database', ['error' => 'Host, database name and user are required.', 'old' => $old]);
        exit;
    }

    try {
        $pdo = Database::connectWithoutDatabase($host, $port, $user, $pass);
        $pdo->exec('CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $name) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
        $pdo->exec('USE `' . str_replace('`', '', $name) . '`');

        $migrator = new Migrator($pdo, dirname(__DIR__) . '/migrations');
        $migrator->migrate();
    } catch (\Throwable $e) {
        wizard_render('database', ['error' => 'Could not connect or set up the database: ' . $e->getMessage(), 'old' => $old]);
        exit;
    }

    $_SESSION['install_db'] = ['host' => $host, 'port' => $port, 'name' => $name, 'user' => $user, 'pass' => $pass];
    wizard_render('site', ['error' => null, 'old' => []]);
    exit;
}

// --- Step: site + owner account ------------------------------------------
if ($step === 'site') {
    if (empty($_SESSION['install_db'])) {
        wizard_render('database', ['error' => 'Please complete the database step first.', 'old' => []]);
        exit;
    }

    if (!wizard_csrf_check()) {
        wizard_render('site', ['error' => 'Your session expired. Please try again.', 'old' => []]);
        exit;
    }

    $baseUrl = rtrim((string) ($_POST['base_url'] ?? ''), '/');
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    $displayName = trim((string) ($_POST['display_name'] ?? 'Hasan')) ?: 'Hasan';
    $old = ['base_url' => $baseUrl, 'email' => $email, 'display_name' => $displayName];

    $errors = [];
    if ($baseUrl === '' || !filter_var($baseUrl, FILTER_VALIDATE_URL)) {
        $errors[] = 'Enter a valid site URL, e.g. https://example.com';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (mb_strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }
    $isProduction = !str_contains($baseUrl, 'localhost') && !str_contains($baseUrl, '127.0.0.1');
    if ($isProduction && !str_starts_with($baseUrl, 'https://')) {
        $errors[] = 'Use https:// for a production site URL. (http:// is only accepted for localhost.)';
    }

    if ($errors !== []) {
        wizard_render('site', ['error' => implode(' ', $errors), 'old' => $old]);
        exit;
    }

    $db = $_SESSION['install_db'];
    $config = [
        'app' => [
            'env' => $isProduction ? 'production' : 'development',
            'base_url' => $baseUrl,
            'secret_key' => bin2hex(random_bytes(48)),
        ],
        'db' => [
            'host' => $db['host'],
            'port' => $db['port'],
            'name' => $db['name'],
            'user' => $db['user'],
            'pass' => $db['pass'],
            'charset' => 'utf8mb4',
        ],
        'mail' => [
            'driver' => 'mail',
            'smtp_host' => '',
            'smtp_port' => 587,
            'smtp_secure' => 'tls',
            'smtp_user' => '',
            'smtp_pass' => '',
            'from_address' => 'no-reply@' . (parse_url($baseUrl, PHP_URL_HOST) ?: 'localhost'),
            'from_name' => 'Hasan Journal',
        ],
    ];

    $php = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";
    if (file_put_contents($configPath, $php) === false) {
        wizard_render('site', ['error' => 'Could not write app/config.php. Check that app/ is writable.', 'old' => $old]);
        exit;
    }
    chmod($configPath, 0600);

    // Reconnect using the freshly written config so every subsequent
    // call in this request goes through the real app stack.
    Database::setConnection(new \PDO(
        "mysql:host={$db['host']};port={$db['port']};dbname={$db['name']};charset=utf8mb4",
        $db['user'],
        $db['pass'],
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC],
    ));

    (new SiteSetting())->seedDefaults();

    try {
        (new User())->create($email, $password, $displayName);
    } catch (\Throwable $e) {
        wizard_render('site', ['error' => 'Could not create the owner account: ' . $e->getMessage(), 'old' => $old]);
        exit;
    }

    unset($_SESSION['install_db'], $_SESSION['install_csrf']);
    wizard_render('done', ['baseUrl' => $baseUrl]);
    exit;
}

wizard_render('requirements', ['checks' => check_requirements(), 'allPass' => !in_array(false, check_requirements(), true)]);
