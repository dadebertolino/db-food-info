<?php
/**
 * DBFI_Taxonomy — tassonomia dei 14 allergeni (Allegato II, Reg. UE 1169/2011).
 *
 * @package DB_Food_Info
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DBFI_Taxonomy {

	const TAXONOMY = 'dbfi_allergene';

	/**
	 * I 14 allergeni dell'Allegato II. slug => [nome, icona].
	 *
	 * @return array
	 */
	public static function allergens() {
		return array(
			'glutine'           => array( 'Cereali contenenti glutine', '🌾' ),
			'crostacei'         => array( 'Crostacei', '🦐' ),
			'uova'              => array( 'Uova', '🥚' ),
			'pesce'             => array( 'Pesce', '🐟' ),
			'arachidi'          => array( 'Arachidi', '🥜' ),
			'soia'              => array( 'Soia', '🌱' ),
			'latte'             => array( 'Latte e lattosio', '🥛' ),
			'frutta-a-guscio'   => array( 'Frutta a guscio', '🌰' ),
			'sedano'            => array( 'Sedano', '🥬' ),
			'senape'            => array( 'Senape', '🟡' ),
			'sesamo'            => array( 'Semi di sesamo', '⚪' ),
			'solfiti'           => array( 'Anidride solforosa e solfiti', '🍷' ),
			'lupini'            => array( 'Lupini', '🫘' ),
			'molluschi'         => array( 'Molluschi', '🐚' ),
		);
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
	}

	/**
	 * Registra la tassonomia sugli articoli prodotto.
	 */
	public static function register() {
		register_taxonomy(
			self::TAXONOMY,
			'product',
			array(
				'labels'            => array(
					'name'          => 'Allergeni',
					'singular_name' => 'Allergene',
					'menu_name'     => 'Allergeni',
					'search_items'  => 'Cerca allergeni',
					'all_items'     => 'Tutti gli allergeni',
					'edit_item'     => 'Modifica allergene',
					'update_item'   => 'Aggiorna allergene',
				),
				'public'            => true,
				'hierarchical'      => false,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array( 'slug' => 'allergene' ),
			)
		);
	}

	/**
	 * Precarica i 14 termini (idempotente).
	 */
	public static function seed_terms() {
		self::register();
		foreach ( self::allergens() as $slug => $data ) {
			if ( ! term_exists( $slug, self::TAXONOMY ) ) {
				wp_insert_term( $data[0], self::TAXONOMY, array( 'slug' => $slug ) );
			}
		}
	}

	/**
	 * Icona per slug (fallback: pallino).
	 *
	 * @param string $slug Term slug.
	 * @return string
	 */
	public static function icon( $slug ) {
		$all = self::allergens();
		return isset( $all[ $slug ] ) ? $all[ $slug ][1] : '•';
	}
}
