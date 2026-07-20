<?php
/**
 * Plugin Name:       Barber Booking
 * Plugin URI:        https://example.com/barber-booking
 * Description:       Plugin per prenotazioni di barbieri con WhatsApp, postazioni e staff.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            OpenCode
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       barber-booking
 * Domain Path:       /languages
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking;

defined( 'ABSPATH' ) || exit;

const PLUGIN_VERSION    = '1.0.0';
const PLUGIN_DB_VERSION = '1.0.0';
const PLUGIN_FILE       = __FILE__;
const PLUGIN_PATH       = __DIR__ . '/';
define( __NAMESPACE__ . '\\PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( __NAMESPACE__ . '\\PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
const PLUGIN_SETTINGS          = 'barber_booking_settings';
const PLUGIN_DB_VERSION_OPTION = 'barber_booking_db_version';

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( strpos( $class_name, $prefix ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class_name, strlen( $prefix ) );
		$file           = PLUGIN_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Plugin activation.
 */
function activate_plugin(): void {
	Core\Activator::activate();
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\\activate_plugin' );

/**
 * Plugin deactivation.
 */
function deactivate_plugin(): void {
	Core\Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\deactivate_plugin' );

/**
 * Initialize plugin.
 */
function run_plugin(): void {
	$plugin = new Core\Plugin();
	$plugin->run();
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\\run_plugin' );
