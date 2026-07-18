# DB Food Info

Informazioni alimentari conformi al **Reg. UE 1169/2011** per prodotti WooCommerce. Pensato per laboratori artigianali, aziende agricole e PMI agroalimentari che vendono online.

*Niente registrazioni, niente nag, niente trucchi.*

## Perché

Nella vendita a distanza di alimenti, le informazioni obbligatorie (ingredienti, allergeni evidenziati, quantità netta, operatore responsabile, conservazione) devono essere **disponibili prima della conclusione dell'acquisto** (art. 14 Reg. 1169/2011). Affidarle alla descrizione libera del prodotto significa dimenticarle. Questo plugin le rende strutturate, visibili e — se vuoi — obbligatorie.

## Funzionalità

- **Tassonomia dei 14 allergeni** (Allegato II) precaricata all'attivazione, con archivi filtrabili e colonna in admin
- **Metabox prodotto** con i campi obbligatori: denominazione legale, ingredienti, tracce, quantità netta, conservazione, TMC, operatore responsabile (precompilabile dalle impostazioni)
- **Allergeni evidenziati negli ingredienti**: scrivi `*nocciole*` e sulla pagina prodotto appare **NOCCIOLE** in grassetto, come richiesto dall'art. 21
- **Box "Informazioni alimentari"** renderizzato *prima* del pulsante Acquista (hook `woocommerce_single_product_summary`, priorità 25), accessibile (WCAG 2.1 AA)
- **Dichiarazione nutrizionale** facoltativa (valori per 100 g) con tabella dedicata
- **Blocco di pubblicazione**: i prodotti nelle categorie sorvegliate non sono pubblicabili se mancano i campi obbligatori o la valutazione allergeni (termini assegnati oppure dichiarazione esplicita di assenza) — conformità by design
- **JSON-LD**: ingredienti e allergeni aggiunti ai dati strutturati del prodotto WooCommerce
- **Shortcode `[dbfi_legenda_allergeni]`** per la pagina informativa del negozio, con conteggi e link agli archivi
- **Aggiornamenti automatici** da GitHub Releases (componente condiviso DB Plugin Suite)

## Installazione

1. Carica lo ZIP da *Plugin → Aggiungi nuovo → Carica plugin*
2. Attiva. I 14 allergeni vengono creati automaticamente
3. *Impostazioni → DB Food Info*: imposta l'operatore responsabile predefinito e le categorie soggette a blocco di pubblicazione

## Note di conformità

- Il plugin è uno strumento tecnico: la correttezza dei contenuti (ricette, allergeni, tracce) resta responsabilità dell'operatore del settore alimentare.
- La dichiarazione nutrizionale è obbligatoria in via generale; l'esenzione dell'Allegato V p. 19 (fornitura diretta in piccole quantità a livello locale) va valutata con un consulente per la vendita online.
- Nessun dato personale trattato, nessun cookie, nessuna chiamata a servizi esterni (eccetto il controllo aggiornamenti verso GitHub, solo lato admin).

## Requisiti

WordPress 5.8+, PHP 7.4+, WooCommerce attivo.

## Licenza

GPL v2 or later — © Davide Bertolino · [davidebertolino.it](https://www.davidebertolino.it)

## Changelog

### 1.0.0
- Prima release: tassonomia allergeni, metabox 1169/2011, box frontend, dichiarazione nutrizionale, blocco di pubblicazione, JSON-LD, shortcode legenda, updater GitHub.
