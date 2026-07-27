<?php
/**
 * Schedules admin controller.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Admin;

use BarberBooking\Core\Capabilities;
use BarberBooking\Data\Barber;
use BarberBooking\Data\Schedule;

defined( 'ABSPATH' ) || exit;

/**
 * Schedules admin controller.
 */
class Schedules_Controller {

	private const PAGE_SLUG = 'barber-booking-schedules';

	/**
	 * Render page.
	 */
	public function render(): void {
		if ( ! current_user_can( Capabilities::CAP_MANAGE_BARBERS ) ) {
			return;
		}

		$this->handle_post();
		$this->handle_delete();

		$action    = sanitize_text_field( wp_unslash( $_GET['action'] ?? 'list' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$id        = absint( wp_unslash( $_GET['id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$barber_id = isset( $_GET['barber_id'] ) ? absint( wp_unslash( $_GET['barber_id'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<?php

		if ( 'edit' === $action || 'add' === $action ) {
			$this->render_form( $id );
		} else {
			$this->render_list( $barber_id );
		}

		?>
		</div>
		<?php
	}

	/**
	 * Handle POST request.
	 */
	private function handle_post(): void {
		if ( empty( $_POST['barber_booking_schedule_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['barber_booking_schedule_nonce'] ) ), 'barber_booking_schedule_action' ) ) {
			return;
		}

		if ( ! current_user_can( Capabilities::CAP_MANAGE_BARBERS ) ) {
			return;
		}

		$barber_id = isset( $_POST['barber_id'] ) && '' !== $_POST['barber_id'] ? absint( wp_unslash( $_POST['barber_id'] ) ) : null;

		$data = array(
			'barber_id'   => $barber_id,
			'day_of_week' => absint( wp_unslash( $_POST['day_of_week'] ?? 0 ) ),
			'start_time'  => sanitize_text_field( wp_unslash( $_POST['start_time'] ?? '09:00' ) ) . ':00',
			'end_time'    => sanitize_text_field( wp_unslash( $_POST['end_time'] ?? '19:00' ) ) . ':00',
			'active'      => ! empty( $_POST['active'] ),
		);

		$id = absint( wp_unslash( $_POST['id'] ?? 0 ) );

		if ( $id ) {
			Schedule::update( $id, $data );
		} else {
			Schedule::insert( $data );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Handle delete action.
	 */
	private function handle_delete(): void {
		$action = sanitize_text_field( wp_unslash( $_GET['bb_action'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$id     = absint( wp_unslash( $_GET['id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( 'delete' !== $action || ! $id ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'barber_booking_schedule_delete' ) ) {
			return;
		}

		if ( ! current_user_can( Capabilities::CAP_MANAGE_BARBERS ) ) {
			return;
		}

		Schedule::delete( $id );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Render list.
	 *
	 * @param int|null $barber_id Optional barber filter.
	 */
	private function render_list( ?int $barber_id ): void {
		$barbers   = Barber::get_active();
		$schedules = null === $barber_id ? Schedule::get_all() : Schedule::get_for_barber( $barber_id );
		$days      = $this->get_days();
		?>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=add' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Add Schedule', 'barber-booking' ); ?>
			</a>
		</p>

		<form method="get" class="bb-filter">
			<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
			<label>
				<?php esc_html_e( 'Barber', 'barber-booking' ); ?>
				<select name="barber_id">
					<option value=""><?php esc_html_e( 'All', 'barber-booking' ); ?></option>
					<option value="0" <?php selected( $barber_id, 0 ); ?>><?php esc_html_e( 'Global', 'barber-booking' ); ?></option>
					<?php foreach ( $barbers as $barber ) : ?>
						<option value="<?php echo esc_attr( $barber->id ); ?>" <?php selected( $barber_id, (int) $barber->id ); ?>>
							<?php echo esc_html( $barber->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php submit_button( __( 'Filter', 'barber-booking' ), 'secondary', '', false ); ?>
		</form>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Barber', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Day', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Start', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'End', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Active', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'barber-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $schedules ) ) : ?>
				<tr>
					<td colspan="6"><?php esc_html_e( 'No schedules found.', 'barber-booking' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $schedules as $schedule ) : ?>
					<tr>
						<td>
							<?php
							if ( null === $schedule->barber_id ) {
								esc_html_e( 'Global', 'barber-booking' );
							} else {
								$barber = Barber::get( (int) $schedule->barber_id );
								echo esc_html( $barber->name ?? '#' . $schedule->barber_id );
							}
							?>
						</td>
						<td><?php echo esc_html( $days[ (int) $schedule->day_of_week ] ?? '' ); ?></td>
						<td><?php echo esc_html( substr( $schedule->start_time, 0, 5 ) ); ?></td>
						<td><?php echo esc_html( substr( $schedule->end_time, 0, 5 ) ); ?></td>
						<td><?php echo (int) $schedule->active ? esc_html__( 'Yes', 'barber-booking' ) : esc_html__( 'No', 'barber-booking' ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $schedule->id ) ); ?>">
								<?php esc_html_e( 'Edit', 'barber-booking' ); ?>
							</a> |
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&bb_action=delete&id=' . $schedule->id ), 'barber_booking_schedule_delete' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this schedule?', 'barber-booking' ) ); ?>');">
								<?php esc_html_e( 'Delete', 'barber-booking' ); ?>
							</a>
						</td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render form.
	 *
	 * @param int $id Schedule ID.
	 */
	private function render_form( int $id ): void {
		$schedule = $id ? Schedule::get_for_day( 0 ) : null; // Dummy call to load class; real object fetched by id via direct query.
		if ( $id ) {
			global $wpdb;
			$schedule = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $wpdb->prefix . 'barber_schedules', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

		$barbers = Barber::get_active();
		$days    = $this->get_days();
		?>
		<form method="post">
			<?php wp_nonce_field( 'barber_booking_schedule_action', 'barber_booking_schedule_nonce' ); ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
			<table class="form-table">
				<tr>
					<th><label for="barber_id"><?php esc_html_e( 'Barber', 'barber-booking' ); ?></label></th>
					<td>
						<select id="barber_id" name="barber_id">
							<option value="" <?php selected( $schedule->barber_id ?? null, null ); ?>><?php esc_html_e( 'Global', 'barber-booking' ); ?></option>
							<?php foreach ( $barbers as $barber ) : ?>
								<option value="<?php echo esc_attr( $barber->id ); ?>" <?php selected( (int) ( $schedule->barber_id ?? -1 ), (int) $barber->id ); ?>>
									<?php echo esc_html( $barber->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="day_of_week"><?php esc_html_e( 'Day', 'barber-booking' ); ?></label></th>
					<td>
						<select id="day_of_week" name="day_of_week" required>
							<?php foreach ( $days as $index => $day ) : ?>
								<option value="<?php echo esc_attr( $index ); ?>" <?php selected( (int) ( $schedule->day_of_week ?? 1 ), $index ); ?>>
									<?php echo esc_html( $day ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="start_time"><?php esc_html_e( 'Start', 'barber-booking' ); ?></label></th>
					<td><input type="time" id="start_time" name="start_time" value="<?php echo esc_attr( substr( $schedule->start_time ?? '09:00:00', 0, 5 ) ); ?>" required></td>
				</tr>
				<tr>
					<th><label for="end_time"><?php esc_html_e( 'End', 'barber-booking' ); ?></label></th>
					<td><input type="time" id="end_time" name="end_time" value="<?php echo esc_attr( substr( $schedule->end_time ?? '19:00:00', 0, 5 ) ); ?>" required></td>
				</tr>
				<tr>
					<th><label for="active"><?php esc_html_e( 'Active', 'barber-booking' ); ?></label></th>
					<td><input type="checkbox" id="active" name="active" value="1" <?php checked( $schedule->active ?? 1, 1 ); ?>></td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Schedule', 'barber-booking' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Get translated days.
	 *
	 * @return array
	 */
	private function get_days(): array {
		return array(
			__( 'Sunday', 'barber-booking' ),
			__( 'Monday', 'barber-booking' ),
			__( 'Tuesday', 'barber-booking' ),
			__( 'Wednesday', 'barber-booking' ),
			__( 'Thursday', 'barber-booking' ),
			__( 'Friday', 'barber-booking' ),
			__( 'Saturday', 'barber-booking' ),
		);
	}
}
