<?php
/**
 * DBFI_Admin — metabox prodotto, impostazioni, blocco di pubblicazione.
 *
 * @package DB_Food_Info
 */

if (!defined('ABSPATH')) exit;

class DBFI_Admin {

    const NONCE = 'dbfi_product_nonce';

    /** @var DBFI_Admin|null */
    private static $instance = null;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('add_meta_boxes',        array($this, 'add_metabox'));
        add_action('save_post_product',     array($this, 'save_meta'), 10, 2);
        add_action('save_post_product',     array($this, 'gate_publish'), 20, 2);
        add_action('admin_notices',         array($this, 'gate_notice'));
        add_action('admin_menu',            array($this, 'settings_page'));
        add_action('admin_init',            array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue'));
    }

    /* =====================================================================
     * Definizione campi
     * ================================================================== */

    /**
     * Campi meta del prodotto: chiave => [etichetta, obbligatorio].
     *
     * @return array
     */
    public static function fields() {
        return array(
            '_dbfi_denominazione' => array(__('Denominazione legale dell\'alimento', 'db-food-info'), true),
            '_dbfi_ingredienti'   => array(__('Ingredienti (in ordine decrescente di peso — racchiudi gli allergeni tra asterischi: *nocciole*)', 'db-food-info'), true),
            '_dbfi_tracce'        => array(__('Può contenere tracce di', 'db-food-info'), false),
            '_dbfi_quantita'      => array(__('Quantità netta (es. 500 g)', 'db-food-info'), true),
            '_dbfi_conservazione' => array(__('Modalità di conservazione', 'db-food-info'), true),
            '_dbfi_tmc'           => array(__('Termine minimo di conservazione / scadenza (indicativo)', 'db-food-info'), false),
            '_dbfi_operatore'     => array(__('Operatore del settore alimentare responsabile', 'db-food-info'), true),
        );
    }

    /**
     * Campi della dichiarazione nutrizionale (per 100 g). Facoltativi.
     *
     * @return array
     */
    public static function nutrition_fields() {
        return array(
            '_dbfi_energia'     => __('Energia (kJ / kcal)', 'db-food-info'),
            '_dbfi_grassi'      => __('Grassi (g)', 'db-food-info'),
            '_dbfi_saturi'      => __('di cui acidi grassi saturi (g)', 'db-food-info'),
            '_dbfi_carboidrati' => __('Carboidrati (g)', 'db-food-info'),
            '_dbfi_zuccheri'    => __('di cui zuccheri (g)', 'db-food-info'),
            '_dbfi_proteine'    => __('Proteine (g)', 'db-food-info'),
            '_dbfi_sale'        => __('Sale (g)', 'db-food-info'),
        );
    }

    public function enqueue($hook) {
        if (in_array($hook, array('post.php', 'post-new.php', 'settings_page_dbfi-settings'), true)) {
            wp_enqueue_style('dbfi-admin-ui', DBFI_PLUGIN_URL . 'assets/css/db-admin-ui.css', array(), DBFI_VERSION);
            wp_enqueue_style('dbfi-admin', DBFI_PLUGIN_URL . 'assets/css/admin.css', array('dbfi-admin-ui'), DBFI_VERSION);
        }
    }

    /* =====================================================================
     * Metabox
     * ================================================================== */

    public function add_metabox() {
        add_meta_box(
            'dbfi_food_info',
            __('Informazioni alimentari (Reg. UE 1169/2011)', 'db-food-info'),
            array($this, 'render_metabox'),
            'product',
            'normal',
            'high'
        );
    }

    public function render_metabox($post) {
        wp_nonce_field(self::NONCE, self::NONCE);
        $settings = DBFI_Core::settings();

        echo '<div class="dbfi-metabox">';
        echo '<div class="db-ui-notice db-ui-notice-info"><p>'
            . esc_html__('Compila i campi obbligatori (*): senza di essi il prodotto nelle categorie alimentari non potrà essere pubblicato. Gli allergeni racchiusi tra asterischi negli ingredienti verranno mostrati in grassetto sulla pagina prodotto, come richiesto dall\'art. 21 del Regolamento.', 'db-food-info')
            . '</p></div>';

        foreach (self::fields() as $key => $field) {
            $value = get_post_meta($post->ID, $key, true);
            if ('_dbfi_operatore' === $key && '' === $value && !empty($settings['operatore'])) {
                $value = $settings['operatore'];
            }
            $required = $field[1] ? ' <span class="dbfi-req" aria-hidden="true">*</span>' : '';
            echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($field[0]) . $required . '</strong></label><br />';
            if (in_array($key, array('_dbfi_ingredienti', '_dbfi_conservazione'), true)) {
                echo '<textarea class="widefat" rows="3" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '">' . esc_textarea($value) . '</textarea>';
            } else {
                echo '<input type="text" class="widefat" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
            }
            echo '</p>';
        }

        echo '<details class="dbfi-nutrition"><summary><strong>'
            . esc_html__('Dichiarazione nutrizionale (per 100 g)', 'db-food-info')
            . '</strong> — '
            . esc_html__('facoltativa se applicabile l\'esenzione All. V p. 19 (fornitura diretta locale in piccole quantità)', 'db-food-info')
            . '</summary>';
        foreach (self::nutrition_fields() as $key => $label) {
            $value = get_post_meta($post->ID, $key, true);
            echo '<p><label for="' . esc_attr($key) . '">' . esc_html($label) . '</label><br />';
            echo '<input type="text" class="regular-text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" /></p>';
        }
        echo '</details>';

        $no_all = get_post_meta($post->ID, '_dbfi_no_allergeni', true);
        echo '<p><label><input type="checkbox" name="_dbfi_no_allergeni" value="1" ' . checked($no_all, '1', false) . ' /> <strong>'
            . esc_html__('Dichiaro che il prodotto non contiene allergeni', 'db-food-info')
            . '</strong> ' . esc_html__('dell\'Allegato II (valutazione effettuata)', 'db-food-info') . '</label></p>';
        echo '<div class="db-ui-notice db-ui-notice-warning"><p>'
            . esc_html__('Gli allergeni presenti vanno selezionati anche nel riquadro Allergeni nella colonna laterale: alimentano il riepilogo con icone e i filtri di ricerca. In assenza sia dei termini sia della dichiarazione qui sopra, il prodotto non sarà pubblicabile.', 'db-food-info')
            . '</p></div>';
        echo '</div>';
    }

    public function save_meta($post_id, $post) {
        if (!isset($_POST[self::NONCE]) || !wp_verify_nonce(sanitize_key($_POST[self::NONCE]), self::NONCE)) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $keys = array_merge(array_keys(self::fields()), array_keys(self::nutrition_fields()));
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $key, sanitize_textarea_field(wp_unslash($_POST[$key])));
            }
        }
        update_post_meta($post_id, '_dbfi_no_allergeni', isset($_POST['_dbfi_no_allergeni']) ? '1' : '');
    }

    /* =====================================================================
     * Blocco di pubblicazione
     * ================================================================== */

    /**
     * Se il prodotto appartiene alle categorie sorvegliate e mancano campi
     * obbligatori o la valutazione allergeni, riporta a bozza.
     */
    public function gate_publish($post_id, $post) {
        if ('publish' !== $post->post_status) return;
        if (!$this->is_gated($post_id)) return;

        $missing = $this->missing_fields($post_id);
        if (empty($missing)) return;

        // Evita ricorsione.
        remove_action('save_post_product', array($this, 'gate_publish'), 20);
        wp_update_post(array('ID' => $post_id, 'post_status' => 'draft'));
        add_action('save_post_product', array($this, 'gate_publish'), 20, 2);

        set_transient('dbfi_gate_' . get_current_user_id(), $missing, 60);
    }

    /**
     * Il prodotto ricade nel controllo?
     *
     * @param int $post_id Product ID.
     * @return bool
     */
    private function is_gated($post_id) {
        $settings = DBFI_Core::settings();
        if (!empty($settings['gate_all'])) return true;

        $cats = array_filter(array_map('absint', (array) $settings['gate_cats']));
        if (empty($cats)) return false;

        return has_term($cats, 'product_cat', $post_id);
    }

    /**
     * Elenco (etichette) dei campi obbligatori mancanti.
     *
     * @param int $post_id Product ID.
     * @return array
     */
    private function missing_fields($post_id) {
        $missing = array();
        foreach (self::fields() as $key => $field) {
            if ($field[1] && '' === trim((string) get_post_meta($post_id, $key, true))) {
                $missing[] = $field[0];
            }
        }
        // Valutazione allergeni: termini assegnati o dichiarazione esplicita.
        $has_terms     = has_term('', DBFI_Taxonomy::TAXONOMY, $post_id);
        $declared_none = '1' === get_post_meta($post_id, '_dbfi_no_allergeni', true);
        if (!$has_terms && !$declared_none) {
            $missing[] = __('Valutazione allergeni (assegna i termini o dichiara l\'assenza)', 'db-food-info');
        }
        return $missing;
    }

    public function gate_notice() {
        $missing = get_transient('dbfi_gate_' . get_current_user_id());
        if (!$missing) return;
        delete_transient('dbfi_gate_' . get_current_user_id());

        echo '<div class="notice notice-error"><p><strong>DB Food Info</strong>: '
            . esc_html__('prodotto riportato in bozza. Ai sensi del Reg. UE 1169/2011 le informazioni alimentari devono essere disponibili prima dell\'acquisto. Campi mancanti:', 'db-food-info')
            . '</p><ul style="list-style:disc;margin-left:2em;">';
        foreach ((array) $missing as $label) {
            echo '<li>' . esc_html($label) . '</li>';
        }
        echo '</ul></div>';
    }

    /* =====================================================================
     * Impostazioni
     * ================================================================== */

    public function settings_page() {
        add_options_page('DB Food Info', 'DB Food Info', 'manage_options', 'dbfi-settings', array($this, 'render_settings'));
    }

    public function register_settings() {
        register_setting('dbfi_settings_group', DBFI_OPTION_KEY, array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }

    public function sanitize_settings($input) {
        return array(
            'operatore'           => sanitize_text_field($input['operatore'] ?? ''),
            'gate_all'            => empty($input['gate_all']) ? 0 : 1,
            'gate_cats'           => array_map('absint', (array) ($input['gate_cats'] ?? array())),
            'jsonld'              => empty($input['jsonld']) ? 0 : 1,
            'delete_on_uninstall' => empty($input['delete_on_uninstall']) ? 0 : 1,
        );
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) return;
        $s    = DBFI_Core::settings();
        $cats = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
        $opt  = DBFI_OPTION_KEY;
        ?>
        <div class="wrap dbfi-settings">
            <h1>DB Food Info</h1>
            <form method="post" action="options.php">
                <?php settings_fields('dbfi_settings_group'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="dbfi_operatore"><?php esc_html_e('Operatore responsabile predefinito', 'db-food-info'); ?></label></th>
                        <td>
                            <input type="text" class="regular-text" id="dbfi_operatore" name="<?php echo esc_attr($opt); ?>[operatore]" value="<?php echo esc_attr($s['operatore']); ?>" />
                            <p class="description"><?php esc_html_e('Ragione sociale e indirizzo dell\'OSA (art. 8 Reg. 1169/2011). Precompila il campo sui nuovi prodotti.', 'db-food-info'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Blocco di pubblicazione', 'db-food-info'); ?></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr($opt); ?>[gate_all]" value="1" <?php checked($s['gate_all'], 1); ?> /> <?php esc_html_e('Applica a tutti i prodotti', 'db-food-info'); ?></label>
                            <p class="description"><?php esc_html_e('Oppure limita alle categorie alimentari:', 'db-food-info'); ?></p>
                            <select name="<?php echo esc_attr($opt); ?>[gate_cats][]" multiple size="6" style="min-width:280px;">
                                <?php foreach ($cats as $cat) : ?>
                                    <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected(in_array($cat->term_id, (array) $s['gate_cats'], true)); ?>><?php echo esc_html($cat->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Dati strutturati', 'db-food-info'); ?></th>
                        <td><label><input type="checkbox" name="<?php echo esc_attr($opt); ?>[jsonld]" value="1" <?php checked($s['jsonld'], 1); ?> /> <?php esc_html_e('Aggiungi ingredienti e allergeni al JSON-LD del prodotto', 'db-food-info'); ?></label></td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Disinstallazione', 'db-food-info'); ?></th>
                        <td>
                            <label><input type="checkbox" name="<?php echo esc_attr($opt); ?>[delete_on_uninstall]" value="1" <?php checked($s['delete_on_uninstall'], 1); ?> /> <?php esc_html_e('Alla disinstallazione elimina anche le informazioni alimentari dei prodotti', 'db-food-info'); ?></label>
                            <p class="description"><?php esc_html_e('Se disattivato (consigliato), ingredienti e allergeni restano nel database come contenuto del catalogo.', 'db-food-info'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(__('Salva impostazioni', 'db-food-info')); ?>
            </form>
        </div>
        <?php
    }
}
