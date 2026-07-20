# AGENTS.md — WordPress Plugin Development

## Skills di riferimento
Caricare quando pertinenti al task:
- `wordpress-pro` → temi, blocchi Gutenberg, REST API, WooCommerce, ACF, performance
- `wp-plugin-development` → architettura plugin, lifecycle, Settings API, cron, packaging

## Standard
- WordPress Coding Standards: https://developer.wordpress.org/coding-standards/wordpress-coding-standards/
- Solo API native WordPress; nessun framework esterno se non esplicitamente richiesto
- Tutte le stringhe utente internazionalizzate con text domain

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

---

## MUST NOT
- Fidarsi di `$_POST`/`$_GET`/`$_REQUEST` senza sanitizzazione
- Output senza escaping
- SQL con concatenazione stringhe
- Modificare file core di WordPress
- Omettere capability check nelle funzioni admin
- Usare PHP short tag o funzioni deprecate
- Omettere internazionalizzazione (i18n) nelle stringhe visibili
