<?php
/**
 * Booking form template.
 *
 * @package BarberBooking
 */

defined( 'ABSPATH' ) || exit;

$settings     = \BarberBooking\Core\Brand::get_settings();
$privacy_page = \BarberBooking\Core\Brand::get_privacy_page();
?>
<div class="barber-booking-form" id="barber-booking-form">
	<div class="bb-form-header">
		<?php if ( ! empty( $settings['brand_logo'] ) ) : ?>
			<img src="<?php echo esc_url( $settings['brand_logo'] ); ?>" alt="<?php echo esc_attr( $settings['brand_name'] ?? '' ); ?>" class="bb-logo">
		<?php endif; ?>
		<h2 class="bb-form-title"><?php echo esc_html( $settings['brand_name'] ?? get_bloginfo( 'name' ) ); ?></h2>
	</div>

	<div class="bb-stepper">
		<div class="bb-step active" data-step="1"><?php esc_html_e( 'Service', 'barber-booking' ); ?></div>
		<div class="bb-step" data-step="2"><?php esc_html_e( 'Barber', 'barber-booking' ); ?></div>
		<div class="bb-step" data-step="3"><?php esc_html_e( 'Date', 'barber-booking' ); ?></div>
		<div class="bb-step" data-step="4"><?php esc_html_e( 'Details', 'barber-booking' ); ?></div>
		<div class="bb-step" data-step="5"><?php esc_html_e( 'Confirm', 'barber-booking' ); ?></div>
	</div>

	<form class="bb-form" id="bb-booking-form" novalidate>
		<!-- Step 1: Service -->
		<div class="bb-step-content active" data-step="1">
			<h3><?php esc_html_e( 'Choose a service', 'barber-booking' ); ?></h3>
			<div class="bb-loading" id="bb-services-loading"><?php esc_html_e( 'Loading...', 'barber-booking' ); ?></div>
			<div class="bb-options-grid" id="bb-services-list"></div>
			<input type="hidden" name="service_id" id="bb-service-id" required>
			<div class="bb-actions">
				<button type="button" class="bb-btn bb-btn-next" data-next="2" disabled><?php esc_html_e( 'Next', 'barber-booking' ); ?></button>
			</div>
		</div>

		<!-- Step 2: Barber -->
		<div class="bb-step-content" data-step="2">
			<h3><?php esc_html_e( 'Choose a barber', 'barber-booking' ); ?></h3>
			<div class="bb-options-grid" id="bb-barbers-list"></div>
			<input type="hidden" name="barber_id" id="bb-barber-id">
			<div class="bb-actions">
				<button type="button" class="bb-btn bb-btn-prev" data-prev="1"><?php esc_html_e( 'Back', 'barber-booking' ); ?></button>
				<button type="button" class="bb-btn bb-btn-skip" id="bb-skip-barber"><?php esc_html_e( 'No preference', 'barber-booking' ); ?></button>
				<button type="button" class="bb-btn bb-btn-next" data-next="3" disabled><?php esc_html_e( 'Next', 'barber-booking' ); ?></button>
			</div>
		</div>

		<!-- Step 3: Date & Slot -->
		<div class="bb-step-content" data-step="3">
			<h3><?php esc_html_e( 'Choose date and time', 'barber-booking' ); ?></h3>
			<div class="bb-field">
				<label for="bb-date"><?php esc_html_e( 'Date', 'barber-booking' ); ?></label>
				<input type="date" name="date" id="bb-date" min="<?php echo esc_attr( gmdate( 'Y-m-d' ) ); ?>" required>
			</div>
			<div class="bb-loading" id="bb-slots-loading" style="display:none;"><?php esc_html_e( 'Loading...', 'barber-booking' ); ?></div>
			<div class="bb-slots-grid" id="bb-slots-list"></div>
			<input type="hidden" name="time" id="bb-time" required>
			<input type="hidden" name="station_id" id="bb-station-id" required>
			<div class="bb-actions">
				<button type="button" class="bb-btn bb-btn-prev" data-prev="2"><?php esc_html_e( 'Back', 'barber-booking' ); ?></button>
				<button type="button" class="bb-btn bb-btn-next" data-next="4" disabled><?php esc_html_e( 'Next', 'barber-booking' ); ?></button>
			</div>
		</div>

		<!-- Step 4: Customer details -->
		<div class="bb-step-content" data-step="4">
			<h3><?php esc_html_e( 'Your details', 'barber-booking' ); ?></h3>
			<div class="bb-field">
				<label for="bb-name"><?php esc_html_e( 'Name', 'barber-booking' ); ?> *</label>
				<input type="text" name="name" id="bb-name" required>
			</div>
			<div class="bb-field">
				<label for="bb-phone"><?php esc_html_e( 'Phone', 'barber-booking' ); ?> *</label>
				<input type="tel" name="phone" id="bb-phone" placeholder="+39..." required>
			</div>
			<div class="bb-field">
				<label for="bb-email"><?php esc_html_e( 'Email (optional)', 'barber-booking' ); ?></label>
				<input type="email" name="email" id="bb-email">
			</div>
			<div class="bb-field">
				<label for="bb-notes"><?php esc_html_e( 'Notes (optional)', 'barber-booking' ); ?></label>
				<textarea name="notes" id="bb-notes" rows="3"></textarea>
			</div>
			<div class="bb-field bb-field-checkbox">
				<input type="checkbox" name="gdpr_consent" id="bb-gdpr" required>
				<label for="bb-gdpr">
					<?php esc_html_e( 'I accept the privacy policy', 'barber-booking' ); ?>
					<?php if ( $privacy_page ) : ?>
						<a href="<?php echo esc_url( $privacy_page ); ?>" target="_blank"><?php esc_html_e( 'Read here', 'barber-booking' ); ?></a>
					<?php endif; ?>
				</label>
			</div>
			<div class="bb-actions">
				<button type="button" class="bb-btn bb-btn-prev" data-prev="3"><?php esc_html_e( 'Back', 'barber-booking' ); ?></button>
				<button type="button" class="bb-btn bb-btn-next" data-next="5"><?php esc_html_e( 'Review', 'barber-booking' ); ?></button>
			</div>
		</div>

		<!-- Step 5: Review -->
		<div class="bb-step-content" data-step="5">
			<h3><?php esc_html_e( 'Review your booking', 'barber-booking' ); ?></h3>
			<div class="bb-review" id="bb-review"></div>
			<div class="bb-actions">
				<button type="button" class="bb-btn bb-btn-prev" data-prev="4"><?php esc_html_e( 'Back', 'barber-booking' ); ?></button>
				<button type="submit" class="bb-btn bb-btn-primary"><?php esc_html_e( 'Confirm Booking', 'barber-booking' ); ?></button>
			</div>
		</div>
	</form>

	<div class="bb-message" id="bb-message" style="display:none;"></div>
</div>
