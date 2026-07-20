<?php
/**
 * Instructions page template.
 *
 * @package BarberBooking
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap bb-instructions">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'Introduzione', 'barber-booking' ); ?></h2>
		<p>
			<?php esc_html_e( 'Barber Booking è un plugin per gestire le prenotazioni online di un negozio di barbiere. I clienti possono prenotare un appuntamento scegliendo servizio, barbiere, data e orario senza registrarsi.', 'barber-booking' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Dal backend puoi configurare servizi, postazioni, barbieri, orari di apertura, ferie e le notifiche WhatsApp.', 'barber-booking' ); ?>
		</p>
	</div>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'Primo avvio', 'barber-booking' ); ?></h2>
		<p>
			<?php esc_html_e( 'Dopo l\'attivazione del plugin vengono creati automaticamente tre ruoli utente:', 'barber-booking' ); ?>
		</p>
		<ul>
			<li>
				<strong><?php esc_html_e( 'Barber Superadmin', 'barber-booking' ); ?></strong>:
				<?php esc_html_e( 'può configurare brand, Twilio e tutte le impostazioni del plugin.', 'barber-booking' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Barber Admin', 'barber-booking' ); ?></strong>:
				<?php esc_html_e( 'può gestire servizi, postazioni, barbieri, orari, ferie e tutte le prenotazioni.', 'barber-booking' ); ?>
			</li>
			<li>
				<strong><?php esc_html_e( 'Barber', 'barber-booking' ); ?></strong>:
				<?php esc_html_e( 'può visualizzare solo le proprie prenotazioni dal pannello dedicato.', 'barber-booking' ); ?>
			</li>
		</ul>
		<p>
			<?php esc_html_e( 'Assegna i ruoli da Utenti → Aggiungi nuovo / Modifica. Il cliente non ha bisogno di un ruolo: prenota direttamente dal frontend.', 'barber-booking' ); ?>
		</p>
	</div>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'Configurazione brand', 'barber-booking' ); ?></h2>
		<p>
			<?php esc_html_e( 'Vai in Barber Booking → Impostazioni → Brand per impostare il nome, i colori, il logo e il CSS personalizzato. Questi valori vengono applicati automaticamente al form di prenotazione.', 'barber-booking' ); ?>
		</p>
	</div>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'Configurazione WhatsApp (Twilio)', 'barber-booking' ); ?></h2>
		<p>
			<?php esc_html_e( 'La sezione Twilio è visibile solo al superadmin. Per inviare notifiche WhatsApp:', 'barber-booking' ); ?>
		</p>
		<ol>
			<li><?php esc_html_e( 'Crea un account Twilio e abilita il numero WhatsApp mittente.', 'barber-booking' ); ?></li>
			<li><?php esc_html_e( 'Inserisci Account SID, Auth Token e il numero WhatsApp mittente.', 'barber-booking' ); ?></li>
			<li><?php esc_html_e( 'Attiva la modalità test per inviare le notifiche solo al numero di test autorizzato.', 'barber-booking' ); ?></li>
			<li><?php esc_html_e( 'Attiva la conferma e/o il reminder e imposta le ore di anticipo.', 'barber-booking' ); ?></li>
		</ol>
	</div>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'Come usare il form di prenotazione', 'barber-booking' ); ?></h2>
		<p>
			<?php esc_html_e( 'Inserisci il form in una pagina in uno di questi modi:', 'barber-booking' ); ?>
		</p>
		<ul>
			<li>
				<?php esc_html_e( 'Shortcode:', 'barber-booking' ); ?>
				<code>[barber-booking-form]</code>
			</li>
			<li>
				<?php esc_html_e( 'Blocco Gutenberg:', 'barber-booking' ); ?>
				<?php esc_html_e( 'aggiungi il blocco "Barber Booking Form".', 'barber-booking' ); ?>
			</li>
		</ul>
	</div>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'Gestione servizi', 'barber-booking' ); ?></h2>
		<p>
			<?php esc_html_e( 'Barber Booking → Servizi permette di creare i servizi disponibili. Ogni servizio ha un nome, una descrizione, una durata in minuti, un prezzo e un colore per il calendario.', 'barber-booking' ); ?>
		</p>
	</div>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'Gestione postazioni', 'barber-booking' ); ?></h2>
		<p>
			<?php esc_html_e( 'Barber Booking → Postazioni permette di definire le postazioni del negozio. Ogni appuntamento viene associato a una postazione specifica per evitare sovrapposizioni.', 'barber-booking' ); ?>
		</p>
	</div>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'Gestione barbieri', 'barber-booking' ); ?></h2>
		<p>
			<?php esc_html_e( 'Barber Booking → Barbieri permette di creare i profili dei barbieri. Per ogni barbiere puoi indicare nome, email, telefono, colore del calendario, servizi erogati e postazioni utilizzate.', 'barber-booking' ); ?>
		</p>
	</div>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'Orari di apertura e ferie', 'barber-booking' ); ?></h2>
		<p>
			<?php esc_html_e( 'Barber Booking → Orari e Barber Booking → Ferie permettono di definire gli orari di apertura standard e i giorni di chiusura. La disponibilità del frontend viene calcolata in automatico in base a questi dati.', 'barber-booking' ); ?>
		</p>
	</div>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'Calendario e appuntamenti', 'barber-booking' ); ?></h2>
		<p>
			<?php esc_html_e( 'Barber Booking → Calendario mostra la vista settimanale degli appuntamenti. Barber Booking → Appuntamenti mostra l\'elenco completo con filtri per data, barbiere e stato. I barbieri possono accedere al proprio pannello da Barber Booking → Pannello barbiere.', 'barber-booking' ); ?>
		</p>
	</div>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'Notifiche', 'barber-booking' ); ?></h2>
		<p>
			<?php esc_html_e( 'Il plugin invia una notifica WhatsApp di conferma al cliente al momento della prenotazione e un reminder prima dell\'appuntamento in base alle impostazioni. Ogni tentativo viene registrato nel log notifiche.', 'barber-booking' ); ?>
		</p>
	</div>

	<div class="bb-instructions-section">
		<h2><?php esc_html_e( 'FAQ e troubleshooting', 'barber-booking' ); ?></h2>
		<h3><?php esc_html_e( 'Lo slot non appare disponibile', 'barber-booking' ); ?></h3>
		<p>
			<?php esc_html_e( 'Verifica che il barbiere sia associato al servizio, che la postazione sia attiva e che l\'orario di apertura copra l\'orario desiderato.', 'barber-booking' ); ?>
		</p>
		<h3><?php esc_html_e( 'Il form non si carica', 'barber-booking' ); ?></h3>
		<p>
			<?php esc_html_e( 'Controlla che non ci sia un conflitto JavaScript con il tema o con altri plugin. Prova a disattivare temporaneamente altri plugin o a usare il shortcode invece del blocco.', 'barber-booking' ); ?>
		</p>
		<h3><?php esc_html_e( 'Le notifiche WhatsApp non partono', 'barber-booking' ); ?></h3>
		<p>
			<?php esc_html_e( 'Verifica che Twilio sia configurato correttamente, che il numero di test sia impostato e che le notifiche siano attive in Barber Booking → Impostazioni → Notifiche.', 'barber-booking' ); ?>
		</p>
	</div>
</div>
