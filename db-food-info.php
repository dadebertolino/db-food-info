<?php
/**
 * Plugin Name:       DB Food Info
 * Plugin URI:        https://www.davidebertolino.it/progetti/db-food-info/
 * Description:       Informazioni alimentari conformi al Reg. UE 1169/2011 per prodotti WooCommerce: ingredienti con allergeni evidenziati, tassonomia dei 14 allergeni, dichiarazione nutrizionale, JSON-LD e blocco di pubblicazione per prodotti incompleti. Niente servizi esterni, niente tracciamento, niente nag.
 * Version:           1.1.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Davide Bertolino
 * Author URI:        https://www.davidebertolino.it
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       db-food-info
 * Domain Path:       /languages
 *
 * @package DB_Food_Info
 */

if (!defined('ABSPATH')) exit;

/* -------------------------------------------------------------------------
 * Costanti
 * ---------------------------------------------------------------------- */
define('DBFI_VERSION',     '1.1.0');
define('DBFI_PLUGIN_FILE', __FILE__);
define('DBFI_PLUGIN_DIR',  plugin_dir_path(__FILE__));
define('DBFI_PLUGIN_URL',  plugin_dir_url(__FILE__));
define('DBFI_PLUGIN_BASE', plugin_basename(__FILE__));
define('DBFI_TEXT_DOMAIN', 'db-food-info');
define('DBFI_OPTION_KEY',  'dbfi_settings');

/* -------------------------------------------------------------------------
 * GitHub Auto-Updater (componente condiviso)
 * ---------------------------------------------------------------------- */
require_once DBFI_PLUGIN_DIR . 'inc/class-updater.php';
new DB_GitHub_Updater(__FILE__, 'dadebertolino', 'db-food-info');

/* -------------------------------------------------------------------------
 * Bootstrap del plugin
 * ---------------------------------------------------------------------- */
require_once DBFI_PLUGIN_DIR . 'inc/class-core.php';

/**
 * Avvia il plugin dopo il caricamento dei plugin (così altri possono filtrare).
 */
function dbfi_boot() {
    DBFI_Core::instance();
}
add_action('plugins_loaded', 'dbfi_boot', 5);

/**
 * Carica le traduzioni.
 */
function dbfi_load_textdomain() {
    load_plugin_textdomain(
        DBFI_TEXT_DOMAIN,
        false,
        dirname(DBFI_PLUGIN_BASE) . '/languages'
    );
}
add_action('init', 'dbfi_load_textdomain', 1);

/* -------------------------------------------------------------------------
 * Attivazione / disattivazione
 * ---------------------------------------------------------------------- */
function dbfi_activate() {
    require_once DBFI_PLUGIN_DIR . 'inc/class-taxonomy.php';
    DBFI_Taxonomy::register();
    DBFI_Taxonomy::seed_terms();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'dbfi_activate');

register_deactivation_hook(__FILE__, 'flush_rewrite_rules');
