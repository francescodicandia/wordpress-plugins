<?php
/**
 * Admin menu.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 */
class Admin {

	/**
	 * Initialize.
	 */
	public function init(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_filter( 'wp_redirect', array( $this, 'clean_buffer_before_redirect' ), 0 );
	}

	/**
	 * Clean output buffers before redirect to prevent "headers already sent" errors.
	 *
	 * @param string $location Redirect URL.
	 * @return string
	 */
	public function clean_buffer_before_redirect( string $location ): string {
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		return $location;
	}

	/**
	 * Add menu pages.
	 */
	public function add_menu_pages(): void {
		$menu_title = apply_filters( 'barber_booking_menu_title', __( 'Barber Booking', 'barber-booking' ) );

		add_menu_page(
			$menu_title,
			$menu_title,
			\BarberBooking\Core\Capabilities::CAP_MANAGE_APPOINTMENTS,
			'barber-booking',
			array( $this, 'render_dashboard' ),
			'dashicons-calendar-alt',
			30
		);

		add_submenu_page(
			'barber-booking',
			__( 'Dashboard', 'barber-booking' ),
			__( 'Dashboard', 'barber-booking' ),
			\BarberBooking\Core\Capabilities::CAP_MANAGE_APPOINTMENTS,
			'barber-booking',
			array( $this, 'render_dashboard' )
		);

		add_submenu_page(
			'barber-booking',
			__( 'Appointments', 'barber-booking' ),
			__( 'Appointments', 'barber-booking' ),
			\BarberBooking\Core\Capabilities::CAP_MANAGE_APPOINTMENTS,
			'barber-booking-appointments',
			array( new Appointments_Controller(), 'render' )
		);

		add_submenu_page(
			'barber-booking',
			__( 'Calendar', 'barber-booking' ),
			__( 'Calendar', 'barber-booking' ),
			\BarberBooking\Core\Capabilities::CAP_MANAGE_APPOINTMENTS,
			'barber-booking-calendar',
			array( new Calendar(), 'render' )
		);

		add_submenu_page(
			'barber-booking',
			__( 'Services', 'barber-booking' ),
			__( 'Services', 'barber-booking' ),
			\BarberBooking\Core\Capabilities::CAP_MANAGE_SERVICES,
			'barber-booking-services',
			array( new Services_Controller(), 'render' )
		);

		add_submenu_page(
			'barber-booking',
			__( 'Stations', 'barber-booking' ),
			__( 'Stations', 'barber-booking' ),
			\BarberBooking\Core\Capabilities::CAP_MANAGE_STATIONS,
			'barber-booking-stations',
			array( new Stations_Controller(), 'render' )
		);

		add_submenu_page(
			'barber-booking',
			__( 'Barbers', 'barber-booking' ),
			__( 'Barbers', 'barber-booking' ),
			\BarberBooking\Core\Capabilities::CAP_MANAGE_BARBERS,
			'barber-booking-barbers',
			array( new Barbers_Controller(), 'render' )
		);

		add_submenu_page(
			'barber-booking',
			__( 'Schedules', 'barber-booking' ),
			__( 'Schedules', 'barber-booking' ),
			\BarberBooking\Core\Capabilities::CAP_MANAGE_BARBERS,
			'barber-booking-schedules',
			array( new Schedules_Controller(), 'render' )
		);

		add_submenu_page(
			'barber-booking',
			__( 'Holidays', 'barber-booking' ),
			__( 'Holidays', 'barber-booking' ),
			\BarberBooking\Core\Capabilities::CAP_MANAGE_BARBERS,
			'barber-booking-holidays',
			array( new Holidays_Controller(), 'render' )
		);

		add_submenu_page(
			'barber-booking',
			__( 'Instructions', 'barber-booking' ),
			__( 'Instructions', 'barber-booking' ),
			\BarberBooking\Core\Capabilities::CAP_MANAGE_APPOINTMENTS,
			'barber-booking-instructions',
			array( $this, 'render_instructions' )
		);
	}

	/**
	 * Render instructions page.
	 */
	public function render_instructions(): void {
		require_once \BarberBooking\PLUGIN_PATH . 'templates/admin/instructions.php';
	}

	/**
	 * Render dashboard.
	 */
	public function render_dashboard(): void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Welcome to Barber Booking.', 'barber-booking' ); ?></p>
			<div class="bb-dashboard-widgets">
				<div class="bb-dashboard-widget">
					<h3><?php esc_html_e( 'Today', 'barber-booking' ); ?></h3>
					<p class="bb-big-number">
						<?php
						echo esc_html(
							count(
								\BarberBooking\Data\Appointment::get_for_range(
									gmdate( 'Y-m-d' ),
									gmdate( 'Y-m-d' ),
									null,
									null,
									'confirmed'
								)
							)
						);
						?>
					</p>
					<p><?php esc_html_e( 'confirmed appointments', 'barber-booking' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}
}
