# DB Food Info

**EU-compliant food information for WooCommerce products (Reg. EU 1169/2011).**
Ingredients with highlighted allergens, the 14 Annex II allergens as a taxonomy, nutrition declaration, structured data, and publish-blocking for incomplete products.

Built for Italian artisan food producers, farms and SMEs selling online.

*No sign-ups, no nags, no tricks.*

---

## Features

### Food information (Reg. EU 1169/2011)
- **14 Annex II allergens** pre-seeded as a `dbfi_allergene` taxonomy on activation: filterable archives, admin column, icon badges on the product page
- **Highlighted allergens in the ingredients list**: write `*hazelnuts*` and the front end renders **HAZELNUTS** in bold, as required by art. 21
- **Structured product fields**: legal denomination, ingredients, "may contain traces of", net quantity, storage conditions, best-before, responsible food business operator (pre-fillable from settings)
- **Nutrition declaration** (per 100 g) with an accessible table — optional, for businesses covered by the Annex V exemption
- **Front-end box rendered before the Add to cart button** (`woocommerce_single_product_summary`, priority 25): information available *before* purchase, as art. 14 requires for distance selling

### Compliance by design
- **Publish gating**: products in monitored categories cannot be published with empty mandatory fields or an unassessed allergen status — they are returned to draft with the exact list of what is missing
- **Explicit allergen assessment**: "no allergens" is a conscious declaration (checkbox), never an omission
- **JSON-LD**: ingredients and allergens added to the WooCommerce product structured data
- `[dbfi_legenda_allergeni]` shortcode: allergen legend with product counts and archive links
- `[dbfi_info_prodotto id=""]` shortcode: render the food-info box anywhere (page-builder-proof)

### Privacy by Design
- No personal data processed, no cookies, no external services on the front end
- The only remote call is the update check against the GitHub API, admin-side only
- Declares its (absence of) processing to the site owner via the WordPress privacy policy guide (Tools → Privacy)
- `uninstall.php` removes options and transients; product food data is deleted only if explicitly enabled in settings (it is catalog content, not plugin junk)

## Installation

1. Download the ZIP from the [latest release](../../releases/latest)
2. WordPress → *Plugins → Add New → Upload Plugin* → activate
3. The 14 allergens are created automatically
4. *Settings → DB Food Info*: set the default food business operator and the product categories subject to publish gating

## Requirements

- WordPress 5.8+
- PHP 7.4+
- WooCommerce (active)

## Tech Stack

- Vanilla PHP, zero external dependencies, zero build step
- Shared DB Plugin Suite components: `DB_GitHub_Updater`, `db-admin-ui.css`
- WCAG 2.1 AA front-end output
- CI: PHP lint matrix (7.4 → 8.3) + PHPCS (WordPress-Extra); release ZIP built automatically on tag push

## How It Works

All food data is stored as plain post meta with the `_dbfi_` prefix — queryable, exportable, WP-CLI friendly. The allergen taxonomy is public with archives (`/allergene/frutta-a-guscio/`). The publish gate hooks `save_post_product` at priority 20 (after meta save) and returns non-compliant products to draft, storing the missing-field list in a per-user transient for the admin notice.

## Public Filters & Hooks

- The front-end box uses standard WooCommerce hooks: relocate it via `remove_action` / `add_action` on `DBFI_Frontend::render_box`
- `DBFI_Taxonomy::allergens()` is the single source of truth for the 14 allergens (slug, name, icon)

## License

GPL v2 or later — see [LICENSE](LICENSE)

## Author

**Davide Bertolino** — [davidebertolino.it](https://www.davidebertolino.it)
Part of the **DB Plugin Suite**: standalone, free, privacy-by-design WordPress plugins for Italian schools and SMEs.

---

## 🇮🇹 Italiano

**Informazioni alimentari conformi al Reg. UE 1169/2011 per prodotti WooCommerce.**

Nella vendita a distanza di alimenti, ingredienti e allergeni devono essere disponibili **prima della conclusione dell'acquisto** (art. 14). DB Food Info li trasforma in campi strutturati e obbligatori: tassonomia dei 14 allergeni dell'Allegato II precaricata, allergeni evidenziati in grassetto negli ingredienti (sintassi `*nocciole*`), dichiarazione nutrizionale facoltativa, dati strutturati JSON-LD e — soprattutto — **blocco di pubblicazione**: il prodotto alimentare incompleto torna in bozza con l'elenco esatto di ciò che manca. La valutazione allergeni è sempre esplicita: o si assegnano i termini, o si dichiara l'assenza.

Il plugin non tratta dati personali, non imposta cookie e non contatta servizi esterni sul frontend: nulla da dichiarare in Privacy o Cookie Policy. Alla disinstallazione i dati alimentari dei prodotti vengono conservati, salvo scelta esplicita contraria nelle impostazioni.

**Nota di conformità**: il plugin è uno strumento tecnico; la correttezza di ricette, allergeni e tracce resta responsabilità dell'operatore del settore alimentare (art. 8). L'esenzione dalla dichiarazione nutrizionale (Allegato V, p. 19) va valutata con un consulente, in particolare per la vendita online.

## Changelog

### 1.1.1 — 2026-07
- Nuovo shortcode `[dbfi_info_prodotto id=""]`: renderizza il box informazioni alimentari ovunque — pensato per temi e page builder che bypassano gli hook WooCommerce (senza `id` usa il prodotto corrente)
- Legenda automatica "Gli allergeni sono evidenziati in grassetto" sotto l'elenco ingredienti, mostrata solo quando almeno un allergene è marcato
- `render_box()` accetta ora un ID prodotto esplicito

### 1.1.0 — 2026-07
- Allineamento completo alle convenzioni DB Plugin Suite: bootstrap con `DBFI_Core` singleton, updater canonico condiviso, `db-admin-ui.css` ufficiale
- Internazionalizzazione: tutte le stringhe nel text domain `db-food-info`, file `.pot` in `languages/`
- Nuovo `uninstall.php` multisite-aware con opzione "elimina dati alla disinstallazione" (default: conserva)
- Nuova classe `DBFI_Privacy`: dichiarazione formale di assenza di trattamenti nella guida policy di WordPress
- CI GitHub Actions (lint PHP 7.4–8.3 + PHPCS) e release automatica dello ZIP al push del tag
- File LICENSE

### 1.0.0
- Prima release: tassonomia allergeni Allegato II, metabox 1169/2011, box frontend pre-acquisto, dichiarazione nutrizionale, blocco di pubblicazione con valutazione allergeni esplicita, JSON-LD, shortcode legenda, updater GitHub.

## Internationalization

Text domain: `db-food-info`. Template file: `languages/db-food-info.pot`. Source strings are in Italian (the plugin's primary audience); translations welcome via pull request.
