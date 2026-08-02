/**
 * Advanced Settings - Gallery component.
 *
 * Handles gallery preview hover states showing the hover/focus colors.
 *
 * @package WooCommerce_Elementor_Colors
 */
(function ($) {
	'use strict';

	if (typeof window.WooceSettings === 'undefined') {
		return;
	}

	var settings = window.WooceSettings;

	function applyHover(preview) {
		var widgetKey = preview.data('widget-key');
		var state = preview.data('state');
		var hoverSelect = $('.wooce-color-select[data-widget-key="' + widgetKey + '"][data-state="' + state.replace(/_normal$/, '_hover') + '"]');

		if (!hoverSelect.length) {
			return;
		}

		var hoverHex = settings.colorMap[hoverSelect.val()] || '#000000';

		preview.data('original-color', preview.css('background-color'));

		if (preview.data('is-button') === 1) {
			preview.css('background-color', hoverHex);
			preview.css('color', settings.contrastText(hoverHex));
		} else {
			preview.css('color', hoverHex);
		}
	}

	function restoreHover(preview) {
		var original = preview.data('original-color');

		if (!original) {
			return;
		}

		var widgetKey = preview.data('widget-key');
		var state = preview.data('state');
		var select = $('.wooce-color-select[data-widget-key="' + widgetKey + '"][data-state="' + state + '"]');
		var baseHex = settings.colorMap[select.val()] || '#000000';

		if (preview.data('is-button') === 1) {
			preview.css('background-color', baseHex);
			preview.css('color', settings.contrastText(baseHex));
		} else {
			preview.css('color', baseHex);
		}

		preview.removeData('original-color');
	}

	function bindEvents() {
		$(document).on('mouseenter', '.wooce-gallery-preview', function () {
			var preview = $(this);

			if (preview.data('state').indexOf('hover') !== -1 || preview.data('state').indexOf('focus') !== -1) {
				return;
			}

			applyHover(preview);
		});

		$(document).on('mouseleave', '.wooce-gallery-preview', function () {
			restoreHover($(this));
		});
	}

	function init() {
		bindEvents();
	}

	$(document).ready(init);
})(jQuery);
