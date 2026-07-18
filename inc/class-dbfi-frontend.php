<?php
/**
 * DBFI_Frontend — box informazioni alimentari, JSON-LD, shortcode legenda.
 *
 * @package DB_Food_Info
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DBFI_Frontend {

	public static function init() {
		// Priorità 25: dopo prezzo/estratto, PRIMA del pulsante Aggiungi al
		// carrello (30) — l'informazione deve precedere l'acquisto (art. 14
		// Reg. 1169/2011).
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_box' ), 25 );
		add_filter( 'woocommerce_structured_data_product', array( __CLASS__, 'jsonld' ), 10, 2 );
		add_shortcode( 'dbfi_legenda_allergeni', array( __CLASS__, 'legend_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
	}

	public static function enqueue() {
		if ( is_product() || is_singular() ) {
			wp_enqueue_style( 'dbfi-frontend', DBFI_PLUGIN_URL . 'assets/css/frontend.css', array(), DBFI_VERSION );
		}
	}

	/**
	 * Ingredienti con allergeni evidenziati: *testo* diventa <strong>.
	 * Escaping prima, markup dopo — mai HTML utente in output.
	 *
	 * @param string $raw Ingredienti come salvati.
	 * @return string HTML sicuro.
	 */
	public static function format_ingredients( $raw ) {
		$safe = esc_html( $raw );
		return preg_replace(
			'/\*([^*\n]+)\*/u',
			'<strong class="dbfi-allergen-word">$1</strong>',
			$safe
		);
	}

	/**
	 * Box "Informazioni alimentari" sulla pagina prodotto.
	 */
	public static function render_box() {
		global $post;
		$id  = $post->ID;
		$den = get_post_meta( $id, '_dbfi_denominazione', true );
		$ing = get_post_meta( $id, '_dbfi_ingredienti', true );
		if ( '' === $den && '' === $ing ) {
			return; // Prodotto non alimentare o non compilato.
		}

		$tracce = get_post_meta( $id, '_dbfi_tracce', true );
		$qta    = get_post_meta( $id, '_dbfi_quantita', true );
		$cons   = get_post_meta( $id, '_dbfi_conservazione', true );
		$tmc    = get_post_meta( $id, '_dbfi_tmc', true );
		$osa    = get_post_meta( $id, '_dbfi_operatore', true );
		$terms  = get_the_terms( $id, DBFI_Taxonomy::TAXONOMY );
		$no_all = '1' === get_post_meta( $id, '_dbfi_no_allergeni', true );

		echo '<section class="dbfi-box" aria-labelledby="dbfi-title-' . esc_attr( $id ) . '">';
		echo '<h2 id="dbfi-title-' . esc_attr( $id ) . '" class="dbfi-title">Informazioni alimentari</h2>';

		if ( $den ) {
			echo '<p class="dbfi-row"><span class="dbfi-label">Denominazione:</span> ' . esc_html( $den ) . '</p>';
		}
		if ( $ing ) {
			echo '<p class="dbfi-row"><span class="dbfi-label">Ingredienti:</span> ' . wp_kses_post( self::format_ingredients( $ing ) ) . '</p>';
		}

		if ( $terms && ! is_wp_error( $terms ) ) {
			echo '<div class="dbfi-row"><span class="dbfi-label">Allergeni presenti:</span> ';
			echo '<ul class="dbfi-badges" role="list">';
			foreach ( $terms as $term ) {
				echo '<li class="dbfi-badge"><span aria-hidden="true">' . esc_html( DBFI_Taxonomy::icon( $term->slug ) ) . '</span> ' . esc_html( $term->name ) . '</li>';
			}
			echo '</ul></div>';
		} elseif ( $no_all ) {
			echo '<p class="dbfi-row"><span class="dbfi-label">Allergeni:</span> il prodotto non contiene allergeni dell\'Allegato II Reg. UE 1169/2011.</p>';
		}

		if ( $tracce ) {
			echo '<p class="dbfi-row dbfi-tracce"><span class="dbfi-label">Può contenere tracce di:</span> ' . esc_html( $tracce ) . '</p>';
		}
		if ( $qta ) {
			echo '<p class="dbfi-row"><span class="dbfi-label">Quantità netta:</span> ' . esc_html( $qta ) . '</p>';
		}
		if ( $cons ) {
			echo '<p class="dbfi-row"><span class="dbfi-label">Conservazione:</span> ' . esc_html( $cons ) . '</p>';
		}
		if ( $tmc ) {
			echo '<p class="dbfi-row"><span class="dbfi-label">Da consumarsi preferibilmente entro:</span> ' . esc_html( $tmc ) . '</p>';
		}

		self::render_nutrition( $id );

		if ( $osa ) {
			echo '<p class="dbfi-row dbfi-osa"><span class="dbfi-label">Operatore responsabile:</span> ' . esc_html( $osa ) . '</p>';
		}
		echo '</section>';
	}

	/**
	 * Tabella nutrizionale se compilata.
	 *
	 * @param int $id Product ID.
	 */
	protected static function render_nutrition( $id ) {
		$rows = array();
		foreach ( DBFI_Admin::nutrition_fields() as $key => $label ) {
			$v = get_post_meta( $id, $key, true );
			if ( '' !== $v ) {
				$rows[ $label ] = $v;
			}
		}
		if ( empty( $rows ) ) {
			return;
		}
		echo '<table class="dbfi-nutrition-table"><caption>Dichiarazione nutrizionale (valori medi per 100 g)</caption><tbody>';
		foreach ( $rows as $label => $v ) {
			echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td>' . esc_html( $v ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * Estende il JSON-LD Product di WooCommerce.
	 *
	 * @param array      $markup  Structured data.
	 * @param WC_Product $product Prodotto.
	 * @return array
	 */
	public static function jsonld( $markup, $product ) {
		$settings = DBFI_Admin::settings();
		if ( empty( $settings['jsonld'] ) ) {
			return $markup;
		}
		$id  = $product->get_id();
		$ing = get_post_meta( $id, '_dbfi_ingredienti', true );
		if ( '' === $ing ) {
			return $markup;
		}
		$props   = array();
		$props[] = array(
			'@type' => 'PropertyValue',
			'name'  => 'Ingredienti',
			'value' => str_replace( '*', '', $ing ),
		);
		$terms = get_the_terms( $id, DBFI_Taxonomy::TAXONOMY );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$props[] = array(
				'@type' => 'PropertyValue',
				'name'  => 'Allergeni',
				'value' => implode( ', ', wp_list_pluck( $terms, 'name' ) ),
			);
		}
		$markup['additionalProperty'] = $props;
		return $markup;
	}

	/**
	 * [dbfi_legenda_allergeni] — legenda dei 14 allergeni con conteggio
	 * prodotti e link all'archivio (per pagina informativa del negozio).
	 *
	 * @return string
	 */
	public static function legend_shortcode() {
		$out = '<ul class="dbfi-legend" role="list">';
		foreach ( DBFI_Taxonomy::allergens() as $slug => $data ) {
			$term = get_term_by( 'slug', $slug, DBFI_Taxonomy::TAXONOMY );
			$name = $term ? $term->name : $data[0];
			$out .= '<li class="dbfi-badge"><span aria-hidden="true">' . esc_html( $data[1] ) . '</span> ';
			if ( $term && $term->count > 0 ) {
				$out .= '<a href="' . esc_url( get_term_link( $term ) ) . '">' . esc_html( $name ) . '</a> <span class="dbfi-count">(' . absint( $term->count ) . ')</span>';
			} else {
				$out .= esc_html( $name );
			}
			$out .= '</li>';
		}
		$out .= '</ul>';
		return $out;
	}
}
