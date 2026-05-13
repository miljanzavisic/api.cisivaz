<?php
// ============================================
// ERROR REPORTING (isključeno u produkciji)
// ============================================
error_reporting(0);
ini_set('display_errors', 0);

// ============================================
// BOOTSTRAP - učitavanje core fajlova
// ============================================
define('ABSPATH', __DIR__ . '/');

require_once ABSPATH . 'config.php';              // Konekcija na bazu, sesija, konstante
require_once ABSPATH . 'core/helpers.php';        // Pomoćne funkcije (escaping, validacija, vreme)
require_once ABSPATH . 'core/hooks.php';          // Hook sistem (actions, filters)
require_once ABSPATH . 'core/auth.php';           // Autentifikacija (login, registracija, logout)
require_once ABSPATH . 'core/plugin-loader.php';  // Učitavanje pluginova iz /plugins/ foldera

// Učitavanje svih aktivnih pluginova
load_plugins();

// ============================================
// CSRF ZAŠTITA - samo za POST zahteve koji nisu AJAX
// ============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !is_ajax()) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        die('CSRF token mismatch');
    }
}

// ============================================
// ROUTER - obrada akcija pre renderovanja HTML-a
// ============================================
$action = $_GET['action'] ?? 'home';

// AJAX endpoint - detekcija vremenske zone iz browsera
if ($action === 'set_timezone' && is_ajax()) {
    $data = json_decode(file_get_contents('php://input'), true);
    $timezone = $data['timezone'] ?? 'UTC';
    
    $valid_timezones = DateTimeZone::listIdentifiers();
    if (in_array($timezone, $valid_timezones)) {
        $_SESSION['timezone'] = $timezone;
        json_response(['success' => true]);
    }
    json_response(['success' => false, 'message' => 'Invalid timezone'], 400);
}

// Logout - briše sesiju i preusmerava na početnu
if ($action === 'logout') {
    logout_user();
    header('Location: ' . SITE_URL);
    exit;
}

// AJAX endpoint - login API
if ($action === 'login_api' && is_ajax()) {
    $data = json_decode(file_get_contents('php://input'), true);
    $result = login_user($data['username'] ?? '', $data['password'] ?? '');
    json_response($result);
}

// AJAX endpoint - registracija API
if ($action === 'register_api' && is_ajax()) {
    $data = json_decode(file_get_contents('php://input'), true);
    $result = register_user(
        $data['username'] ?? '',
        $data['email'] ?? '',
        $data['password'] ?? ''
    );
    json_response($result);
}

// ============================================
// HOOK: Plugin rute - pluginovi se kače ovde
// ============================================
do_action('register_routes');

// ============================================
// RENDER HTML STRANICA
// ============================================
require_once ABSPATH . 'templates/header.php';

// LOGIN STRANICA - forma za prijavu
if ($action === 'login' && !is_user_logged_in()):
?>
    <div class="login-form">
        <h2>Login</h2>
        <div class="error-message" id="error-message"></div>
        <form id="login-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
        </form>
    </div>
<?php
// DASHBOARD - prikaz za ulogovane korisnike
elseif (is_user_logged_in()):
    $user = get_current_user_data();
    
    // Skeniranje plugin foldera
    $plugins = is_dir(PLUGINS_DIR) ? scandir(PLUGINS_DIR) : [];
    $plugin_list = array_filter($plugins, function($p) {
        return $p !== '.' && $p !== '..' && is_dir(PLUGINS_DIR . '/' . $p);
    });
?>
    <div class="dashboard-grid">
        <!-- Sistemske informacije -->
        <div class="card">
            <h3>System Info</h3>
            <p>PHP Version: <?php echo esc_html(phpversion()); ?></p>
            <p>Server Time (UTC): <?php echo esc_html(gmdate('d.m.Y. H:i:s')); ?></p>
            <p>Local Time: <?php echo esc_html(core_datetime()); ?></p>
            <p>Your Timezone: <?php echo esc_html(get_user_timezone()); ?></p>
        </div>
        
        <!-- Podaci o korisniku -->
        <div class="card">
            <h3>User Info</h3>
            <?php if ($user): ?>
                <p>Username: <?php echo esc_html($user['username']); ?></p>
                <p>Email: <?php echo esc_html($user['email']); ?></p>
            <?php endif; ?>
        </div>
        
        <!-- Lista pluginova -->
        <div class="card">
            <h3>Plugins</h3>
            <p>Active plugins: <?php echo count($plugin_list); ?></p>
            <ul>
                <?php foreach ($plugin_list as $plugin): ?>
                    <li><?php echo apply_filters('plugin_list_item', esc_html($plugin), $plugin); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php
// POČETNA STRANICA - info za neulogovane
else:
?>
    <div class="info-card">
        <h2>Welcome to API Dashboard</h2>
        <p>This is a private API management system. All access is restricted to authorized users only.</p>
        <p style="margin-top: 1rem;">
            <strong>Features:</strong> Plugin system with hooks and filters, timezone localization, user authentication.
        </p>
    </div>
    <div class="info-card">
        <h2>Getting Started</h2>
        <p>Plugins are located in the <code>/plugins/</code> directory. Each plugin should be in its own folder with a main PHP file matching the folder name.</p>
    </div>
<?php
endif;

require_once ABSPATH . 'templates/footer.php';