<?php
/**
 * DBFI_Core — orchestratore del plugin.
 *
 * Carica i moduli e li istanzia. Se WooCommerce non è attivo mostra
 * un avviso e non carica nulla.
 *
 * @package DB_Food_Info
 */

if (!defined('ABSPATH')) exit;

class DBFI_Core {

    /** @var DBFI_Core|null */
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }

        require_once DBFI_PLUGIN_DIR . 'inc/class-taxonomy.php';
        require_once DBFI_PLUGIN_DIR . 'inc/class-admin.php';
        require_once DBFI_PLUGIN_DIR . 'inc/class-frontend.php';
        require_once DBFI_PLUGIN_DIR . 'inc/class-privacy.php';

        DBFI_Taxonomy::instance();
        DBFI_Admin::instance();
        DBFI_Frontend::instance();
        DBFI_Privacy::instance();
    }

    /**
     * Avviso in bacheca se manca WooCommerce.
     */
    public function woocommerce_missing_notice() {
        echo '<div class="notice notice-error"><p><strong>DB Food Info</strong>: '
            . esc_html__('il plugin richiede WooCommerce attivo.', 'db-food-info')
            . '</p></div>';
    }

    /**
     * Impostazioni del plugin con default.
     *
     * @return array
     */
    public static function settings() {
        return wp_parse_args(
            (array) get_option(DBFI_OPTION_KEY, array()),
            array(
                'operatore'      => '',
                'gate_all'       => 0,
                'gate_cats'      => array(),
                'jsonld'         => 1,
                'delete_on_uninstall' => 0,
            )
        );
    }
}
