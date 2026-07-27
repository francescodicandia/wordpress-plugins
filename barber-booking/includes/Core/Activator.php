<?php
/**
 * Plugin activation.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Activator class.
 */
class Activator {

	/**
	 * Activate plugin.
	 */
	public static function activate(): void {
		self::check_requirements();
		self::create_tables();
		self::insert_default_schedules();
		self::set_default_options();
		self::schedule_events();
		Capabilities::add();
		update_option( \BarberBooking\PLUGIN_DB_VERSION_OPTION, \BarberBooking\PLUGIN_DB_VERSION );
		flush_rewrite_rules();
	}

	/**
	 * Check system requirements.
	 */
	private static function check_requirements(): void {
		load_plugin_textdomain( 'barber-booking', false, \BarberBooking\PLUGIN_DIR . 'languages' );

		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			deactivate_plugins( \BarberBooking\PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'This plugin requires PHP 8.1 or higher.', 'barber-booking' ),
				esc_html__( 'Plugin Activation Error', 'barber-booking' ),
				array( 'back_link' => true )
			);
		}

		global $wp_version;
		if ( version_compare( $wp_version, '6.4', '<' ) ) {
			deactivate_plugins( \BarberBooking\PLUGIN_BASENAME );
			wp_die(
				esc_html__( 'This plugin requires WordPress 6.4 or higher.', 'barber-booking' ),
				esc_html__( 'Plugin Activation Error', 'barber-booking' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Create custom tables.
	 */
	private static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$prefix          = $wpdb->prefix . 'barber_';

		$sql = "CREATE TABLE {$prefix}services (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			description text,
			duration int(10) unsigned NOT NULL DEFAULT 30,
			price decimal(10,2) NOT NULL DEFAULT 0.00,
			color varchar(7) DEFAULT '#000000',
			image_id bigint(20) unsigned DEFAULT 0,
			active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY active (active)
		) {$charset_collate};

		CREATE TABLE {$prefix}stations (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			color varchar(7) DEFAULT '#000000',
			active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY active (active)
		) {$charset_collate};

		CREATE TABLE {$prefix}barbers (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned DEFAULT NULL,
			name varchar(255) NOT NULL,
			email varchar(255) DEFAULT NULL,
			phone varchar(50) DEFAULT NULL,
			color varchar(7) DEFAULT '#000000',
			photo_id bigint(20) unsigned DEFAULT 0,
			active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY active (active),
			KEY user_id (user_id)
		) {$charset_collate};

		CREATE TABLE {$prefix}barber_station (
			barber_id bigint(20) unsigned NOT NULL,
			station_id bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (barber_id, station_id),
			KEY station_id (station_id)
		) {$charset_collate};

		CREATE TABLE {$prefix}barber_service (
			barber_id bigint(20) unsigned NOT NULL,
			service_id bigint(20) unsigned NOT NULL,
			price decimal(10,2) DEFAULT NULL,
			duration int(10) unsigned DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (barber_id, service_id),
			KEY service_id (service_id)
		) {$charset_collate};

		CREATE TABLE {$prefix}schedules (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			barber_id bigint(20) unsigned DEFAULT NULL,
			day_of_week tinyint(1) NOT NULL,
			start_time time NOT NULL,
			end_time time NOT NULL,
			active tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY barber_day (barber_id, day_of_week, active)
		) {$charset_collate};

		CREATE TABLE {$prefix}holidays (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			barber_id bigint(20) unsigned DEFAULT NULL,
			holiday_date date NOT NULL,
			start_time time DEFAULT NULL,
			end_time time DEFAULT NULL,
			all_day tinyint(1) NOT NULL DEFAULT 1,
			reason varchar(255) DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY barber_date (barber_id, holiday_date)
		) {$charset_collate};

		CREATE TABLE {$prefix}customers (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			name varchar(255) NOT NULL,
			email varchar(255) DEFAULT NULL,
			phone varchar(50) NOT NULL,
			notes text,
			gdpr_consent_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY phone (phone)
		) {$charset_collate};

		CREATE TABLE {$prefix}appointments (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			customer_id bigint(20) unsigned NOT NULL,
			service_id bigint(20) unsigned NOT NULL,
			barber_id bigint(20) unsigned NOT NULL,
			station_id bigint(20) unsigned DEFAULT NULL,
			appointment_date date NOT NULL,
			start_time time NOT NULL,
			end_time time NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'confirmed',
			notes text,
			source varchar(50) DEFAULT 'web',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY date_status (appointment_date, status),
			KEY barber_date (barber_id, appointment_date),
			KEY station_date (station_id, appointment_date),
			KEY customer_id (customer_id)
		) {$charset_collate};

		CREATE TABLE {$prefix}appointment_meta (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			appointment_id bigint(20) unsigned NOT NULL,
			meta_key varchar(255) NOT NULL,
			meta_value longtext,
			PRIMARY KEY (id),
			KEY appointment_key (appointment_id, meta_key(191))
		) {$charset_collate};

		CREATE TABLE {$prefix}notifications (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			appointment_id bigint(20) unsigned NOT NULL,
			channel varchar(20) NOT NULL,
			type varchar(50) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			external_id varchar(255) DEFAULT NULL,
			error_message text,
			scheduled_at datetime DEFAULT NULL,
			sent_at datetime DEFAULT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY appointment_id (appointment_id),
			KEY status_scheduled (status, scheduled_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * Set default options.
	 */
	private static function set_default_options(): void {
		if ( false !== get_option( \BarberBooking\PLUGIN_SETTINGS ) ) {
			return;
		}

		$defaults = array(
			'brand_name'                        => get_bloginfo( 'name' ),
			'brand_logo'                        => '',
			'primary_color'                     => '#1a1a1a',
			'secondary_color'                   => '#c9a227',
			'custom_css'                        => '',
			'privacy_page'                      => '',
			'twilio_account_sid'                => '',
			'twilio_auth_token'                 => '',
			'twilio_from_number'                => '',
			'twilio_test_mode'                  => true,
			'twilio_test_number'                => '',
			'twilio_content_sid_confirmation'   => '',
			'twilio_content_sid_reminder'       => '',
			'notification_confirmation_enabled' => true,
			'notification_reminder_enabled'     => true,
			'notification_reminder_hours'       => '24',
			'email_backup_enabled'              => false,
			'payment_enabled'                   => false,
			'payment_gateway'                   => 'stripe',
			'payment_mode'                      => 'full',
			'deposit_amount'                    => 0,
			'opening_hours'                     => self::default_opening_hours(),
			'slot_interval'                     => 15,
		);

		$defaults = apply_filters( 'barber_booking_default_settings', $defaults );

		add_option( \BarberBooking\PLUGIN_SETTINGS, $defaults );
	}

	/**
	 * Insert default schedules.
	 */
	private static function insert_default_schedules(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'barber_schedules';
		$count = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) );

		if ( $count > 0 ) {
			return;
		}

		for ( $i = 1; $i <= 6; $i++ ) {
			$wpdb->insert(
				$table,
				array(
					'barber_id'   => null,
					'day_of_week' => $i,
					'start_time'  => '09:00:00',
					'end_time'    => '19:00:00',
					'active'      => 1,
				),
				array( '%d', '%d', '%s', '%s', '%d' )
			);
		}
	}

	/**
	 * Default opening hours.
	 */
	private static function default_opening_hours(): array {
		$hours = array();
		for ( $i = 0; $i < 7; $i++ ) {
			if ( 0 === $i ) {
				$hours[ $i ] = array(); // Sunday closed.
			} else {
				$hours[ $i ] = array(
					array(
						'start' => '09:00',
						'end'   => '19:00',
					),
				);
			}
		}
		return apply_filters( 'barber_booking_default_opening_hours', $hours );
	}

	/**
	 * Schedule cron events.
	 */
	private static function schedule_events(): void {
		if ( ! wp_next_scheduled( 'barber_booking_hourly_cron' ) ) {
			wp_schedule_event( time(), 'hourly', 'barber_booking_hourly_cron' );
		}
	}
}
