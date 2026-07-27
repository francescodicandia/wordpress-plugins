<?php
/**
 * Availability engine.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Data;

defined( 'ABSPATH' ) || exit;

/**
 * Availability engine.
 */
class Availability {

	/**
	 * Get available slots for a service and date.
	 *
	 * @param int      $service_id Service ID.
	 * @param string   $date Date (Y-m-d).
	 * @param int|null $barber_id Optional barber ID.
	 * @return array
	 */
	public static function get_slots( int $service_id, string $date, ?int $barber_id = null ): array {
		$max_days = apply_filters( 'barber_booking_max_booking_days', 180 );
		if ( strtotime( $date ) > strtotime( "+{$max_days} days" ) ) {
			return array();
		}

		$service = Service::get( $service_id );
		if ( ! $service ) {
			return array();
		}

		$duration = (int) $service->duration;
		$barbers  = self::get_barbers( $service_id, $barber_id );

		if ( empty( $barbers ) ) {
			return array();
		}

		$day_of_week = (int) gmdate( 'w', strtotime( $date ) );
		$slots       = array();
		$interval    = self::get_slot_interval();

		$holidays = Holiday::get_for_range( $date, $date, null );
		$barber_holidays = array();
		foreach ( $holidays as $holiday ) {
			$bid = $holiday->barber_id ? (int) $holiday->barber_id : 0;
			$barber_holidays[ $bid ][] = $holiday;
		}

		$all_appointments = Appointment::get_for_range( $date, $date );
		$appointments_by_barber = array();
		foreach ( $all_appointments as $apt ) {
			$bid = (int) $apt->barber_id;
			$appointments_by_barber[ $bid ][] = $apt;
		}

		foreach ( $barbers as $barber ) {
			$barber_id = (int) $barber->id;

			$relevant_holidays = array_merge(
				$barber_holidays[ $barber_id ] ?? array(),
				$barber_holidays[0] ?? array()
			);

			$is_all_day_off = false;
			$partial_slots  = array();
			foreach ( $relevant_holidays as $holiday ) {
				if ( (int) $holiday->all_day ) {
					$is_all_day_off = true;
					break;
				}
				$partial_slots[] = array(
					'start' => $holiday->start_time,
					'end'   => $holiday->end_time,
				);
			}

			if ( $is_all_day_off ) {
				continue;
			}

			$schedules = self::get_schedules_for_barber( $barber_id, $day_of_week );
			$stations  = Barber::get_stations( $barber_id );

			if ( empty( $stations ) ) {
				continue;
			}

			$barber_apts = $appointments_by_barber[ $barber_id ] ?? array();

			foreach ( $schedules as $schedule ) {
				$start = new \DateTime( $date . ' ' . $schedule->start_time );
				$end   = new \DateTime( $date . ' ' . $schedule->end_time );

				while ( $start < $end ) {
					$slot_end = clone $start;
					$slot_end->modify( "+{$duration} minutes" );

					if ( $slot_end > $end ) {
						break;
					}

					$slot_start_str = $start->format( 'H:i:s' );
					$slot_end_str   = $slot_end->format( 'H:i:s' );

					if ( self::is_in_partial_holiday( $slot_start_str, $slot_end_str, $partial_slots ) ) {
						$start->modify( "+{$interval} minutes" );
						continue;
					}

					if ( self::slot_has_barber_overlap( $slot_start_str, $slot_end_str, $barber_apts ) ) {
						$start->modify( "+{$interval} minutes" );
						continue;
					}

					$station = self::find_free_station( $stations, $slot_start_str, $slot_end_str, $barber_apts );

					if ( $station ) {
						$slots[] = array(
							'time'         => $start->format( 'H:i' ),
							'time_full'    => $start->format( 'H:i:s' ),
							'end_time'     => $slot_end->format( 'H:i:s' ),
							'barber_id'    => $barber_id,
							'barber_name'  => $barber->name,
							'barber_color' => $barber->color,
							'station_id'   => (int) $station->id,
							'station_name' => $station->name,
							'duration'     => $duration,
						);
					}

					$start->modify( "+{$interval} minutes" );
				}
			}
		}

		usort(
			$slots,
			static function ( array $a, array $b ): int {
				$cmp = strcmp( $a['time'], $b['time'] );
				if ( 0 !== $cmp ) {
					return $cmp;
				}
				return strcmp( $a['barber_name'], $b['barber_name'] );
			}
		);

		return $slots;
	}

	/**
	 * Get barbers for a service.
	 *
	 * @param int      $service_id Service ID.
	 * @param int|null $barber_id Optional barber filter.
	 * @return array
	 */
	private static function get_barbers( int $service_id, ?int $barber_id = null ): array {
		if ( null !== $barber_id ) {
			$barber = self::get_barber( $barber_id );
			if ( ! $barber ) {
				return array();
			}
			$services = Barber::get_services( $barber_id );
			foreach ( $services as $service ) {
				if ( (int) $service->id === $service_id ) {
					return array( $barber );
				}
			}
			return array();
		}

		global $wpdb;
		$barber_table   = $wpdb->prefix . 'barber_barbers';
		$relation_table = $wpdb->prefix . 'barber_barber_service';

		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT b.* FROM %i b INNER JOIN %i r ON b.id = r.barber_id WHERE r.service_id = %d AND b.active = 1 ORDER BY b.name ASC',
				$barber_table,
				$relation_table,
				$service_id
			)
		);
	}

	/**
	 * Get barber.
	 *
	 * @param int $barber_id Barber ID.
	 * @return object|null
	 */
	private static function get_barber( int $barber_id ): ?object {
		$barber = Barber::get( $barber_id );
		if ( ! $barber || ! (int) $barber->active ) {
			return null;
		}
		return $barber;
	}

	/**
	 * Get schedules for a barber on a day.
	 *
	 * @param int $barber_id Barber ID.
	 * @param int $day_of_week Day of week.
	 * @return array
	 */
	private static function get_schedules_for_barber( int $barber_id, int $day_of_week ): array {
		$schedules = Schedule::get_for_day( $day_of_week, $barber_id );
		if ( empty( $schedules ) ) {
			$schedules = Schedule::get_for_day( $day_of_week, null );
		}
		return $schedules;
	}

	/**
	 * Find a free station using pre-fetched appointments.
	 *
	 * @param array  $stations  Stations.
	 * @param string $start_time Slot start (H:i:s).
	 * @param string $end_time   Slot end (H:i:s).
	 * @param array  $appointments Pre-fetched appointments for this barber.
	 * @return object|null
	 */
	private static function find_free_station( array $stations, string $start_time, string $end_time, array $appointments ): ?object {
		foreach ( $stations as $station ) {
			$sid = (int) $station->id;
			$occupied = false;
			foreach ( $appointments as $apt ) {
				if ( (int) $apt->station_id !== $sid ) {
					continue;
				}
				if ( in_array( $apt->status, array( 'cancelled', 'no_show' ), true ) ) {
					continue;
				}
				if ( $start_time < $apt->end_time && $end_time > $apt->start_time ) {
					$occupied = true;
					break;
				}
			}
			if ( ! $occupied ) {
				return $station;
			}
		}
		return null;
	}

	/**
	 * Check if barber has an overlapping appointment using pre-fetched data.
	 *
	 * @param string $start_time Slot start (H:i:s).
	 * @param string $end_time   Slot end (H:i:s).
	 * @param array  $appointments Pre-fetched appointments for this barber.
	 * @return bool
	 */
	private static function slot_has_barber_overlap( string $start_time, string $end_time, array $appointments ): bool {
		foreach ( $appointments as $apt ) {
			if ( in_array( $apt->status, array( 'cancelled', 'no_show' ), true ) ) {
				continue;
			}
			if ( $start_time < $apt->end_time && $end_time > $apt->start_time ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if a time slot overlaps with partial holidays.
	 *
	 * @param string $start_time Slot start (H:i:s).
	 * @param string $end_time   Slot end (H:i:s).
	 * @param array  $holidays   Partial holidays with 'start' and 'end'.
	 * @return bool
	 */
	private static function is_in_partial_holiday( string $start_time, string $end_time, array $holidays ): bool {
		foreach ( $holidays as $holiday ) {
			if ( $start_time < $holiday['end'] && $end_time > $holiday['start'] ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get slot interval from settings.
	 *
	 * @return int
	 */
	private static function get_slot_interval(): int {
		$settings = get_option( \BarberBooking\PLUGIN_SETTINGS, array() );
		$interval = absint( $settings['slot_interval'] ?? 15 );
		return max( 5, min( 60, $interval ) );
	}
}
