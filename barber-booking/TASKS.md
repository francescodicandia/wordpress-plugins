# Barber Booking — Task Tracker

Questo file tiene traccia dello sviluppo del plugin. Le voci completate vengono rimosse o spuntate man mano.

## Fase 1 — Fondamenta
- [x] Creare struttura cartelle e file principale `barber-booking.php`
- [x] Implementare autoloader e constants
- [x] Implementare `class-plugin.php` e hook di inizializzazione
- [x] Implementare `class-activator.php`: tabelle, ruoli, capabilities, default options
- [x] Implementare `class-deactivator.php`: pulizia cron
- [x] Implementare `uninstall.php`: rimozione dati opzionale
- [x] Implementare `class-capabilities.php`: ruoli `barber_superadmin`, `barber_admin`, `barber`
- [x] Implementare `class-assets.php`: enqueue admin e frontend
- [x] Implementare `class-i18n.php`: text domain `barber-booking`
- [x] Creare file `.pot` per traduzioni
- [x] Tradurre tutto in italiano (`it_IT.po` e `it_IT.mo`)

## Fase 2 — Settings API
- [x] Pagina impostazioni generali: brand, logo, colori, CSS custom
- [x] Pagina impostazioni Twilio: Account SID, Auth Token, From number, Test Mode, numero test
- [x] Pagina impostazioni notifiche: attiva conferma, attiva reminder, ore reminder
- [x] Pagina impostazioni orari: orario di apertura default (gestito in Fase 4)
- [x] Pagina impostazioni pagamenti: placeholder (enabled false, gateway, deposito)

## Fase 3 — Data layer
- [x] Creare tabelle con `dbDelta`
- [x] Implementare `class-service.php`
- [x] Implementare `class-station.php`
- [x] Implementare `class-barber.php`
- [x] Implementare `class-schedule.php`
- [x] Implementare `class-holiday.php`
- [x] Implementare `class-customer.php`
- [x] Implementare `class-appointment.php`
- [x] Implementare `class-notification.php`

## Fase 4 — Admin UI
- [x] Menu principale “Barber Booking”
- [x] CRUD servizi
- [x] CRUD postazioni
- [x] CRUD barbieri
- [x] Associazione barbiere ↔ postazioni
- [x] Associazione barbiere ↔ servizi
- [x] Gestione orari e ferie
- [x] Calendario prenotazioni (vista settimanale default)
- [x] Lista prenotazioni con filtri
- [x] Pannello barbiere (vista proprie prenotazioni)
- [x] Pulsante “Scrivi su WhatsApp” nelle prenotazioni

## Fase 5 — REST API
- [x] Endpoint `GET /services`
- [x] Endpoint `GET /barbers`
- [x] Endpoint `GET /availability`
- [x] Endpoint `POST /bookings` (con nonce pubblico)
- [x] Endpoint admin CRUD protetti da capability

## Fase 6 — Frontend
- [x] Shortcode `[barber-booking-form]`
- [x] Blocco Gutenberg
- [x] Form a step: servizio → barbiere → data/ora → dati → conferma
- [x] Algoritmo disponibilità slot
- [x] Stile responsive con brand settings
- [x] Validazione e rate limiting

## Fase 7 — Notifiche
- [x] Classe `class-notifier.php`
- [x] Classe `class-twilio-whatsapp.php` con `wp_remote_post`
- [x] Modalità test: invio reale solo al numero di test autorizzato
- [x] Invio conferma WhatsApp al salvataggio
- [x] WP Cron per reminder
- [x] Log notifiche in `wp_barber_notifications`

## Fase 8 — Sicurezza e QA
- [x] Nonce su form e endpoint
- [x] Capability checks
- [x] Sanitizzazione ed escaping
- [x] SQL con `$wpdb->prepare`
- [x] GDPR: checkbox consenso
- [x] PHPCS/WPCS check
- [x] Test attivazione/disattivazione/uninstall su LocalWP via Novamira

## Fase 9 — White-label
- [x] Centralizzare brand settings
- [x] Preparare hook per generazione plugin pre-configurato
- [x] Documentare come rebrandizzare

## Note
- WordPress 6.4+, PHP 8.1+
- Twilio API configurabile solo dal superadmin
- Cliente non si registra: prenota tramite nome/email/telefono
- Stato prenotazione default: `confirmed`
- Calendario admin default: vista settimanale
- PHPCS/WPCS: check superato al 2026-07-19
- Plugin aggiornato su LocalWP (`barberbooking.local`) e traduzioni italiane caricate al 2026-07-19
- Fase 2 (impostazioni orari) caricata su LocalWP al 2026-07-19
- Fix warning Array to string conversion su reminder hours caricato su LocalWP al 2026-07-19
- Fase 4 (gestione orari e ferie) caricata su LocalWP al 2026-07-19
- Fase 5 (endpoint admin CRUD protetti da capability) caricata su LocalWP al 2026-07-19
- Fase 6 (blocco Gutenberg) caricata su LocalWP al 2026-07-19
- Fase 9 (white-label) caricata su LocalWP al 2026-07-20
- Test end-to-end su LocalWP (servizio, postazione, barbiere, prenotazione, sovrapposizione) al 2026-07-20
- Packaging finale v1.0.0 generato al 2026-07-20
- Pubblicato su GitHub in `francescodicandia/wordpress-plugins/barber-booking` al 2026-07-20

## Fase 11 — Pubblicazione su GitHub
- [x] Inizializzare repository Git locale
- [x] Creare `.gitignore`, `README.md`, `LICENSE` GPL-2.0+
- [x] Aggiungere workflow CI con PHPCS e syntax check
- [x] Pushare sul repo `francescodicandia/wordpress-plugins` in sottocartella `barber-booking/`
- [ ] Creare release v1.0.0 su GitHub con allegato zip (richiede token con permesso releases)

## Fase 12 — Pagina istruzioni backend
- [x] Creare template `templates/admin/instructions.php`
- [x] Aggiungere voce di menu "Istruzioni" in `includes/Admin/Admin.php`
- [x] Aggiornare traduzioni `.pot`, `.po`, `.mo`
- [x] Deploy su LocalWP e verificare accesso dal backend

## Note aggiornate
- Pagina istruzioni backend caricata su LocalWP al 2026-07-20

## Fase 10 — Rilascio e packaging
- [x] Test end-to-end completo su LocalWP
- [x] Verifica prenotazione, sovrapposizione, calendario, disponibilità
- [x] Creare `RELEASE.md`
- [x] Generare `barber-booking-v1.0.0.zip`
- [x] Generare `barber-booking.zip` (latest)
