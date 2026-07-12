# WordPress Credits Mentors

Plugin WordPress che mostra i mentor del programma WordPress Credits via shortcode. I dati vengono caricati da Airtable.

## Requisiti

- WordPress 5.0+
- PHP 7.4+
- Airtable base con tabella mentors configurata

## Installazione

1. Scarica la cartella `wordpress-credits-mentors` in `/wp-content/plugins/`
2. Attiva il plugin da WP Admin
3. Vai in **Impostazioni → Credits Mentors** e configura:
   - **Airtable API Token** — Personal Access Token con accesso in lettura alla base
   - **Allowed Statuses** — Status records da includere (default: `Active`)
4. Definisci le costanti nel `wp-config.php`:
   ```php
   define( 'WPCM_AIRTABLE_BASE_ID', 'appXXXXXXXXXXXXXX' );
   define( 'WPCM_AIRTABLE_TABLE_ID', 'tblXXXXXXXXXXXXXX' );
   ```

## Shortcode

```
[wpcredits_mentors]
[wpcredits_mentors view=table]
[wpcredits_mentors view=table fields="Full Name,Email,Sponsor company"]
[wpcredits_mentors columns=2]
```

### Parametri

| Parametro | Descrizione | Default |
|-----------|-------------|---------|
| `view`    | `grid` (card) o `table` | `grid` |
| `columns` | Numero colonne nella grid (1-4) | `3` |
| `fields`  | Colonne nella vista table (nomi campi Airtable) | `Full Name, Email, WordPress profile, Contribution Area - Expertise, Available hours per week, Sponsor company` |

Alias backward-compatibili per `fields`: `name`, `email`, `expertise`, `hours`, `sponsor`, `profile`, `status`, `company`.

## Sviluppo

Il plugin è pensato per il progetto [WordPress Credits](https://wordpress.org/credits/) e si integra con il programma di mentoring delle istituzioni partner.
