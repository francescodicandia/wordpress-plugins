# AGENTS.md — WordPress Plugin Development

## Skills di riferimento
Caricare quando pertinenti al task:
- `wordpress-pro` → temi, blocchi Gutenberg, REST API, WooCommerce, ACF, performance
- `wp-plugin-development` → architettura plugin, lifecycle, Settings API, cron, packaging

## Standard
- WordPress Coding Standards: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/
- Solo API native WordPress; nessun framework esterno se non esplicitamente richiesto
- Tutte le stringhe utente internazionalizzate con text domain
- Target locale di default: `it_IT`; ogni stringa visibile nuova va tradotta nel file `.po` e rigenerato il `.mo`
- Mantenere README e RELEASE aggiornati per ogni versione rilasciata

---

## Workflow

### Fase 1 — Architettura
1. Carica `wp-plugin-development` per le linee guida strutturali
2. Progetta classi principali, endpoint REST (`register_rest_route`), hook necessari
3. Single bootstrap file con header del plugin; loader su hook, no side-effect a load time
4. Admin code dietro `is_admin()` per ridurre overhead frontend
5. Mantieni struttura chiara: `/includes/`, `/templates/`, `/assets/`

### Fase 2 — Boilerplate
1. Hook di attivazione/disattivazione/uninstall (`register_activation_hook`, `uninstall.php`)
2. CPT e taxonomy (`register_post_type`, `register_taxonomy`)
3. Enqueue assets (`wp_enqueue_script`, `wp_enqueue_style`)
4. Settings page con Settings API (`register_setting`, `add_settings_section`, `add_settings_field`)
5. Output codice puro, senza spiegazioni ridondanti

### Fase 3 — Sicurezza
1. Carica `wordpress-pro` per i pattern di sicurezza
2. Sanitizzazione input: `sanitize_text_field()`, `wp_unslash()`, `wp_kses_post()`, `esc_url_raw()`
3. Escaping output: `esc_html()`, `esc_url()`, `esc_attr()`, `wp_kses_post()`
4. Nonce su ogni form/AJAX: `wp_nonce_field()` + `wp_verify_nonce()` o `check_admin_referer()`
5. Capability check: `current_user_can()` prima di ogni operazione privilegiata
6. SQL: sempre `$wpdb->prepare()`, mai concatenazione. Usa `$wpdb->prefix` per nomi tabella
7. Verifica finale: `phpcs --standard=WordPress`

### Fase 4 — Release & Maintenance
1. Versionamento semantico (`MAJOR.MINOR.PATCH`)
2. Prima di ogni release: `php -l` su tutti i file PHP e `phpcs --standard=WordPress`
3. Aggiornare `.pot`, `.po`, `.mo` quando si aggiungono o modificano stringhe utente
4. Creare zip di release pronto per l'installazione (`Plugin → Aggiungi nuovo → Carica plugin`)
5. Aggiornare `README.md` e `RELEASE.md` con le novità della versione
6. Per plugin complessi: aggiungere una pagina di istruzioni nel backend (menu admin)
7. Eseguire un test end-to-end del flusso principale prima di pubblicare

---

## Repository
- Inizializzare Git e mantenere `.gitignore` che escluda:
  - `vendor/`
  - `*.zip`, `dist/`, `build/`
  - `.env`, `.env.local`
  - file di backup delle traduzioni (`*.po~`, `*.mo~`)
  - OS/IDE files (`.DS_Store`, `.vscode/`, `.idea/`)
- Non commettere dipendenze di sviluppo, artefatti di build o secrets
- Preferire repository privati per plugin commerciali/white-label; usare licenza GPL-compatibile se si intende distribuire su WordPress.org

---

## MUST NOT
- Fidarsi di `$_POST`/`$_GET`/`$_REQUEST` senza sanitizzazione
- Output senza escaping
- SQL con concatenazione stringhe
- Modificare file core di WordPress
- Omettere capability check nelle funzioni admin
- Usare PHP short tag o funzioni deprecate
- Omettere internazionalizzazione (i18n) nelle stringhe visibili
- Scrivere secrets, token o credenziali direttamente nel codice

## SHOULD
- Aggiungere una pagina di istruzioni o help nel backend per plugin complessi
- Eseguire un test end-to-end del flusso principale prima di ogni release
- Mantenere `TASKS.md` o equivalente tracker durante lo sviluppo
