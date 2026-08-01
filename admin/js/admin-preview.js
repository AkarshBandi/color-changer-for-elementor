(function ($) {
	'use strict';

	var wooce = {
		colorMap: {},
		dirty: false,
		originalValues: {},

		init: function () {
			if (typeof wooceData === 'undefined' || !wooceData.kitColors) {
				return;
			}

			this.buildColorMap();
			this.bindEvents();
			this.initPreviewChips();
			this.storeOriginalValues();
			this.bindBeforeUnload();
		},

		buildColorMap: function () {
			for (var i = 0; i < wooceData.kitColors.length; i++) {
				var c = wooceData.kitColors[i];
				this.colorMap[c.id] = c.hex;
			}
		},

		bindEvents: function () {
			var self = this;

			$(document).on('change', '.wooce-color-select', function () {
				var widgetKey = $(this).data('widget-key');
				var state = $(this).data('state');
				var colorId = $(this).val();
				self.updatePreviewChip(widgetKey, state, colorId);
				self.markDirty();
			});

			$(document).on('mouseenter', '.wooce-preview-chip', function () {
				var chip = $(this);
				var widgetKey = chip.data('widget-key');
				var state = chip.data('state');

				if (state === 'hover' || state === 'focus') {
					return;
				}

				var hoverSelect = $('.wooce-color-select[data-widget-key="' + widgetKey + '"][data-state="hover"]');
				if (hoverSelect.length) {
					var hoverColorId = hoverSelect.val();
					var hoverHex = self.colorMap[hoverColorId] || '#000000';
					chip.data('original-bg', chip.css('background-color'));
					chip.css('background-color', hoverHex);
				}
			});

			$(document).on('mouseleave', '.wooce-preview-chip', function () {
				var chip = $(this);
				var origBg = chip.data('original-bg');
				if (origBg) {
					chip.css('background-color', origBg);
					chip.removeData('original-bg');
				}
			});

			$('.wooce-preview-on-site').on('click', function (e) {
				e.preventDefault();
				self.savePreview();
			});

			$('.wooce-dismiss-new').on('click', function (e) {
				e.preventDefault();
				self.dismissNewWidgets();
			});

			$('.wooce-rescan').on('click', function (e) {
				e.preventDefault();
				self.rescan();
			});

			$('.wooce-reset-link').on('click', function (e) {
				e.preventDefault();
				var widgetKey = $(this).data('widget-key');
				var select = $(this).closest('td').find('.wooce-color-select');
				var defaultColor = select.find('option').first().val();
				select.val(defaultColor).trigger('change');
			});
		},

		initPreviewChips: function () {
			var self = this;

			$('.wooce-color-select').each(function () {
				var widgetKey = $(this).data('widget-key');
				var state = $(this).data('state');
				var colorId = $(this).val();
				self.updatePreviewChip(widgetKey, state, colorId);
			});
		},

		updatePreviewChip: function (widgetKey, state, colorId) {
			var hex = this.colorMap[colorId] || '#000000';
			var chip = $('.wooce-preview-chip[data-widget-key="' + widgetKey + '"][data-state="' + state + '"]');

			if (chip.length) {
				chip.css('background-color', hex);

				if (state === 'price_regular' || state === 'price_sale' || state === 'stars_filled' || state === 'stars_empty' || state === 'link_normal' || state === 'link_hover') {
					chip.css('color', hex);
				}

				if (state === 'success_border' || state === 'info_border' || state === 'error_border') {
					chip.css('border-top-color', hex);
				}

				if (state === 'success_icon' || state === 'info_icon' || state === 'error_icon') {
					chip.css('color', hex);
				}

				if (state.indexOf('input') !== -1 || state === 'coupon_input' || state.indexOf('qty') !== -1) {
					chip.css('border-color', hex);
				}
			}

			var galleryPreview = $('.wooce-gallery-preview[data-widget-key="' + widgetKey + '"][data-state="' + state + '"]');
			if (galleryPreview.length) {
				if (state.indexOf('button') !== -1 || state.indexOf('proceed') !== -1 || state.indexOf('place_order') !== -1 || state.indexOf('update_cart') !== -1 || state.indexOf('loop') !== -1) {
					galleryPreview.css('background-color', hex);
				} else {
					galleryPreview.css('color', hex);
				}
			}
		},

		storeOriginalValues: function () {
			var self = this;

			$('.wooce-color-select').each(function () {
				var key = $(this).data('widget-key') + '_' + $(this).data('state');
				self.originalValues[key] = $(this).val();
			});
		},

		markDirty: function () {
			this.dirty = true;
		},

		bindBeforeUnload: function () {
			var self = this;

			$(window).on('beforeunload', function () {
				if (!self.dirty) {
					return;
				}

				var changed = false;
				$('.wooce-color-select').each(function () {
					var key = $(this).data('widget-key') + '_' + $(this).data('state');
					if (self.originalValues[key] !== $(this).val()) {
						changed = true;
						return false;
					}
				});

				if (changed) {
					return 'You have unsaved changes.';
				}

				self.dirty = false;
			});

			$('#wooce-settings-form').on('submit', function () {
				self.dirty = false;
			});
		},

		savePreview: function () {
			var self = this;
			var mappings = {};

			$('.wooce-color-select').each(function () {
				var widgetKey = $(this).data('widget-key');
				var state = $(this).data('state');

				if (!mappings[widgetKey]) {
					mappings[widgetKey] = { slots: {} };
				}

				mappings[widgetKey].slots[state] = { color: $(this).val() };
			});

			$('.wooce-preview-on-site').text('Opening preview...').prop('disabled', true);

			$.ajax({
				url: wooceData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wooce_save_preview',
					nonce: wooceData.nonce,
					mappings: mappings
				},
				success: function (response) {
					if (response.success && response.data.preview_url) {
						window.open(response.data.preview_url, '_blank');
					} else {
						alert('Failed to save preview.');
					}
				},
				error: function () {
					alert('AJAX error. Please try again.');
				},
				complete: function () {
					$('.wooce-preview-on-site').text('Preview on Site').prop('disabled', false);
				}
			});
		},

		dismissNewWidgets: function () {
			var self = this;
			var widgets = [];

			$('.wooce-badge-new').each(function () {
				var row = $(this).closest('tr');
				var widgetKey = row.find('.wooce-color-select').first().data('widget-key');
				if (widgetKey) {
					widgets.push(widgetKey);
				}
			});

			if (!widgets.length) {
				alert('No new widgets to dismiss.');
				return;
			}

			$.ajax({
				url: wooceData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wooce_dismiss_new',
					nonce: wooceData.nonce,
					widgets: widgets
				},
				success: function (response) {
					if (response.success) {
						$('.wooce-badge-new').each(function () {
							$(this).removeClass('wooce-badge-new').addClass('wooce-badge-default').text('default');
						});
					}
				},
				error: function () {
					alert('AJAX error. Please try again.');
				}
			});
		},

		rescan: function () {
			var self = this;

			$('.wooce-rescan').text('Scanning...').prop('disabled', true);

			$.ajax({
				url: wooceData.ajaxUrl,
				type: 'POST',
				data: {
					action: 'wooce_rescan',
					nonce: wooceData.nonce
				},
				success: function (response) {
					if (response.success) {
						alert(response.data.message);
						if (response.data.new_count > 0) {
							if (confirm('New elements found. Reload the page to see them?')) {
								location.reload();
							}
						}
					} else {
						alert('Scan failed.');
					}
				},
				error: function () {
					alert('AJAX error. Please try again.');
				},
				complete: function () {
					$('.wooce-rescan').text('Rescan').prop('disabled', false);
				}
			});
		}
	};

	$(document).ready(function () {
		wooce.init();
	});

})(jQuery);
