<?php
/**
 * Core plugin class.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Core plugin class.
 */
class Plugin {

	/**
	 * Run the plugin.
	 */
	public function run(): void {
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_api_hooks();
		$this->define_notification_hooks();
	}

	/**
	 * Set locale.
	 */
	private function set_locale(): void {
		$i18n = new I18n();
		$i18n->init();
	}

	/**
	 * Register admin hooks.
	 */
	private function define_admin_hooks(): void {
		if ( ! is_admin() ) {
			return;
		}

		$assets = new Assets();
		$assets->init_admin();

		$admin = new \BarberBooking\Admin\Admin();
		$admin->init();

		$settings = new \BarberBooking\Admin\Settings();
		$settings->init();
	}

	/**
	 * Register public hooks.
	 */
	private function define_public_hooks(): void {
		$assets = new Assets();
		$assets->init_public();

		$frontend = new \BarberBooking\Frontend\Frontend();
		$frontend->init();
	}

	/**
	 * Register API hooks.
	 */
	private function define_api_hooks(): void {
		$api = new \BarberBooking\API\Rest_API();
		$api->init();
	}

	/**
	 * Register notification hooks.
	 */
	private function define_notification_hooks(): void {
		$notifications = new \BarberBooking\Notifications\Notifications();
		$notifications->init();
	}
}
