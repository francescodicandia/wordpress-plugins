<?php
/**
 * Schedule repository.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Schedule repository.
 */
class Schedule {

	private static string $table;

	/**
	 * Get table name.
	 */
	private static function table(): string {
		global $wpdb;
		if ( empty( self::$table ) ) {
			self::$table = $wpdb->prefix . 'barber_schedules';
		}
		return self::$table;
	}

	/**
	 * Get a schedule.
	 *
	 * @param int $id Schedule ID.
	 * @return object|null
	 */
	public static function get( int $id ): ?object {
		global $wpdb;
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE id = %d',
				self::table(),
				$id
			)
		);
	}

	/**
	 * Get all schedules.
	 *
	 * @return array
	 */
	public static function get_all(): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i ORDER BY barber_id IS NULL, barber_id ASC, day_of_week ASC, start_time ASC',
				self::table()
			)
		);
	}

	/**
	 * Get schedules for a barber.
	 *
	 * @param int|null $barber_id Barber ID or null for global.
	 * @return array
	 */
	public static function get_for_barber( ?int $barber_id = null ): array {
		global $wpdb;

		if ( null === $barber_id ) {
			return $wpdb->get_results(
				$wpdb->prepare(
					'SELECT * FROM %i WHERE barber_id IS NULL ORDER BY day_of_week ASC, start_time ASC',
					self::table()
				)
			);
		}

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE barber_id = %d ORDER BY day_of_week ASC, start_time ASC',
				self::table(),
				$barber_id
			)
		);
	}

	/**
	 * Get schedules for a day.
	 *
	 * @param int      $day_of_week Day of week (0-6).
	 * @param int|null $barber_id Barber ID or null for global.
	 * @return array
	 */
	public static function get_for_day( int $day_of_week, ?int $barber_id = null ): array {
		global $wpdb;

		$sql    = 'SELECT * FROM %i WHERE day_of_week = %d AND active = 1';
		$params = array( self::table(), $day_of_week );

		if ( null === $barber_id ) {
			$sql .= ' AND barber_id IS NULL';
		} else {
			$sql     .= ' AND barber_id = %d';
			$params[] = $barber_id;
		}

		$sql .= ' ORDER BY start_time ASC';

		// phpcs:ignore WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Insert a schedule.
	 *
	 * @param array $data Data.
	 * @return int|false
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$defaults = array(
			'barber_id'   => null,
			'day_of_week' => 1,
			'start_time'  => '09:00:00',
			'end_time'    => '19:00:00',
			'active'      => 1,
		);

		$data = wp_parse_args( $data, $defaults );

		$insert = array(
			'day_of_week' => absint( $data['day_of_week'] ),
			'start_time'  => sanitize_text_field( $data['start_time'] ),
			'end_time'    => sanitize_text_field( $data['end_time'] ),
			'active'      => $data['active'] ? 1 : 0,
		);
		$format = array( '%d', '%s', '%s', '%d' );

		if ( null !== $data['barber_id'] ) {
			$insert['barber_id'] = absint( $data['barber_id'] );
			$format[]            = '%d';
		}

		$result = $wpdb->insert( self::table(), $insert, $format );
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a schedule.
	 *
	 * @param int   $id   Schedule ID.
	 * @param array $data Data.
	 * @return bool
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$update = array();
		$format = array();

		if ( array_key_exists( 'barber_id', $data ) ) {
			$update['barber_id'] = null === $data['barber_id'] ? null : absint( $data['barber_id'] );
			$format[]            = null === $data['barber_id'] ? null : '%d';
		}
		if ( isset( $data['day_of_week'] ) ) {
			$update['day_of_week'] = absint( $data['day_of_week'] );
			$format[]              = '%d';
		}
		if ( isset( $data['start_time'] ) ) {
			$update['start_time'] = sanitize_text_field( $data['start_time'] );
			$format[]             = '%s';
		}
		if ( isset( $data['end_time'] ) ) {
			$update['end_time'] = sanitize_text_field( $data['end_time'] );
			$format[]           = '%s';
		}
		if ( isset( $data['active'] ) ) {
			$update['active'] = $data['active'] ? 1 : 0;
			$format[]         = '%d';
		}

		if ( empty( $update ) ) {
			return false;
		}

		$result = $wpdb->update( self::table(), $update, array( 'id' => $id ), $format, array( '%d' ) );
		return false !== $result;
	}

	/**
	 * Delete a schedule.
	 *
	 * @param int $id Schedule ID.
	 * @return bool
	 */
	public static function delete( int $id ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			self::table(),
			array( 'id' => $id ),
			array( '%d' )
		);
		return false !== $result;
	}
}
