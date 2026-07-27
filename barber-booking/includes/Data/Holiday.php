<?php
/**
 * Holiday repository.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Holiday repository.
 */
class Holiday {

	private static string $table;

	/**
	 * Get table name.
	 */
	private static function table(): string {
		global $wpdb;
		if ( empty( self::$table ) ) {
			self::$table = $wpdb->prefix . 'barber_holidays';
		}
		return self::$table;
	}

	/**
	 * Get a holiday.
	 *
	 * @param int $id Holiday ID.
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
	 * Get all holidays.
	 *
	 * @return array
	 */
	public static function get_all( array $args = array() ): array {
		global $wpdb;

		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = max( 1, (int) ( $args['per_page'] ?? 0 ) );
		$sql      = 'SELECT * FROM %i ORDER BY holiday_date ASC, barber_id ASC';
		$params   = array( self::table() );

		if ( $per_page > 0 ) {
			$sql     .= ' LIMIT %d OFFSET %d';
			$params[] = $per_page;
			$params[] = ( $page - 1 ) * $per_page;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Get all upcoming holidays.
	 *
	 * @param int $days Number of days ahead.
	 * @return array
	 */
	public static function get_upcoming( int $days = 365 ): array {
		$start = gmdate( 'Y-m-d' );
		$end   = gmdate( 'Y-m-d', strtotime( "+{$days} days" ) );
		return self::get_for_range( $start, $end, null );
	}

	/**
	 * Get holidays for a date range.
	 *
	 * @param string   $start_date Start date (Y-m-d).
	 * @param string   $end_date End date (Y-m-d).
	 * @param int|null $barber_id Barber ID or null for all.
	 * @return array
	 */
	public static function get_for_range( string $start_date, string $end_date, ?int $barber_id = null ): array {
		global $wpdb;

		$sql    = 'SELECT * FROM %i WHERE holiday_date BETWEEN %s AND %s';
		$params = array( self::table(), $start_date, $end_date );

		if ( null !== $barber_id ) {
			$sql     .= ' AND (barber_id IS NULL OR barber_id = %d)';
			$params[] = $barber_id;
		}

		$sql .= ' ORDER BY holiday_date ASC';

		// phpcs:ignore WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Insert a holiday.
	 *
	 * @param array $data Data.
	 * @return int|false
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$defaults = array(
			'barber_id'    => null,
			'holiday_date' => gmdate( 'Y-m-d' ),
			'start_time'   => null,
			'end_time'     => null,
			'all_day'      => 1,
			'reason'       => '',
		);

		$data = wp_parse_args( $data, $defaults );

		$insert = array(
			'holiday_date' => sanitize_text_field( $data['holiday_date'] ),
			'all_day'      => $data['all_day'] ? 1 : 0,
			'reason'       => sanitize_text_field( $data['reason'] ),
		);
		$format = array( '%s', '%d', '%s' );

		if ( null !== $data['barber_id'] ) {
			$insert['barber_id'] = absint( $data['barber_id'] );
			$format[]            = '%d';
		}
		if ( null !== $data['start_time'] ) {
			$insert['start_time'] = sanitize_text_field( $data['start_time'] );
			$format[]             = '%s';
		}
		if ( null !== $data['end_time'] ) {
			$insert['end_time'] = sanitize_text_field( $data['end_time'] );
			$format[]           = '%s';
		}

		$result = $wpdb->insert( self::table(), $insert, $format );
		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a holiday.
	 *
	 * @param int   $id   Holiday ID.
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
		if ( array_key_exists( 'holiday_date', $data ) ) {
			$update['holiday_date'] = sanitize_text_field( $data['holiday_date'] );
			$format[]               = '%s';
		}
		if ( array_key_exists( 'start_time', $data ) ) {
			$update['start_time'] = null === $data['start_time'] ? null : sanitize_text_field( $data['start_time'] );
			$format[]             = null === $data['start_time'] ? null : '%s';
		}
		if ( array_key_exists( 'end_time', $data ) ) {
			$update['end_time'] = null === $data['end_time'] ? null : sanitize_text_field( $data['end_time'] );
			$format[]           = null === $data['end_time'] ? null : '%s';
		}
		if ( isset( $data['all_day'] ) ) {
			$update['all_day'] = $data['all_day'] ? 1 : 0;
			$format[]          = '%d';
		}
		if ( isset( $data['reason'] ) ) {
			$update['reason'] = sanitize_text_field( $data['reason'] );
			$format[]         = '%s';
		}

		if ( empty( $update ) ) {
			return false;
		}

		$result = $wpdb->update( self::table(), $update, array( 'id' => $id ), $format, array( '%d' ) );
		return false !== $result;
	}

	/**
	 * Delete a holiday.
	 *
	 * @param int $id Holiday ID.
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
