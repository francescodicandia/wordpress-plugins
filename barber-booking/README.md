# Barber Booking

Plugin WordPress per la gestione delle prenotazioni in negozi di barbiere.

## Descrizione

Barber Booking permette ai clienti di prenotare un appuntamento online scegliendo servizio, barbiere, data e orario. Include un pannello admin completo con calendario, gestione di servizi, postazioni, barbieri, orari di apertura e ferie. È pensato per essere distribuito come plugin standalone su singoli siti WordPress, con un'architettura pronta per scenari white-label/SaaS.

## Requisiti

- WordPress 6.4+
- PHP 8.1+

## Installazione

1. Scarica `barber-booking-v1.0.0.zip` dalla sezione Releases.
2. Da wp-admin vai in **Plugin → Aggiungi nuovo → Carica plugin**.
3. Seleziona lo zip e clicca **Installa ora**.
4. Attiva il plugin.

Dopo l'attivazione, accedi con un utente `barber_superadmin` per configurare brand, Twilio e orari di apertura in **Barber Booking → Impostazioni**.

## Utilizzo

Inserisci il form di prenotazione in una pagina con:

- Shortcode: `[barber-booking-form]`
- Blocco Gutenberg: **Barber Booking Form**

## Struttura del plugin

```
barber-booking/
├── barber-booking.php
├── uninstall.php
├── includes/
│   ├── API/         # Endpoint REST
│   ├── Admin/       # UI wp-admin
│   ├── Core/        # Autoloader, attivazione, brand
│   ├── Data/        # Data layer (tabelle custom)
│   ├── Frontend/    # Shortcode e blocco
│   └── Notifications/ # Twilio WhatsApp
├── languages/       # Traduzioni (it_IT)
├── templates/       # Template admin e frontend
├── assets/          # CSS e JS
└── blocks/          # Blocco Gutenberg
```

## Sviluppo

```bash
# Installa le dipendenze di sviluppo
composer install

# Controllo sintassi PHP
find . -type f -name '*.php' ! -path './vendor/*' -exec php -l {} \;

# Controllo coding standard WordPress
./vendor/bin/phpcs --standard=phpcs.xml.dist
```

## White-label

Vedi [`WHITELABEL.md`](WHITELABEL.md) per istruzioni su come rebrandizzare e pre-configurare il plugin.

## Licenza

GPL-2.0-or-later
