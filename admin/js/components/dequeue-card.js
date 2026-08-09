/**
 * Elementor Colors - Dequeue Card component.
 *
 * Keeps the dequeue toggles' disabled state in sync with the presence of
 * element mappings and marks the form dirty when toggled.
 *
 * @package WooCommerce_Elementor_Colors
 */
(function ($) {
	'use strict';

	if (typeof window.WooceSettings === 'undefined') {
		return;
	}

	var settings = window.WooceSettings;

	function bindEvents() {
		$('#eccw-settings-form input[name="eccw_dequeue_blocks"]').on('change', function () {
			settings.markDirty();
		});
	}

	$(document).ready(bindEvents);
})(jQuery);
