<?php
/**
 * Settings API.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

namespace BarberBooking\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Settings class.
 */
class Settings {

	private const OPTION_GROUP = 'barber_booking_settings';
	private const OPTION_NAME  = 'barber_booking_settings';
	private const PAGE_SLUG    = 'barber-booking-settings';

	/**
	 * Initialize.
	 */
	public function init(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
	}

	/**
	 * Add settings page.
	 */
	public function add_settings_page(): void {
		add_submenu_page(
			'barber-booking',
			__( 'Settings', 'barber-booking' ),
			__( 'Settings', 'barber-booking' ),
			\BarberBooking\Core\Capabilities::CAP_MANAGE_SETTINGS,
			self::PAGE_SLUG,
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->get_defaults(),
			)
		);

		add_settings_section(
			'barber_booking_brand',
			__( 'Brand & White-label', 'barber-booking' ),
			array( $this, 'render_section_brand' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'barber_booking_twilio',
			__( 'Twilio WhatsApp', 'barber-booking' ),
			array( $this, 'render_section_twilio' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'barber_booking_notifications',
			__( 'Notifications', 'barber-booking' ),
			array( $this, 'render_section_notifications' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'barber_booking_payments',
			__( 'Payments', 'barber-booking' ),
			array( $this, 'render_section_payments' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'barber_booking_opening_hours',
			__( 'Opening Hours', 'barber-booking' ),
			array( $this, 'render_section_opening_hours' ),
			self::PAGE_SLUG
		);

		$this->add_brand_fields();
		$this->add_twilio_fields();
		$this->add_notification_fields();
		$this->add_payment_fields();
		$this->add_opening_hours_fields();
	}

	/**
	 * Add brand fields.
	 */
	private function add_brand_fields(): void {
		$fields = array(
			array(
				'id'    => 'brand_name',
				'label' => __( 'Brand Name', 'barber-booking' ),
				'type'  => 'text',
			),
			array(
				'id'    => 'brand_logo',
				'label' => __( 'Logo URL', 'barber-booking' ),
				'type'  => 'url',
			),
			array(
				'id'    => 'primary_color',
				'label' => __( 'Primary Color', 'barber-booking' ),
				'type'  => 'color',
			),
			array(
				'id'    => 'secondary_color',
				'label' => __( 'Secondary Color', 'barber-booking' ),
				'type'  => 'color',
			),
			array(
				'id'    => 'custom_css',
				'label' => __( 'Custom CSS', 'barber-booking' ),
				'type'  => 'textarea',
			),
			array(
				'id'    => 'privacy_page',
				'label' => __( 'Privacy Policy Page', 'barber-booking' ),
				'type'  => 'page',
			),
		);

		foreach ( $fields as $field ) {
			add_settings_field(
				'barber_booking_' . $field['id'],
				$field['label'],
				array( $this, 'render_field' ),
				self::PAGE_SLUG,
				'barber_booking_brand',
				array(
					'label_for' => $field['id'],
					'type'      => $field['type'],
				)
			);
		}
	}

	/**
	 * Add Twilio fields.
	 */
	private function add_twilio_fields(): void {
		$fields = array(
			array(
				'id'    => 'twilio_account_sid',
				'label' => __( 'Account SID', 'barber-booking' ),
				'type'  => 'text',
			),
			array(
				'id'    => 'twilio_auth_token',
				'label' => __( 'Auth Token', 'barber-booking' ),
				'type'  => 'password',
			),
			array(
				'id'    => 'twilio_from_number',
				'label' => __( 'From WhatsApp Number', 'barber-booking' ),
				'type'  => 'text',
			),
			array(
				'id'    => 'twilio_test_mode',
				'label' => __( 'Test Mode', 'barber-booking' ),
				'type'  => 'checkbox',
			),
			array(
				'id'    => 'twilio_test_number',
				'label' => __( 'Authorized Test Number', 'barber-booking' ),
				'type'  => 'text',
			),
			array(
				'id'    => 'twilio_content_sid_confirmation',
				'label' => __( 'Content SID Confirmation', 'barber-booking' ),
				'type'  => 'text',
			),
			array(
				'id'    => 'twilio_content_sid_reminder',
				'label' => __( 'Content SID Reminder', 'barber-booking' ),
				'type'  => 'text',
			),
		);

		foreach ( $fields as $field ) {
			add_settings_field(
				'barber_booking_' . $field['id'],
				$field['label'],
				array( $this, 'render_field' ),
				self::PAGE_SLUG,
				'barber_booking_twilio',
				array(
					'label_for' => $field['id'],
					'type'      => $field['type'],
				)
			);
		}
	}

	/**
	 * Add notification fields.
	 */
	private function add_notification_fields(): void {
		$fields = array(
			array(
				'id'    => 'notification_confirmation_enabled',
				'label' => __( 'Enable WhatsApp Confirmation', 'barber-booking' ),
				'type'  => 'checkbox',
			),
			array(
				'id'    => 'notification_reminder_enabled',
				'label' => __( 'Enable WhatsApp Reminder', 'barber-booking' ),
				'type'  => 'checkbox',
			),
			array(
				'id'    => 'notification_reminder_hours',
				'label' => __( 'Reminder Hours (comma separated)', 'barber-booking' ),
				'type'  => 'text',
			),
			array(
				'id'    => 'email_backup_enabled',
				'label' => __( 'Enable Email Backup', 'barber-booking' ),
				'type'  => 'checkbox',
			),
		);

		foreach ( $fields as $field ) {
			add_settings_field(
				'barber_booking_' . $field['id'],
				$field['label'],
				array( $this, 'render_field' ),
				self::PAGE_SLUG,
				'barber_booking_notifications',
				array(
					'label_for' => $field['id'],
					'type'      => $field['type'],
				)
			);
		}
	}

	/**
	 * Add payment fields.
	 */
	private function add_payment_fields(): void {
		$fields = array(
			array(
				'id'    => 'payment_enabled',
				'label' => __( 'Enable Payments', 'barber-booking' ),
				'type'  => 'checkbox',
			),
			array(
				'id'      => 'payment_gateway',
				'label'   => __( 'Payment Gateway', 'barber-booking' ),
				'type'    => 'select',
				'options' => array(
					'stripe' => 'Stripe',
					'paypal' => 'PayPal',
				),
			),
			array(
				'id'      => 'payment_mode',
				'label'   => __( 'Payment Mode', 'barber-booking' ),
				'type'    => 'select',
				'options' => array(
					'full'    => __( 'Full', 'barber-booking' ),
					'deposit' => __( 'Deposit', 'barber-booking' ),
				),
			),
			array(
				'id'    => 'deposit_amount',
				'label' => __( 'Deposit Amount', 'barber-booking' ),
				'type'  => 'number',
			),
		);

		foreach ( $fields as $field ) {
			add_settings_field(
				'barber_booking_' . $field['id'],
				$field['label'],
				array( $this, 'render_field' ),
				self::PAGE_SLUG,
				'barber_booking_payments',
				array(
					'label_for' => $field['id'],
					'type'      => $field['type'],
					'options'   => $field['options'] ?? array(),
				)
			);
		}
	}

	/**
	 * Add opening hours fields.
	 */
	private function add_opening_hours_fields(): void {
		add_settings_field(
			'barber_booking_opening_hours',
			__( 'Opening Hours', 'barber-booking' ),
			array( $this, 'render_opening_hours_field' ),
			self::PAGE_SLUG,
			'barber_booking_opening_hours',
			array(
				'label_for' => 'opening_hours',
			)
		);

		add_settings_field(
			'barber_booking_slot_interval',
			__( 'Slot Interval (minutes)', 'barber-booking' ),
			array( $this, 'render_field' ),
			self::PAGE_SLUG,
			'barber_booking_opening_hours',
			array(
				'label_for' => 'slot_interval',
				'type'      => 'number',
			)
		);
	}

	/**
	 * Get default settings.
	 */
	private function get_defaults(): array {
		return array_merge(
			\BarberBooking\Core\Brand::get_defaults(),
			array(
				'twilio_account_sid'                => '',
				'twilio_auth_token'                 => '',
				'twilio_from_number'                => '',
				'twilio_test_mode'                  => true,
				'twilio_test_number'                => '',
				'twilio_content_sid_confirmation'   => '',
				'twilio_content_sid_reminder'       => '',
				'notification_confirmation_enabled' => true,
				'notification_reminder_enabled'     => true,
				'notification_reminder_hours'       => '24',
				'email_backup_enabled'              => false,
				'payment_enabled'                   => false,
				'payment_gateway'                   => 'stripe',
				'payment_mode'                      => 'full',
				'deposit_amount'                    => 0,
				'opening_hours'                     => $this->default_opening_hours(),
				'slot_interval'                     => 15,
			)
		);
	}

	/**
	 * Default opening hours.
	 */
	private function default_opening_hours(): array {
		$hours = array();
		for ( $i = 0; $i < 7; $i++ ) {
			if ( 0 === $i ) {
				$hours[ $i ] = array(); // Sunday closed.
			} else {
				$hours[ $i ] = array(
					array(
						'start' => '09:00',
						'end'   => '19:00',
					),
				);
			}
		}
		return $hours;
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Input.
	 * @return array
	 */
	public function sanitize_settings( array $input ): array {
		$defaults  = $this->get_defaults();
		$sanitized = array();

		$sanitized['brand_name']      = sanitize_text_field( $input['brand_name'] ?? $defaults['brand_name'] );
		$sanitized['brand_logo']      = esc_url_raw( $input['brand_logo'] ?? '' );
		$sanitized['primary_color']   = $this->sanitize_hex_color( $input['primary_color'] ?? $defaults['primary_color'] );
		$sanitized['secondary_color'] = $this->sanitize_hex_color( $input['secondary_color'] ?? $defaults['secondary_color'] );
		$sanitized['custom_css']      = wp_strip_all_tags( $input['custom_css'] ?? '' );
		$sanitized['privacy_page']    = absint( $input['privacy_page'] ?? 0 );

		$sanitized['twilio_account_sid']              = sanitize_text_field( $input['twilio_account_sid'] ?? '' );
		$sanitized['twilio_auth_token']               = sanitize_text_field( $input['twilio_auth_token'] ?? '' );
		$sanitized['twilio_from_number']              = sanitize_text_field( $input['twilio_from_number'] ?? '' );
		$sanitized['twilio_test_mode']                = ! empty( $input['twilio_test_mode'] );
		$sanitized['twilio_test_number']              = sanitize_text_field( $input['twilio_test_number'] ?? '' );
		$sanitized['twilio_content_sid_confirmation'] = sanitize_text_field( $input['twilio_content_sid_confirmation'] ?? '' );
		$sanitized['twilio_content_sid_reminder']     = sanitize_text_field( $input['twilio_content_sid_reminder'] ?? '' );

		$sanitized['notification_confirmation_enabled'] = ! empty( $input['notification_confirmation_enabled'] );
		$sanitized['notification_reminder_enabled']     = ! empty( $input['notification_reminder_enabled'] );
		$sanitized['notification_reminder_hours']       = $this->sanitize_reminder_hours( $input['notification_reminder_hours'] ?? '24' );
		$sanitized['email_backup_enabled']              = ! empty( $input['email_backup_enabled'] );

		$sanitized['payment_enabled'] = ! empty( $input['payment_enabled'] );
		$sanitized['payment_gateway'] = sanitize_text_field( $input['payment_gateway'] ?? 'stripe' );
		$sanitized['payment_mode']    = sanitize_text_field( $input['payment_mode'] ?? 'full' );
		$sanitized['deposit_amount']  = floatval( $input['deposit_amount'] ?? 0 );

		$sanitized['opening_hours'] = $this->sanitize_opening_hours( $input['opening_hours'] ?? array() );
		$sanitized['slot_interval'] = max( 5, min( 60, absint( $input['slot_interval'] ?? 15 ) ) );

		return $sanitized;
	}

	/**
	 * Sanitize opening hours.
	 *
	 * @param array $input Input.
	 * @return array
	 */
	private function sanitize_opening_hours( array $input ): array {
		$defaults = $this->default_opening_hours();
		$clean    = array();

		for ( $i = 0; $i < 7; $i++ ) {
			$day = $input[ $i ] ?? array();

			if ( empty( $day['open'] ) ) {
				$clean[ $i ] = array();
				continue;
			}

			$start = sanitize_text_field( $day['start'] ?? '' );
			$end   = sanitize_text_field( $day['end'] ?? '' );

			if ( ! $this->is_valid_time( $start ) || ! $this->is_valid_time( $end ) || $start >= $end ) {
				$clean[ $i ] = $defaults[ $i ];
				continue;
			}

			$clean[ $i ] = array(
				array(
					'start' => $start,
					'end'   => $end,
				),
			);
		}

		return $clean;
	}

	/**
	 * Validate time format.
	 *
	 * @param string $time Time.
	 * @return bool
	 */
	private function is_valid_time( string $time ): bool {
		return (bool) preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $time );
	}

	/**
	 * Sanitize hex color.
	 *
	 * @param string $color Color.
	 * @return string
	 */
	private function sanitize_hex_color( string $color ): string {
		$color = sanitize_hex_color( $color );
		return $color ? $color : '#000000';
	}

	/**
	 * Sanitize reminder hours.
	 *
	 * @param string $hours Hours.
	 * @return string
	 */
	private function sanitize_reminder_hours( string $hours ): string {
		$parts = array_filter( array_map( 'intval', explode( ',', $hours ) ) );
		$parts = array_filter(
			$parts,
			static function ( int $h ): bool {
				return $h > 0 && $h <= 168;
			}
		);
		return $parts ? implode( ',', $parts ) : '24';
	}

	/**
	 * Render a field.
	 *
	 * @param array $args Arguments.
	 */
	public function render_field( array $args ): void {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$id      = $args['label_for'];
		$type    = $args['type'];
		$value   = $options[ $id ] ?? '';
		if ( is_array( $value ) ) {
			$value = implode( ',', $value );
		}
		$name = self::OPTION_NAME . '[' . $id . ']';

		switch ( $type ) {
			case 'text':
			case 'url':
			case 'color':
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" />',
					esc_attr( $type ),
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
				break;

			case 'password':
				printf(
					'<input type="password" id="%1$s" name="%2$s" value="%3$s" class="regular-text" autocomplete="off" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%1$s" name="%2$s" value="%3$s" class="small-text" step="0.01" />',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( (string) $value )
				);
				break;

			case 'checkbox':
				printf(
					'<input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s />',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( (bool) $value, true, false )
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="6" class="large-text code">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( (string) $value )
				);
				break;

			case 'select':
				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $args['options'] as $key => $label ) {
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr( $key ),
						selected( $value, $key, false ),
						esc_html( $label )
					);
				}
				echo '</select>';
				break;

			case 'page':
				wp_dropdown_pages(
					array(
						'name'             => $name, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'id'               => $id, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'selected'         => (int) $value,
						'show_option_none' => esc_html__( '— Select —', 'barber-booking' ),
					)
				);
				break;
		}
	}

	/**
	 * Render brand section.
	 */
	public function render_section_brand(): void {
		echo '<p>' . esc_html__( 'Customize the appearance and white-label options.', 'barber-booking' ) . '</p>';
	}

	/**
	 * Render Twilio section.
	 */
	public function render_section_twilio(): void {
		echo '<p>' . esc_html__( 'Configure Twilio credentials. Only the superadmin can access these settings.', 'barber-booking' ) . '</p>';
	}

	/**
	 * Render notifications section.
	 */
	public function render_section_notifications(): void {
		echo '<p>' . esc_html__( 'Configure WhatsApp confirmation and reminder messages.', 'barber-booking' ) . '</p>';
	}

	/**
	 * Render payments section.
	 */
	public function render_section_payments(): void {
		echo '<p>' . esc_html__( 'Payments are disabled by default. Enable them only when a gateway is configured.', 'barber-booking' ) . '</p>';
	}

	/**
	 * Render opening hours section.
	 */
	public function render_section_opening_hours(): void {
		echo '<p>' . esc_html__( 'Set the default opening hours for the shop.', 'barber-booking' ) . '</p>';
	}

	/**
	 * Render opening hours field.
	 */
	public function render_opening_hours_field(): void {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$hours   = $options['opening_hours'] ?? $this->default_opening_hours();
		$days    = array(
			__( 'Sunday', 'barber-booking' ),
			__( 'Monday', 'barber-booking' ),
			__( 'Tuesday', 'barber-booking' ),
			__( 'Wednesday', 'barber-booking' ),
			__( 'Thursday', 'barber-booking' ),
			__( 'Friday', 'barber-booking' ),
			__( 'Saturday', 'barber-booking' ),
		);

		echo '<table class="form-table bb-opening-hours">';
		foreach ( $days as $index => $day ) {
			$day_hours = $hours[ $index ] ?? array();
			$is_open   = ! empty( $day_hours );
			$start     = $day_hours[0]['start'] ?? '09:00';
			$end       = $day_hours[0]['end'] ?? '19:00';
			$base      = self::OPTION_NAME . '[opening_hours][' . $index . ']';
			?>
			<tr>
				<th><?php echo esc_html( $day ); ?></th>
				<td>
					<label>
						<input type="checkbox" name="<?php echo esc_attr( $base . '[open]' ); ?>" value="1" <?php checked( $is_open ); ?> />
						<?php esc_html_e( 'Open', 'barber-booking' ); ?>
					</label>
					<input type="time" name="<?php echo esc_attr( $base . '[start]' ); ?>" value="<?php echo esc_attr( $start ); ?>" />
					<input type="time" name="<?php echo esc_attr( $base . '[end]' ); ?>" value="<?php echo esc_attr( $end ); ?>" />
				</td>
			</tr>
			<?php
		}
		echo '</table>';
	}

	/**
	 * Render settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( \BarberBooking\Core\Capabilities::CAP_MANAGE_SETTINGS ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php settings_errors( self::OPTION_GROUP ); ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button( __( 'Save Settings', 'barber-booking' ) );
				?>
			</form>
		</div>
		<?php
	}
}
