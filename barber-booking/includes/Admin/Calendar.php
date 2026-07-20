<?php
/**
 * Calendar admin view.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Admin;

use BarberBooking\Core\Capabilities;
use BarberBooking\Data\Appointment;
use BarberBooking\Data\Barber;
use BarberBooking\Data\Customer;
use BarberBooking\Data\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Calendar class.
 */
class Calendar {

	private const PAGE_SLUG = 'barber-booking-calendar';

	/**
	 * Render calendar.
	 */
	public function render(): void {
		if ( ! current_user_can( Capabilities::CAP_MANAGE_APPOINTMENTS ) ) {
			return;
		}

		$week_start = isset( $_GET['week_start'] ) ? sanitize_text_field( wp_unslash( $_GET['week_start'] ) ) : gmdate( 'Y-m-d', strtotime( 'monday this week' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$start      = strtotime( $week_start );
		$days       = array();
		for ( $i = 0; $i < 7; $i++ ) {
			$days[] = gmdate( 'Y-m-d', strtotime( "+{$i} days", $start ) );
		}
		$end = end( $days );

		$barber_id = isset( $_GET['barber_id'] ) ? absint( wp_unslash( $_GET['barber_id'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification

		if ( current_user_can( 'barber' ) ) {
			$barber = $this->get_current_barber();
			if ( $barber ) {
				$barber_id = $barber->id;
			}
		}

		$appointments = Appointment::get_for_range( $days[0], $end, $barber_id );
		$by_day       = array();
		foreach ( $appointments as $appointment ) {
			$by_day[ $appointment->appointment_date ][] = $appointment;
		}
		$barbers = Barber::get_active();

		$prev_week = gmdate( 'Y-m-d', strtotime( '-7 days', $start ) );
		$next_week = gmdate( 'Y-m-d', strtotime( '+7 days', $start ) );

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="bb-calendar-nav">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&week_start=' . $prev_week ) ); ?>" class="button"><?php esc_html_e( 'Previous Week', 'barber-booking' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&week_start=' . gmdate( 'Y-m-d', strtotime( 'monday this week' ) ) ) ); ?>" class="button"><?php esc_html_e( 'This Week', 'barber-booking' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&week_start=' . $next_week ) ); ?>" class="button"><?php esc_html_e( 'Next Week', 'barber-booking' ); ?></a>
			</div>

			<?php if ( ! current_user_can( 'barber' ) ) : ?>
			<form method="get" class="bb-filters">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
				<input type="hidden" name="week_start" value="<?php echo esc_attr( $week_start ); ?>">
				<label><?php esc_html_e( 'Barber', 'barber-booking' ); ?>
					<select name="barber_id">
						<option value=""><?php esc_html_e( 'All', 'barber-booking' ); ?></option>
						<?php foreach ( $barbers as $barber ) : ?>
							<option value="<?php echo esc_attr( $barber->id ); ?>" <?php selected( $barber_id, $barber->id ); ?>>
								<?php echo esc_html( $barber->name ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<?php submit_button( __( 'Filter', 'barber-booking' ), 'secondary', 'filter', false ); ?>
			</form>
			<?php endif; ?>

			<div class="bb-calendar-grid">
			<?php foreach ( $days as $day ) : ?>
				<div class="bb-calendar-day">
					<h4><?php echo esc_html( date_i18n( 'l d M', strtotime( $day ) ) ); ?></h4>
					<?php if ( ! empty( $by_day[ $day ] ) ) : ?>
						<?php foreach ( $by_day[ $day ] as $appointment ) : ?>
							<?php
							$customer = Customer::get( (int) $appointment->customer_id );
							$service  = Service::get( (int) $appointment->service_id );
							$barber   = Barber::get( (int) $appointment->barber_id );
							?>
							<div class="bb-calendar-event">
								<strong><?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $appointment->start_time ) ) ); ?></strong><br>
								<?php echo esc_html( $customer->name ?? '' ); ?><br>
								<small><?php echo esc_html( $service->name ?? '' ); ?> - <?php echo esc_html( $barber->name ?? '' ); ?></small>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<p class="bb-no-events"><?php esc_html_e( 'No appointments', 'barber-booking' ); ?></p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get current barber.
	 *
	 * @return object|null
	 */
	private function get_current_barber(): ?object {
		$user_id = get_current_user_id();
		if ( ! $user_id ) {
			return null;
		}
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE user_id = %d AND active = 1 LIMIT 1',
				$wpdb->prefix . 'barber_barbers',
				$user_id
			)
		);
		return $row ?: null;
	}
}
