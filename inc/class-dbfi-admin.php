<?php
/**
 * DBFI_Admin — metabox prodotto, impostazioni, blocco di pubblicazione.
 *
 * @package DB_Food_Info
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DBFI_Admin {

	const NONCE   = 'dbfi_product_nonce';
	const OPTIONS = 'dbfi_settings';

	/**
	 * Campi meta del prodotto: chiave => [etichetta, obbligatorio].
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'_dbfi_denominazione' => array( 'Denominazione legale dell\'alimento', true ),
			'_dbfi_ingredienti'   => array( 'Ingredienti (in ordine decrescente di peso — racchiudi gli allergeni tra asterischi: *nocciole*)', true ),
			'_dbfi_tracce'        => array( 'Può contenere tracce di', false ),
			'_dbfi_quantita'      => array( 'Quantità netta (es. 500 g)', true ),
			'_dbfi_conservazione' => array( 'Modalità di conservazione', true ),
			'_dbfi_tmc'           => array( 'Termine minimo di conservazione / scadenza (indicativo)', false ),
			'_dbfi_operatore'     => array( 'Operatore del settore alimentare responsabile', true ),
		);
	}

	/**
	 * Campi della dichiarazione nutrizionale (per 100 g). Facoltativi.
	 *
	 * @return array
	 */
	public static function nutrition_fields() {
		return array(
			'_dbfi_energia'      => 'Energia (kJ / kcal)',
			'_dbfi_grassi'       => 'Grassi (g)',
			'_dbfi_saturi'       => 'di cui acidi grassi saturi (g)',
			'_dbfi_carboidrati'  => 'Carboidrati (g)',
			'_dbfi_zuccheri'     => 'di cui zuccheri (g)',
			'_dbfi_proteine'     => 'Proteine (g)',
			'_dbfi_sale'         => 'Sale (g)',
		);
	}

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_metabox' ) );
		add_action( 'save_post_product', array( __CLASS__, 'save_meta' ), 10, 2 );
		add_action( 'save_post_product', array( __CLASS__, 'gate_publish' ), 20, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'gate_notice' ) );
		add_action( 'admin_menu', array( __CLASS__, 'settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue( $hook ) {
		if ( in_array( $hook, array( 'post.php', 'post-new.php', 'settings_page_dbfi-settings' ), true ) ) {
			wp_enqueue_style( 'dbfi-admin-ui', DBFI_PLUGIN_URL . 'assets/css/db-admin-ui.css', array(), DBFI_VERSION );
			wp_enqueue_style( 'dbfi-admin', DBFI_PLUGIN_URL . 'assets/css/admin.css', array( 'dbfi-admin-ui' ), DBFI_VERSION );
		}
	}

	/* ---------------------------------------------------------- Metabox */

	public static function add_metabox() {
		add_meta_box(
			'dbfi_food_info',
			'Informazioni alimentari (Reg. UE 1169/2011)',
			array( __CLASS__, 'render_metabox' ),
			'product',
			'normal',
			'high'
		);
	}

	public static function render_metabox( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE );
		$settings = self::settings();
		echo '<div class="dbfi-metabox">';
		echo '<p class="dbfi-hint">Compila i campi obbligatori (*): senza di essi il prodotto nelle categorie alimentari non potrà essere pubblicato. Gli allergeni racchiusi tra asterischi negli ingredienti verranno mostrati <strong>in grassetto</strong> sulla pagina prodotto, come richiesto dall\'art. 21 del Regolamento.</p>';

		foreach ( self::fields() as $key => $field ) {
			$value = get_post_meta( $post->ID, $key, true );
			if ( '_dbfi_operatore' === $key && '' === $value && ! empty( $settings['operatore'] ) ) {
				$value = $settings['operatore'];
			}
			$required = $field[1] ? ' <span class="dbfi-req" aria-hidden="true">*</span>' : '';
			$label    = esc_html( $field[0] ) . $required;
			echo '<p><label for="' . esc_attr( $key ) . '"><strong>' . $label . '</strong></label><br />';
			if ( in_array( $key, array( '_dbfi_ingredienti', '_dbfi_conservazione' ), true ) ) {
				echo '<textarea class="widefat" rows="3" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '">' . esc_textarea( $value ) . '</textarea>';
			} else {
				echo '<input type="text" class="widefat" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" />';
			}
			echo '</p>';
		}

		echo '<details class="dbfi-nutrition"><summary><strong>Dichiarazione nutrizionale (per 100 g)</strong> — facoltativa se applicabile l\'esenzione All. V p.19 (fornitura diretta locale in piccole quantità)</summary>';
		foreach ( self::nutrition_fields() as $key => $label ) {
			$value = get_post_meta( $post->ID, $key, true );
			echo '<p><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label><br />';
			echo '<input type="text" class="regular-text" id="' . esc_attr( $key ) . '" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" /></p>';
		}
		echo '</details>';
		$no_all = get_post_meta( $post->ID, '_dbfi_no_allergeni', true );
		echo '<p><label><input type="checkbox" name="_dbfi_no_allergeni" value="1" ' . checked( $no_all, '1', false ) . ' /> <strong>Dichiaro che il prodotto non contiene allergeni</strong> dell\'Allegato II (valutazione effettuata)</label></p>';
		echo '<p class="dbfi-hint">Gli allergeni presenti vanno selezionati anche nel riquadro <strong>Allergeni</strong> nella colonna laterale: alimentano il riepilogo con icone e i filtri di ricerca. In assenza sia dei termini sia della dichiarazione qui sopra, il prodotto non sarà pubblicabile.</p>';
		echo '</div>';
	}

	public static function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE ] ), self::NONCE ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$keys = array_merge( array_keys( self::fields() ), array_keys( self::nutrition_fields() ) );
		foreach ( $keys as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$raw = wp_unslash( $_POST[ $key ] );
				update_post_meta( $post_id, $key, sanitize_textarea_field( $raw ) );
			}
		}
		update_post_meta( $post_id, '_dbfi_no_allergeni', isset( $_POST['_dbfi_no_allergeni'] ) ? '1' : '' );
	}

	/* ------------------------------------------------ Blocco pubblicazione */

	/**
	 * Se il prodotto appartiene alle categorie sorvegliate e mancano campi
	 * obbligatori o gli allergeni non sono stati valutati, riporta a bozza.
	 */
	public static function gate_publish( $post_id, $post ) {
		if ( 'publish' !== $post->post_status ) {
			return;
		}
		if ( ! self::is_gated( $post_id ) ) {
			return;
		}
		$missing = self::missing_fields( $post_id );
		if ( empty( $missing ) ) {
			return;
		}
		// Evita ricorsione.
		remove_action( 'save_post_product', array( __CLASS__, 'gate_publish' ), 20 );
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);
		add_action( 'save_post_product', array( __CLASS__, 'gate_publish' ), 20, 2 );
		set_transient( 'dbfi_gate_' . get_current_user_id(), $missing, 60 );
	}

	/**
	 * Il prodotto ricade nel controllo?
	 *
	 * @param int $post_id Product ID.
	 * @return bool
	 */
	protected static function is_gated( $post_id ) {
		$settings = self::settings();
		if ( ! empty( $settings['gate_all'] ) ) {
			return true;
		}
		$cats = array_filter( array_map( 'absint', (array) ( $settings['gate_cats'] ?? array() ) ) );
		if ( empty( $cats ) ) {
			return false;
		}
		return has_term( $cats, 'product_cat', $post_id );
	}

	/**
	 * Elenco (etichette) dei campi obbligatori mancanti.
	 *
	 * @param int $post_id Product ID.
	 * @return array
	 */
	protected static function missing_fields( $post_id ) {
		$missing = array();
		foreach ( self::fields() as $key => $field ) {
			if ( $field[1] && '' === trim( (string) get_post_meta( $post_id, $key, true ) ) ) {
				$missing[] = $field[0];
			}
		}
		// Gli allergeni devono essere stati valutati: o termini assegnati o
		// spunta esplicita "nessun allergene".
		$has_terms = has_term( '', DBFI_Taxonomy::TAXONOMY, $post_id );
		$declared_none = '1' === get_post_meta( $post_id, '_dbfi_no_allergeni', true );
		if ( ! $has_terms && ! $declared_none ) {
			$missing[] = 'Valutazione allergeni (assegna i termini o dichiara l\'assenza)';
		}
		return $missing;
	}

	public static function gate_notice() {
		$missing = get_transient( 'dbfi_gate_' . get_current_user_id() );
		if ( ! $missing ) {
			return;
		}
		delete_transient( 'dbfi_gate_' . get_current_user_id() );
		echo '<div class="notice notice-error"><p><strong>DB Food Info</strong>: prodotto riportato in bozza. Ai sensi del Reg. UE 1169/2011 le informazioni alimentari devono essere disponibili prima dell\'acquisto. Campi mancanti:</p><ul style="list-style:disc;margin-left:2em;">';
		foreach ( (array) $missing as $label ) {
			echo '<li>' . esc_html( $label ) . '</li>';
		}
		echo '</ul></div>';
	}

	/* ----------------------------------------------------------- Settings */

	public static function settings() {
		return wp_parse_args(
			(array) get_option( self::OPTIONS, array() ),
			array(
				'operatore' => '',
				'gate_all'  => 0,
				'gate_cats' => array(),
				'jsonld'    => 1,
			)
		);
	}

	public static function settings_page() {
		add_options_page( 'DB Food Info', 'DB Food Info', 'manage_options', 'dbfi-settings', array( __CLASS__, 'render_settings' ) );
	}

	public static function register_settings() {
		register_setting(
			'dbfi_settings_group',
			self::OPTIONS,
			array( 'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ) )
		);
	}

	public static function sanitize_settings( $input ) {
		return array(
			'operatore' => sanitize_text_field( $input['operatore'] ?? '' ),
			'gate_all'  => empty( $input['gate_all'] ) ? 0 : 1,
			'gate_cats' => array_map( 'absint', (array) ( $input['gate_cats'] ?? array() ) ),
			'jsonld'    => empty( $input['jsonld'] ) ? 0 : 1,
		);
	}

	public static function render_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$s    = self::settings();
		$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		?>
		<div class="wrap dbfi-settings">
			<h1>DB Food Info — Impostazioni</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'dbfi_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="dbfi_operatore">Operatore responsabile predefinito</label></th>
						<td><input type="text" class="regular-text" id="dbfi_operatore" name="<?php echo esc_attr( self::OPTIONS ); ?>[operatore]" value="<?php echo esc_attr( $s['operatore'] ); ?>" />
						<p class="description">Ragione sociale e indirizzo dell'OSA (art. 8 Reg. 1169/2011). Precompila il campo sui nuovi prodotti.</p></td>
					</tr>
					<tr>
						<th scope="row">Blocco di pubblicazione</th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTIONS ); ?>[gate_all]" value="1" <?php checked( $s['gate_all'], 1 ); ?> /> Applica a <strong>tutti</strong> i prodotti</label>
							<p class="description">Oppure limita alle categorie alimentari:</p>
							<select name="<?php echo esc_attr( self::OPTIONS ); ?>[gate_cats][]" multiple size="6" style="min-width:280px;">
								<?php foreach ( $cats as $cat ) : ?>
									<option value="<?php echo esc_attr( $cat->term_id ); ?>" <?php selected( in_array( $cat->term_id, (array) $s['gate_cats'], true ) ); ?>><?php echo esc_html( $cat->name ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">Dati strutturati</th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTIONS ); ?>[jsonld]" value="1" <?php checked( $s['jsonld'], 1 ); ?> /> Aggiungi ingredienti e allergeni al JSON-LD del prodotto</label></td>
					</tr>
				</table>
				<?php submit_button( 'Salva impostazioni' ); ?>
			</form>
		</div>
		<?php
	}
}
