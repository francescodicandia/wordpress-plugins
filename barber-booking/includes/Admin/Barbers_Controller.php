<?php
/**
 * Barbers admin controller.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Admin;

use BarberBooking\Core\Capabilities;
use BarberBooking\Data\Barber;
use BarberBooking\Data\Service;
use BarberBooking\Data\Station;

defined( 'ABSPATH' ) || exit;

/**
 * Barbers admin controller.
 */
class Barbers_Controller {

	private const PAGE_SLUG = 'barber-booking-barbers';

	/**
	 * Render page.
	 */
	public function render(): void {
		if ( ! current_user_can( Capabilities::CAP_MANAGE_BARBERS ) ) {
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
		if ( empty( $_POST['barber_booking_barber_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['barber_booking_barber_nonce'] ) ), 'barber_booking_barber_action' ) ) {
			return;
		}

		if ( ! current_user_can( Capabilities::CAP_MANAGE_BARBERS ) ) {
			return;
		}

		$data = array(
			'name'   => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'email'  => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'phone'  => sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) ),
			'color'  => sanitize_hex_color( wp_unslash( $_POST['color'] ?? '#000000' ) ) ?: '#000000',
			'active' => ! empty( $_POST['active'] ),
		);

		$id = absint( $_POST['id'] ?? 0 );

		if ( $id ) {
			Barber::update( $id, $data );
		} else {
			$id = Barber::insert( $data );
		}

		if ( $id ) {
			$stations = isset( $_POST['stations'] ) ? array_map( 'intval', (array) $_POST['stations'] ) : array();
			$services = isset( $_POST['services'] ) ? array_map( 'intval', (array) $_POST['services'] ) : array();

			$this->sync_stations( $id, $stations );
			$this->sync_services( $id, $services );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Sync stations.
	 *
	 * @param int   $barber_id Barber ID.
	 * @param array $stations Station IDs.
	 */
	private function sync_stations( int $barber_id, array $stations ): void {
		$current     = Barber::get_stations( $barber_id );
		$current_ids = array_map(
			static function ( $s ) {
				return (int) $s->id;
			},
			$current
		);

		foreach ( $stations as $station_id ) {
			if ( ! in_array( (int) $station_id, $current_ids, true ) ) {
				Barber::assign_station( $barber_id, (int) $station_id );
			}
		}

		foreach ( $current_ids as $current_id ) {
			if ( ! in_array( $current_id, $stations, true ) ) {
				Barber::remove_station( $barber_id, $current_id );
			}
		}
	}

	/**
	 * Sync services.
	 *
	 * @param int   $barber_id Barber ID.
	 * @param array $services Service IDs.
	 */
	private function sync_services( int $barber_id, array $services ): void {
		$current     = Barber::get_services( $barber_id );
		$current_ids = array_map(
			static function ( $s ) {
				return (int) $s->id;
			},
			$current
		);

		foreach ( $services as $service_id ) {
			if ( ! in_array( (int) $service_id, $current_ids, true ) ) {
				Barber::assign_service( $barber_id, (int) $service_id );
			}
		}

		foreach ( $current_ids as $current_id ) {
			if ( ! in_array( $current_id, $services, true ) ) {
				Barber::remove_service( $barber_id, $current_id );
			}
		}
	}

	/**
	 * Render list.
	 */
	private function render_list(): void {
		$barbers = Barber::get_all();
		?>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=add' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Add Barber', 'barber-booking' ); ?>
			</a>
		</p>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Active', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'barber-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $barbers as $barber ) : ?>
				<tr>
					<td><?php echo esc_html( $barber->name ); ?></td>
					<td><?php echo esc_html( $barber->phone ); ?></td>
					<td><?php echo (int) $barber->active ? esc_html__( 'Yes', 'barber-booking' ) : esc_html__( 'No', 'barber-booking' ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $barber->id ) ); ?>">
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
	 * @param int $id Barber ID.
	 */
	private function render_form( int $id ): void {
		$barber               = $id ? Barber::get( $id ) : null;
		$stations             = Station::get_active();
		$services             = Service::get_active();
		$assigned_stations    = $id ? Barber::get_stations( $id ) : array();
		$assigned_services    = $id ? Barber::get_services( $id ) : array();
		$assigned_station_ids = array_map(
			static function ( $s ) {
				return (int) $s->id;
			},
			$assigned_stations
		);
		$assigned_service_ids = array_map(
			static function ( $s ) {
				return (int) $s->id;
			},
			$assigned_services
		);
		?>
		<form method="post">
			<?php wp_nonce_field( 'barber_booking_barber_action', 'barber_booking_barber_nonce' ); ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
			<table class="form-table">
				<tr>
					<th><label for="name"><?php esc_html_e( 'Name', 'barber-booking' ); ?></label></th>
					<td><input type="text" id="name" name="name" value="<?php echo esc_attr( $barber->name ?? '' ); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="email"><?php esc_html_e( 'Email', 'barber-booking' ); ?></label></th>
					<td><input type="email" id="email" name="email" value="<?php echo esc_attr( $barber->email ?? '' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="phone"><?php esc_html_e( 'Phone', 'barber-booking' ); ?></label></th>
					<td><input type="text" id="phone" name="phone" value="<?php echo esc_attr( $barber->phone ?? '' ); ?>" class="regular-text"></td>
				</tr>
				<tr>
					<th><label for="color"><?php esc_html_e( 'Color', 'barber-booking' ); ?></label></th>
					<td><input type="color" id="color" name="color" value="<?php echo esc_attr( $barber->color ?? '#000000' ); ?>"></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Stations', 'barber-booking' ); ?></th>
					<td>
					<?php foreach ( $stations as $station ) : ?>
						<label style="display:block;margin-bottom:4px;">
							<input type="checkbox" name="stations[]" value="<?php echo esc_attr( $station->id ); ?>" <?php checked( in_array( (int) $station->id, $assigned_station_ids, true ) ); ?>>
							<?php echo esc_html( $station->name ); ?>
						</label>
					<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Services', 'barber-booking' ); ?></th>
					<td>
					<?php foreach ( $services as $service ) : ?>
						<label style="display:block;margin-bottom:4px;">
							<input type="checkbox" name="services[]" value="<?php echo esc_attr( $service->id ); ?>" <?php checked( in_array( (int) $service->id, $assigned_service_ids, true ) ); ?>>
							<?php echo esc_html( $service->name ); ?>
						</label>
					<?php endforeach; ?>
					</td>
				</tr>
				<tr>
					<th><label for="active"><?php esc_html_e( 'Active', 'barber-booking' ); ?></label></th>
					<td><input type="checkbox" id="active" name="active" value="1" <?php checked( $barber->active ?? 1, 1 ); ?>></td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Barber', 'barber-booking' ) ); ?>
		</form>
		<?php
	}
}
