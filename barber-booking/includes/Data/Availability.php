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

		foreach ( $barbers as $barber ) {
			$barber_id = (int) $barber->id;
			$schedules = self::get_schedules_for_barber( $barber_id, $day_of_week );
			$stations  = Barber::get_stations( $barber_id );

			if ( empty( $stations ) ) {
				continue;
			}

			foreach ( $schedules as $schedule ) {
				$start = new \DateTime( $date . ' ' . $schedule->start_time );
				$end   = new \DateTime( $date . ' ' . $schedule->end_time );

				while ( $start < $end ) {
					$slot_end = clone $start;
					$slot_end->modify( "+{$duration} minutes" );

					if ( $slot_end > $end ) {
						break;
					}

					$station = self::find_available_station(
						$stations,
						$barber_id,
						$date,
						$start->format( 'H:i:s' ),
						$slot_end->format( 'H:i:s' )
					);

					if ( $station && ! self::has_barber_overlap( $barber_id, $date, $start->format( 'H:i:s' ), $slot_end->format( 'H:i:s' ) ) ) {
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
	 * Find an available station.
	 *
	 * @param array  $stations Stations.
	 * @param int    $barber_id Barber ID.
	 * @param string $date Date.
	 * @param string $start_time Start time.
	 * @param string $end_time End time.
	 * @return object|null
	 */
	private static function find_available_station( array $stations, int $barber_id, string $date, string $start_time, string $end_time ): ?object {
		foreach ( $stations as $station ) {
			$overlapping = Appointment::get_overlapping(
				$barber_id,
				(int) $station->id,
				$date,
				$start_time,
				$end_time
			);

			if ( empty( $overlapping ) ) {
				return $station;
			}
		}
		return null;
	}

	/**
	 * Check if barber has an overlapping appointment.
	 *
	 * @param int    $barber_id Barber ID.
	 * @param string $date Date.
	 * @param string $start_time Start time.
	 * @param string $end_time End time.
	 * @return bool
	 */
	private static function has_barber_overlap( int $barber_id, string $date, string $start_time, string $end_time ): bool {
		$overlapping = Appointment::get_overlapping(
			$barber_id,
			null,
			$date,
			$start_time,
			$end_time
		);
		return ! empty( $overlapping );
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
