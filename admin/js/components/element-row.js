/**
 * Elementor Colors - Element Row component.
 *
 * Handles per-row color selects, preview chips, hover states, and
 * reset-to-default behavior.
 *
 * @package WooCommerce_Elementor_Colors
 */
(function ($) {
	'use strict';

	if (typeof window.WooceSettings === 'undefined') {
		return;
	}

	var settings = window.WooceSettings;

	function updateChip(widgetKey, state, colorId) {
		var hex = settings.colorMap[colorId] || '#000000';
		var chip = $('.eccw-preview-chip[data-widget-key="' + widgetKey + '"][data-state="' + state + '"]');

		if (!chip.length) {
			return;
		}

		chip.css('background-color', hex);

		if (chip.data('is-button') === 1) {
			chip.css('color', settings.contrastText(hex));
		}
	}

	function resetRow(widgetKey, state, defaultColor) {
		var select = $('.eccw-color-select[data-widget-key="' + widgetKey + '"][data-state="' + state + '"]');

		if (!select.length) {
			return;
		}

		select.val(defaultColor).trigger('change');
	}

	function bindEvents() {
		$(document).on('change', '.eccw-color-select', function () {
			var widgetKey = $(this).data('widget-key');
			var state = $(this).data('state');
			var colorId = $(this).val();

			updateChip(widgetKey, state, colorId);

			var galleryPreview = $('.eccw-gallery-preview[data-widget-key="' + widgetKey + '"][data-state="' + state + '"]');
			if (galleryPreview.length) {
				var hex = settings.colorMap[colorId] || '#000000';

				if (galleryPreview.data('is-button') === 1) {
					galleryPreview.css('background-color', hex);
					galleryPreview.css('color', settings.contrastText(hex));
				} else {
					galleryPreview.css('color', hex);
				}
			}

			settings.markDirty();
		});

		$(document).on('click', '.eccw-reset-link', function (e) {
			e.preventDefault();

			var row = $(this).closest('.eccw-color-row');
			var select = row.find('.eccw-color-select');
			var widgetKey = select.data('widget-key');
			var state = select.data('state');
			var defaultColor = $(this).data('default') || settings.defaults[state] || 'text';

			resetRow(widgetKey, state, defaultColor);
		});
	}

	function init() {
		bindEvents();
	}

	$(document).ready(init);
})(jQuery);
