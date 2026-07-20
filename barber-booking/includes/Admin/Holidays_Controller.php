<?php
/**
 * Holidays admin controller.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Admin;

use BarberBooking\Core\Capabilities;
use BarberBooking\Data\Barber;
use BarberBooking\Data\Holiday;

defined( 'ABSPATH' ) || exit;

/**
 * Holidays admin controller.
 */
class Holidays_Controller {

	private const PAGE_SLUG = 'barber-booking-holidays';

	/**
	 * Render page.
	 */
	public function render(): void {
		if ( ! current_user_can( Capabilities::CAP_MANAGE_BARBERS ) ) {
			return;
		}

		$this->handle_post();
		$this->handle_delete();

		$action = sanitize_text_field( wp_unslash( $_GET['action'] ?? 'list' ) ); // phpcs:ignore WordPress.Security.NonceVerification
		$id     = absint( wp_unslash( $_GET['id'] ?? 0 ) ); // phpcs:ignore WordPress.Security.NonceVerification

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<?php

		if ( 'edit' === $action || 'add' === $action ) {
			$this->render_form( $id );
		} else {
			$this->render_list();
		}

		?>
		</div>
		<?php
	}

	/**
	 * Handle POST request.
	 */
	private function handle_post(): void {
		if ( empty( $_POST['barber_booking_holiday_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['barber_booking_holiday_nonce'] ) ), 'barber_booking_holiday_action' ) ) {
			return;
		}

		if ( ! current_user_can( Capabilities::CAP_MANAGE_BARBERS ) ) {
			return;
		}

		$barber_id = isset( $_POST['barber_id'] ) && '' !== $_POST['barber_id'] ? absint( wp_unslash( $_POST['barber_id'] ) ) : null;
		$all_day   = ! empty( $_POST['all_day'] );

		$data = array(
			'barber_id'    => $barber_id,
			'holiday_date' => sanitize_text_field( wp_unslash( $_POST['holiday_date'] ?? gmdate( 'Y-m-d' ) ) ),
			'all_day'      => $all_day,
			'reason'       => sanitize_text_field( wp_unslash( $_POST['reason'] ?? '' ) ),
		);

		if ( ! $all_day ) {
			$start = sanitize_text_field( wp_unslash( $_POST['start_time'] ?? '' ) );
			$end   = sanitize_text_field( wp_unslash( $_POST['end_time'] ?? '' ) );
			if ( $start ) {
				$data['start_time'] = $start . ':00';
			}
			if ( $end ) {
				$data['end_time'] = $end . ':00';
			}
		}

		$id = absint( $_POST['id'] ?? 0 );

		if ( $id ) {
			Holiday::update( $id, $data );
		} else {
			Holiday::insert( $data );
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

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) ), 'barber_booking_holiday_delete' ) ) {
			return;
		}

		if ( ! current_user_can( Capabilities::CAP_MANAGE_BARBERS ) ) {
			return;
		}

		Holiday::delete( $id );
		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Render list.
	 */
	private function render_list(): void {
		$holidays   = Holiday::get_upcoming();
		$barbers    = Barber::get_active();
		$barber_map = array();
		foreach ( $barbers as $barber ) {
			$barber_map[ (int) $barber->id ] = $barber->name;
		}
		?>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=add' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Add Holiday', 'barber-booking' ); ?>
			</a>
		</p>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Barber', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Date', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Time', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Reason', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'barber-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( empty( $holidays ) ) : ?>
				<tr>
					<td colspan="5"><?php esc_html_e( 'No upcoming holidays.', 'barber-booking' ); ?></td>
				</tr>
			<?php else : ?>
				<?php foreach ( $holidays as $holiday ) : ?>
					<tr>
						<td>
							<?php
							if ( null === $holiday->barber_id ) {
								esc_html_e( 'Global', 'barber-booking' );
							} else {
								echo esc_html( $barber_map[ (int) $holiday->barber_id ] ?? '#' . $holiday->barber_id );
							}
							?>
						</td>
						<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $holiday->holiday_date ) ) ); ?></td>
						<td>
							<?php
							if ( (int) $holiday->all_day ) {
								esc_html_e( 'All day', 'barber-booking' );
							} elseif ( $holiday->start_time && $holiday->end_time ) {
								echo esc_html( substr( $holiday->start_time, 0, 5 ) . ' - ' . substr( $holiday->end_time, 0, 5 ) );
							} else {
								echo '—';
							}
							?>
						</td>
						<td><?php echo esc_html( $holiday->reason ); ?></td>
						<td>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $holiday->id ) ); ?>">
								<?php esc_html_e( 'Edit', 'barber-booking' ); ?>
							</a> |
							<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&bb_action=delete&id=' . $holiday->id ), 'barber_booking_holiday_delete' ) ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Delete this holiday?', 'barber-booking' ) ); ?>');">
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
	 * @param int $id Holiday ID.
	 */
	private function render_form( int $id ): void {
		$holiday = null;
		if ( $id ) {
			global $wpdb;
			$holiday = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $wpdb->prefix . 'barber_holidays', $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		}

		$barbers = Barber::get_active();
		$all_day = (int) ( $holiday->all_day ?? 1 );
		?>
		<form method="post">
			<?php wp_nonce_field( 'barber_booking_holiday_action', 'barber_booking_holiday_nonce' ); ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
			<table class="form-table">
				<tr>
					<th><label for="barber_id"><?php esc_html_e( 'Barber', 'barber-booking' ); ?></label></th>
					<td>
						<select id="barber_id" name="barber_id">
							<option value="" <?php selected( $holiday->barber_id ?? null, null ); ?>><?php esc_html_e( 'Global', 'barber-booking' ); ?></option>
							<?php foreach ( $barbers as $barber ) : ?>
								<option value="<?php echo esc_attr( $barber->id ); ?>" <?php selected( (int) ( $holiday->barber_id ?? -1 ), (int) $barber->id ); ?>>
									<?php echo esc_html( $barber->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th><label for="holiday_date"><?php esc_html_e( 'Date', 'barber-booking' ); ?></label></th>
					<td><input type="date" id="holiday_date" name="holiday_date" value="<?php echo esc_attr( $holiday->holiday_date ?? gmdate( 'Y-m-d' ) ); ?>" required></td>
				</tr>
				<tr>
					<th><label for="all_day"><?php esc_html_e( 'All day', 'barber-booking' ); ?></label></th>
					<td><input type="checkbox" id="all_day" name="all_day" value="1" <?php checked( $all_day, 1 ); ?>></td>
				</tr>
				<tr>
					<th><label for="start_time"><?php esc_html_e( 'Start', 'barber-booking' ); ?></label></th>
					<td><input type="time" id="start_time" name="start_time" value="<?php echo esc_attr( substr( $holiday->start_time ?? '', 0, 5 ) ); ?>"></td>
				</tr>
				<tr>
					<th><label for="end_time"><?php esc_html_e( 'End', 'barber-booking' ); ?></label></th>
					<td><input type="time" id="end_time" name="end_time" value="<?php echo esc_attr( substr( $holiday->end_time ?? '', 0, 5 ) ); ?>"></td>
				</tr>
				<tr>
					<th><label for="reason"><?php esc_html_e( 'Reason', 'barber-booking' ); ?></label></th>
					<td><input type="text" id="reason" name="reason" value="<?php echo esc_attr( $holiday->reason ?? '' ); ?>" class="regular-text"></td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Holiday', 'barber-booking' ) ); ?>
		</form>
		<?php
	}
}
