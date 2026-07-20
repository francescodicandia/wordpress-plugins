<?php
/**
 * Stations admin controller.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Admin;

use BarberBooking\Core\Capabilities;
use BarberBooking\Data\Station;

defined( 'ABSPATH' ) || exit;

/**
 * Stations admin controller.
 */
class Stations_Controller {

	private const PAGE_SLUG = 'barber-booking-stations';

	/**
	 * Render page.
	 */
	public function render(): void {
		if ( ! current_user_can( Capabilities::CAP_MANAGE_STATIONS ) ) {
			return;
		}

		$this->handle_post();

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
		if ( empty( $_POST['barber_booking_station_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['barber_booking_station_nonce'] ) ), 'barber_booking_station_action' ) ) {
			return;
		}

		if ( ! current_user_can( Capabilities::CAP_MANAGE_STATIONS ) ) {
			return;
		}

		$data = array(
			'name'   => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'color'  => sanitize_hex_color( wp_unslash( $_POST['color'] ?? '#000000' ) ) ?: '#000000',
			'active' => ! empty( $_POST['active'] ),
		);

		$id = absint( $_POST['id'] ?? 0 );

		if ( $id ) {
			Station::update( $id, $data );
		} else {
			Station::insert( $data );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Render list.
	 */
	private function render_list(): void {
		$stations = Station::get_all();
		?>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=add' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Add Station', 'barber-booking' ); ?>
			</a>
		</p>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Active', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'barber-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $stations as $station ) : ?>
				<tr>
					<td><?php echo esc_html( $station->name ); ?></td>
					<td><?php echo (int) $station->active ? esc_html__( 'Yes', 'barber-booking' ) : esc_html__( 'No', 'barber-booking' ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $station->id ) ); ?>">
							<?php esc_html_e( 'Edit', 'barber-booking' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render form.
	 *
	 * @param int $id Station ID.
	 */
	private function render_form( int $id ): void {
		$station = $id ? Station::get( $id ) : null;
		?>
		<form method="post">
			<?php wp_nonce_field( 'barber_booking_station_action', 'barber_booking_station_nonce' ); ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
			<table class="form-table">
				<tr>
					<th><label for="name"><?php esc_html_e( 'Name', 'barber-booking' ); ?></label></th>
					<td><input type="text" id="name" name="name" value="<?php echo esc_attr( $station->name ?? '' ); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="color"><?php esc_html_e( 'Color', 'barber-booking' ); ?></label></th>
					<td><input type="color" id="color" name="color" value="<?php echo esc_attr( $station->color ?? '#000000' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="active"><?php esc_html_e( 'Active', 'barber-booking' ); ?></label></th>
					<td><input type="checkbox" id="active" name="active" value="1" <?php checked( $station->active ?? 1, 1 ); ?>></td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Station', 'barber-booking' ) ); ?>
		</form>
		<?php
	}
}
