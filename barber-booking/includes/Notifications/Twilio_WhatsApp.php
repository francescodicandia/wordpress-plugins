<?php
/**
 * Twilio WhatsApp sender.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Notifications;

use BarberBooking\Data\Appointment;
use BarberBooking\Data\Customer;
use BarberBooking\Data\Notification;
use BarberBooking\Data\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Twilio WhatsApp sender.
 */
class Twilio_WhatsApp {

	/**
	 * Send WhatsApp message for an appointment.
	 *
	 * @param int      $appointment_id Appointment ID.
	 * @param string   $type Type: confirmation or reminder.
	 * @param int|null $notification_id Optional existing notification ID.
	 * @return bool
	 */
	public function send( int $appointment_id, string $type, ?int $notification_id = null ): bool {
		$settings = get_option( \BarberBooking\PLUGIN_SETTINGS, array() );

		if ( empty( $settings['twilio_account_sid'] ) || empty( $settings['twilio_auth_token'] ) ) {
			return false;
		}

		$appointment = Appointment::get( $appointment_id );
		if ( ! $appointment ) {
			return false;
		}

		$customer = Customer::get( (int) $appointment->customer_id );
		$service  = Service::get( (int) $appointment->service_id );

		if ( ! $customer || ! $service ) {
			return false;
		}

		$content_sid = '';
		if ( 'confirmation' === $type ) {
			if ( empty( $settings['notification_confirmation_enabled'] ) ) {
				return false;
			}
			$content_sid = $settings['twilio_content_sid_confirmation'] ?? '';
		} elseif ( 'reminder' === $type ) {
			if ( empty( $settings['notification_reminder_enabled'] ) ) {
				return false;
			}
			$content_sid = $settings['twilio_content_sid_reminder'] ?? '';
		}

		if ( empty( $content_sid ) ) {
			return false;
		}

		$to = $this->normalize_phone( $customer->phone );
		if ( ! $to ) {
			return false;
		}

		$test_mode   = ! empty( $settings['twilio_test_mode'] );
		$test_number = $settings['twilio_test_number'] ?? '';

		if ( null === $notification_id ) {
			$notification_id = Notification::log( $appointment_id, 'whatsapp', $type, 'pending' );
		}

		if ( $test_mode ) {
			if ( empty( $test_number ) ) {
				Notification::mark_sent( $notification_id, 'SIMULATED_' . $type . '_' . $appointment_id );
				return true;
			}
			$to = $this->normalize_phone( $test_number );
		}

		$from = $this->normalize_from( $settings['twilio_from_number'] ?? '' );

		$variables = array(
			'1' => $customer->name,
			'2' => $service->name,
			'3' => $this->format_date( $appointment->appointment_date ),
			'4' => $this->format_time( $appointment->start_time ),
		);

		$result = $this->request(
			$settings['twilio_account_sid'],
			$settings['twilio_auth_token'],
			$to,
			$from,
			$content_sid,
			$variables
		);

		if ( is_wp_error( $result ) ) {
			Notification::mark_failed( $notification_id, $result->get_error_message() );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $result ), true );
		if ( isset( $body['sid'] ) ) {
			Notification::mark_sent( $notification_id, $body['sid'] );
			return true;
		}

		$error = $body['message'] ?? wp_remote_retrieve_response_message( $result );
		Notification::mark_failed( $notification_id, (string) $error );
		return false;
	}

	/**
	 * Make Twilio API request.
	 *
	 * @param string $account_sid Account SID.
	 * @param string $auth_token Auth token.
	 * @param string $to To number.
	 * @param string $from From number.
	 * @param string $content_sid Content SID.
	 * @param array  $variables Variables.
	 * @return array|\WP_Error
	 */
	private function request( string $account_sid, string $auth_token, string $to, string $from, string $content_sid, array $variables ) {
		$url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";

		$body = array(
			'To'               => $to,
			'From'             => $from,
			'ContentSid'       => $content_sid,
			'ContentVariables' => wp_json_encode( $variables ),
		);

		return wp_remote_post(
			$url,
			array(
				'body'    => $body,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $account_sid . ':' . $auth_token ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
				),
				'timeout' => 30,
			)
		);
	}

	/**
	 * Normalize phone number to WhatsApp format.
	 *
	 * @param string $phone Phone number.
	 * @return string
	 */
	private function normalize_phone( string $phone ): string {
		$phone = preg_replace( '/[^0-9+]/', '', $phone );
		if ( empty( $phone ) ) {
			return '';
		}
		if ( strpos( $phone, '+' ) !== 0 ) {
			$phone = '+' . $phone;
		}
		return 'whatsapp:' . $phone;
	}

	/**
	 * Normalize from number.
	 *
	 * @param string $from From number.
	 * @return string
	 */
	private function normalize_from( string $from ): string {
		$from = preg_replace( '/[^0-9+]/', '', $from );
		if ( empty( $from ) ) {
			return '';
		}
		if ( strpos( $from, '+' ) !== 0 ) {
			$from = '+' . $from;
		}
		return 'whatsapp:' . $from;
	}

	/**
	 * Format date.
	 *
	 * @param string $date Date.
	 * @return string
	 */
	private function format_date( string $date ): string {
		$timestamp = strtotime( $date );
		return $timestamp ? date_i18n( get_option( 'date_format' ), $timestamp ) : $date;
	}

	/**
	 * Format time.
	 *
	 * @param string $time Time.
	 * @return string
	 */
	private function format_time( string $time ): string {
		$timestamp = strtotime( $time );
		return $timestamp ? date_i18n( get_option( 'time_format' ), $timestamp ) : $time;
	}
}
