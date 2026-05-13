<?php
// ============================================
// Sprečiti direktan pristup fajlu
// ============================================
if (!defined('ABSPATH')) exit;

// ============================================
// OUTPUT ESCAPING (XSS zaštita)
// ============================================

/**
 * Escapuje string za bezbedan HTML output
 * Koristi se za sve podatke koji se prikazuju u HTML-u
 * 
 * @param string $string  Neobezbeđen string
 * @return string         String bezbedan za HTML output
 */
function esc_html($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Escapuje string za bezbedno korišćenje u HTML atributima
 * 
 * @param string $string  Neobezbeđen string
 * @return string         String bezbedan za HTML atribut
 */
function esc_attr($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// ============================================
// KORISNIČKA IP ADRESA
// ============================================

/**
 * Detektuje pravu IP adresu korisnika
 * Proverava proxy i CloudFlare zaglavlja pre REMOTE_ADDR
 * 
 * @return string  Validirana IP adresa ili '0.0.0.0' ako nije validna
 */
function get_user_ip() {
    // Proxy zaglavlja (CloudFlare, nginx, itd.)
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP);
        if ($ip) return $ip;
    }
    
    // X-Forwarded-For može sadržati više IP adresa - uzimamo prvu
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = filter_var(trim($ips[0]), FILTER_VALIDATE_IP);
        if ($ip) return $ip;
    }
    
    // Standardna IP adresa
    return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

// ============================================
// VREMENSKE FUNKCIJE (lokalizacija za plugine)
// ============================================

/**
 * Vraća vremensku zonu korisnika
 * Zona se detektuje iz browsera i čuva u sesiji
 * 
 * @return string  Vremenska zona (npr. 'Europe/Belgrade') ili 'UTC' kao fallback
 */
function get_user_timezone() {
    return $_SESSION['timezone'] ?? 'UTC';
}

/**
 * Formatira vreme u korisničkoj vremenskoj zoni
 * Osnovna funkcija koju pluginovi treba da koriste za prikaz vremena
 * 
 * @param string    $format     PHP date format (default: 'H:i:s')
 * @param int|null  $timestamp  Unix timestamp ili null za trenutno vreme
 * 
 * @return string  Formatirano vreme u korisničkoj zoni
 */
function core_time($format = 'H:i:s', $timestamp = null) {
    $timestamp = $timestamp ?? time();
    $tz = new DateTimeZone(get_user_timezone());
    $dt = new DateTime('@' . $timestamp);
    $dt->setTimezone($tz);
    return $dt->format($format);
}

/**
 * Formatira datum u korisničkoj vremenskoj zoni
 * Plugin shortcut za core_time sa defaultnim datumskim formation
 * 
 * @param string    $format     Format datuma (default: 'd.m.Y')
 * @param int|null  $timestamp  Unix timestamp ili null za trenutno vreme
 * 
 * @return string  Formatiran datum
 */
function core_date($format = 'd.m.Y', $timestamp = null) {
    return core_time($format, $timestamp);
}

/**
 * Formatira datum i vreme u korisničkoj vremenskoj zoni
 * Plugin shortcut za core_time sa defaultnim datum+vreme formation
 * 
 * @param string    $format     Format (default: 'd.m.Y. H:i:s')
 * @param int|null  $timestamp  Unix timestamp ili null za trenutno vreme
 * 
 * @return string  Formatiran datum i vreme
 */
function core_datetime($format = 'd.m.Y. H:i:s', $timestamp = null) {
    return core_time($format, $timestamp);
}

// ============================================
// VALIDACIJA INPUTA
// ============================================

/**
 * Validira email adresu
 * Koristi PHP-ov filter_var sa FILTER_VALIDATE_EMAIL
 * 
 * @param string $email  Email adresa za proveru
 * @return bool          True ako je validan email
 */
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validira username
 * Dozvoljeni karakteri: slova (a-z, A-Z), brojevi (0-9), donja crta (_)
 * Dužina: 3-50 karaktera
 * 
 * @param string $username  Korisničko ime za proveru
 * @return bool             True ako je validan username
 */
function validate_username($username) {
    return preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username) === 1;
}

/**
 * Validira dužinu lozinke
 * Minimum 8 karaktera - proširiti po potrebi (velika slova, brojevi, simboli)
 * 
 * @param string $password  Lozinka za proveru
 * @return bool             True ako lozinka ima 8+ karaktera
 */
function validate_password($password) {
    return strlen($password) >= 8;
}

// ============================================
// JSON RESPONSE
// ============================================

/**
 * Šalje JSON odgovor i prekida izvršavanje skripte
 * Koristi se za sve AJAX endpoint-e
 * 
 * @param mixed $data    Podaci za JSON enkodiranje
 * @param int   $status  HTTP status kod (default: 200 OK)
 */
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// AJAX DETEKCIJA
// ============================================

/**
 * Proverava da li je trenutni zahtev AJAX
 * Proverava X-Requested-With zaglavlje koje šalje jQuery/Fetch
 * 
 * @return bool  True ako je AJAX zahtev
 */
function is_ajax() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
