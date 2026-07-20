<?php
/**
 * REST API.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\API;

use BarberBooking\Core\Capabilities;
use BarberBooking\Data\Appointment;
use BarberBooking\Data\Availability;
use BarberBooking\Data\Barber;
use BarberBooking\Data\Customer;
use BarberBooking\Data\Holiday;
use BarberBooking\Data\Schedule;
use BarberBooking\Data\Service;
use BarberBooking\Data\Station;

defined( 'ABSPATH' ) || exit;

/**
 * REST API class.
 */
class Rest_API {

	private const NAMESPACE = 'barber-booking/v1';

	/**
	 * Initialize.
	 */
	public function init(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes.
	 */
	public function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/services',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_services' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/barbers',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_barbers' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'service_id' => array(
						'required'          => false,
						'validate_callback' => array( $this, 'validate_id' ),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/availability',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_availability' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'service_id' => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_id' ),
					),
					'date'       => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_date' ),
					),
					'barber_id'  => array(
						'required'          => false,
						'validate_callback' => array( $this, 'validate_id' ),
					),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/bookings',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_booking' ),
				'permission_callback' => array( $this, 'can_book' ),
				'args'                => array(
					'service_id'   => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_id' ),
					),
					'barber_id'    => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_id' ),
					),
					'station_id'   => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_id' ),
					),
					'date'         => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_date' ),
					),
					'time'         => array(
						'required'          => true,
						'validate_callback' => array( $this, 'validate_time' ),
					),
					'name'         => array(
						'required' => true,
						'type'     => 'string',
					),
					'phone'        => array(
						'required' => true,
						'type'     => 'string',
					),
					'email'        => array(
						'required' => false,
						'type'     => 'string',
					),
					'notes'        => array(
						'required' => false,
						'type'     => 'string',
					),
					'gdpr_consent' => array(
						'required' => true,
						'type'     => 'boolean',
					),
				),
			)
		);

		$this->register_admin_routes();
	}

	/**
	 * Register admin CRUD routes.
	 */
	private function register_admin_routes(): void {
		$resources = array(
			'services'  => array(
				'capability' => Capabilities::CAP_MANAGE_SERVICES,
				'repo'       => Service::class,
				'fields'     => array(
					'name'        => 'string',
					'description' => 'string',
					'duration'    => 'int',
					'price'       => 'float',
					'color'       => 'color',
					'image_id'    => 'int',
					'active'      => 'bool',
				),
			),
			'stations'  => array(
				'capability' => Capabilities::CAP_MANAGE_STATIONS,
				'repo'       => Station::class,
				'fields'     => array(
					'name'   => 'string',
					'color'  => 'color',
					'active' => 'bool',
				),
			),
			'barbers'   => array(
				'capability' => Capabilities::CAP_MANAGE_BARBERS,
				'repo'       => Barber::class,
				'fields'     => array(
					'name'   => 'string',
					'email'  => 'email',
					'phone'  => 'string',
					'color'  => 'color',
					'active' => 'bool',
				),
			),
			'schedules' => array(
				'capability' => Capabilities::CAP_MANAGE_BARBERS,
				'repo'       => Schedule::class,
				'fields'     => array(
					'barber_id'   => 'int_or_null',
					'day_of_week' => 'int',
					'start_time'  => 'time',
					'end_time'    => 'time',
					'active'      => 'bool',
				),
			),
			'holidays'  => array(
				'capability' => Capabilities::CAP_MANAGE_BARBERS,
				'repo'       => Holiday::class,
				'fields'     => array(
					'barber_id'    => 'int_or_null',
					'holiday_date' => 'date',
					'start_time'   => 'time',
					'end_time'     => 'time',
					'all_day'      => 'bool',
					'reason'       => 'string',
				),
			),
		);

		foreach ( $resources as $base => $config ) {
			$this->register_admin_resource( $base, $config );
		}
	}

	/**
	 * Register admin routes for a single resource.
	 *
	 * @param string $base Resource base.
	 * @param array  $config Resource configuration.
	 */
	private function register_admin_resource( string $base, array $config ): void {
		$cap      = $config['capability'];
		$repo     = $config['repo'];
		$fields   = $config['fields'];
		$route    = '/admin/' . $base;
		$route_id = $route . '/(?P<id>\d+)';

		register_rest_route(
			self::NAMESPACE,
			$route,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => function () use ( $repo, $fields ) {
					return $this->admin_list( $repo, $fields );
				},
				'permission_callback' => function () use ( $cap ) {
					return $this->can_admin( $cap );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			$route_id,
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => function ( \WP_REST_Request $request ) use ( $repo, $fields ) {
					return $this->admin_item( $repo, $fields, $request );
				},
				'permission_callback' => function () use ( $cap ) {
					return $this->can_admin( $cap );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			$route,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => function ( \WP_REST_Request $request ) use ( $repo, $fields ) {
					return $this->admin_create( $repo, $fields, $request );
				},
				'permission_callback' => function () use ( $cap ) {
					return $this->can_admin( $cap );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			$route_id,
			array(
				'methods'             => \WP_REST_Server::EDITABLE,
				'callback'            => function ( \WP_REST_Request $request ) use ( $repo, $fields ) {
					return $this->admin_update( $repo, $fields, $request );
				},
				'permission_callback' => function () use ( $cap ) {
					return $this->can_admin( $cap );
				},
			)
		);

		register_rest_route(
			self::NAMESPACE,
			$route_id,
			array(
				'methods'             => \WP_REST_Server::DELETABLE,
				'callback'            => function ( \WP_REST_Request $request ) use ( $repo ) {
					return $this->admin_delete( $repo, $request );
				},
				'permission_callback' => function () use ( $cap ) {
					return $this->can_admin( $cap );
				},
			)
		);
	}

	/**
	 * Check admin capability.
	 *
	 * @param string $capability Capability.
	 * @return bool
	 */
	private function can_admin( string $capability ): bool {
		return is_user_logged_in() && current_user_can( $capability );
	}

	/**
	 * Admin list.
	 *
	 * @param string $repo Repository class.
	 * @param array  $fields Field definitions.
	 * @return \WP_REST_Response
	 */
	private function admin_list( string $repo, array $fields ): \WP_REST_Response {
		$items = $repo::get_all();
		$data  = array();

		foreach ( $items as $item ) {
			$data[] = $this->map_item( $item, $fields );
		}

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Admin item.
	 *
	 * @param string           $repo Repository class.
	 * @param array            $fields Field definitions.
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	private function admin_item( string $repo, array $fields, \WP_REST_Request $request ) {
		$id = (int) $request->get_param( 'id' );

		$item = $repo::get( $id );
		if ( ! $item ) {
			return new \WP_Error(
				'not_found',
				__( 'Item not found.', 'barber-booking' ),
				array( 'status' => 404 )
			);
		}

		return new \WP_REST_Response( $this->map_item( $item, $fields ), 200 );
	}

	/**
	 * Admin create.
	 *
	 * @param string           $repo Repository class.
	 * @param array            $fields Field definitions.
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	private function admin_create( string $repo, array $fields, \WP_REST_Request $request ) {
		$data = $this->get_data_from_request( $request, $fields );
		$id   = $repo::insert( $data );

		if ( ! $id ) {
			return new \WP_Error(
				'create_error',
				__( 'Unable to create item.', 'barber-booking' ),
				array( 'status' => 500 )
			);
		}

		$item = $repo::get( $id );
		return new \WP_REST_Response( $this->map_item( $item, $fields ), 201 );
	}

	/**
	 * Admin update.
	 *
	 * @param string           $repo Repository class.
	 * @param array            $fields Field definitions.
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	private function admin_update( string $repo, array $fields, \WP_REST_Request $request ) {
		$id   = (int) $request->get_param( 'id' );
		$data = $this->get_data_from_request( $request, $fields );

		$repo::update( $id, $data );

		$item = $repo::get( $id );
		if ( ! $item ) {
			return new \WP_Error(
				'not_found',
				__( 'Item not found.', 'barber-booking' ),
				array( 'status' => 404 )
			);
		}

		return new \WP_REST_Response( $this->map_item( $item, $fields ), 200 );
	}

	/**
	 * Admin delete.
	 *
	 * @param string           $repo Repository class.
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	private function admin_delete( string $repo, \WP_REST_Request $request ): \WP_REST_Response {
		$id = (int) $request->get_param( 'id' );
		$repo::delete( $id );
		return new \WP_REST_Response( array( 'deleted' => true ), 200 );
	}

	/**
	 * Map item to response.
	 *
	 * @param object $item Item.
	 * @param array  $fields Field definitions.
	 * @return array
	 */
	private function map_item( object $item, array $fields ): array {
		$data = array(
			'id' => (int) $item->id,
		);

		foreach ( $fields as $key => $type ) {
			$value = $item->$key ?? null;

			if ( 'int_or_null' === $type ) {
				$data[ $key ] = null === $value ? null : (int) $value;
				continue;
			}

			$data[ $key ] = $this->cast_value( $value, $type );
		}

		return $data;
	}

	/**
	 * Cast value.
	 *
	 * @param mixed  $value Value.
	 * @param string $type Type.
	 * @return mixed
	 */
	private function cast_value( $value, string $type ) {
		switch ( $type ) {
			case 'int':
				return (int) $value;
			case 'float':
				return (float) $value;
			case 'bool':
				return (bool) $value;
			default:
				return null === $value ? null : (string) $value;
		}
	}

	/**
	 * Get data from request.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @param array            $fields Field definitions.
	 * @return array
	 */
	private function get_data_from_request( \WP_REST_Request $request, array $fields ): array {
		$params = $request->get_json_params() ?: $request->get_body_params();
		$data   = array();

		foreach ( $fields as $key => $type ) {
			if ( ! isset( $params[ $key ] ) ) {
				continue;
			}
			$data[ $key ] = $this->sanitize_value( $params[ $key ], $type );
		}

		return $data;
	}

	/**
	 * Sanitize value.
	 *
	 * @param mixed  $value Value.
	 * @param string $type Type.
	 * @return mixed
	 */
	private function sanitize_value( $value, string $type ) {
		switch ( $type ) {
			case 'int':
				return absint( $value );
			case 'int_or_null':
				return empty( $value ) ? null : absint( $value );
			case 'float':
				return floatval( $value );
			case 'bool':
				return ! empty( $value );
			case 'date':
				return sanitize_text_field( $value );
			case 'time':
				return sanitize_text_field( $value );
			case 'email':
				return sanitize_email( $value );
			case 'color':
				$color = sanitize_hex_color( $value );
				return $color ? $color : '#000000';
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Validate ID.
	 *
	 * @param mixed $param Parameter.
	 * @return bool
	 */
	public function validate_id( $param ): bool {
		return is_numeric( $param ) && (int) $param > 0;
	}

	/**
	 * Validate date.
	 *
	 * @param mixed $param Parameter.
	 * @return bool
	 */
	public function validate_date( $param ): bool {
		return (bool) strtotime( (string) $param ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $param );
	}

	/**
	 * Validate time.
	 *
	 * @param mixed $param Parameter.
	 * @return bool
	 */
	public function validate_time( $param ): bool {
		return (bool) preg_match( '/^\d{2}:\d{2}(?::\d{2})?$/', (string) $param );
	}

	/**
	 * Check if booking is allowed.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	public function can_book( \WP_REST_Request $request ): bool {
		$nonce = $request->get_header( 'x_wp_nonce' );
		if ( ! wp_verify_nonce( (string) $nonce, 'wp_rest' ) ) {
			return false;
		}
		return $this->check_rate_limit();
	}

	/**
	 * Check rate limit.
	 *
	 * @return bool
	 */
	private function check_rate_limit(): bool {
		$ip = $this->get_client_ip();
		if ( empty( $ip ) ) {
			return true;
		}

		$key   = 'bb_rate_' . md5( $ip );
		$count = get_transient( $key );

		if ( false === $count ) {
			set_transient( $key, 1, HOUR_IN_SECONDS );
			return true;
		}

		if ( (int) $count >= 5 ) {
			return false;
		}

		set_transient( $key, (int) $count + 1, HOUR_IN_SECONDS );
		return true;
	}

	/**
	 * Get client IP.
	 *
	 * @return string
	 */
	private function get_client_ip(): string {
		$keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $keys as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) ) {
				$ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) );
				$ip  = trim( $ips[0] );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '';
	}

	/**
	 * Get services.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_services(): \WP_REST_Response {
		$services = Service::get_active();
		$data     = array();

		foreach ( $services as $service ) {
			$data[] = array(
				'id'          => (int) $service->id,
				'name'        => $service->name,
				'description' => $service->description,
				'duration'    => (int) $service->duration,
				'price'       => (float) $service->price,
				'color'       => $service->color,
				'image_id'    => (int) $service->image_id,
			);
		}

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Get barbers.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_barbers( \WP_REST_Request $request ): \WP_REST_Response {
		$service_id = $request->get_param( 'service_id' );
		$barbers    = array();

		if ( $service_id ) {
			$barbers = Barber::get_services( (int) $service_id );
		} else {
			$barbers = Barber::get_active();
		}

		$data = array();
		foreach ( $barbers as $barber ) {
			$data[] = array(
				'id'       => (int) $barber->id,
				'name'     => $barber->name,
				'color'    => $barber->color,
				'photo_id' => (int) $barber->photo_id,
				'price'    => isset( $barber->barber_price ) ? (float) $barber->barber_price : null,
				'duration' => isset( $barber->barber_duration ) ? (int) $barber->barber_duration : null,
			);
		}

		return new \WP_REST_Response( $data, 200 );
	}

	/**
	 * Get availability.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_availability( \WP_REST_Request $request ): \WP_REST_Response {
		$service_id = (int) $request->get_param( 'service_id' );
		$date       = (string) $request->get_param( 'date' );
		$barber_id  = $request->get_param( 'barber_id' );
		$barber_id  = null !== $barber_id ? (int) $barber_id : null;

		$slots = Availability::get_slots( $service_id, $date, $barber_id );

		return new \WP_REST_Response( $slots, 200 );
	}

	/**
	 * Create booking.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_booking( \WP_REST_Request $request ) {
		$params = $request->get_json_params() ?: $request->get_body_params();

		$service_id = (int) ( $params['service_id'] ?? 0 );
		$barber_id  = (int) ( $params['barber_id'] ?? 0 );
		$station_id = (int) ( $params['station_id'] ?? 0 );
		$date       = sanitize_text_field( $params['date'] ?? '' );
		$time       = sanitize_text_field( $params['time'] ?? '' );
		$name       = sanitize_text_field( $params['name'] ?? '' );
		$phone      = sanitize_text_field( $params['phone'] ?? '' );
		$email      = sanitize_email( $params['email'] ?? '' );
		$notes      = sanitize_textarea_field( $params['notes'] ?? '' );
		$gdpr       = ! empty( $params['gdpr_consent'] );

		if ( ! $gdpr ) {
			return new \WP_Error(
				'gdpr_required',
				__( 'You must accept the privacy policy to continue.', 'barber-booking' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $name ) || empty( $phone ) ) {
			return new \WP_Error(
				'missing_fields',
				__( 'Name and phone are required.', 'barber-booking' ),
				array( 'status' => 400 )
			);
		}

		$service = Service::get( $service_id );
		$barber  = Barber::get( $barber_id );
		$station = Station::get( $station_id );

		if ( ! $service || ! $barber || ! $station ) {
			return new \WP_Error(
				'invalid_selection',
				__( 'Invalid service, barber or station.', 'barber-booking' ),
				array( 'status' => 400 )
			);
		}

		$start = \DateTime::createFromFormat( 'H:i', $time ) ?: \DateTime::createFromFormat( 'H:i:s', $time );
		if ( ! $start ) {
			return new \WP_Error(
				'invalid_time',
				__( 'Invalid time format.', 'barber-booking' ),
				array( 'status' => 400 )
			);
		}

		$end = clone $start;
		$end->modify( '+' . (int) $service->duration . ' minutes' );

		$overlapping = Appointment::get_overlapping(
			$barber_id,
			$station_id,
			$date,
			$start->format( 'H:i:s' ),
			$end->format( 'H:i:s' )
		);

		if ( ! empty( $overlapping ) ) {
			return new \WP_Error(
				'slot_unavailable',
				__( 'The selected slot is no longer available. Please choose another time.', 'barber-booking' ),
				array( 'status' => 409 )
			);
		}

		$customer_id = Customer::upsert(
			array(
				'name'  => $name,
				'phone' => $phone,
				'email' => $email,
			)
		);

		if ( ! $customer_id ) {
			return new \WP_Error(
				'customer_error',
				__( 'Unable to save customer data.', 'barber-booking' ),
				array( 'status' => 500 )
			);
		}

		$appointment_id = Appointment::insert(
			array(
				'customer_id'      => $customer_id,
				'service_id'       => $service_id,
				'barber_id'        => $barber_id,
				'station_id'       => $station_id,
				'appointment_date' => $date,
				'start_time'       => $start->format( 'H:i:s' ),
				'end_time'         => $end->format( 'H:i:s' ),
				'status'           => 'confirmed',
				'notes'            => $notes,
				'source'           => 'web',
			)
		);

		if ( ! $appointment_id ) {
			return new \WP_Error(
				'booking_error',
				__( 'Unable to create booking.', 'barber-booking' ),
				array( 'status' => 500 )
			);
		}

		do_action( 'barber_booking_after_create_appointment', $appointment_id );

		$appointment = Appointment::get( $appointment_id );

		return new \WP_REST_Response(
			array(
				'success'     => true,
				'appointment' => $appointment,
				'message'     => __( 'Booking confirmed.', 'barber-booking' ),
			),
			201
		);
	}
}
