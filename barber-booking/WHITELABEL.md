# White-label Guide — Barber Booking

This document explains how to rebrand and pre-configure the Barber Booking plugin for a specific client or third-level domain.

## Brand Settings

Brand settings are centralized in `BarberBooking\Core\Brand` and stored in the option `barber_booking_settings`.

You can change them from:

- **WP Admin → Barber Booking → Settings → Brand & White-label**
- Programmatically via filters (see below)

Available brand keys:

| Key               | Description                          |
|-------------------|--------------------------------------|
| `brand_name`      | Name shown in the booking form       |
| `brand_logo`      | URL of the logo image                |
| `primary_color`   | Primary brand color (hex)            |
| `secondary_color` | Secondary/accent color (hex)         |
| `custom_css`      | Additional CSS for the frontend      |
| `privacy_page`    | Page ID used for the privacy link    |

## Filters

### `barber_booking_default_brand_settings`

Override only the brand defaults before they are saved on activation.

```php
add_filter(
	'barber_booking_default_brand_settings',
	static function ( array $defaults ): array {
		$defaults['brand_name']      = 'My Barber Shop';
		$defaults['primary_color']   = '#000000';
		$defaults['secondary_color'] = '#ffcc00';
		return $defaults;
	}
);
```

### `barber_booking_default_settings`

Override the full default settings array (brand, Twilio, notifications, payments, opening hours, etc.).

```php
add_filter(
	'barber_booking_default_settings',
	static function ( array $defaults ): array {
		$defaults['brand_name']       = 'My Barber Shop';
		$defaults['primary_color']    = '#000000';
		$defaults['secondary_color']  = '#ffcc00';
		$defaults['slot_interval']    = 30;
		$defaults['opening_hours']    = array(
			array(), // Sunday closed.
			array( array( 'start' => '08:00', 'end' => '20:00' ) ),
			array( array( 'start' => '08:00', 'end' => '20:00' ) ),
			array( array( 'start' => '08:00', 'end' => '20:00' ) ),
			array( array( 'start' => '08:00', 'end' => '20:00' ) ),
			array( array( 'start' => '08:00', 'end' => '20:00' ) ),
			array( array( 'start' => '08:00', 'end' => '20:00' ) ),
		);
		return $defaults;
	}
);
```

### `barber_booking_default_opening_hours`

Override only the default opening hours.

```php
add_filter(
	'barber_booking_default_opening_hours',
	static function ( array $hours ): array {
		$hours[1] = array( array( 'start' => '08:00', 'end' => '20:00' ) );
		return $hours;
	}
);
```

### `barber_booking_menu_title`

Change the top-level admin menu label.

```php
add_filter(
	'barber_booking_menu_title',
	static function ( string $title ): string {
		return 'My Barber Shop';
	}
);
```

## Pre-configured Installations

To ship a pre-configured plugin, place the filters above in a small mu-plugin or in the theme `functions.php`. The defaults are applied only when the plugin is activated for the first time, so make sure the filters are active **before** activation.

Example mu-plugin: `wp-content/mu-plugins/my-barber-brand.php`

```php
<?php
/**
 * Pre-configured brand for Barber Booking.
 */

add_filter(
	'barber_booking_default_settings',
	static function ( array $defaults ): array {
		$defaults['brand_name']      = 'My Barber Shop';
		$defaults['brand_logo']      = 'https://example.com/logo.png';
		$defaults['primary_color']   = '#111111';
		$defaults['secondary_color'] = '#d4af37';
		$defaults['slot_interval']   = 15;
		return $defaults;
	}
);

add_filter(
	'barber_booking_menu_title',
	static function ( string $title ): string {
		return 'My Barber Shop';
	}
);
```

## Full Rebrand (text domain and slug)

For a complete white-label (different text domain, plugin slug, PHP namespace), you currently need to perform a manual search/replace over the source code:

1. Copy the plugin folder to a new name, e.g. `my-barber-booking`.
2. Rename `barber-booking.php` to `my-barber-booking.php` and update the plugin header.
3. Replace globally:
   - `barber-booking` → `my-barber-booking` (text domain, option names, table prefixes, REST namespace, etc.)
   - `BarberBooking` → `MyBarberBooking` (PHP namespace)
   - `barber_booking_` → `my_barber_booking_` (option/hook/cron prefixes)
   - `wp_barber_` → `wp_mybarber_` (database table prefixes)
4. Regenerate the `.pot` file and translations.
5. Update constants in the main plugin file.

A future version may provide an automated build script for this step.

## Notes

- Always test the re-branded plugin on a staging/local site (e.g. LocalWP) before deploying to production.
- Translations must match the final text domain. If you change the text domain, regenerate `languages/*.pot` and `*.po`/`*.mo` files.
- Database tables are created with the prefix configured in the plugin constants, so changing the prefix after activation requires manual migration or a fresh install.
