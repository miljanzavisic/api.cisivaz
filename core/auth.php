<?php
// ============================================
// Sprečiti direktan pristup fajlu
// ============================================
if (!defined('ABSPATH')) exit;

// ============================================
// REGISTRACIJA KORISNIKA
// ============================================

/**
 * Registruje novog korisnika u bazi
 * 
 * @param string $username  Korisničko ime (3-50 karaktera, slova, brojevi, _)
 * @param string $email     Email adresa (mora biti validna)
 * @param string $password  Lozinka (minimum 8 karaktera)
 * 
 * @return array ['success' => bool, 'message' => string]
 */
function register_user($username, $email, $password) {
    $db = $GLOBALS['db'];
    
    // Validacija inputa - sve provere pre upita u bazu
    if (!validate_username($username)) {
        return ['success' => false, 'message' => 'Username must be 3-50 chars, letters, numbers, underscores'];
    }
    
    if (!validate_email($email)) {
        return ['success' => false, 'message' => 'Invalid email format'];
    }
    
    if (!validate_password($password)) {
        return ['success' => false, 'message' => 'Password must be at least 8 characters'];
    }
    
    // Provera da li korisnik već postoji (username ili email)
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->close();
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    $stmt->close();
    
    // Hashovanje lozinke - bcrypt sa cost faktorom 12
    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    // Unos korisnika u bazu
    $stmt = $db->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $hashed);
    $stmt->execute();
    $user_id = $db->insert_id;
    $stmt->close();
    
    // Hook: obaveštava pluginove o novoj registraciji
    do_action('after_user_register', $user_id, $username, $email);
    
    return ['success' => true, 'message' => 'Registration successful'];
}

// ============================================
// LOGIN KORISNIKA
// ============================================

/**
 * Loguje korisnika na sistem
 * 
 * Prihvata username ili email. Proverava rate limiting pre pokušaja.
 * Nakon uspešnog logina regeneriše session ID (session fixation zaštita).
 * 
 * @param string $username  Korisničko ime ILI email
 * @param string $password  Lozinka
 * 
 * @return array ['success' => bool, 'message' => string, 'user_id' => int|null]
 */
function login_user($username, $password) {
    $db = $GLOBALS['db'];
    
    // Rate limiting - provera da li je korisnik blokiran
    if ($_SESSION['login_attempts'] >= MAX_LOGIN_ATTEMPTS) {
        $time_left = LOCKOUT_TIME - (time() - $_SESSION['last_attempt_time']);
        if ($time_left > 0) {
            return [
                'success' => false, 
                'message' => "Too many attempts. Try again in {$time_left} seconds."
            ];
        }
        // Istekao period blokade - resetuj brojač
        $_SESSION['login_attempts'] = 0;
    }
    
    $username = trim($username);
    
    // Potraga za korisnikom po username ILI email
    $stmt = $db->prepare("SELECT id, username, password FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Korisnik nije pronađen - povećaj brojač pokušaja
    if ($result->num_rows === 0) {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        $stmt->close();
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Provera lozinke - koristi bcrypt
    if (!password_verify($password, $user['password'])) {
        $_SESSION['login_attempts']++;
        $_SESSION['last_attempt_time'] = time();
        return ['success' => false, 'message' => 'Invalid credentials'];
    }
    
    // Uspešan login - resetuj brojač pokušaja
    $_SESSION['login_attempts'] = 0;
    
    // Zaštita od session fixation: regeneriši ID pre postavljanja podataka
    session_regenerate_id(false);
    
    // Postavljanje sesijskih varijabli
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['login_time'] = time();
    $_SESSION['user_ip'] = get_user_ip();
    
    // Hook: obaveštava pluginove o uspešnom loginu
    do_action('after_user_login', $user['id'], $user['username']);
    
    return [
        'success' => true, 
        'message' => 'Login successful', 
        'user_id' => $user['id']
    ];
}

// ============================================
// PROVERA SESIJE
// ============================================

/**
 * Proverava da li je korisnik trenutno ulogovan
 * 
 * @return bool True ako sesija sadrži user_id
 */
function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

// ============================================
// PODACI O TRENUTNOM KORISNIKU
// ============================================

/**
 * Vraća podatke o trenutno ulogovanom korisniku iz baze
 * 
 * @return array|null Asocijativni niz sa id, username, email, created_at ili null
 */
function get_current_user_data() {
    $db = $GLOBALS['db'];
    
    if (!is_user_logged_in()) return null;
    
    $stmt = $db->prepare("SELECT id, username, email, created_at FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    return $user;
}

// ============================================
// LOGOUT
// ============================================

/**
 * Izloguje korisnika - briše sesiju i session cookie
 * 
 * @return array ['success' => bool, 'message' => string]
 */
function logout_user() {
    // Hook: obaveštava pluginove pre logout-a
    do_action('before_user_logout', $_SESSION['user_id'] ?? null);
    
    // Prazni sesiju
    $_SESSION = [];
    
    // Briši session cookie iz browsera
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),          // Ime cookie-a (obično PHPSESSID)
            '',                      // Prazna vrednost
            time() - 42000,          // Datum u prošlosti = brisanje
            $params["path"],         // Putanja
            $params["domain"],       // Domen
            $params["secure"],       // Secure flag
            $params["httponly"]      // HttpOnly flag
        );
    }
    
    // Uništava sesiju na serveru
    session_destroy();
    
    return ['success' => true, 'message' => 'Logged out'];
}
