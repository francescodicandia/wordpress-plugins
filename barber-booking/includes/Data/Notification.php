<?php
/**
 * Notification repository.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Notification repository.
 */
class Notification {

	private static string $table;

	/**
	 * Get table name.
	 */
	private static function table(): string {
		global $wpdb;
		if ( empty( self::$table ) ) {
			self::$table = $wpdb->prefix . 'barber_notifications';
		}
		return self::$table;
	}

	/**
	 * Get notification by ID.
	 *
	 * @param int $id Notification ID.
	 * @return object|null
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				self::table(),
				$id
			)
		);
		return $row ?: null;
	}

	/**
	 * Log a notification.
	 *
	 * @param int    $appointment_id Appointment ID.
	 * @param string $channel Channel.
	 * @param string $type Type.
	 * @param string $status Status.
	 * @param string $scheduled_at Optional scheduled time.
	 * @return int|false
	 */
	public static function log( int $appointment_id, string $channel, string $type, string $status = 'pending', ?string $scheduled_at = null ) {
		global $wpdb;

		$insert = array(
			'appointment_id' => $appointment_id,
			'channel'        => sanitize_text_field( $channel ),
			'type'           => sanitize_text_field( $type ),
			'status'         => sanitize_text_field( $status ),
		);
		$format = array( '%d', '%s', '%s', '%s' );

		if ( null !== $scheduled_at ) {
			$insert['scheduled_at'] = sanitize_text_field( $scheduled_at );
			$format[]               = '%s';
		}

		$result = $wpdb->insert( self::table(), $insert, $format );
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Mark notification as sent.
	 *
	 * @param int         $id Notification ID.
	 * @param string|null $external_id External ID.
	 * @return bool
	 */
	public static function mark_sent( int $id, ?string $external_id = null ): bool {
		global $wpdb;

		$update = array(
			'status'  => 'sent',
			'sent_at' => gmdate( 'Y-m-d H:i:s' ),
		);
		$format = array( '%s', '%s' );

		if ( null !== $external_id ) {
			$update['external_id'] = sanitize_text_field( $external_id );
			$format[]              = '%s';
		}

		$result = $wpdb->update( self::table(), $update, array( 'id' => $id ), $format, array( '%d' ) );
		return false !== $result;
	}

	/**
	 * Mark notification as failed.
	 *
	 * @param int    $id Notification ID.
	 * @param string $error Error message.
	 * @return bool
	 */
	public static function mark_failed( int $id, string $error ): bool {
		global $wpdb;
		$result = $wpdb->update(
			self::table(),
			array(
				'status'        => 'failed',
				'error_message' => sanitize_text_field( $error ),
			),
			array( 'id' => $id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		return false !== $result;
	}

	/**
	 * Get pending notifications for reminders.
	 *
	 * @param int $hours Hours before appointment.
	 * @return array
	 */
	public static function get_pending_reminders( int $hours ): array {
		global $wpdb;

		$now   = gmdate( 'Y-m-d H:i:s' );
		$lower = gmdate( 'Y-m-d H:i:s', strtotime( "+{$hours} hour -30 minutes" ) );
		$upper = gmdate( 'Y-m-d H:i:s', strtotime( "+{$hours} hour +30 minutes" ) );

		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT n.*, a.appointment_date, a.start_time, a.customer_id, a.service_id, a.barber_id
				FROM %i n
				INNER JOIN %i a ON n.appointment_id = a.id
				WHERE n.channel = 'whatsapp' AND n.type = 'reminder' AND n.status = 'pending'
				AND n.scheduled_at BETWEEN %s AND %s
				AND a.status = 'confirmed'
				ORDER BY n.scheduled_at ASC",
				self::table(),
				$wpdb->prefix . 'barber_appointments',
				$lower,
				$upper
			)
		);
	}

	/**
	 * Get notifications for an appointment.
	 *
	 * @param int $appointment_id Appointment ID.
	 * @return array
	 */
	public static function get_for_appointment( int $appointment_id ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE appointment_id = %d ORDER BY created_at DESC',
				self::table(),
				$appointment_id
			)
		);
	}
}
