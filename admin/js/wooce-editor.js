(function () {
	'use strict';

	var WooceEditor = {
		currentTarget: null,
		currentWidget: null,
		currentSlotId: null,
		draft: {},
		auditMode: false,
		initialized: false,
		colorMap: {},

		init: function () {
			if (typeof wooceData === 'undefined') {
				return;
			}

			this.buildColorMap();
			this.injectWidgetAttributes();
			this.bindEvents();
			this.injectLiveStyle();
			this.calcPerformance();
			this.initialized = true;
		},

		buildColorMap: function () {
			for (var i = 0; i < wooceData.kitColors.length; i++) {
				var c = wooceData.kitColors[i];
				this.colorMap[c.id] = c.hex;
			}
		},

		injectWidgetAttributes: function () {
			var selectors = wooceData.registrySelectors;
			if (!selectors) return;

			for (var widgetKey in selectors) {
				if (!selectors.hasOwnProperty(widgetKey)) continue;
				var selectorList = selectors[widgetKey];
				for (var s = 0; s < selectorList.length; s++) {
					try {
						var elements = document.querySelectorAll(selectorList[s]);
						for (var e = 0; e < elements.length; e++) {
							elements[e].setAttribute('data-wooce-widget', widgetKey);
						}
					} catch (err) {
						// Skip invalid selectors
					}
				}
			}
		},

		injectLiveStyle: function () {
			if (!document.getElementById('wooce-live-style')) {
				var style = document.createElement('style');
				style.id = 'wooce-live-style';
				document.head.appendChild(style);
			}
		},

		bindEvents: function () {
			var self = this;

			document.addEventListener('click', function (e) {
				var target = e.target;
				var widgetKey = target.getAttribute('data-wooce-widget');

				if (!widgetKey) {
					var parent = target.closest('[data-wooce-widget]');
					if (parent) {
						widgetKey = parent.getAttribute('data-wooce-widget');
						target = parent;
					}
				}

				if (widgetKey) {
					if (target.closest('#wooce-editor-toolbar') || target.closest('#wooce-editor-card')) {
						return;
					}
					e.preventDefault();
					e.stopPropagation();
					self.handleElementClick(target, widgetKey);
					return;
				}

				if (!target.closest('#wooce-editor-toolbar') && !target.closest('#wooce-editor-card')) {
					self.dismissCard();
				}
			});

			document.getElementById('wooce-audit-mode') && document.getElementById('wooce-audit-mode').addEventListener('change', function () {
				self.toggleAuditMode(this.checked);
			});

			document.querySelector('.wooce-save-btn') && document.querySelector('.wooce-save-btn').addEventListener('click', function () {
				self.saveAll();
			});

			document.querySelector('.wooce-share-btn') && document.querySelector('.wooce-share-btn').addEventListener('click', function () {
				self.generateShareLink();
			});

			document.querySelector('.wooce-reset-btn') && document.querySelector('.wooce-reset-btn').addEventListener('click', function () {
				self.resetDefaults();
			});

			document.querySelector('.wooce-exit-btn') && document.querySelector('.wooce-exit-btn').addEventListener('click', function () {
				window.location.href = wooceData.settingsUrl || '/wp-admin/admin.php?page=wooce-settings';
			});

			document.querySelector('.wooce-undo-btn') && document.querySelector('.wooce-undo-btn').addEventListener('click', function () {
				self.undo();
			});

			document.querySelector('.wooce-card-picker') && document.querySelector('.wooce-card-picker').addEventListener('input', function () {
				var hex = this.value;
				document.querySelector('.wooce-card-hex-input').value = hex;
				self.updateContrast(hex);
			});

			document.querySelector('.wooce-card-hex-input') && document.querySelector('.wooce-card-hex-input').addEventListener('input', function () {
				var hex = this.value;
				if (/^#[0-9a-f]{6}$/i.test(hex)) {
					document.querySelector('.wooce-card-picker').value = hex;
					self.updateContrast(hex);
				}
			});

			document.querySelector('.wooce-apply-btn') && document.querySelector('.wooce-apply-btn').addEventListener('click', function () {
				var hex = document.querySelector('.wooce-card-picker').value;
				self.pickColor(hex);
			});

			document.querySelector('.wooce-revert-btn') && document.querySelector('.wooce-revert-btn').addEventListener('click', function () {
				self.revertColor();
			});

			document.querySelector('.wooce-card-close') && document.querySelector('.wooce-card-close').addEventListener('click', function () {
				self.dismissCard();
			});

			document.addEventListener('keydown', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
					e.preventDefault();
					self.undo();
				}
			});
		},

		handleElementClick: function (target, widgetKey) {
			this.currentTarget = target;
			this.currentWidget = widgetKey;

			var widgetData = wooceData.widgetSlots[widgetKey];
			if (!widgetData) return;

			this.highlightElement(target);

			document.querySelector('.wooce-active-element-name').textContent = widgetData.label;

			var activeStateEl = document.querySelector('.wooce-state-tabs');
			activeStateEl.innerHTML = '';

			var firstSlot = null;

			widgetData.slots.forEach(function (slot, idx) {
				var tab = document.createElement('button');
				tab.type = 'button';
				tab.className = 'wooce-state-tab' + (idx === 0 ? ' active' : '');
				tab.textContent = slot.label;
				tab.dataset.slotId = slot.slot_id;

				tab.addEventListener('click', function () {
					activeStateEl.querySelectorAll('.wooce-state-tab').forEach(function (t) {
						t.classList.remove('active');
					});
					this.classList.add('active');
					self.switchSlot(slot.slot_id);
				});

				activeStateEl.appendChild(tab);

				if (idx === 0) {
					firstSlot = slot.slot_id;
				}
			});

			this.currentSlotId = firstSlot;
			this.showCard(target);

			if (firstSlot) {
				this.loadCurrentColor(firstSlot);
			}

			document.querySelector('.wooce-toolbar-idle-state').style.display = 'none';
			document.querySelector('.wooce-toolbar-active-state').style.display = 'flex';
		},

		switchSlot: function (slotId) {
			this.currentSlotId = slotId;
			this.loadCurrentColor(slotId);
		},

		highlightElement: function (target) {
			this.clearHighlight();
			if (!target) return;

			var ring = document.createElement('div');
			ring.className = 'wooce-element-highlight';
			var rect = target.getBoundingClientRect();
			var scrollY = window.scrollY || window.pageYOffset;
			var scrollX = window.scrollX || window.pageXOffset;

			ring.style.cssText = 'position:absolute;pointer-events:none;z-index:99997;border:2px solid #2271b1;border-radius:3px;box-shadow:0 0 0 4px rgba(34,113,177,0.25);top:' + (rect.top + scrollY) + 'px;left:' + (rect.left + scrollX) + 'px;width:' + rect.width + 'px;height:' + rect.height + 'px;transition:all 0.15s ease;';
			document.body.appendChild(ring);
			this.highlightRing = ring;
		},

		removeHighlight: function () {
			if (this.highlightRing) {
				this.highlightRing.remove();
				this.highlightRing = null;
			}
		},

		loadCurrentColor: function (slotId) {
			var widgetKey = this.currentWidget;
			var hex = '#000000';

			if (this.draft[widgetKey] && this.draft[widgetKey].slots[slotId]) {
				hex = this.draft[widgetKey].slots[slotId].color;
			} else if (this.currentTarget) {
				var computed = window.getComputedStyle(this.currentTarget);
				var bg = computed.backgroundColor;
				var color = computed.color;

				var widgetData = wooceData.widgetSlots[widgetKey];
				var slotDef = null;
				if (widgetData) {
					widgetData.slots.forEach(function (s) {
						if (s.slot_id === slotId) slotDef = s;
					});
				}

				if (slotDef && slotDef.props) {
					if (slotDef.props.indexOf('background-color') !== -1) {
						hex = this.rgbToHex(bg);
					} else if (slotDef.props.indexOf('color') !== -1) {
						hex = this.rgbToHex(color);
					} else if (slotDef.props.indexOf('border-color') !== -1) {
						hex = this.rgbToHex(computed.borderTopColor);
					}
				}
			}

			if (hex === '#000000' || hex === '#000') {
				var saved = this.draft[widgetKey];
				if (!saved || !saved.slots[slotId]) {
					var widgetIndex = wooceData.widgetSlots[widgetKey];
					if (widgetIndex) {
						widgetIndex.slots.forEach(function (s) {
							if (s.slot_id === slotId && s.states.indexOf('normal') !== -1) {
								var defaultColorId = 'primary';
								if (wooceData.kitColors.length > 0) {
									defaultColorId = wooceData.kitColors[0].id;
								}
								if (wooceData.colorMap && wooceData.colorMap[defaultColorId]) {
									hex = wooceData.colorMap[defaultColorId];
								}
							}
						});
					}
				}
			}

			document.querySelector('.wooce-card-picker').value = hex;
			document.querySelector('.wooce-card-hex-input').value = hex;
			this.updateContrast(hex);
		},

		pickColor: function (hex) {
			if (!this.currentWidget || !this.currentSlotId) return;

			var self = this;

			if (!this.draft[this.currentWidget]) {
				this.draft[this.currentWidget] = { slots: {} };
			}

			this.draft[this.currentWidget].slots[this.currentSlotId] = { color: hex };

			this.updateUnsavedIndicator();

			this.setButtonLoading('.wooce-apply-btn', true, 'Applying...');

			var xhr = new XMLHttpRequest();
			xhr.open('POST', wooceData.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				self.setButtonLoading('.wooce-apply-btn', false);
				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success && resp.data.css) {
							self.applyCss(resp.data.css);
							self.showToast('Color applied', 'success');
						}
					} catch (e) {}
				}
			};

			var params = 'action=wooce_live_update&widget_key=' + encodeURIComponent(this.currentWidget) +
				'&slot_id=' + encodeURIComponent(this.currentSlotId) +
				'&hex=' + encodeURIComponent(hex) +
				'&nonce=' + encodeURIComponent(wooceData.nonce);

			xhr.send(params);

			this.applyLocalCss(this.currentWidget, this.currentSlotId, hex);
		},

		revertColor: function () {
			if (!this.currentWidget || !this.currentSlotId) return;

			var self = this;
			this.setButtonLoading('.wooce-revert-btn', true, 'Reverting...');

			var xhr = new XMLHttpRequest();
			xhr.open('POST', wooceData.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				self.setButtonLoading('.wooce-revert-btn', false);
				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success) {
							if (self.draft[self.currentWidget] && self.draft[self.currentWidget].slots[self.currentSlotId]) {
								delete self.draft[self.currentWidget].slots[self.currentSlotId];
							}
							self.rebuildLocalCss();
							self.showToast('Reverted to default', 'info');
						} else {
							self.showToast('Revert failed', 'error');
						}
					} catch (e) {
						self.showToast('Revert failed', 'error');
					}
				} else {
					self.showToast('Connection error', 'error');
				}
			};

			var params = 'action=wooce_live_update&widget_key=' + encodeURIComponent(this.currentWidget) +
				'&slot_id=' + encodeURIComponent(this.currentSlotId) +
				'&remove=1&nonce=' + encodeURIComponent(wooceData.nonce);

			xhr.send(params);
		},

		undo: function () {
			var self = this;
			this.setButtonLoading('.wooce-undo-btn', true, 'Undoing...');

			var xhr = new XMLHttpRequest();
			xhr.open('POST', wooceData.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				self.setButtonLoading('.wooce-undo-btn', false);
				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success) {
							self.draft = {};
							self.clearAllLocalCss();
							self.showToast('Undo successful', 'success');
							// Reload the page to reflect the restored mappings.
							location.reload();
						} else {
							self.showToast(resp.data.message || 'Nothing to undo', 'info');
						}
					} catch (e) {
						self.showToast('Undo failed', 'error');
					}
				} else {
					self.showToast('Connection error', 'error');
				}
			};

			var params = 'action=wooce_undo_action&nonce=' + encodeURIComponent(wooceData.nonce);
			xhr.send(params);
		},

		saveAll: function () {
			var self = this;

			var btn = document.querySelector('.wooce-save-btn');
			if (btn) {
				btn.textContent = 'Saving...';
				btn.disabled = true;
			}

			var xhr = new XMLHttpRequest();
			xhr.open('POST', wooceData.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				if (btn) {
					btn.textContent = '💾 Save Changes';
					btn.disabled = false;
				}

				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success) {
							self.draft = {};
							self.updateUnsavedIndicator();
							self.showToast('✅ Changes saved permanently!', 'success');
						} else {
							self.showToast('Save failed: ' + (resp.data.message || 'Unknown error'), 'error');
						}
					} catch (e) {
						self.showToast('Save failed', 'error');
					}
				} else {
					self.showToast('Connection error', 'error');
				}
			};

			var params = 'action=wooce_commit_live&nonce=' + encodeURIComponent(wooceData.nonce);
			xhr.send(params);
		},

		generateShareLink: function () {
			var self = this;
			this.setButtonLoading('.wooce-share-btn', true, 'Generating...');

			var xhr = new XMLHttpRequest();
			xhr.open('POST', wooceData.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				self.setButtonLoading('.wooce-share-btn', false);
				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success && resp.data.share_url) {
							if (navigator.clipboard && navigator.clipboard.writeText) {
								navigator.clipboard.writeText(resp.data.share_url).then(function () {
									self.showToast('📋 Share link copied to clipboard!', 'success');
								}).catch(function () {
									self.showToast('Share URL: ' + resp.data.share_url, 'info');
								});
							} else {
								self.showToast('Share URL: ' + resp.data.share_url, 'info');
							}
						} else {
							self.showToast('Failed to generate share link', 'error');
						}
					} catch (e) {
						self.showToast('Failed to generate share link', 'error');
					}
				}
			};

			var params = 'action=wooce_generate_share&nonce=' + encodeURIComponent(wooceData.nonce);
			xhr.send(params);
		},

		resetDefaults: function () {
			var self = this;
			if (!window.confirm('Reset all elements to their default colors? This cannot be undone.')) {
				return;
			}

			this.setButtonLoading('.wooce-reset-btn', true, 'Resetting...');

			var xhr = new XMLHttpRequest();
			xhr.open('POST', wooceData.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				self.setButtonLoading('.wooce-reset-btn', false);
				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success) {
							self.draft = {};
							self.showToast('All elements reset to defaults', 'success');
							location.reload();
						} else {
							self.showToast(resp.data.message || 'Reset failed', 'error');
						}
					} catch (e) {
						self.showToast('Reset failed', 'error');
					}
				} else {
					self.showToast('Connection error', 'error');
				}
			};

			var params = 'action=wooce_reset_defaults&nonce=' + encodeURIComponent(wooceData.nonce);
			xhr.send(params);
		},

		showCard: function (target) {
			var card = document.getElementById('wooce-editor-card');
			if (!card) return;

			var rect = target.getBoundingClientRect();
			var scrollY = window.scrollY || window.pageYOffset;
			var adminBar = document.getElementById('wpadminbar');
			var adminBarHeight = adminBar ? adminBar.offsetHeight : 0;

			card.style.display = 'block';
			card.style.left = Math.max(10, Math.min(rect.left + rect.width / 2 - 150, window.innerWidth - 320)) + 'px';
			card.style.top = (rect.top + scrollY - card.offsetHeight - 12 - adminBarHeight) + 'px';

			if (rect.top < 200) {
				card.style.top = (rect.bottom + scrollY + 12 + adminBarHeight) + 'px';
				card.querySelector('.wooce-card-arrow').style.top = '-8px';
				card.querySelector('.wooce-card-arrow').style.bottom = 'auto';
			} else {
				card.querySelector('.wooce-card-arrow').style.bottom = '-8px';
				card.querySelector('.wooce-card-arrow').style.top = 'auto';
			}
		},

		dismissCard: function () {
			document.getElementById('wooce-editor-card').style.display = 'none';
			document.querySelector('.wooce-toolbar-idle-state').style.display = 'flex';
			document.querySelector('.wooce-toolbar-active-state').style.display = 'none';
			this.removeHighlight();
			this.currentTarget = null;
			this.currentWidget = null;
			this.currentSlotId = null;
		},

		toggleAuditMode: function (enabled) {
			this.auditMode = enabled;

			var allElements = document.querySelectorAll('[data-wooce-widget]');
			var existingOutlines = document.querySelectorAll('.wooce-audit-outline');

			if (!enabled) {
				existingOutlines.forEach(function (el) { el.remove(); });
				return;
			}

			existingOutlines.forEach(function (el) { el.remove(); });

			var seen = new Set();
			var selectors = wooceData.registrySelectors;
			if (selectors) {
				for (var wk in selectors) {
					if (!selectors.hasOwnProperty(wk)) continue;
					selectors[wk].forEach(function (sel) {
						try {
							document.querySelectorAll(sel).forEach(function (el) { seen.add(el); });
						} catch (e) {}
					});
				}
			}
			var totalElements = seen.size;
			var covered = 0;

			allElements.forEach(function (el) {
				var outline = document.createElement('div');
				outline.className = 'wooce-audit-outline';
				var rect = el.getBoundingClientRect();
				var scrollY = window.scrollY || window.pageYOffset;
				var scrollX = window.scrollX || window.pageXOffset;

				outline.style.cssText = 'position:absolute;pointer-events:none;z-index:99998;border:2px solid #00c853;border-radius:2px;top:' + (rect.top + scrollY) + 'px;left:' + (rect.left + scrollX) + 'px;width:' + rect.width + 'px;height:' + rect.height + 'px;';
				document.body.appendChild(outline);
				covered++;
			});

			var toolbar = document.querySelector('.wooce-toolbar-inner');
			if (toolbar) {
				var existing = toolbar.querySelector('.wooce-audit-counter');
				if (existing) existing.remove();

				var counter = document.createElement('span');
				counter.className = 'wooce-audit-counter';
				counter.textContent = 'Coverage: ' + covered + '/' + totalElements;
				toolbar.querySelector('.wooce-toolbar-idle-state').appendChild(counter);
			}
		},

		applyCss: function (css) {
			var style = document.getElementById('wooce-live-style');
			if (style) {
				style.textContent += '\n' + css;
			}
		},

		applyLocalCss: function (widgetKey, slotId, hex) {
			var selectors = wooceData.registrySelectors[widgetKey];
			if (!selectors) return;

			var slotDef = null;
			var widgetData = wooceData.widgetSlots[widgetKey];
			if (widgetData) {
				widgetData.slots.forEach(function (s) {
					if (s.slot_id === slotId) slotDef = s;
				});
			}
			if (!slotDef || !slotDef.props || !slotDef.states) return;

			var css = '';
			selectors.forEach(function (sel) {
				slotDef.states.forEach(function (state) {
					var stateSuffix = '';
					if (state === 'hover') stateSuffix = ':hover';
					else if (state === 'focus') stateSuffix = ':focus';
					else if (state === 'disabled') stateSuffix = ':disabled';
					else if (state === 'active') stateSuffix = ':active';

					css += sel + stateSuffix + ' { ';
					slotDef.props.forEach(function (prop) {
						css += prop + ': ' + hex + ' !important; ';
					});
					if (state === 'disabled') {
						css += 'opacity: 0.5 !important; ';
					}
					css += ' } ';
				});
			});

			this.applyCss(css);
		},

		clearAllLocalCss: function () {
			var style = document.getElementById('wooce-live-style');
			if (style) {
				style.textContent = '';
			}
		},

		rebuildLocalCss: function () {
			this.clearAllLocalCss();
			for (var wk in this.draft) {
				if (!this.draft.hasOwnProperty(wk)) continue;
				for (var sid in this.draft[wk].slots) {
					if (!this.draft[wk].slots.hasOwnProperty(sid)) continue;
					var hex = this.draft[wk].slots[sid].color;
					this.applyLocalCss(wk, sid, hex);
				}
			}
		},

		updateContrast: function (hex) {
			var bgColor = '#ffffff';
			if (this.currentTarget) {
				var bg = window.getComputedStyle(this.currentTarget).backgroundColor;
				if (bg === 'transparent' || bg === 'rgba(0, 0, 0, 0)') {
					if (this.currentTarget.parentElement) {
						bg = window.getComputedStyle(this.currentTarget.parentElement).backgroundColor;
					}
				}
				if (bg && bg !== 'transparent' && bg !== 'rgba(0, 0, 0, 0)') {
					bgColor = this.rgbToHex(bg);
				}
			}

			var ratio = this.getContrastRatio(hex, bgColor);
			var badge = document.querySelector('.wooce-card-contrast');

			if (badge) {
				if (ratio >= 4.5) {
					badge.innerHTML = '✅ WCAG AA Pass (' + ratio.toFixed(1) + ':1)';
					badge.style.color = '#46b450';
				} else if (ratio >= 3) {
					badge.innerHTML = '⚠️ ' + ratio.toFixed(1) + ':1 — needs improvement';
					badge.style.color = '#f0ad4e';
				} else {
					badge.innerHTML = '❌ ' + ratio.toFixed(1) + ':1 — low readability';
					badge.style.color = '#b32d2e';
				}
			}
		},

		getContrastRatio: function (hex1, hex2) {
			var lum1 = this.getRelativeLuminance(this.hexToRgb(hex1));
			var lum2 = this.getRelativeLuminance(this.hexToRgb(hex2));
			var lighter = Math.max(lum1, lum2);
			var darker = Math.min(lum1, lum2);
			return (lighter + 0.05) / (darker + 0.05);
		},

		getRelativeLuminance: function (rgb) {
			var r = this.srgb(rgb.r / 255);
			var g = this.srgb(rgb.g / 255);
			var b = this.srgb(rgb.b / 255);
			return 0.2126 * r + 0.7152 * g + 0.0722 * b;
		},

		srgb: function (c) {
			return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
		},

		hexToRgb: function (hex) {
			hex = hex.replace('#', '');
			if (hex.length === 3) {
				hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
			}
			return {
				r: parseInt(hex.substr(0, 2), 16),
				g: parseInt(hex.substr(2, 2), 16),
				b: parseInt(hex.substr(4, 2), 16)
			};
		},

		rgbToHex: function (rgb) {
			if (!rgb || rgb === 'transparent' || rgb === 'rgba(0, 0, 0, 0)') {
				return '#ffffff';
			}

			var match = rgb.match(/^rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i);
			if (!match) {
				match = rgb.match(/^rgba\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*,\s*[\d.]+\s*\)$/i);
			}

			if (match) {
				var r = parseInt(match[1], 10);
				var g = parseInt(match[2], 10);
				var b = parseInt(match[3], 10);
				return '#' + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
			}

			return '#ffffff';
		},

		calcPerformance: function () {
			var badge = document.querySelector('.wooce-performance-badge');
			if (!badge) return;

			if (!wooceData.dequeueEnabled) {
				badge.textContent = '⚡ Enable CSS dequeue to reduce page weight';
				return;
			}

			var wcSize = wooceData.wcCssSize || 0;
			var generatedSize = 0;

			var style = document.getElementById('wooce-dynamic-css');
			if (style) {
				generatedSize += style.textContent.length;
			}

			var liveStyle = document.getElementById('wooce-live-style');
			if (liveStyle) {
				generatedSize += liveStyle.textContent.length;
			}

			var saved = Math.max(0, wcSize - generatedSize);

			if (saved > 1024) {
				badge.textContent = '⚡ Saved ' + (saved / 1024).toFixed(1) + ' KB of CSS';
			} else if (saved > 0) {
				badge.textContent = '⚡ Saved ' + saved + ' B of CSS';
			} else {
				badge.textContent = '';
			}
		},

		setButtonLoading: function (selector, loading, loadingText) {
			var btn = document.querySelector(selector);
			if (!btn) return;
			if (loading) {
				btn.dataset.originalText = btn.textContent;
				btn.textContent = loadingText || 'Working...';
				btn.disabled = true;
			} else {
				btn.textContent = btn.dataset.originalText || btn.textContent;
				btn.disabled = false;
			}
		},

		updateUnsavedIndicator: function () {
			var badge = document.querySelector('.wooce-unsaved-badge');
			if (!badge) return;
			var hasDraft = Object.keys(this.draft).length > 0;
			badge.style.display = hasDraft ? 'inline' : 'none';
		},

		showToast: function (message, type) {
			var existing = document.querySelector('.wooce-toast');
			if (existing) existing.remove();

			var toast = document.createElement('div');
			toast.className = 'wooce-toast wooce-toast-' + (type || 'info');
			toast.textContent = message;
			toast.style.cssText = 'position:fixed;bottom:80px;left:50%;transform:translateX(-50%);z-index:999999;padding:12px 24px;border-radius:6px;font-size:14px;font-family:-apple-system,BlinkMacSystemFont,sans-serif;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:opacity 0.3s;';

			if (type === 'success') toast.style.background = '#46b450';
			else if (type === 'error') toast.style.background = '#b32d2e';
			else toast.style.background = '#2271b1';
			toast.style.color = '#fff';

			document.body.appendChild(toast);

			setTimeout(function () {
				toast.style.opacity = '0';
				setTimeout(function () { toast.remove(); }, 300);
			}, 3000);
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () { WooceEditor.init(); });
	} else {
		WooceEditor.init();
	}

	window.WooceEditor = WooceEditor;
})();
