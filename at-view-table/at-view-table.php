<?php
/**
 * Plugin Name: AT View Table
 * Description: Display Airtable table data in a WordPress table view.
 * Version:     0.1.0
 * Author:      WordPress Credits Team
 * Text Domain: at-view-table
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package WordPressCredits
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPCM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPCM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCM_VERSION', '0.1.0' );

if ( ! defined( 'WPCM_CACHE_TTL' ) ) {
    define( 'WPCM_CACHE_TTL', HOUR_IN_SECONDS );
}

require_once WPCM_PLUGIN_DIR . 'includes/class-airtable-api.php';
require_once WPCM_PLUGIN_DIR . 'includes/class-mentor-display.php';
require_once WPCM_PLUGIN_DIR . 'includes/class-admin.php';

function wpcm_init() {
    new WPCM_Mentor_Display();

    if ( is_admin() ) {
        new WPCM_Admin();
    }
}
add_action( 'plugins_loaded', 'wpcm_init' );

function wpcm_enqueue_styles() {
    wp_enqueue_style(
        'at-view-table',
        WPCM_PLUGIN_URL . 'assets/css/mentors.css',
        array(),
        WPCM_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'wpcm_enqueue_styles' );
