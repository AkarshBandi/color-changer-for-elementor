/**
 * Elementor Colors - Actions component.
 *
 * Handles the header action buttons: Rescan, Dismiss All New, and
 * Preview on Site.
 *
 * @package WooCommerce_Elementor_Colors
 */
(function ($) {
	'use strict';

	if (typeof eccwData === 'undefined' || typeof window.WooceSettings === 'undefined') {
		return;
	}

	var settings = window.WooceSettings;

	function request(data, done) {
		$.ajax({
			url: eccwData.ajaxUrl,
			type: 'POST',
			data: $.extend({ nonce: eccwData.nonce }, data),
			success: function (response) {
				if (response.success) {
					done(response.data);
					return;
				}

				alert(response.data && response.data.message ? response.data.message : 'Request failed.');
			},
			error: function () {
				alert('AJAX error. Please try again.');
			}
		});
	}

	function rescan() {
		var button = $('[data-eccw-action="rescan"]');

		button.text('Scanning...').prop('disabled', true);

		request({ action: 'eccw_rescan' }, function (data) {
			alert(data.message);

			if (data.new_count > 0 && window.confirm('New elements found. Reload the page to see them?')) {
				window.location.reload();
			}
		}).always(function () {
			button.text('Rescan').prop('disabled', false);
		});
	}

	function dismissNew() {
		var widgets = [];

		$('.eccw-badge-new').each(function () {
			var widgetKey = $(this).closest('tr').find('.eccw-color-select').first().data('widget-key');

			if (widgetKey) {
				widgets.push(widgetKey);
			}
		});

		if (!widgets.length) {
			alert('No new widgets to dismiss.');
			return;
		}

		request({ action: 'eccw_dismiss_new', widgets: widgets }, function () {
			$('.eccw-badge-new').each(function () {
				$(this).removeClass('eccw-badge-new').addClass('eccw-badge-default').text('default');
			});

			var button = $('[data-eccw-action="dismiss-new"]');
			button.prop('disabled', true);
		});
	}

	function previewOnSite() {
		var button = $('[data-eccw-action="preview-on-site"]');
		var mappings = {};

		$('.eccw-color-select').each(function () {
			var widgetKey = $(this).data('widget-key');
			var state = $(this).data('state');

			if (!mappings[widgetKey]) {
				mappings[widgetKey] = { slots: {} };
			}

			mappings[widgetKey].slots[state] = { color: $(this).val() };
		});

		button.text('Opening preview...').prop('disabled', true);

		request({ action: 'eccw_save_preview', mappings: mappings }, function (data) {
			window.open(data.preview_url, '_blank');
		}).always(function () {
			button.text('Preview on Site').prop('disabled', false);
		});
	}

	function bindEvents() {
		$(document).on('click', '[data-eccw-action="rescan"]', function (e) {
			e.preventDefault();
			rescan();
		});

		$(document).on('click', '[data-eccw-action="dismiss-new"]', function (e) {
			e.preventDefault();
			dismissNew();
		});

		$(document).on('click', '[data-eccw-action="preview-on-site"]', function (e) {
			e.preventDefault();
			previewOnSite();
		});
	}

	$(document).ready(bindEvents);
})(jQuery);
