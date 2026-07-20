<?php
/**
 * Services admin controller.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Admin;

use BarberBooking\Core\Capabilities;
use BarberBooking\Data\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Services admin controller.
 */
class Services_Controller {

	private const PAGE_SLUG = 'barber-booking-services';

	/**
	 * Render page.
	 */
	public function render(): void {
		if ( ! current_user_can( Capabilities::CAP_MANAGE_SERVICES ) ) {
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
		if ( empty( $_POST['barber_booking_service_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['barber_booking_service_nonce'] ) ), 'barber_booking_service_action' ) ) {
			return;
		}

		if ( ! current_user_can( Capabilities::CAP_MANAGE_SERVICES ) ) {
			return;
		}

		$data = array(
			'name'        => sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			'description' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ),
			'duration'    => absint( $_POST['duration'] ?? 30 ),
			'price'       => floatval( wp_unslash( $_POST['price'] ?? 0 ) ),
			'color'       => sanitize_hex_color( wp_unslash( $_POST['color'] ?? '#000000' ) ) ?: '#000000',
			'image_id'    => absint( $_POST['image_id'] ?? 0 ),
			'active'      => ! empty( $_POST['active'] ),
		);

		$id = absint( $_POST['id'] ?? 0 );

		if ( $id ) {
			Service::update( $id, $data );
		} else {
			Service::insert( $data );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
		exit;
	}

	/**
	 * Render list.
	 */
	private function render_list(): void {
		$services = Service::get_all();
		?>
		<p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=add' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Add Service', 'barber-booking' ); ?>
			</a>
		</p>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Name', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Duration', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Price', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Active', 'barber-booking' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'barber-booking' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $services as $service ) : ?>
				<tr>
					<td><?php echo esc_html( $service->name ); ?></td>
					<td><?php echo esc_html( $service->duration ); ?> min</td>
					<td><?php echo esc_html( number_format_i18n( (float) $service->price, 2 ) ); ?> €</td>
					<td><?php echo (int) $service->active ? esc_html__( 'Yes', 'barber-booking' ) : esc_html__( 'No', 'barber-booking' ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG . '&action=edit&id=' . $service->id ) ); ?>">
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
	 * @param int $id Service ID.
	 */
	private function render_form( int $id ): void {
		$service = $id ? Service::get( $id ) : null;
		?>
		<form method="post">
			<?php wp_nonce_field( 'barber_booking_service_action', 'barber_booking_service_nonce' ); ?>
			<input type="hidden" name="id" value="<?php echo esc_attr( $id ); ?>">
			<table class="form-table">
				<tr>
					<th><label for="name"><?php esc_html_e( 'Name', 'barber-booking' ); ?></label></th>
					<td><input type="text" id="name" name="name" value="<?php echo esc_attr( $service->name ?? '' ); ?>" class="regular-text" required></td>
				</tr>
				<tr>
					<th><label for="description"><?php esc_html_e( 'Description', 'barber-booking' ); ?></label></th>
					<td><textarea id="description" name="description" rows="3" class="large-text"><?php echo esc_textarea( $service->description ?? '' ); ?></textarea></td>
				</tr>
				<tr>
					<th><label for="duration"><?php esc_html_e( 'Duration (minutes)', 'barber-booking' ); ?></label></th>
					<td><input type="number" id="duration" name="duration" value="<?php echo esc_attr( $service->duration ?? 30 ); ?>" class="small-text" required></td>
				</tr>
				<tr>
					<th><label for="price"><?php esc_html_e( 'Price', 'barber-booking' ); ?></label></th>
					<td><input type="number" step="0.01" id="price" name="price" value="<?php echo esc_attr( $service->price ?? 0 ); ?>" class="small-text"></td>
				</tr>
				<tr>
					<th><label for="color"><?php esc_html_e( 'Color', 'barber-booking' ); ?></label></th>
					<td><input type="color" id="color" name="color" value="<?php echo esc_attr( $service->color ?? '#000000' ); ?>"></td>
				</tr>
				<tr>
					<th><label for="active"><?php esc_html_e( 'Active', 'barber-booking' ); ?></label></th>
					<td><input type="checkbox" id="active" name="active" value="1" <?php checked( $service->active ?? 1, 1 ); ?>></td>
				</tr>
			</table>
			<?php submit_button( __( 'Save Service', 'barber-booking' ) ); ?>
		</form>
		<?php
	}
}
