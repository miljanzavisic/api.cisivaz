<?php
// ============================================
// DATABASE CONFIGURATION
// ============================================
// ABSPATH se definiše u index.php pre ovoga

// Error reporting - isključeno u produkciji
error_reporting(0);
ini_set('display_errors', 0);

// Kredencijali za bazu
define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'footballrs_api');
define('DB_USER', 'footballrs');
define('DB_PASS', '0trust@cpanel');

// Putanje
define('PLUGINS_DIR', ABSPATH . 'plugins');
define('SITE_URL', 'https://api.cisivaz.com');

// ============================================
// DATABASE CONNECTION
// ============================================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $db = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($db->connect_error) {
        throw new Exception('Connection failed');
    }
    $GLOBALS['db'] = $db;
    $db->set_charset('utf8mb4');
} catch (Exception $e) {
    // Sakrivena poruka - ne otkriva detalje napadaču
    http_response_code(500);
    die('Service temporarily unavailable.');
}

// ============================================
// SECURITY HEADERS
// ============================================
header('X-Frame-Options: DENY');                              // Sprečava clickjacking
header('X-Content-Type-Options: nosniff');                    // Sprečava MIME sniffing
header('X-XSS-Protection: 1; mode=block');                    // XSS filter u browseru
header('Referrer-Policy: strict-origin-when-cross-origin');   // Ograničava referrer info

// ============================================
// SESSION CONFIGURATION
// ============================================
ini_set('session.cookie_httponly', 1);        // Cookie nije dostupan JavaScript-u
ini_set('session.cookie_secure', 1);          // Samo preko HTTPS (sajt ima SSL)
ini_set('session.use_strict_mode', 0);        // Fleksibilniji mod za sesije
ini_set('session.cookie_samesite', 'Lax');    // Lax=dozvoljava linkove sa drugih sajtova
ini_set('session.cookie_path', '/');          // Cookie dostupan na celom sajtu
ini_set('session.cookie_domain', '.api.cisivaz.com'); // Domen za cookie

session_start();

// ============================================
// CSRF PROTECTION - zaštita od Cross-Site Request Forgery
// ============================================
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ============================================
// RATE LIMITING - zaštita od brute force napada
// ============================================
if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt_time'] = time();
}

define('MAX_LOGIN_ATTEMPTS', 5);     // Maksimalan broj pokušaja
define('LOCKOUT_TIME', 900);         // Vreme blokade u sekundama (15 minuta)