<?php
/**
 * Appointment repository.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Appointment repository.
 */
class Appointment {

	private static string $table;

	/**
	 * Get table name.
	 */
	private static function table(): string {
		global $wpdb;
		if ( empty( self::$table ) ) {
			self::$table = $wpdb->prefix . 'barber_appointments';
		}
		return self::$table;
	}

	/**
	 * Get appointment by ID.
	 *
	 * @param int $id Appointment ID.
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
	 * Get appointments for a date range.
	 *
	 * @param string      $start_date Start date.
	 * @param string      $end_date End date.
	 * @param int|null    $barber_id Optional barber filter.
	 * @param int|null    $station_id Optional station filter.
	 * @param string|null $status Optional status filter.
	 * @return array
	 */
	public static function get_for_range( string $start_date, string $end_date, ?int $barber_id = null, ?int $station_id = null, ?string $status = null ): array {
		global $wpdb;

		$sql    = 'SELECT * FROM %i WHERE appointment_date BETWEEN %s AND %s';
		$params = array( self::table(), $start_date, $end_date );

		if ( null !== $barber_id ) {
			$sql     .= ' AND barber_id = %d';
			$params[] = $barber_id;
		}
		if ( null !== $station_id ) {
			$sql     .= ' AND station_id = %d';
			$params[] = $station_id;
		}
		if ( null !== $status ) {
			$sql     .= ' AND status = %s';
			$params[] = $status;
		}

		$sql .= ' ORDER BY appointment_date ASC, start_time ASC';

		// phpcs:ignore WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Get appointments for a range with related data (customer, service, barber).
	 *
	 * @param string      $start_date Start date.
	 * @param string      $end_date End date.
	 * @param int|null    $barber_id Optional barber filter.
	 * @param string|null $status Optional status filter.
	 * @return array
	 */
	public static function get_for_range_with_relations( string $start_date, string $end_date, ?int $barber_id = null, ?string $status = null ): array {
		global $wpdb;

		$customers_table = $wpdb->prefix . 'barber_customers';
		$services_table  = $wpdb->prefix . 'barber_services';
		$barbers_table   = $wpdb->prefix . 'barber_barbers';

		$sql    = "SELECT a.*, c.name AS customer_name, c.phone AS customer_phone, s.name AS service_name, b.name AS barber_name
			FROM %i AS a
			LEFT JOIN {$customers_table} AS c ON a.customer_id = c.id
			LEFT JOIN {$services_table} AS s ON a.service_id = s.id
			LEFT JOIN {$barbers_table} AS b ON a.barber_id = b.id
			WHERE a.appointment_date BETWEEN %s AND %s";
		$params = array(
			self::table(),
			$start_date,
			$end_date,
		);

		if ( null !== $barber_id ) {
			$sql     .= ' AND a.barber_id = %d';
			$params[] = $barber_id;
		}
		if ( null !== $status ) {
			$sql     .= ' AND a.status = %s';
			$params[] = $status;
		}

		$sql .= ' ORDER BY a.appointment_date ASC, a.start_time ASC';

		// phpcs:ignore WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Get overlapping appointments.
	 *
	 * @param int      $barber_id Barber ID.
	 * @param int|null $station_id Station ID.
	 * @param string   $date Date.
	 * @param string   $start_time Start time.
	 * @param string   $end_time End time.
	 * @param int|null $exclude_id Appointment to exclude.
	 * @return array
	 */
	public static function get_overlapping( int $barber_id, ?int $station_id, string $date, string $start_time, string $end_time, ?int $exclude_id = null ): array {
		global $wpdb;

		$sql    = "SELECT * FROM %i WHERE appointment_date = %s AND status NOT IN ('cancelled', 'no_show') AND (
			(barber_id = %d AND start_time < %s AND end_time > %s)
		)";
		$params = array(
			self::table(),
			$date,
			$barber_id,
			$end_time,
			$start_time,
		);

		if ( null !== $station_id ) {
			$sql     .= ' OR (station_id = %d AND start_time < %s AND end_time > %s)';
			$params[] = $station_id;
			$params[] = $end_time;
			$params[] = $start_time;
		}

		if ( null !== $exclude_id ) {
			$sql     .= ' AND id != %d';
			$params[] = $exclude_id;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Insert an appointment.
	 *
	 * @param array $data Data.
	 * @return int|false
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$defaults = array(
			'customer_id'      => 0,
			'service_id'       => 0,
			'barber_id'        => 0,
			'station_id'       => null,
			'appointment_date' => gmdate( 'Y-m-d' ),
			'start_time'       => '09:00:00',
			'end_time'         => '09:30:00',
			'status'           => 'confirmed',
			'notes'            => '',
			'source'           => 'web',
		);

		$data = wp_parse_args( $data, $defaults );

		$insert = array(
			'customer_id'      => absint( $data['customer_id'] ),
			'service_id'       => absint( $data['service_id'] ),
			'barber_id'        => absint( $data['barber_id'] ),
			'appointment_date' => sanitize_text_field( $data['appointment_date'] ),
			'start_time'       => sanitize_text_field( $data['start_time'] ),
			'end_time'         => sanitize_text_field( $data['end_time'] ),
			'status'           => sanitize_text_field( $data['status'] ),
			'notes'            => sanitize_textarea_field( $data['notes'] ),
			'source'           => sanitize_text_field( $data['source'] ),
		);
		$format = array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' );

		if ( null !== $data['station_id'] ) {
			$insert['station_id'] = absint( $data['station_id'] );
			$format[]             = '%d';
		}

		$result = $wpdb->insert( self::table(), $insert, $format );
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update an appointment.
	 *
	 * @param int   $id   Appointment ID.
	 * @param array $data Data.
	 * @return bool
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$update = array();
		$format = array();

		$fields = array(
			'customer_id'      => '%d',
			'service_id'       => '%d',
			'barber_id'        => '%d',
			'station_id'       => '%d',
			'appointment_date' => '%s',
			'start_time'       => '%s',
			'end_time'         => '%s',
			'status'           => '%s',
			'notes'            => '%s',
			'source'           => '%s',
		);

		foreach ( $fields as $field => $placeholder ) {
			if ( isset( $data[ $field ] ) ) {
				if ( in_array( $field, array( 'appointment_date', 'start_time', 'end_time', 'status', 'source' ), true ) ) {
					$update[ $field ] = sanitize_text_field( $data[ $field ] );
				} elseif ( 'notes' === $field ) {
					$update[ $field ] = sanitize_textarea_field( $data[ $field ] );
				} else {
					$update[ $field ] = absint( $data[ $field ] );
				}
				$format[] = $placeholder;
			}
		}

		if ( empty( $update ) ) {
			return false;
		}

		$result = $wpdb->update( self::table(), $update, array( 'id' => $id ), $format, array( '%d' ) );
		return false !== $result;
	}

	/**
	 * Delete an appointment.
	 *
	 * @param int $id Appointment ID.
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
