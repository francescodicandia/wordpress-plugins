<?php
/**
 * Assets.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Assets class.
 */
class Assets {

	/**
	 * Initialize admin assets.
	 */
	public function init_admin(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin' ) );
	}

	/**
	 * Initialize public assets.
	 */
	public function init_public(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public' ) );
	}

	/**
	 * Enqueue admin assets.
	 */
	public function enqueue_admin(): void {
		$screen = get_current_screen();

		if ( ! $screen || strpos( $screen->id, 'barber-booking' ) === false ) {
			return;
		}

		wp_enqueue_style(
			'barber-booking-admin',
			\BarberBooking\PLUGIN_URL . 'assets/css/admin.css',
			array(),
			\BarberBooking\PLUGIN_VERSION
		);

		wp_enqueue_script(
			'barber-booking-admin',
			\BarberBooking\PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			\BarberBooking\PLUGIN_VERSION,
			true
		);
	}

	/**
	 * Enqueue public assets.
	 */
	public function enqueue_public(): void {
		wp_enqueue_style(
			'barber-booking-public',
			\BarberBooking\PLUGIN_URL . 'assets/css/public.css',
			array(),
			\BarberBooking\PLUGIN_VERSION
		);

		$brand_css = ':root { --bb-primary: ' . Brand::get_primary_color() . '; --bb-secondary: ' . Brand::get_secondary_color() . '; }';
		$custom_css = Brand::get_custom_css();
		if ( $custom_css ) {
			$brand_css .= wp_strip_all_tags( $custom_css );
		}
		wp_add_inline_style( 'barber-booking-public', $brand_css );
	}
}
