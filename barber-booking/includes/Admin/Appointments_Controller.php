<?php
/**
 * Appointments admin controller.
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
 * Appointments admin controller.
 */
class Appointments_Controller {

	private const PAGE_SLUG = 'barber-booking-appointments';

	/**
	 * Render page.
	 */
	public function render(): void {
		if ( ! current_user_can( Capabilities::CAP_MANAGE_APPOINTMENTS ) ) {
			return;
		}

		$this->handle_actions();

		$barber_id = isset( $_GET['barber_id'] ) ? absint( wp_unslash( $_GET['barber_id'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
		$status    = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
		$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : gmdate( 'Y-m-d' ); // phpcs:ignore WordPress.Security.NonceVerification
		$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : gmdate( 'Y-m-d', strtotime( '+7 days' ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( current_user_can( 'barber' ) ) {
			$barber = $this->get_current_barber();
			if ( $barber ) {
				$barber_id = $barber->id;
			}
		}

		$appointments = Appointment::get_for_range( $date_from, $date_to, $barber_id, null, $status );
		$barbers      = Barber::get_active();

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<form method="get" class="bb-filters">
				<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
				<label><?php esc_html_e( 'From', 'barber-booking' ); ?>
					<input type="date" name="date_from" value="<?php echo esc_attr( $date_from ); ?>">
				</label>
				<label><?php esc_html_e( 'To', 'barber-booking' ); ?>
					<input type="date" name="date_to" value="<?php echo esc_attr( $date_to ); ?>">
				</label>
				<?php if ( ! current_user_can( 'barber' ) ) : ?>
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
				<?php endif; ?>
				<label><?php esc_html_e( 'Status', 'barber-booking' ); ?>
					<select name="status">
						<option value=""><?php esc_html_e( 'All', 'barber-booking' ); ?></option>
						<?php foreach ( $this->get_statuses() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</label>
				<?php submit_button( __( 'Filter', 'barber-booking' ), 'secondary', 'filter', false ); ?>
			</form>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date & Time', 'barber-booking' ); ?></th>
						<th><?php esc_html_e( 'Customer', 'barber-booking' ); ?></th>
						<th><?php esc_html_e( 'Service', 'barber-booking' ); ?></th>
						<th><?php esc_html_e( 'Barber', 'barber-booking' ); ?></th>
						<th><?php esc_html_e( 'Status', 'barber-booking' ); ?></th>
						<th><?php esc_html_e( 'Actions', 'barber-booking' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $appointments as $appointment ) : ?>
					<?php
					$customer = Customer::get( (int) $appointment->customer_id );
					$service  = Service::get( (int) $appointment->service_id );
					$barber   = Barber::get( (int) $appointment->barber_id );
					?>
					<tr>
						<td>
							<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $appointment->appointment_date ) ) ); ?><br>
							<?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime( $appointment->start_time ) ) ); ?>
						</td>
						<td>
							<?php echo esc_html( $customer->name ?? '' ); ?><br>
							<?php echo esc_html( $customer->phone ?? '' ); ?>
						</td>
						<td><?php echo esc_html( $service->name ?? '' ); ?></td>
						<td><?php echo esc_html( $barber->name ?? '' ); ?></td>
						<td><?php echo esc_html( $this->get_status_label( $appointment->status ) ); ?></td>
						<td>
							<?php if ( $customer && $customer->phone ) : ?>
								<a href="<?php echo esc_url( $this->whatsapp_link( $customer->name, $customer->phone, $appointment ) ); ?>" target="_blank" class="button button-small">
									<?php esc_html_e( 'WhatsApp', 'barber-booking' ); ?>
								</a>
							<?php endif; ?>
							<?php if ( 'confirmed' === $appointment->status ) : ?>
								<a href="<?php echo esc_url( $this->action_url( 'complete', (int) $appointment->id ) ); ?>" class="button button-small">
									<?php esc_html_e( 'Complete', 'barber-booking' ); ?>
								</a>
							<?php endif; ?>
							<a href="<?php echo esc_url( $this->action_url( 'cancel', (int) $appointment->id ) ); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e( 'Cancel this appointment?', 'barber-booking' ); ?>');">
								<?php esc_html_e( 'Cancel', 'barber-booking' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Get current barber for logged in user.
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

	/**
	 * Handle actions.
	 */
	private function handle_actions(): void {
		$action = sanitize_text_field( wp_unslash( $_GET['bb_action'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$id     = absint( wp_unslash( $_GET['id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( empty( $action ) || ! $id ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'barber_booking_appointment_action' ) ) {
			return;
		}

		if ( 'complete' === $action ) {
			Appointment::update( $id, array( 'status' => 'completed' ) );
		} elseif ( 'cancel' === $action ) {
			Appointment::update( $id, array( 'status' => 'cancelled' ) );
		} elseif ( 'no_show' === $action ) {
			Appointment::update( $id, array( 'status' => 'no_show' ) );
		}

		wp_safe_redirect( remove_query_arg( array( 'bb_action', 'id', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Build action URL.
	 *
	 * @param string $action Action.
	 * @param int    $id Appointment ID.
	 * @return string
	 */
	private function action_url( string $action, int $id ): string {
		$url = admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&bb_action=' . $action . '&id=' . $id );
		return wp_nonce_url( $url, 'barber_booking_appointment_action' );
	}

	/**
	 * Build WhatsApp link.
	 *
	 * @param string $name Customer name.
	 * @param string $phone Customer phone.
	 * @param object $appointment Appointment.
	 * @return string
	 */
	private function whatsapp_link( string $name, string $phone, object $appointment ): string {
		$phone = preg_replace( '/[^0-9]/', '', $phone );
		$date  = date_i18n( get_option( 'date_format' ), strtotime( $appointment->appointment_date ) );
		$time  = date_i18n( get_option( 'time_format' ), strtotime( $appointment->start_time ) );
		$text  = sprintf(
			// translators: 1: customer name, 2: appointment date, 3: appointment time.
			__( 'Hi %1$s, this is for your appointment on %2$s at %3$s.', 'barber-booking' ),
			$name,
			$date,
			$time
		);
		return 'https://wa.me/' . $phone . '?text=' . rawurlencode( $text );
	}

	/**
	 * Get statuses.
	 *
	 * @return array
	 */
	private function get_statuses(): array {
		return array(
			'confirmed' => __( 'Confirmed', 'barber-booking' ),
			'completed' => __( 'Completed', 'barber-booking' ),
			'cancelled' => __( 'Cancelled', 'barber-booking' ),
			'no_show'   => __( 'No Show', 'barber-booking' ),
		);
	}

	/**
	 * Get status label.
	 *
	 * @param string $status Status.
	 * @return string
	 */
	private function get_status_label( string $status ): string {
		$statuses = $this->get_statuses();
		return $statuses[ $status ] ?? $status;
	}
}
