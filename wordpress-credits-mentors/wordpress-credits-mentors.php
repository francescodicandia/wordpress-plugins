<?php
/**
 * Plugin Name: WordPress Credits Mentors
 * Description: Display mentors from Airtable on the WordPress Credits handbook page. Use shortcode [wpcredits_mentors].
 * Version:     1.0.0
 * Author:      WordPress Credits Team
 * Text Domain: wpcredits-mentors
 *
 * @package WordPressCredits
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WPCM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPCM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCM_VERSION', '1.0.0' );

if ( ! defined( 'WPCM_AIRTABLE_BASE_ID' ) ) {
    define( 'WPCM_AIRTABLE_BASE_ID', 'appXXXXXXXXXXXXXX' );
}
if ( ! defined( 'WPCM_AIRTABLE_TABLE_ID' ) ) {
    define( 'WPCM_AIRTABLE_TABLE_ID', 'tblXXXXXXXXXXXXXX' );
}
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
        'wpcredits-mentors',
        WPCM_PLUGIN_URL . 'assets/css/mentors.css',
        array(),
        WPCM_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'wpcm_enqueue_styles' );
