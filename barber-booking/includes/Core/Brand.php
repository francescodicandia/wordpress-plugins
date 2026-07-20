<?php
/**
 * Brand helper.
 *
 * Centralizes access to brand and white-label settings.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Brand helper class.
 */
class Brand {

	/**
	 * Get all brand settings with defaults.
	 *
	 * @return array
	 */
	public static function get_settings(): array {
		$settings = get_option( \BarberBooking\PLUGIN_SETTINGS, array() );
		$defaults = self::get_defaults();

		return wp_parse_args( $settings, $defaults );
	}

	/**
	 * Get default brand settings.
	 *
	 * @return array
	 */
	public static function get_defaults(): array {
		return apply_filters(
			'barber_booking_default_brand_settings',
			array(
				'brand_name'      => get_bloginfo( 'name' ),
				'brand_logo'      => '',
				'primary_color'   => '#1a1a1a',
				'secondary_color' => '#c9a227',
				'custom_css'      => '',
				'privacy_page'    => '',
			)
		);
	}

	/**
	 * Get a brand setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default_value Default value.
	 * @return mixed
	 */
	public static function get( string $key, $default_value = '' ) {
		$settings = self::get_settings();
		return $settings[ $key ] ?? $default_value;
	}

	/**
	 * Get brand name.
	 *
	 * @return string
	 */
	public static function get_name(): string {
		return (string) self::get( 'brand_name', get_bloginfo( 'name' ) );
	}

	/**
	 * Get brand logo URL.
	 *
	 * @return string
	 */
	public static function get_logo(): string {
		return (string) self::get( 'brand_logo', '' );
	}

	/**
	 * Get primary color.
	 *
	 * @return string
	 */
	public static function get_primary_color(): string {
		$color = sanitize_hex_color( self::get( 'primary_color', '#1a1a1a' ) );
		return $color ? $color : '#1a1a1a';
	}

	/**
	 * Get secondary color.
	 *
	 * @return string
	 */
	public static function get_secondary_color(): string {
		$color = sanitize_hex_color( self::get( 'secondary_color', '#c9a227' ) );
		return $color ? $color : '#c9a227';
	}

	/**
	 * Get custom CSS.
	 *
	 * @return string
	 */
	public static function get_custom_css(): string {
		return (string) self::get( 'custom_css', '' );
	}

	/**
	 * Get privacy page URL.
	 *
	 * @return string
	 */
	public static function get_privacy_page(): string {
		$page_id = (int) self::get( 'privacy_page', 0 );
		return $page_id ? (string) get_permalink( $page_id ) : '';
	}
}
