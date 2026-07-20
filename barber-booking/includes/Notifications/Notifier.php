<?php
/**
 * Notifier.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Notifier class.
 */
class Notifier {

	/**
	 * Send confirmation.
	 *
	 * @param int $appointment_id Appointment ID.
	 * @return bool
	 */
	public function send_confirmation( int $appointment_id ): bool {
		$twilio = new Twilio_WhatsApp();
		return $twilio->send( $appointment_id, 'confirmation' );
	}

	/**
	 * Send reminder.
	 *
	 * @param int      $appointment_id Appointment ID.
	 * @param int|null $notification_id Existing notification ID.
	 * @return bool
	 */
	public function send_reminder( int $appointment_id, ?int $notification_id = null ): bool {
		$twilio = new Twilio_WhatsApp();
		return $twilio->send( $appointment_id, 'reminder', $notification_id );
	}
}
