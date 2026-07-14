<?php
/**
 * Plugin Name: AT View Table
 * Description: Display Airtable table data in a WordPress table view.
 * Version:     0.2.0
 * Author:      WordPress Credits Team
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Text Domain: at-view-table
 * Domain Path: /languages
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package ATViewTable
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'ATVT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ATVT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ATVT_VERSION', '0.2.0' );

if ( ! defined( 'ATVT_CACHE_TTL' ) ) {
    define( 'ATVT_CACHE_TTL', HOUR_IN_SECONDS );
}

/**
 * Get the cache TTL in seconds from the admin setting.
 *
 * Falls back to ATVT_CACHE_TTL constant or 1 hour if the option is not set.
 *
 * @return int Cache TTL in seconds.
 */
function atvt_get_cache_ttl() {
    $ttl_minutes = intval( get_option( 'atvt_cache_ttl', 0 ) );

    if ( $ttl_minutes > 0 ) {
        return $ttl_minutes * MINUTE_IN_SECONDS;
    }

    return ATVT_CACHE_TTL;
}

/**
 * Get the current cache salt for transient key generation.
 *
 * Bumping this value invalidates all cached Airtable data at once.
 *
 * @return int Cache salt value.
 */
function atvt_get_cache_salt() {
    $salt = get_option( 'atvt_cache_salt' );

    if ( false === $salt ) {
        $salt = 1;
        add_option( 'atvt_cache_salt', $salt, '', 'no' );
    }

    return (int) $salt;
}

require_once ATVT_PLUGIN_DIR . 'includes/class-airtable-api.php';
require_once ATVT_PLUGIN_DIR . 'includes/class-table-display.php';
require_once ATVT_PLUGIN_DIR . 'includes/class-admin.php';

function atvt_init() {
    load_plugin_textdomain( 'at-view-table', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

    new ATVT_Table_Display();

    if ( is_admin() ) {
        new ATVT_Admin();
    }
}
add_action( 'plugins_loaded', 'atvt_init' );

function atvt_enqueue_assets() {
    wp_enqueue_style(
        'at-view-table',
        ATVT_PLUGIN_URL . 'assets/css/at-view-table.css',
        array(),
        ATVT_VERSION
    );

    wp_enqueue_script(
        'at-view-table',
        ATVT_PLUGIN_URL . 'assets/js/at-view-table.js',
        array(),
        ATVT_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'atvt_enqueue_assets' );
