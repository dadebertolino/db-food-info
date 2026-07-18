<?php
/**
 * DBFI_Taxonomy — tassonomia dei 14 allergeni (Allegato II, Reg. UE 1169/2011).
 *
 * @package DB_Food_Info
 */

if (!defined('ABSPATH')) exit;

class DBFI_Taxonomy {

    const TAXONOMY = 'dbfi_allergene';

    /** @var DBFI_Taxonomy|null */
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array(__CLASS__, 'register'));
    }

    /* =====================================================================
     * Database allergeni
     * ================================================================== */

    /**
     * I 14 allergeni dell'Allegato II. slug => [nome, icona].
     *
     * @return array
     */
    public static function allergens() {
        return array(
            'glutine'         => array(__('Cereali contenenti glutine', 'db-food-info'), '🌾'),
            'crostacei'       => array(__('Crostacei', 'db-food-info'), '🦐'),
            'uova'            => array(__('Uova', 'db-food-info'), '🥚'),
            'pesce'           => array(__('Pesce', 'db-food-info'), '🐟'),
            'arachidi'        => array(__('Arachidi', 'db-food-info'), '🥜'),
            'soia'            => array(__('Soia', 'db-food-info'), '🌱'),
            'latte'           => array(__('Latte e lattosio', 'db-food-info'), '🥛'),
            'frutta-a-guscio' => array(__('Frutta a guscio', 'db-food-info'), '🌰'),
            'sedano'          => array(__('Sedano', 'db-food-info'), '🥬'),
            'senape'          => array(__('Senape', 'db-food-info'), '🟡'),
            'sesamo'          => array(__('Semi di sesamo', 'db-food-info'), '⚪'),
            'solfiti'         => array(__('Anidride solforosa e solfiti', 'db-food-info'), '🍷'),
            'lupini'          => array(__('Lupini', 'db-food-info'), '🫘'),
            'molluschi'       => array(__('Molluschi', 'db-food-info'), '🐚'),
        );
    }

    /* =====================================================================
     * Registrazione e seed
     * ================================================================== */

    public static function register() {
        register_taxonomy(self::TAXONOMY, 'product', array(
            'labels' => array(
                'name'          => __('Allergeni', 'db-food-info'),
                'singular_name' => __('Allergene', 'db-food-info'),
                'menu_name'     => __('Allergeni', 'db-food-info'),
                'search_items'  => __('Cerca allergeni', 'db-food-info'),
                'all_items'     => __('Tutti gli allergeni', 'db-food-info'),
                'edit_item'     => __('Modifica allergene', 'db-food-info'),
                'update_item'   => __('Aggiorna allergene', 'db-food-info'),
            ),
            'public'            => true,
            'hierarchical'      => false,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => array('slug' => 'allergene'),
        ));
    }

    /**
     * Precarica i 14 termini (idempotente).
     */
    public static function seed_terms() {
        foreach (self::allergens() as $slug => $data) {
            if (!term_exists($slug, self::TAXONOMY)) {
                wp_insert_term($data[0], self::TAXONOMY, array('slug' => $slug));
            }
        }
    }

    /**
     * Icona per slug (fallback: pallino).
     *
     * @param string $slug Term slug.
     * @return string
     */
    public static function icon($slug) {
        $all = self::allergens();
        return isset($all[$slug]) ? $all[$slug][1] : '•';
    }
}
