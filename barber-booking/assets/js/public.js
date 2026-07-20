(function () {
	'use strict';

	const form = document.getElementById('bb-booking-form');
	if (!form) {
		return;
	}

	const state = {
		step: 1,
		service: null,
		barber: null,
		date: '',
		time: '',
		stationId: '',
		slots: [],
		services: [],
		barbers: [],
	};

	const els = {
		servicesList: document.getElementById('bb-services-list'),
		servicesLoading: document.getElementById('bb-services-loading'),
		barbersList: document.getElementById('bb-barbers-list'),
		slotsList: document.getElementById('bb-slots-list'),
		slotsLoading: document.getElementById('bb-slots-loading'),
		dateInput: document.getElementById('bb-date'),
		review: document.getElementById('bb-review'),
		message: document.getElementById('bb-message'),
		inputs: {
			serviceId: document.getElementById('bb-service-id'),
			barberId: document.getElementById('bb-barber-id'),
			time: document.getElementById('bb-time'),
			stationId: document.getElementById('bb-station-id'),
		},
	};

	init();

	function init() {
		loadServices();
		bindNavigation();
		bindForm();
		updateBrandColors();
	}

	function updateBrandColors() {
		if (!BarberBooking.brand) {
			return;
		}
		const root = document.documentElement;
		root.style.setProperty('--bb-primary', BarberBooking.brand.primaryColor);
		root.style.setProperty('--bb-secondary', BarberBooking.brand.secondaryColor);
	}

	async function api(url, options) {
		options = options || {};
		const response = await fetch(BarberBooking.restUrl + url, {
			...options,
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': BarberBooking.nonce,
				...(options.headers || {}),
			},
		});
		if (!response.ok) {
			const error = await response.json().catch(function () {
				return {};
			});
			throw new Error(error.message || BarberBooking.i18n.error);
		}
		return response.json();
	}

	async function loadServices() {
		try {
			state.services = await api('services');
			renderServices();
		} catch (e) {
			els.servicesList.innerHTML = '<p class="bb-error">' + escapeHtml(e.message) + '</p>';
		} finally {
			els.servicesLoading.style.display = 'none';
		}
	}

	function renderServices() {
		els.servicesList.innerHTML = state.services.map(function (s) {
			return '<button type="button" class="bb-option-card" data-service-id="' + s.id + '" data-service-name="' + escapeHtml(s.name) + '" data-duration="' + s.duration + '" data-price="' + s.price + '">' +
				'<span class="bb-option-name">' + escapeHtml(s.name) + '</span>' +
				'<span class="bb-option-meta">' + s.duration + ' min - ' + formatPrice(s.price) + '</span>' +
				'</button>';
		}).join('');
		els.servicesList.querySelectorAll('.bb-option-card').forEach(function (btn) {
			btn.addEventListener('click', function () {
				selectService(btn);
			});
		});
	}

	function selectService(btn) {
		const id = parseInt(btn.dataset.serviceId, 10);
		state.service = state.services.find(function (s) {
			return s.id === id;
		});
		els.inputs.serviceId.value = id;
		els.servicesList.querySelectorAll('.bb-option-card').forEach(function (b) {
			b.classList.remove('selected');
		});
		btn.classList.add('selected');
		enableNext(1, true);
		loadBarbers(id);
	}

	async function loadBarbers(serviceId) {
		try {
			state.barbers = await api('barbers?service_id=' + serviceId);
			renderBarbers();
		} catch (e) {
			els.barbersList.innerHTML = '<p class="bb-error">' + escapeHtml(e.message) + '</p>';
		}
	}

	function renderBarbers() {
		if (state.barbers.length === 0) {
			els.barbersList.innerHTML = '<p>' + BarberBooking.i18n.noSlots + '</p>';
			return;
		}
		els.barbersList.innerHTML = state.barbers.map(function (b) {
			return '<button type="button" class="bb-option-card" data-barber-id="' + b.id + '" data-barber-name="' + escapeHtml(b.name) + '">' +
				'<span class="bb-option-name">' + escapeHtml(b.name) + '</span>' +
				'</button>';
		}).join('');
		els.barbersList.querySelectorAll('.bb-option-card').forEach(function (btn) {
			btn.addEventListener('click', function () {
				selectBarber(btn);
			});
		});
	}

	function selectBarber(btn) {
		const id = parseInt(btn.dataset.barberId, 10);
		state.barber = state.barbers.find(function (b) {
			return b.id === id;
		});
		els.inputs.barberId.value = id;
		els.barbersList.querySelectorAll('.bb-option-card').forEach(function (b) {
			b.classList.remove('selected');
		});
		btn.classList.add('selected');
		enableNext(2, true);
	}

	function bindNavigation() {
		form.querySelectorAll('.bb-btn-next').forEach(function (btn) {
			btn.addEventListener('click', function () {
				goToStep(parseInt(btn.dataset.next, 10));
			});
		});
		form.querySelectorAll('.bb-btn-prev').forEach(function (btn) {
			btn.addEventListener('click', function () {
				goToStep(parseInt(btn.dataset.prev, 10));
			});
		});
		document.getElementById('bb-skip-barber').addEventListener('click', function () {
			state.barber = null;
			els.inputs.barberId.value = '';
			goToStep(3);
		});
		els.dateInput.addEventListener('change', function (e) {
			state.date = e.target.value;
			loadSlots();
		});
	}

	function goToStep(step) {
		state.step = step;
		form.querySelectorAll('.bb-step-content').forEach(function (el) {
			el.classList.remove('active');
		});
		form.querySelector('.bb-step-content[data-step="' + step + '"]').classList.add('active');
		form.querySelectorAll('.bb-step').forEach(function (el) {
			el.classList.toggle('active', parseInt(el.dataset.step, 10) <= step);
		});
		if (step === 5) {
			renderReview();
		}
	}

	async function loadSlots() {
		if (!state.date || !state.service) {
			return;
		}
		els.slotsLoading.style.display = 'block';
		els.slotsList.innerHTML = '';
		try {
			const barberParam = state.barber ? '&barber_id=' + state.barber.id : '';
			state.slots = await api('availability?service_id=' + state.service.id + '&date=' + state.date + barberParam);
			renderSlots();
		} catch (e) {
			els.slotsList.innerHTML = '<p class="bb-error">' + escapeHtml(e.message) + '</p>';
		} finally {
			els.slotsLoading.style.display = 'none';
		}
	}

	function renderSlots() {
		if (state.slots.length === 0) {
			els.slotsList.innerHTML = '<p>' + BarberBooking.i18n.noSlots + '</p>';
			enableNext(3, false);
			return;
		}
		els.slotsList.innerHTML = state.slots.map(function (slot) {
			return '<button type="button" class="bb-slot-card" data-time="' + slot.time + '" data-station-id="' + slot.station_id + '" data-barber-id="' + slot.barber_id + '">' +
				'<span class="bb-slot-time">' + escapeHtml(slot.time) + '</span>' +
				'<span class="bb-slot-barber">' + escapeHtml(slot.barber_name) + '</span>' +
				'</button>';
		}).join('');
		els.slotsList.querySelectorAll('.bb-slot-card').forEach(function (btn) {
			btn.addEventListener('click', function () {
				selectSlot(btn);
			});
		});
		enableNext(3, false);
	}

	function selectSlot(btn) {
		state.time = btn.dataset.time;
		state.stationId = btn.dataset.stationId;
		els.inputs.time.value = state.time;
		els.inputs.stationId.value = state.stationId;
		if (!state.barber) {
			state.barber = state.barbers.find(function (b) {
				return b.id === parseInt(btn.dataset.barberId, 10);
			}) || null;
			if (state.barber) {
				els.inputs.barberId.value = state.barber.id;
			}
		}
		els.slotsList.querySelectorAll('.bb-slot-card').forEach(function (b) {
			b.classList.remove('selected');
		});
		btn.classList.add('selected');
		enableNext(3, true);
	}

	function renderReview() {
		const serviceName = state.service ? state.service.name : '';
		const barberName = state.barber ? state.barber.name : BarberBooking.i18n.noSlots;
		const date = state.date;
		const time = state.time;
		const name = document.getElementById('bb-name').value;
		const phone = document.getElementById('bb-phone').value;
		const email = document.getElementById('bb-email').value;
		const notes = document.getElementById('bb-notes').value;

		els.review.innerHTML =
			'<p><strong>' + BarberBooking.i18n.selectService + ':</strong> ' + escapeHtml(serviceName) + '</p>' +
			'<p><strong>' + BarberBooking.i18n.selectBarber + ':</strong> ' + escapeHtml(barberName) + '</p>' +
			'<p><strong>' + BarberBooking.i18n.selectDate + ':</strong> ' + escapeHtml(date) + ' ' + escapeHtml(time) + '</p>' +
			'<p><strong>' + BarberBooking.i18n.yourName + ':</strong> ' + escapeHtml(name) + '</p>' +
			'<p><strong>' + BarberBooking.i18n.yourPhone + ':</strong> ' + escapeHtml(phone) + '</p>' +
			(email ? '<p><strong>' + BarberBooking.i18n.yourEmail + ':</strong> ' + escapeHtml(email) + '</p>' : '') +
			(notes ? '<p><strong>' + BarberBooking.i18n.notes + ':</strong> ' + escapeHtml(notes) + '</p>' : '');
	}

	function bindForm() {
		form.addEventListener('submit', async function (e) {
			e.preventDefault();
			const submitBtn = form.querySelector('button[type="submit"]');
			submitBtn.disabled = true;

			const payload = {
				service_id: parseInt(els.inputs.serviceId.value, 10),
				barber_id: parseInt(els.inputs.barberId.value, 10) || null,
				station_id: parseInt(els.inputs.stationId.value, 10),
				date: state.date,
				time: state.time,
				name: document.getElementById('bb-name').value,
				phone: document.getElementById('bb-phone').value,
				email: document.getElementById('bb-email').value,
				notes: document.getElementById('bb-notes').value,
				gdpr_consent: document.getElementById('bb-gdpr').checked,
			};

			try {
				const result = await api('bookings', {
					method: 'POST',
					body: JSON.stringify(payload),
				});
				showMessage(result.message || BarberBooking.i18n.confirmed, 'success');
				form.reset();
				goToStep(1);
			} catch (e) {
				showMessage(e.message, 'error');
			} finally {
				submitBtn.disabled = false;
			}
		});
	}

	function enableNext(step, enable) {
		const btn = form.querySelector('.bb-step-content[data-step="' + step + '"] .bb-btn-next');
		if (btn) {
			btn.disabled = !enable;
		}
	}

	function showMessage(text, type) {
		els.message.textContent = text;
		els.message.className = 'bb-message bb-message-' + type;
		els.message.style.display = 'block';
		setTimeout(function () {
			els.message.style.display = 'none';
		}, 8000);
	}

	function escapeHtml(text) {
		const div = document.createElement('div');
		div.textContent = text;
		return div.innerHTML;
	}

	function formatPrice(price) {
		return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'EUR' }).format(price);
	}
})();
