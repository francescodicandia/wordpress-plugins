<?php
/**
 * Frontend.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend class.
 */
class Frontend {

	/**
	 * Initialize.
	 */
	public function init(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'barber-booking-form', array( $this, 'render_form' ) );
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register Gutenberg blocks.
	 */
	public function register_blocks(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			\BarberBooking\PLUGIN_PATH . 'blocks/booking-form',
			array(
				'render_callback' => array( $this, 'render_booking_form_block' ),
			)
		);
	}

	/**
	 * Render booking form block.
	 *
	 * @return string
	 */
	public function render_booking_form_block(): string {
		$this->enqueue_assets();
		return $this->render_form();
	}

	/**
	 * Enqueue assets.
	 */
	public function enqueue_assets(): void {
		wp_enqueue_style( 'barber-booking-public' );

		wp_enqueue_script(
			'barber-booking-public',
			\BarberBooking\PLUGIN_URL . 'assets/js/public.js',
			array(),
			\BarberBooking\PLUGIN_VERSION,
			true
		);

		$brand = \BarberBooking\Core\Brand::get_settings();

		wp_localize_script(
			'barber-booking-public',
			'BarberBooking',
			array(
				'restUrl' => rest_url( 'barber-booking/v1/' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'brand'   => array(
					'name'           => $brand['brand_name'] ?? get_bloginfo( 'name' ),
					'logo'           => $brand['brand_logo'] ?? '',
					'primaryColor'   => $brand['primary_color'] ?? '#1a1a1a',
					'secondaryColor' => $brand['secondary_color'] ?? '#c9a227',
					'customCss'      => $brand['custom_css'] ?? '',
					'privacyPage'    => $brand['privacy_page'] ?? '',
				),
				'i18n'    => array(
					'loading'       => __( 'Loading...', 'barber-booking' ),
					'next'          => __( 'Next', 'barber-booking' ),
					'prev'          => __( 'Back', 'barber-booking' ),
					'confirm'       => __( 'Confirm Booking', 'barber-booking' ),
					'noSlots'       => __( 'No available slots for this day.', 'barber-booking' ),
					'selectService' => __( 'Select a service', 'barber-booking' ),
					'selectBarber'  => __( 'Select a barber', 'barber-booking' ),
					'selectDate'    => __( 'Select a date', 'barber-booking' ),
					'selectSlot'    => __( 'Select a time', 'barber-booking' ),
					'yourName'      => __( 'Your name', 'barber-booking' ),
					'yourPhone'     => __( 'Phone number', 'barber-booking' ),
					'yourEmail'     => __( 'Email (optional)', 'barber-booking' ),
					'notes'         => __( 'Notes (optional)', 'barber-booking' ),
					'gdpr'          => __( 'I accept the privacy policy', 'barber-booking' ),
					'privacyLink'   => __( 'Read privacy policy', 'barber-booking' ),
					'confirmed'     => __( 'Booking confirmed!', 'barber-booking' ),
					'error'         => __( 'An error occurred. Please try again.', 'barber-booking' ),
				),
			)
		);
	}

	/**
	 * Render form.
	 *
	 * @return string
	 */
	public function render_form(): string {
		ob_start();
		include \BarberBooking\PLUGIN_PATH . 'templates/frontend/booking-form.php';
		return ob_get_clean();
	}
}
