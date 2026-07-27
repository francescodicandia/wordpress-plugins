# Barber Booking — Release 1.1.1

**Versione:** 1.1.1  
**Data rilascio:** 2026-07-27  
**Testato su:** WordPress 6.4+, PHP 8.1+, LocalWP  
**Text domain:** `barber-booking`

---

## Panoramica

Barber Booking è un plugin WordPress custom per la gestione delle prenotazioni in negozi di barbiere. È pensato per essere distribuito come plugin standalone su singoli siti WordPress, con un'architettura pronta per futuri scenari white-label/SaaS.

## Novità in 1.1.1

- **Fix:** "headers already sent" su submit form admin (output buffer pulito prima del redirect via `wp_redirect` filter)

## Novità in 1.1.0

- Audit di sicurezza e performance completato (20+ findings risolti)
- **N+1 availability:** prefetch bulk di tutti gli appuntamenti del giorno in 1 query
- **N+1 appuntamenti:** JOIN-based `get_for_range_with_relations()`
- **Nonce defense-in-depth:** tutti gli endpoint admin CRUD verificano il nonce REST
- **Rate-limit IP:** usa solo `REMOTE_ADDR`, soglia filtrabile via `barber_booking_rate_limit_per_hour`
- **Twilio token:** mascherato in input, preservato al salvataggio
- **GDPR consent:** colonna `gdpr_consent_at` in `customers`, passato da frontend
- **Paginazione:** `page`/`per_page` su tutti i list endpoint
- **Max 180gg future:** validazione `barber_booking_max_booking_days`
- **Codice morto rimosso:** metodi inutilizzati in `Notification`, `Brand`, `Appointment`
- **Paginazione impostazioni checkbox:** `preserve_data_on_uninstall`

---

## Funzionalità incluse

- Prenotazione frontend pubblica (shortcode `[barber-booking-form]` e blocco Gutenberg).
- Ruoli custom: `barber_superadmin`, `barber_admin`, `barber`.
- CRUD admin per servizi, postazioni, barbieri, orari di apertura e ferie.
- Calendario settimanale con filtro per barbiere.
- Pannello barbiere che mostra solo i propri appuntamenti.
- Verifica disponibilità in tempo reale via REST API.
- Notifiche WhatsApp via Twilio in modalità test (invio reale solo al numero di test configurato dal superadmin).
- Brand settings centralizzati e filtri white-label.
- Traduzione completa in italiano (`it_IT`).
- Sicurezza: nonce, sanitizzazione, escaping, capability checks, rate limiting IP.

---

## Installazione

1. Scarica `barber-booking-v1.1.1.zip`.
2. Da wp-admin vai in **Plugin → Aggiungi nuovo → Carica plugin**.
3. Seleziona lo zip e clicca **Installa ora**.
4. Attiva il plugin.
5. Il superadmin può configurare brand e Twilio in **Barber Booking → Impostazioni**.

---

## File inclusi

```
barber-booking/
├── barber-booking.php
├── uninstall.php
├── WHITELABEL.md
├── blocks/
│   └── booking-form/
├── includes/
│   ├── API/
│   ├── Admin/
│   ├── Core/
│   ├── Data/
│   ├── Frontend/
│   └── Notifications/
├── languages/
│   ├── barber-booking.pot
│   ├── barber-booking-it_IT.mo
│   └── it_IT.po
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── templates/
    ├── admin/
    └── frontend/
```

---

## Note tecniche

- **Tabelle create all'attivazione:** services, stations, barbers, barber_services, barber_stations, schedules, holidays, customers, appointments, notifications.
- **Hook principali:**
  - `barber_booking_default_brand_settings`
  - `barber_booking_default_settings`
  - `barber_booking_default_opening_hours`
  - `barber_booking_menu_title`
- Vedi `WHITELABEL.md` per istruzioni di rebrandizzazione.

---

## Build del package

Generare lo zip escludendo file di sviluppo/config:

```bash
git archive --format=zip --output=barber-booking-v1.1.1.zip HEAD
```

Questo comando rispetta `.gitignore` (esclude `.env`, `vendor/`, `*.zip`, ecc.).

## Verifica del package

- PHP syntax check: passato.
- PHPCS con standard WordPress: passato.
- Test end-to-end su LocalWP (creazione servizio, postazione, barbiere, prenotazione, controllo sovrapposizione): passato.

---

## Supporto / sviluppo futuro

- Integrazione pagamenti (gateway Stripe/PayPal placeholder già presente).
- Multilingua completa.
- Notifiche email/SMS aggiuntive.
- Tema di default per pagina di prenotazione.
