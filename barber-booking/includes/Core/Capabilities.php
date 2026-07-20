<?php
/**
 * Capabilities and roles.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Capabilities class.
 */
class Capabilities {

	public const CAP_MANAGE_SETTINGS         = 'barber_manage_settings';
	public const CAP_MANAGE_USERS            = 'barber_manage_users';
	public const CAP_MANAGE_SERVICES         = 'barber_manage_services';
	public const CAP_MANAGE_STATIONS         = 'barber_manage_stations';
	public const CAP_MANAGE_BARBERS          = 'barber_manage_barbers';
	public const CAP_MANAGE_APPOINTMENTS     = 'barber_manage_appointments';
	public const CAP_MANAGE_OWN_APPOINTMENTS = 'barber_manage_own_appointments';
	public const CAP_VIEW_OWN_APPOINTMENTS   = 'barber_view_own_appointments';
	public const CAP_MANAGE_CUSTOMERS        = 'barber_manage_customers';
	public const CAP_SEND_NOTIFICATIONS      = 'barber_send_notifications';

	/**
	 * Add roles and capabilities.
	 */
	public static function add(): void {
		self::add_admin_caps();
		self::add_role( 'barber_superadmin', __( 'Barber Superadmin', 'barber-booking' ), self::superadmin_caps() );
		self::add_role( 'barber_admin', __( 'Barber Admin', 'barber-booking' ), self::admin_caps() );
		self::add_role( 'barber', __( 'Barber', 'barber-booking' ), self::barber_caps() );
	}

	/**
	 * Remove roles and capabilities.
	 */
	public static function remove(): void {
		self::remove_admin_caps();
		remove_role( 'barber_superadmin' );
		remove_role( 'barber_admin' );
		remove_role( 'barber' );
	}

	/**
	 * Add capabilities to administrator role.
	 */
	private static function add_admin_caps(): void {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}
		foreach ( self::all_caps() as $cap ) {
			$admin->add_cap( $cap );
		}
	}

	/**
	 * Remove capabilities from administrator role.
	 */
	private static function remove_admin_caps(): void {
		$admin = get_role( 'administrator' );
		if ( ! $admin ) {
			return;
		}
		foreach ( self::all_caps() as $cap ) {
			$admin->remove_cap( $cap );
		}
	}

	/**
	 * Add a role.
	 */
	private static function add_role( string $slug, string $name, array $caps ): void {
		remove_role( $slug );
		add_role( $slug, $name, $caps );
	}

	/**
	 * All capabilities.
	 */
	private static function all_caps(): array {
		return array_keys( self::superadmin_caps() );
	}

	/**
	 * Superadmin capabilities.
	 */
	private static function superadmin_caps(): array {
		return array(
			self::CAP_MANAGE_SETTINGS         => true,
			self::CAP_MANAGE_USERS            => true,
			self::CAP_MANAGE_SERVICES         => true,
			self::CAP_MANAGE_STATIONS         => true,
			self::CAP_MANAGE_BARBERS          => true,
			self::CAP_MANAGE_APPOINTMENTS     => true,
			self::CAP_MANAGE_OWN_APPOINTMENTS => true,
			self::CAP_VIEW_OWN_APPOINTMENTS   => true,
			self::CAP_MANAGE_CUSTOMERS        => true,
			self::CAP_SEND_NOTIFICATIONS      => true,
		);
	}

	/**
	 * Admin capabilities.
	 */
	private static function admin_caps(): array {
		return array(
			self::CAP_MANAGE_SERVICES         => true,
			self::CAP_MANAGE_STATIONS         => true,
			self::CAP_MANAGE_BARBERS          => true,
			self::CAP_MANAGE_APPOINTMENTS     => true,
			self::CAP_MANAGE_OWN_APPOINTMENTS => true,
			self::CAP_VIEW_OWN_APPOINTMENTS   => true,
			self::CAP_MANAGE_CUSTOMERS        => true,
			self::CAP_SEND_NOTIFICATIONS      => true,
		);
	}

	/**
	 * Barber capabilities.
	 */
	private static function barber_caps(): array {
		return array(
			self::CAP_MANAGE_OWN_APPOINTMENTS => true,
			self::CAP_VIEW_OWN_APPOINTMENTS   => true,
		);
	}
}
