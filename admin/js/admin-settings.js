/**
 * Advanced Settings - bootstrap + shared helpers.
 *
 * Exposes a global WooceSettings namespace with the color map, WCAG
 * contrast helper, and a page-wide dirty/unsaved-changes guard.
 *
 * @package WooCommerce_Elementor_Colors
 */
(function ($) {
	'use strict';

	if (typeof wooceData === 'undefined' || !wooceData.kitColors) {
		return;
	}

	window.WooceSettings = {
		colorMap: {},
		defaults: {},
		newCount: 0,
		dirty: false,
		originalValues: {},

		init: function () {
			this.buildColorMap();
			this.storeOriginalValues();
			this.bindBeforeUnload();
		},

		buildColorMap: function () {
			var i;
			for (i = 0; i < wooceData.kitColors.length; i++) {
				this.colorMap[wooceData.kitColors[i].id] = wooceData.kitColors[i].hex;
			}

			this.defaults = wooceData.defaults || {};
			this.newCount = wooceData.newCount || 0;
		},

		hexToToken: function (hex) {
			var normalized = String(hex || '').toLowerCase();
			var id;

			for (id in this.colorMap) {
				if (Object.prototype.hasOwnProperty.call(this.colorMap, id) && this.colorMap[id].toLowerCase() === normalized) {
					return id;
				}
			}

			return '';
		},

		/**
		 * WCAG relative luminance of a hex color.
		 *
		 * @param {string} hex Hex color (e.g. #1B9E77).
		 * @return {number} Relative luminance in [0, 1].
		 */
		luminance: function (hex) {
			var rgb = this.hexToRgb(hex);
			var channels = [rgb.r, rgb.g, rgb.b].map(function (channel) {
				var c = channel / 255;

				if (c <= 0.03928) {
					return c / 12.92;
				}

				return Math.pow((c + 0.055) / 1.055, 2.4);
			});

			return 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
		},

		hexToRgb: function (hex) {
			var value = String(hex || '').replace('#', '');

			if (value.length === 3) {
				value = value.split('').map(function (c) {
					return c + c;
				}).join('');
			}

			var intValue = parseInt(value, 16);

			return {
				r: (intValue >> 16) & 255,
				g: (intValue >> 8) & 255,
				b: intValue & 255
			};
		},

		/**
		 * Choose a readable text color for a given background.
		 *
		 * @param {string} hex Background hex color.
		 * @return {string} '#ffffff' for dark backgrounds, '#111111' otherwise.
		 */
		contrastText: function (hex) {
			return this.luminance(hex) > 0.179 ? '#111111' : '#ffffff';
		},

		storeOriginalValues: function () {
			var self = this;

			$('.wooce-color-select').each(function () {
				var key = $(this).data('widget-key') + '_' + $(this).data('state');
				self.originalValues[key] = $(this).val();
			});

			$('#wooce-settings-form input[type="checkbox"]').each(function () {
				self.originalValues[$(this).attr('name')] = $(this).prop('checked');
			});
		},

		markDirty: function () {
			this.dirty = true;
		},

		hasChanges: function () {
			var self = this;
			var changed = false;

			$('.wooce-color-select').each(function () {
				var key = $(this).data('widget-key') + '_' + $(this).data('state');
				if (self.originalValues[key] !== $(this).val()) {
					changed = true;
					return false;
				}
			});

			if (changed) {
				return true;
			}

			$('#wooce-settings-form input[type="checkbox"]').each(function () {
				var key = $(this).attr('name');
				if (self.originalValues[key] !== $(this).prop('checked')) {
					changed = true;
					return false;
				}
			});

			return changed;
		},

		bindBeforeUnload: function () {
			var self = this;

			$(window).on('beforeunload', function () {
				if (!self.dirty) {
					return;
				}

				if (self.hasChanges()) {
					return 'You have unsaved changes.';
				}

				self.dirty = false;
			});

			$('#wooce-settings-form').on('submit', function () {
				self.dirty = false;
			});
		}
	};

	$(document).ready(function () {
		window.WooceSettings.init();
	});
})(jQuery);
