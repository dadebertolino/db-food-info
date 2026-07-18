<?php
/**
 * DBFI_Privacy — trasparenza sui trattamenti (pattern DB Plugin Suite).
 *
 * DB Food Info non tratta dati personali: le informazioni gestite
 * (ingredienti, allergeni, valori nutrizionali) riguardano i prodotti,
 * non le persone. Il plugin non imposta cookie, non contatta servizi
 * esterni sul frontend e non registra dati dei visitatori.
 *
 * Questa classe lo dichiara formalmente al titolare del sito tramite la
 * guida policy di WordPress (Strumenti → Privacy), in coerenza con il
 * principio di trasparenza della suite.
 *
 * @package DB_Food_Info
 */

if (!defined('ABSPATH')) exit;

class DBFI_Privacy {

    /** @var DBFI_Privacy|null */
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_init', array($this, 'add_policy_content'));
    }

    /**
     * Testo suggerito per l'informativa privacy del sito.
     */
    public function add_policy_content() {
        if (!function_exists('wp_add_privacy_policy_content')) return;

        $content = '<p>' . esc_html__(
            'DB Food Info gestisce esclusivamente informazioni relative ai prodotti (ingredienti, allergeni, valori nutrizionali) ai fini del Reg. UE 1169/2011. Il plugin non raccoglie né tratta dati personali dei visitatori, non imposta cookie e non comunica con servizi esterni durante la navigazione. Non è pertanto necessaria alcuna integrazione nell\'informativa privacy del sito.',
            'db-food-info'
        ) . '</p>';

        wp_add_privacy_policy_content('DB Food Info', wp_kses_post($content));
    }
}
