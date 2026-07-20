<?php
/**
 * Station repository.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Station repository.
 */
class Station {

	private static string $table;

	/**
	 * Get table name.
	 */
	private static function table(): string {
		global $wpdb;
		if ( empty( self::$table ) ) {
			self::$table = $wpdb->prefix . 'barber_stations';
		}
		return self::$table;
	}

	/**
	 * Get a station by ID.
	 *
	 * @param int $id Station ID.
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
	 * Get all stations.
	 *
	 * @param array $args Arguments.
	 * @return array
	 */
	public static function get_all( array $args = array() ): array {
		global $wpdb;

		$active = isset( $args['active'] ) ? (bool) $args['active'] : null;
		$sql    = 'SELECT * FROM %i';
		$params = array( self::table() );

		if ( null !== $active ) {
			$sql     .= ' WHERE active = %d';
			$params[] = $active ? 1 : 0;
		}

		$sql .= ' ORDER BY name ASC';

		// phpcs:ignore WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Insert a station.
	 *
	 * @param array $data Data.
	 * @return int|false
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$defaults = array(
			'name'   => '',
			'color'  => '#000000',
			'active' => 1,
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			self::table(),
			array(
				'name'   => sanitize_text_field( $data['name'] ),
				'color'  => sanitize_hex_color( $data['color'] ) ?: '#000000',
				'active' => $data['active'] ? 1 : 0,
			),
			array( '%s', '%s', '%d' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a station.
	 *
	 * @param int   $id   Station ID.
	 * @param array $data Data.
	 * @return bool
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$update = array();
		$format = array();

		if ( isset( $data['name'] ) ) {
			$update['name'] = sanitize_text_field( $data['name'] );
			$format[]       = '%s';
		}
		if ( isset( $data['color'] ) ) {
			$update['color'] = sanitize_hex_color( $data['color'] ) ?: '#000000';
			$format[]        = '%s';
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
	 * Delete a station.
	 *
	 * @param int $id Station ID.
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

	/**
	 * Get active stations.
	 *
	 * @return array
	 */
	public static function get_active(): array {
		return self::get_all( array( 'active' => true ) );
	}
}
