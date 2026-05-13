<?php
// ============================================
// Sprečiti direktan pristup fajlu
// ============================================
if (!defined('ABSPATH')) exit;

// ============================================
// PLUGIN LOADER - učitavanje pluginova
// ============================================

/**
 * Skenira /plugins/ folder i učitava sve aktivne pluginove
 * 
 * Struktura plugina:
 *   /plugins/ime-plugina/
 *   └── ime-plugina.php    (glavni fajl, naziv mora biti isti kao folder)
 * 
 * Plugin fajlovi se učitavaju sa require_once, tako da mogu da koriste
 * add_action() i add_filter() za registrovanje hook-ova.
 * 
 * @return void
 */
function load_plugins() {
    $plugins_dir = PLUGINS_DIR;
    
    // Ako folder ne postoji, kreiraj ga (prva instalacija)
    if (!is_dir($plugins_dir)) {
        mkdir($plugins_dir, 0755, true);
        return;
    }
    
    // Skeniraj sve fajlove i foldere
    $plugins = scandir($plugins_dir);
    
    foreach ($plugins as $plugin) {
        // Preskoči . i .. foldere
        if ($plugin === '.' || $plugin === '..') continue;
        
        $plugin_path = $plugins_dir . '/' . $plugin;
        
        // Samo ako je folder (ne pojedinačni fajlovi)
        if (is_dir($plugin_path)) {
            // Glavni fajl plugina: /plugins/ime/ime.php
            $plugin_file = $plugin_path . '/' . $plugin . '.php';
            
            if (file_exists($plugin_file)) {
                require_once $plugin_file;
            }
        }
    }
    
    // Hook: svi pluginovi su učitani, mogu početi sa radom
    do_action('plugins_loaded');
}
