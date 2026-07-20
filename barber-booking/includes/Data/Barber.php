<?php
/**
 * Barber repository.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Barber repository.
 */
class Barber {

	private static string $table;
	private static string $station_table;
	private static string $service_table;

	/**
	 * Get barbers table name.
	 */
	private static function table(): string {
		global $wpdb;
		if ( empty( self::$table ) ) {
			self::$table = $wpdb->prefix . 'barber_barbers';
		}
		return self::$table;
	}

	/**
	 * Get station relation table name.
	 */
	private static function station_table(): string {
		global $wpdb;
		if ( empty( self::$station_table ) ) {
			self::$station_table = $wpdb->prefix . 'barber_barber_station';
		}
		return self::$station_table;
	}

	/**
	 * Get service relation table name.
	 */
	private static function service_table(): string {
		global $wpdb;
		if ( empty( self::$service_table ) ) {
			self::$service_table = $wpdb->prefix . 'barber_barber_service';
		}
		return self::$service_table;
	}

	/**
	 * Get a barber by ID.
	 *
	 * @param int $id Barber ID.
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
	 * Get all barbers.
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
	 * Insert a barber.
	 *
	 * @param array $data Data.
	 * @return int|false
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$defaults = array(
			'user_id'  => null,
			'name'     => '',
			'email'    => '',
			'phone'    => '',
			'color'    => '#000000',
			'photo_id' => 0,
			'active'   => 1,
		);

		$data = wp_parse_args( $data, $defaults );

		$insert = array(
			'name'     => sanitize_text_field( $data['name'] ),
			'email'    => sanitize_email( $data['email'] ),
			'phone'    => sanitize_text_field( $data['phone'] ),
			'color'    => sanitize_hex_color( $data['color'] ) ?: '#000000',
			'photo_id' => absint( $data['photo_id'] ),
			'active'   => $data['active'] ? 1 : 0,
		);
		$format = array( '%s', '%s', '%s', '%s', '%d', '%d' );

		if ( null !== $data['user_id'] ) {
			$insert['user_id'] = absint( $data['user_id'] );
			$format[]          = '%d';
		}

		$result = $wpdb->insert( self::table(), $insert, $format );

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a barber.
	 *
	 * @param int   $id   Barber ID.
	 * @param array $data Data.
	 * @return bool
	 */
	public static function update( int $id, array $data ): bool {
		global $wpdb;

		$update = array();
		$format = array();

		if ( isset( $data['user_id'] ) ) {
			$update['user_id'] = absint( $data['user_id'] );
			$format[]          = '%d';
		}
		if ( isset( $data['name'] ) ) {
			$update['name'] = sanitize_text_field( $data['name'] );
			$format[]       = '%s';
		}
		if ( isset( $data['email'] ) ) {
			$update['email'] = sanitize_email( $data['email'] );
			$format[]        = '%s';
		}
		if ( isset( $data['phone'] ) ) {
			$update['phone'] = sanitize_text_field( $data['phone'] );
			$format[]        = '%s';
		}
		if ( isset( $data['color'] ) ) {
			$update['color'] = sanitize_hex_color( $data['color'] ) ?: '#000000';
			$format[]        = '%s';
		}
		if ( isset( $data['photo_id'] ) ) {
			$update['photo_id'] = absint( $data['photo_id'] );
			$format[]           = '%d';
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
	 * Delete a barber.
	 *
	 * @param int $id Barber ID.
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
	 * Get active barbers.
	 *
	 * @return array
	 */
	public static function get_active(): array {
		return self::get_all( array( 'active' => true ) );
	}

	/**
	 * Get stations assigned to a barber.
	 *
	 * @param int $barber_id Barber ID.
	 * @return array
	 */
	public static function get_stations( int $barber_id ): array {
		global $wpdb;
		$station_table  = $wpdb->prefix . 'barber_stations';
		$relation_table = self::station_table();

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT s.* FROM %i s INNER JOIN %i r ON s.id = r.station_id WHERE r.barber_id = %d AND s.active = 1 ORDER BY s.name ASC',
				$station_table,
				$relation_table,
				$barber_id
			)
		);
	}

	/**
	 * Assign a station to a barber.
	 *
	 * @param int $barber_id Barber ID.
	 * @param int $station_id Station ID.
	 * @return bool
	 */
	public static function assign_station( int $barber_id, int $station_id ): bool {
		global $wpdb;
		$result = $wpdb->replace(
			self::station_table(),
			array(
				'barber_id'  => $barber_id,
				'station_id' => $station_id,
			),
			array( '%d', '%d' )
		);
		return false !== $result;
	}

	/**
	 * Remove a station from a barber.
	 *
	 * @param int $barber_id Barber ID.
	 * @param int $station_id Station ID.
	 * @return bool
	 */
	public static function remove_station( int $barber_id, int $station_id ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			self::station_table(),
			array(
				'barber_id'  => $barber_id,
				'station_id' => $station_id,
			),
			array( '%d', '%d' )
		);
		return false !== $result;
	}

	/**
	 * Get services assigned to a barber.
	 *
	 * @param int $barber_id Barber ID.
	 * @return array
	 */
	public static function get_services( int $barber_id ): array {
		global $wpdb;
		$service_table  = $wpdb->prefix . 'barber_services';
		$relation_table = self::service_table();

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT s.*, r.price as barber_price, r.duration as barber_duration FROM %i s INNER JOIN %i r ON s.id = r.service_id WHERE r.barber_id = %d AND s.active = 1 ORDER BY s.name ASC',
				$service_table,
				$relation_table,
				$barber_id
			)
		);
	}

	/**
	 * Assign a service to a barber.
	 *
	 * @param int        $barber_id Barber ID.
	 * @param int        $service_id Service ID.
	 * @param float|null $price Optional price override.
	 * @param int|null   $duration Optional duration override.
	 * @return bool
	 */
	public static function assign_service( int $barber_id, int $service_id, ?float $price = null, ?int $duration = null ): bool {
		global $wpdb;

		$data   = array(
			'barber_id'  => $barber_id,
			'service_id' => $service_id,
		);
		$format = array( '%d', '%d' );

		if ( null !== $price ) {
			$data['price'] = floatval( $price );
			$format[]      = '%f';
		}
		if ( null !== $duration ) {
			$data['duration'] = absint( $duration );
			$format[]         = '%d';
		}

		$result = $wpdb->replace( self::service_table(), $data, $format );
		return false !== $result;
	}

	/**
	 * Remove a service from a barber.
	 *
	 * @param int $barber_id Barber ID.
	 * @param int $service_id Service ID.
	 * @return bool
	 */
	public static function remove_service( int $barber_id, int $service_id ): bool {
		global $wpdb;
		$result = $wpdb->delete(
			self::service_table(),
			array(
				'barber_id'  => $barber_id,
				'service_id' => $service_id,
			),
			array( '%d', '%d' )
		);
		return false !== $result;
	}
}
