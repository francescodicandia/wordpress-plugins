<?php
/**
 * Service repository.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Service repository.
 */
class Service {

	private static string $table;

	/**
	 * Get table name.
	 */
	private static function table(): string {
		global $wpdb;
		if ( empty( self::$table ) ) {
			self::$table = $wpdb->prefix . 'barber_services';
		}
		return self::$table;
	}

	/**
	 * Get a service by ID.
	 *
	 * @param int $id Service ID.
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
	 * Get all services.
	 *
	 * @param array $args Arguments.
	 * @return array
	 */
	public static function get_all( array $args = array() ): array {
		global $wpdb;

		$active   = isset( $args['active'] ) ? (bool) $args['active'] : null;
		$page     = max( 1, (int) ( $args['page'] ?? 1 ) );
		$per_page = max( 1, (int) ( $args['per_page'] ?? 0 ) );
		$sql      = 'SELECT * FROM %i';
		$params   = array( self::table() );

		if ( null !== $active ) {
			$sql     .= ' WHERE active = %d';
			$params[] = $active ? 1 : 0;
		}

		$sql .= ' ORDER BY name ASC';

		if ( $per_page > 0 ) {
			$sql     .= ' LIMIT %d OFFSET %d';
			$params[] = $per_page;
			$params[] = ( $page - 1 ) * $per_page;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Insert a service.
	 *
	 * @param array $data Data.
	 * @return int|false
	 */
	public static function insert( array $data ) {
		global $wpdb;

		$defaults = array(
			'name'        => '',
			'description' => '',
			'duration'    => 30,
			'price'       => 0.00,
			'color'       => '#000000',
			'image_id'    => 0,
			'active'      => 1,
		);

		$data = wp_parse_args( $data, $defaults );

		$result = $wpdb->insert(
			self::table(),
			array(
				'name'        => sanitize_text_field( $data['name'] ),
				'description' => wp_kses_post( $data['description'] ),
				'duration'    => absint( $data['duration'] ),
				'price'       => floatval( $data['price'] ),
				'color'       => sanitize_hex_color( $data['color'] ) ?: '#000000',
				'image_id'    => absint( $data['image_id'] ),
				'active'      => $data['active'] ? 1 : 0,
			),
			array( '%s', '%s', '%d', '%f', '%s', '%d', '%d' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a service.
	 *
	 * @param int   $id   Service ID.
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
		if ( isset( $data['description'] ) ) {
			$update['description'] = wp_kses_post( $data['description'] );
			$format[]              = '%s';
		}
		if ( isset( $data['duration'] ) ) {
			$update['duration'] = absint( $data['duration'] );
			$format[]           = '%d';
		}
		if ( isset( $data['price'] ) ) {
			$update['price'] = floatval( $data['price'] );
			$format[]        = '%f';
		}
		if ( isset( $data['color'] ) ) {
			$update['color'] = sanitize_hex_color( $data['color'] ) ?: '#000000';
			$format[]        = '%s';
		}
		if ( isset( $data['image_id'] ) ) {
			$update['image_id'] = absint( $data['image_id'] );
			$format[]           = '%d';
		}
		if ( isset( $data['active'] ) ) {
			$update['active'] = $data['active'] ? 1 : 0;
			$format[]         = '%d';
		}

		if ( empty( $update ) ) {
			return false;
		}

		$where_format = array( '%d' );
		$result       = $wpdb->update( self::table(), $update, array( 'id' => $id ), $format, $where_format );

		return false !== $result;
	}

	/**
	 * Delete a service.
	 *
	 * @param int $id Service ID.
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
	 * Get active services.
	 *
	 * @return array
	 */
	public static function get_active(): array {
		return self::get_all( array( 'active' => true ) );
	}
}
