<?php
/**
 * Uninstall handler for DB Food Info.
 *
 * Eseguito SOLO quando l'utente disinstalla esplicitamente il plugin
 * (non durante la disattivazione). Rimuove opzioni e transient; i meta
 * alimentari dei prodotti e i termini della tassonomia vengono rimossi
 * solo se l'utente lo ha richiesto nelle impostazioni, perché sono
 * contenuto del catalogo e non dati di funzionamento.
 *
 * @package DB_Food_Info
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

/**
 * Pulizia per singolo sito. Estratta in funzione per iterare i blog
 * in multisite.
 */
function dbfi_uninstall_site() {
    global $wpdb;

    $settings    = (array) get_option('dbfi_settings', array());
    $delete_data = !empty($settings['delete_on_uninstall']);

    /* ---------------------------------------------------------------------
     * Opzioni e transient (sempre)
     * ------------------------------------------------------------------ */
    delete_option('dbfi_settings');

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_dbfi\_%' OR option_name LIKE '\_transient\_timeout\_dbfi\_%'");

    if (!$delete_data) {
        return;
    }

    /* ---------------------------------------------------------------------
     * Meta alimentari dei prodotti (solo su richiesta esplicita)
     * ------------------------------------------------------------------ */
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery
    $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '\_dbfi\_%'");

    /* ---------------------------------------------------------------------
     * Tassonomia allergeni
     * ------------------------------------------------------------------ */
    $terms = get_terms(array(
        'taxonomy'   => 'dbfi_allergene',
        'hide_empty' => false,
        'fields'     => 'ids',
    ));
    if (!is_wp_error($terms)) {
        foreach ($terms as $term_id) {
            wp_delete_term($term_id, 'dbfi_allergene');
        }
    }
}

if (is_multisite()) {
    $site_ids = get_sites(array('fields' => 'ids'));
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);
        dbfi_uninstall_site();
        restore_current_blog();
    }
} else {
    dbfi_uninstall_site();
}
