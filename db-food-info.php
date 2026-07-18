<?php
/**
 * Plugin Name:       DB Food Info
 * Plugin URI:        https://www.davidebertolino.it/progetti/
 * Description:       Informazioni alimentari conformi al Reg. UE 1169/2011 per prodotti WooCommerce: ingredienti con allergeni evidenziati, tassonomia dei 14 allergeni, dichiarazione nutrizionale, JSON-LD e blocco di pubblicazione per prodotti incompleti. Niente registrazioni, niente nag, niente trucchi.
 * Version:           1.0.0
 * Author:            Davide Bertolino
 * Author URI:        https://www.davidebertolino.it
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       db-food-info
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'DBFI_VERSION', '1.0.0' );
define( 'DBFI_PLUGIN_FILE', __FILE__ );
define( 'DBFI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'DBFI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once DBFI_PLUGIN_DIR . 'inc/class-updater.php';
require_once DBFI_PLUGIN_DIR . 'inc/class-dbfi-taxonomy.php';
require_once DBFI_PLUGIN_DIR . 'inc/class-dbfi-admin.php';
require_once DBFI_PLUGIN_DIR . 'inc/class-dbfi-frontend.php';

/**
 * Bootstrap.
 */
function dbfi_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'dbfi_woocommerce_missing_notice' );
		return;
	}
	DBFI_Taxonomy::init();
	DBFI_Admin::init();
	DBFI_Frontend::init();

	if ( class_exists( 'DB_GitHub_Updater' ) ) {
		new DB_GitHub_Updater( __FILE__, 'dadebertolino', 'db-food-info' );
	}
}
add_action( 'plugins_loaded', 'dbfi_init' );

/**
 * Notice when WooCommerce is not active.
 */
function dbfi_woocommerce_missing_notice() {
	echo '<div class="notice notice-error"><p><strong>DB Food Info</strong>: il plugin richiede WooCommerce attivo.</p></div>';
}

/**
 * Activation: register taxonomy and seed the 14 allergens.
 */
function dbfi_activate() {
	DBFI_Taxonomy::register();
	DBFI_Taxonomy::seed_terms();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'dbfi_activate' );

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
