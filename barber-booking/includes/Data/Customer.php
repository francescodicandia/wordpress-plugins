<?php
/**
 * Customer repository.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Customer repository.
 */
class Customer {

	private static string $table;

	/**
	 * Get table name.
	 */
	private static function table(): string {
		global $wpdb;
		if ( empty( self::$table ) ) {
			self::$table = $wpdb->prefix . 'barber_customers';
		}
		return self::$table;
	}

	/**
	 * Get a customer by ID.
	 *
	 * @param int $id Customer ID.
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
	 * Get customer by phone.
	 *
	 * @param string $phone Phone number.
	 * @return object|null
	 */
	public static function get_by_phone( string $phone ): ?object {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE phone = %s LIMIT 1',
				self::table(),
				$phone
			)
		);
		return $row ?: null;
	}

	/**
	 * Get all customers.
	 *
	 * @param array $args Arguments.
	 * @return array
	 */
	public static function get_all( array $args = array() ): array {
		global $wpdb;

		$sql    = 'SELECT * FROM %i';
		$params = array( self::table() );

		if ( ! empty( $args['search'] ) ) {
			$sql     .= ' WHERE name LIKE %s OR phone LIKE %s OR email LIKE %s';
			$search   = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$params[] = $search;
			$params[] = $search;
			$params[] = $search;
		}

		$sql .= ' ORDER BY name ASC';

		// phpcs:ignore WordPress.DB.PreparedSQL
		return $wpdb->get_results( $wpdb->prepare( $sql, ...$params ) );
	}

	/**
	 * Insert or update customer by phone.
	 *
	 * @param array $data Data.
	 * @return int|false
	 */
	public static function upsert( array $data ) {
		global $wpdb;

		$existing = null;
		if ( ! empty( $data['phone'] ) ) {
			$existing = self::get_by_phone( $data['phone'] );
		}

		if ( $existing ) {
			self::update( (int) $existing->id, $data );
			return (int) $existing->id;
		}

		$insert = array(
			'name'  => sanitize_text_field( $data['name'] ?? '' ),
			'email' => sanitize_email( $data['email'] ?? '' ),
			'phone' => sanitize_text_field( $data['phone'] ?? '' ),
			'notes' => sanitize_textarea_field( $data['notes'] ?? '' ),
		);

		$result = $wpdb->insert(
			self::table(),
			$insert,
			array( '%s', '%s', '%s', '%s' )
		);

		return $result ? $wpdb->insert_id : false;
	}

	/**
	 * Update a customer.
	 *
	 * @param int   $id   Customer ID.
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
		if ( isset( $data['email'] ) ) {
			$update['email'] = sanitize_email( $data['email'] );
			$format[]        = '%s';
		}
		if ( isset( $data['phone'] ) ) {
			$update['phone'] = sanitize_text_field( $data['phone'] );
			$format[]        = '%s';
		}
		if ( isset( $data['notes'] ) ) {
			$update['notes'] = sanitize_textarea_field( $data['notes'] );
			$format[]        = '%s';
		}

		if ( empty( $update ) ) {
			return false;
		}

		$result = $wpdb->update( self::table(), $update, array( 'id' => $id ), $format, array( '%d' ) );
		return false !== $result;
	}

	/**
	 * Delete a customer.
	 *
	 * @param int $id Customer ID.
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
