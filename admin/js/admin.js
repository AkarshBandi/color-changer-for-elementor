/**
 * Store Design — admin screen behaviour.
 *
 * One file, no jQuery. This replaced five scripts whose only shared state was a
 * global `WooceSettings` object.
 *
 * Everything here is enhancement. The form submits and saves with JavaScript
 * off: the `<select>` elements are the fields, the swatches beside them are
 * decoration, and the preview is a picture of what the server already decided.
 */
(function () {
	'use strict';

	if (typeof window.eccwData === 'undefined') {
		return;
	}

	var data = window.eccwData;
	var strings = data.i18n || {};
	var form = document.getElementById('eccw-form');

	if (!form) {
		return;
	}

	/* -- Colour lookup ---------------------------------------------------- */

	var hexByToken = {};

	(data.kitColors || []).forEach(function (color) {
		hexByToken[color.id] = color.hex;
	});

	function hexFor(value) {
		if (!value) {
			return '';
		}

		if (value.charAt(0) === '#') {
			return value;
		}

		return hexByToken[value] || '';
	}

	/* -- WCAG readable text ----------------------------------------------- */

	function luminance(hex) {
		var value = String(hex || '').replace('#', '');

		if (value.length === 3) {
			value = value.split('').map(function (c) {
				return c + c;
			}).join('');
		}

		var int = parseInt(value, 16);

		if (isNaN(int)) {
			return 1;
		}

		var channels = [(int >> 16) & 255, (int >> 8) & 255, int & 255].map(function (channel) {
			var c = channel / 255;

			return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
		});

		return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
	}

	function readableOn(hex) {
		return luminance(hex) > 0.179 ? '#111111' : '#ffffff';
	}

	/* -- Swatch beside each select ---------------------------------------- */

	function paintChip(select) {
		var chip = form.querySelector('[data-eccw-chip="' + select.id + '"]');

		if (!chip) {
			return;
		}

		var option = select.options[select.selectedIndex];
		var hex = (option && option.getAttribute('data-hex')) || hexFor(select.value);

		if (hex) {
			chip.style.backgroundColor = hex;
		}
	}

	/* -- Custom colour ----------------------------------------------------- */

	function customOption(select) {
		return select.querySelector('[data-eccw-custom-option]');
	}

	/** Show the picker only while "Custom colour" is the selection. */
	function syncPicker(select) {
		var picker = form.querySelector('[data-eccw-picker="' + select.id + '"]');
		var option = customOption(select);

		if (!picker || !option) {
			return;
		}

		var isCustom = select.selectedIndex === option.index;

		picker.hidden = !isCustom;

		if (!isCustom) {
			return;
		}

		// Entering custom mode from a palette colour: seed the picker with the
		// colour already showing, so the first thing the owner sees is where
		// they were rather than an arbitrary default.
		var seed = option.getAttribute('data-hex') || hexFor(previousValue[select.id]) || '#000000';

		if (/^#[0-9a-f]{6}$/i.test(seed)) {
			picker.value = seed.toLowerCase();
		}

		writeCustom(select, picker.value);
	}

	/**
	 * The picker never submits. It writes into the select's custom option, which
	 * keeps the select as the only field the server reads — and keeps the whole
	 * screen working with JavaScript off.
	 */
	function writeCustom(select, hex) {
		var option = customOption(select);

		if (!option || !/^#[0-9a-f]{6}$/i.test(hex)) {
			return;
		}

		hex = hex.toLowerCase();

		option.value = hex;
		option.setAttribute('data-hex', hex);
		option.textContent = (strings.customColour || 'Custom colour') + ' — ' + hex.toUpperCase();
		select.value = hex;

		paintChip(select);
		paintPreview(select);
	}

	/* -- Live preview ------------------------------------------------------ */

	// Which element's colour drives which part of the mock. The preview is only
	// honest if it follows the same slots the stylesheet is built from.
	var previewSources = {
		'wc-add-to-cart': 'button',
		'wc-product-price': 'price',
		'wc-sale-badge': 'badge',
		'wc-star-rating': 'stars',
		'wc-general-links': 'link'
	};

	function paintPreview(select) {
		var widgetKey = select.getAttribute('data-widget-key');
		var target = previewSources[widgetKey];

		if (!target || String(select.getAttribute('data-state')).slice(-7) !== '_normal') {
			return;
		}

		var node = form.querySelector('[data-eccw-paint="' + target + '"]');
		var hex = hexFor(select.value);

		if (!node || !hex) {
			return;
		}

		if (target === 'button' || target === 'badge') {
			node.style.backgroundColor = hex;

			if (autoContrastOn()) {
				node.style.color = readableOn(hex);
			}

			return;
		}

		node.style.color = hex;
	}

	function autoContrastOn() {
		var toggle = form.querySelector('[data-testid="eccw-toggle-contrast"]');

		return !toggle || toggle.checked;
	}

	/* -- Master switch ---------------------------------------------------- */

	function syncMasterSwitch() {
		var toggle = form.querySelector('[data-eccw-live="enabled"]');
		var hero = form.querySelector('.eccw-hero');
		var state = form.querySelector('.eccw-hero-state');

		if (!toggle || !hero) {
			return;
		}

		hero.classList.toggle('is-off', !toggle.checked);

		if (state) {
			var next = toggle.checked
				? state.getAttribute('data-eccw-on')
				: state.getAttribute('data-eccw-off');

			if (next) {
				state.textContent = next;
			}
		}
	}

	/* -- Unsaved changes -------------------------------------------------- */

	var initial = {};
	var dirty = false;

	function fieldKey(field) {
		return field.name || field.id;
	}

	function snapshot() {
		Array.prototype.forEach.call(form.elements, function (field) {
			if (!fieldKey(field)) {
				return;
			}

			initial[fieldKey(field)] = field.type === 'checkbox' ? field.checked : field.value;
		});
	}

	function hasChanges() {
		var changed = false;

		Array.prototype.forEach.call(form.elements, function (field) {
			var key = fieldKey(field);

			if (changed || !key || !(key in initial)) {
				return;
			}

			var now = field.type === 'checkbox' ? field.checked : field.value;

			if (now !== initial[key]) {
				changed = true;
			}
		});

		return changed;
	}

	function updateDirtyHint() {
		var hint = form.querySelector('[data-eccw-dirty-hint]');

		dirty = hasChanges();

		if (hint) {
			hint.hidden = !dirty;
		}
	}

	/* -- AJAX ------------------------------------------------------------- */

	function post(payload, onSuccess, onDone) {
		var body = new FormData();

		body.append('nonce', data.nonce);

		Object.keys(payload).forEach(function (key) {
			var value = payload[key];

			if (Array.isArray(value)) {
				value.forEach(function (item) {
					body.append(key + '[]', item);
				});

				return;
			}

			body.append(key, value);
		});

		// Always resolves. The version this replaced chained `.always()` onto a
		// function that returned nothing, so every rescan threw a TypeError and
		// left the button disabled reading "Checking\u2026" forever.
		window.fetch(data.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		}).then(function (response) {
			return response.json();
		}).then(function (json) {
			if (json && json.success) {
				onSuccess(json.data || {});
				return;
			}

			notify((json && json.data && json.data.message) || strings.requestFailed, 'bad');
		}).catch(function () {
			notify(strings.requestFailed, 'bad');
		}).then(function () {
			if (onDone) {
				onDone();
			}
		});
	}

	/*
	 * Report a result on the page instead of in a browser dialog.
	 *
	 * alert() interrupts: it steals focus, has to be dismissed before the page
	 * can be touched again, and looks like the browser talking rather than the
	 * plugin. For a screen whose whole premise is that the owner never has to
	 * think about internals, one sentence of good news should not require an OK
	 * click. This writes into a live region that already exists in the markup,
	 * so the placement is fixed and assistive technology announces it without
	 * moving focus.
	 */
	function notify(message, kind) {
		var region = document.getElementById('eccw-live');

		if (!region) {
			window.alert(message);
			return;
		}

		region.textContent = '';

		var note = document.createElement('div');

		note.className = 'eccw-note ' + (kind === 'bad' ? 'eccw-note-bad' : 'eccw-note-good');
		note.textContent = message;
		region.appendChild(note);

		// Only scrolled to when it is out of sight; scrolling a message that is
		// already on screen just moves the page under the reader.
		var box = region.getBoundingClientRect();

		if (box.top < 0 || box.bottom > window.innerHeight) {
			region.scrollIntoView({ block: 'center', behavior: 'smooth' });
		}
	}

	function rescan(button) {
		var original = button.textContent;

		button.textContent = strings.scanning || 'Checking…';
		button.disabled = true;

		post({ action: 'eccw_rescan' }, function (result) {
			if (result.message) {
				notify(result.message);
			}

			if (result.new_count > 0 && window.confirm(strings.reloadPrompt)) {
				window.location.reload();
			}
		}, function () {
			button.textContent = original;
			button.disabled = false;
		});
	}

	function resetDefaults(button) {
		if (!window.confirm(button.getAttribute('data-confirm'))) {
			return;
		}

		var original = button.textContent;

		button.textContent = strings.resetting || 'Resetting…';
		button.disabled = true;

		post({ action: 'eccw_reset_defaults' }, function (result) {
			if (result.message) {
				notify(result.message);
			}

			// The server chose the new values, so re-reading the page is the
			// only way the screen and the database agree.
			dirty = false;
			window.location.reload();
		}, function () {
			button.textContent = original;
			button.disabled = false;
		});
	}

	/* -- Wiring ----------------------------------------------------------- */

	// Remembered so that switching to "Custom colour" can seed the picker with
	// the colour that was showing a moment ago.
	var previousValue = {};

	form.addEventListener('input', function (event) {
		if (event.target.matches('[data-eccw-picker]')) {
			var target = document.getElementById(event.target.getAttribute('data-eccw-picker'));

			if (target) {
				writeCustom(target, event.target.value);
				updateDirtyHint();
			}
		}
	});

	form.addEventListener('change', function (event) {
		var field = event.target;

		if (field.matches('[data-eccw-select]')) {
			paintChip(field);
			paintPreview(field);
			syncPicker(field);
			previousValue[field.id] = field.value;
		}

		if (field.matches('[data-eccw-live="enabled"]')) {
			syncMasterSwitch();
		}

		if (field.matches('[data-testid="eccw-toggle-contrast"]')) {
			repaintAll();
		}

		updateDirtyHint();
	});

	form.addEventListener('click', function (event) {
		var reset = event.target.closest('[data-eccw-reset]');

		if (reset) {
			event.preventDefault();

			var select = document.getElementById(reset.getAttribute('data-eccw-reset'));
			var fallback = reset.getAttribute('data-default');

			if (select) {
				select.value = fallback;
				paintChip(select);
				paintPreview(select);
				syncPicker(select);
				updateDirtyHint();
			}

			return;
		}

		var action = event.target.closest('[data-eccw-action]');

		if (!action) {
			return;
		}

		event.preventDefault();

		switch (action.getAttribute('data-eccw-action')) {
			case 'rescan':
				rescan(action);
				break;
			case 'reset-defaults':
				resetDefaults(action);
				break;
		}
	});

	function repaintAll() {
		form.querySelectorAll('[data-eccw-select]').forEach(function (select) {
			paintChip(select);
			paintPreview(select);
			syncPicker(select);
			previousValue[select.id] = select.value;
		});
	}

	form.addEventListener('submit', function () {
		dirty = false;
	});

	window.addEventListener('beforeunload', function (event) {
		if (dirty) {
			event.preventDefault();
			event.returnValue = strings.unsaved || '';
			return strings.unsaved || '';
		}
	});

	snapshot();
	repaintAll();
	syncMasterSwitch();
})();
