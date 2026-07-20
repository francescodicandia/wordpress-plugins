# Barber Booking — Release 1.0.0

**Versione:** 1.0.0  
**Data rilascio:** 2026-07-20  
**Testato su:** WordPress 6.4+, PHP 8.1+, LocalWP  
**Text domain:** `barber-booking`

---

## Panoramica

Barber Booking è un plugin WordPress custom per la gestione delle prenotazioni in negozi di barbiere. È pensato per essere distribuito come plugin standalone su singoli siti WordPress, con un'architettura pronta per futuri scenari white-label/SaaS.

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

1. Scarica `barber-booking-v1.0.0.zip`.
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
