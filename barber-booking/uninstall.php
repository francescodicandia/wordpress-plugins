<?php
/**
 * Uninstall script.
 *
 * @package BarberBooking
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$settings = get_option( 'barber_booking_settings', array() );
$preserve = ! empty( $settings['preserve_data_on_uninstall'] );

if ( $preserve ) {
	return;
}

global $wpdb;

$tables = array(
	'services',
	'stations',
	'barbers',
	'barber_station',
	'barber_service',
	'schedules',
	'holidays',
	'customers',
	'appointments',
	'appointment_meta',
	'notifications',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}barber_{$table}" ); // phpcs:ignore WordPress.DB.DirectQuery, WordPress.DB.PreparedSQL
}

// Delete options.
delete_option( 'barber_booking_settings' );
delete_option( 'barber_booking_db_version' );

// Remove plugin roles.
remove_role( 'barber_superadmin' );
remove_role( 'barber_admin' );
remove_role( 'barber' );

// Remove plugin capabilities from administrator.
$admin = get_role( 'administrator' );
if ( $admin ) {
	$caps = array(
		'barber_manage_settings',
		'barber_manage_users',
		'barber_manage_services',
		'barber_manage_stations',
		'barber_manage_barbers',
		'barber_manage_appointments',
		'barber_manage_own_appointments',
		'barber_view_own_appointments',
		'barber_manage_customers',
		'barber_send_notifications',
	);
	foreach ( $caps as $cap ) {
		$admin->remove_cap( $cap );
	}
}

wp_cache_flush();
