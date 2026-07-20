<?php
/**
 * Internationalization.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Internationalization class.
 */
class I18n {

	/**
	 * Initialize.
	 */
	public function init(): void {
		add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );
	}

	/**
	 * Load plugin textdomain.
	 */
	public function load_plugin_textdomain(): void {
		load_plugin_textdomain(
			'barber-booking',
			false,
			dirname( \BarberBooking\PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
