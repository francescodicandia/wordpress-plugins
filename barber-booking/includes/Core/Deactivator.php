<?php
/**
 * Plugin deactivation.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Deactivator class.
 */
class Deactivator {

	/**
	 * Deactivate plugin.
	 */
	public static function deactivate(): void {
		self::clear_scheduled_events();
		flush_rewrite_rules();
	}

	/**
	 * Clear scheduled cron events.
	 */
	private static function clear_scheduled_events(): void {
		wp_clear_scheduled_hook( 'barber_booking_hourly_cron' );
	}
}
