<?php
/**
 * Notifications coordinator.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Notifications;

use BarberBooking\Data\Appointment;
use BarberBooking\Data\Notification;

defined( 'ABSPATH' ) || exit;

/**
 * Notifications class.
 */
class Notifications {

	/**
	 * Initialize.
	 */
	public function init(): void {
		add_action( 'barber_booking_after_create_appointment', array( $this, 'after_create' ), 10, 1 );
		add_action( 'barber_booking_hourly_cron', array( $this, 'send_reminders' ) );
	}

	/**
	 * After appointment creation.
	 *
	 * @param int $appointment_id Appointment ID.
	 */
	public function after_create( int $appointment_id ): void {
		$notifier = new Notifier();
		$notifier->send_confirmation( $appointment_id );
		$this->schedule_reminders( $appointment_id );
	}

	/**
	 * Send reminders.
	 */
	public function send_reminders(): void {
		global $wpdb;

		$now = gmdate( 'Y-m-d H:i:s' );

		$notifications = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT n.* FROM %i n
				INNER JOIN %i a ON n.appointment_id = a.id
				WHERE n.channel = 'whatsapp' AND n.type = 'reminder' AND n.status = 'pending'
				AND n.scheduled_at <= %s
				AND a.status = 'confirmed'
				ORDER BY n.scheduled_at ASC",
				$wpdb->prefix . 'barber_notifications',
				$wpdb->prefix . 'barber_appointments',
				$now
			)
		);

		$notifier = new Notifier();
		foreach ( $notifications as $notification ) {
			$notifier->send_reminder( (int) $notification->appointment_id, (int) $notification->id );
		}
	}

	/**
	 * Schedule reminders for an appointment.
	 *
	 * @param int $appointment_id Appointment ID.
	 */
	private function schedule_reminders( int $appointment_id ): void {
		$settings = get_option( \BarberBooking\PLUGIN_SETTINGS, array() );
		if ( empty( $settings['notification_reminder_enabled'] ) ) {
			return;
		}

		$hours = $settings['notification_reminder_hours'] ?? '24';
		if ( is_array( $hours ) ) {
			$hours = array_filter( array_map( 'intval', $hours ) );
		} else {
			$hours = array_filter( array_map( 'intval', explode( ',', $hours ) ) );
		}

		$appointment = Appointment::get( $appointment_id );
		if ( ! $appointment ) {
			return;
		}

		$datetime = $appointment->appointment_date . ' ' . $appointment->start_time;
		$base     = strtotime( $datetime );
		if ( false === $base ) {
			return;
		}

		foreach ( $hours as $hour ) {
			$scheduled = gmdate( 'Y-m-d H:i:s', $base - ( $hour * HOUR_IN_SECONDS ) );
			Notification::log( $appointment_id, 'whatsapp', 'reminder', 'pending', $scheduled );
		}
	}
}
