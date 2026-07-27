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

}
