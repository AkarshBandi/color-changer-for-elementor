(function () {
	'use strict';

	var WooceEditor = {
		currentTarget: null,
		currentWidget: null,
		currentSlotId: null,
		draft: {},
		initialized: false,
		colorMap: {},
		liveTimer: null,

		init: function () {
			if (typeof wooceData === 'undefined') {
				return;
			}

			this.buildColorMap();
			this.injectWidgetAttributes();
			this.bindEvents();
			this.injectLiveStyle();
			this.renderCoverageBadge();
			this.restoreLiveDraft();
			this.updateUnsavedIndicator();
			this.syncToolbarHeight();
			this.initialized = true;
		},

		syncToolbarHeight: function () {
			var toolbar = document.querySelector('#wooce-editor-toolbar');
			var panel = document.querySelector('#wooce-editor-panel');
			if (!toolbar || !panel) return;
			panel.style.setProperty('--wooce-toolbar-height', toolbar.offsetHeight + 'px');
		},

		renderCoverageBadge: function () {
			var badge = document.querySelector('.wooce-coverage-badge');
			if (!badge) return;

			var coverage = wooceData.coverage;
			if (!coverage || !coverage.total) {
				badge.style.display = 'none';
				return;
			}

			var pct = Math.round((coverage.configured / coverage.total) * 100);
			badge.textContent = coverage.configured + '/' + coverage.total + ' styled (' + pct + '%)';
			badge.style.display = 'inline';

			if (pct >= 80) {
				badge.style.color = '#46b450';
			} else if (pct >= 40) {
				badge.style.color = '#f0ad4e';
			} else {
				badge.style.color = '#b32d2e';
			}
		},

		restoreLiveDraft: function () {
			if (!wooceData.liveDraft || typeof wooceData.liveDraft !== 'object') {
				return;
			}

			var hasColors = false;
			var self = this;

			Object.keys(wooceData.liveDraft).forEach(function (wk) {
				var wd = wooceData.liveDraft[wk];
				if (!wd || !wd.slots) return;

				Object.keys(wd.slots).forEach(function (sid) {
					if (wd.slots[sid] && wd.slots[sid].color) {
						if (!self.draft[wk]) {
							self.draft[wk] = { slots: {} };
						}
						self.draft[wk].slots[sid] = { color: wd.slots[sid].color };
						hasColors = true;
					}
				});
			});

			if (hasColors) {
				this.rebuildLocalCss();
			}
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
					if (target.closest('#wooce-editor-toolbar') || target.closest('#wooce-editor-panel')) {
						return;
					}
					e.preventDefault();
					e.stopImmediatePropagation();
					self.handleElementClick(target, widgetKey);
					return;
				}

				if (!target.closest('#wooce-editor-toolbar') && !target.closest('#wooce-editor-panel')) {
					self.dismissPanel();
				}
			}, true);

			document.querySelector('.wooce-save-btn') && document.querySelector('.wooce-save-btn').addEventListener('click', function () {
				self.saveAll();
			});

			document.querySelector('.wooce-reset-btn') && document.querySelector('.wooce-reset-btn').addEventListener('click', function () {
				self.resetDefaults();
			});

			document.querySelector('.wooce-share-btn') && document.querySelector('.wooce-share-btn').addEventListener('click', function () {
				self.generateShare();
			});

			document.querySelector('.wooce-undo-btn') && document.querySelector('.wooce-undo-btn').addEventListener('click', function () {
				self.undoLastSave();
			});

			document.addEventListener('keydown', function (e) {
				if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') {
					e.preventDefault();
					self.undoLastSave();
				}
			});

			document.querySelector('.wooce-exit-btn') && document.querySelector('.wooce-exit-btn').addEventListener('click', function () {
				window.location.href = wooceData.settingsUrl || '/wp-admin/admin.php?page=wooce-settings';
			});

			document.querySelector('.wooce-panel-picker') && document.querySelector('.wooce-panel-picker').addEventListener('input', function () {
				var hex = this.value;
				document.querySelector('.wooce-panel-hex-input').value = hex;
				self.updateContrast(hex);
				self.setActiveSwatch(hex);
				self.applyColor(hex);
			});

			document.querySelector('.wooce-panel-hex-input') && document.querySelector('.wooce-panel-hex-input').addEventListener('input', function () {
				var hex = this.value;
				if (/^#[0-9a-f]{6}$/i.test(hex)) {
					document.querySelector('.wooce-panel-picker').value = hex;
					self.updateContrast(hex);
					self.setActiveSwatch(hex);
					self.applyColor(hex);
				}
			});

			document.querySelector('.wooce-revert-btn') && document.querySelector('.wooce-revert-btn').addEventListener('click', function () {
				self.revertColor();
			});

			document.querySelector('.wooce-panel-close') && document.querySelector('.wooce-panel-close').addEventListener('click', function () {
				self.dismissPanel();
			});

			var resizeTimer = null;
			window.addEventListener('resize', function () {
				clearTimeout(resizeTimer);
				resizeTimer = setTimeout(function () {
					self.syncToolbarHeight();
				}, 150);
			});

			window.addEventListener('load', function () {
				self.syncToolbarHeight();
			});

			if (document.fonts && document.fonts.ready) {
				document.fonts.ready.then(function () {
					self.syncToolbarHeight();
				});
			}
		},

		handleElementClick: function (target, widgetKey) {
			var self = this;
			this.currentTarget = target;
			this.currentWidget = widgetKey;

			var widgetData = wooceData.widgetSlots[widgetKey];
			if (!widgetData) return;

			this.highlightElement(target);

			var tabsEl = document.querySelector('.wooce-panel-tabs');
			tabsEl.innerHTML = '';
			this.renderPalette();

			var firstSlot = null;

			widgetData.slots.forEach(function (slot, idx) {
				var tab = document.createElement('button');
				tab.type = 'button';
				tab.className = 'wooce-panel-tab' + (idx === 0 ? ' active' : '');
				tab.textContent = slot.label;
				tab.dataset.slotId = slot.slot_id;

				tab.addEventListener('click', function () {
					tabsEl.querySelectorAll('.wooce-panel-tab').forEach(function (t) {
						t.classList.remove('active');
					});
					this.classList.add('active');
					self.switchSlot(slot.slot_id);
				});

				tabsEl.appendChild(tab);

				if (idx === 0) {
					firstSlot = slot.slot_id;
				}
			});

			this.currentSlotId = firstSlot;

			document.querySelector('.wooce-panel-element-name').textContent = widgetData.label;
			this.showPanel();

			if (firstSlot) {
				this.loadCurrentColor(firstSlot);
			}
		},

		renderPalette: function () {
			var self = this;
			var palette = document.querySelector('.wooce-panel-palette');
			if (!palette) return;
			palette.innerHTML = '';

			var colors = wooceData.kitColors || [];
			var currentHex = (document.querySelector('.wooce-panel-picker') || {}).value || '';

			if (!colors.length) {
				palette.style.display = 'none';
				return;
			}

			palette.style.display = 'block';

			colors.forEach(function (c) {
				var swatch = document.createElement('button');
				swatch.type = 'button';
				swatch.className = 'wooce-panel-swatch';
				swatch.title = (c.label || c.id) + ' · ' + c.hex;
				swatch.dataset.hex = c.hex;
				swatch.dataset.label = c.label || c.id;
				swatch.style.background = c.hex;

				if (c.hex.toLowerCase() === currentHex.toLowerCase()) {
					swatch.classList.add('active');
				}

				swatch.addEventListener('click', function () {
					var hex = this.dataset.hex;
					document.querySelector('.wooce-panel-picker').value = hex;
					document.querySelector('.wooce-panel-hex-input').value = hex;
					palette.querySelectorAll('.wooce-panel-swatch').forEach(function (s) {
						s.classList.remove('active');
					});
					this.classList.add('active');
					self.updateContrast(hex);
					self.applyColor(hex);
				});

				palette.appendChild(swatch);
			});
		},

		switchSlot: function (slotId) {
			this.currentSlotId = slotId;
			this.loadCurrentColor(slotId);
		},

		highlightElement: function (target) {
			this.removeHighlight();
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
			var self = this;
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
								if (self.colorMap && self.colorMap[defaultColorId]) {
									hex = self.colorMap[defaultColorId];
								}
							}
						});
					}
				}
			}

			document.querySelector('.wooce-panel-picker').value = hex;
			document.querySelector('.wooce-panel-hex-input').value = hex;
			this.updateContrast(hex);
			this.setActiveSwatch(hex);
		},

		setActiveSwatch: function (hex) {
			var palette = document.querySelector('.wooce-panel-palette');
			if (!palette) return;

			palette.querySelectorAll('.wooce-panel-swatch').forEach(function (s) {
				var isMatch = s.dataset.hex && s.dataset.hex.toLowerCase() === hex.toLowerCase();
				s.classList.toggle('active', isMatch);
			});
		},

		applyColor: function (hex) {
			if (!this.currentWidget || !this.currentSlotId) return;

			var self = this;

			if (!this.draft[this.currentWidget]) {
				this.draft[this.currentWidget] = { slots: {} };
			}

			this.draft[this.currentWidget].slots[this.currentSlotId] = { color: hex };
			this.applyLocalCss(this.currentWidget, this.currentSlotId, hex);
			this.updateUnsavedIndicator();

			if (this.liveTimer) {
				clearTimeout(this.liveTimer);
			}

			// Remember the pending change so saveAll() can flush it before
			// committing, preventing a color picked <150ms ago from being lost.
			this.pendingUpdate = {
				widgetKey: this.currentWidget,
				slotId: this.currentSlotId,
				hex: hex
			};

			this.liveTimer = setTimeout(function () {
				self.flushLiveUpdate();
			}, 150);
		},

		flushLiveUpdate: function () {
			if (!this.pendingUpdate) return;

			var self = this;
			var update = this.pendingUpdate;
			this.pendingUpdate = null;
			this.liveTimer = null;

			var params = 'action=wooce_live_update&widget_key=' + encodeURIComponent(update.widgetKey) +
				'&slot_id=' + encodeURIComponent(update.slotId) +
				'&hex=' + encodeURIComponent(update.hex) +
				'&nonce=' + encodeURIComponent(wooceData.nonce);

			var xhr = new XMLHttpRequest();
			xhr.open('POST', wooceData.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success && resp.data.css) {
							self.applyCss(resp.data.css);
						}
					} catch (e) {}
				}
			};
			xhr.send(params);
		},

		revertColor: function () {
			if (!this.currentWidget || !this.currentSlotId) return;

			var self = this;

			if (this.draft[this.currentWidget] && this.draft[this.currentWidget].slots[this.currentSlotId]) {
				delete this.draft[this.currentWidget].slots[this.currentSlotId];
			}

			this.rebuildLocalCss();
			this.updateUnsavedIndicator();

			var xhr = new XMLHttpRequest();
			xhr.open('POST', wooceData.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success) {
							self.loadCurrentColor(self.currentSlotId);
							self.showToast('Reset to default', 'info');
						}
					} catch (e) {}
				}
			};

			var params = 'action=wooce_live_update&widget_key=' + encodeURIComponent(this.currentWidget) +
				'&slot_id=' + encodeURIComponent(this.currentSlotId) +
				'&remove=1&nonce=' + encodeURIComponent(wooceData.nonce);

			xhr.send(params);
		},

		saveAll: function () {
			var self = this;

			// Flush any color picked within the last 150ms so the commit
			// includes it instead of a stale server transient.
			if (this.liveTimer) {
				clearTimeout(this.liveTimer);
				this.flushLiveUpdate();
			}

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
					btn.textContent = 'Save Changes';
				}

				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success) {
							self.draft = {};
							self.clearAllLocalCss();
							self.updateUnsavedIndicator();
							self.showToast('Changes saved permanently!', 'success');
						} else {
							btn && (btn.disabled = false);
							self.showToast(resp.data.message || 'Nothing to save yet — pick a color first.', 'info');
						}
					} catch (e) {
						btn && (btn.disabled = false);
						self.showToast('Save failed', 'error');
					}
				} else {
					btn && (btn.disabled = false);
					self.showToast('Connection error', 'error');
				}
			};

			var params = 'action=wooce_commit_live&draft=' + encodeURIComponent(JSON.stringify(self.draft)) +
				'&nonce=' + encodeURIComponent(wooceData.nonce);
			xhr.send(params);
		},

		resetDefaults: function () {
			var self = this;
			if (!window.confirm('Reset every element on this site back to its default color? This cannot be undone.')) {
				return;
			}

			var xhr = new XMLHttpRequest();
			xhr.open('POST', wooceData.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success) {
							self.draft = {};
							self.updateUnsavedIndicator();
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

		generateShare: function () {
			var self = this;

			if (this.liveTimer) {
				clearTimeout(this.liveTimer);
				this.flushLiveUpdate();
			}

			var btn = document.querySelector('.wooce-share-btn');
			if (btn) {
				btn.textContent = 'Generating...';
				btn.disabled = true;
			}

			var xhr = new XMLHttpRequest();
			xhr.open('POST', wooceData.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				btn && (btn.textContent = 'Share');
				btn && (btn.disabled = false);

				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success && resp.data.share_url) {
							self.showShareDialog(resp.data.share_url);
						} else {
							self.showToast(resp.data.message || 'Nothing to share yet.', 'info');
						}
					} catch (e) {
						self.showToast('Share failed', 'error');
					}
				} else {
					self.showToast('Connection error', 'error');
				}
			};

			var params = 'action=wooce_generate_share&nonce=' + encodeURIComponent(wooceData.nonce);
			xhr.send(params);
		},

		showShareDialog: function (url) {
			var existing = document.querySelector('.wooce-share-dialog');
			if (existing) existing.remove();

			var overlay = document.createElement('div');
			overlay.className = 'wooce-share-dialog';
			overlay.style.cssText = 'position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:999999;display:flex;align-items:center;justify-content:center;';

			var box = document.createElement('div');
			box.style.cssText = 'background:#fff;border-radius:8px;padding:24px;max-width:520px;width:90%;font-family:-apple-system,BlinkMacSystemFont,sans-serif;box-shadow:0 8px 30px rgba(0,0,0,0.25);';

			var title = document.createElement('h3');
			title.textContent = 'Share Preview Link';
			title.style.cssText = 'margin:0 0 8px;font-size:16px;color:#1d2327;';

			var desc = document.createElement('p');
			desc.textContent = 'Anyone with this link can view the current colors on the live site. The link expires after one hour.';
			desc.style.cssText = 'margin:0 0 14px;font-size:13px;color:#50575e;';

			var input = document.createElement('input');
			input.type = 'text';
			input.readOnly = true;
			input.value = url;
			input.style.cssText = 'width:100%;padding:10px;border:1px solid #dcdcde;border-radius:4px;font-size:13px;box-sizing:border-box;margin-bottom:12px;';

			var actions = document.createElement('div');
			actions.style.cssText = 'display:flex;gap:8px;justify-content:flex-end;';

			var copyBtn = document.createElement('button');
			copyBtn.type = 'button';
			copyBtn.textContent = 'Copy Link';
			copyBtn.className = 'button button-primary';
			copyBtn.addEventListener('click', function () {
				input.select();
				try {
					document.execCommand('copy');
				} catch (e) {}
				self.showToast('Link copied!', 'success');
			});

			var closeBtn = document.createElement('button');
			closeBtn.type = 'button';
			closeBtn.textContent = 'Close';
			closeBtn.className = 'button';
			closeBtn.addEventListener('click', function () {
				overlay.remove();
			});

			overlay.addEventListener('click', function (e) {
				if (e.target === overlay) overlay.remove();
			});

			actions.appendChild(copyBtn);
			actions.appendChild(closeBtn);
			box.appendChild(title);
			box.appendChild(desc);
			box.appendChild(input);
			box.appendChild(actions);
			overlay.appendChild(box);
			document.body.appendChild(overlay);

			input.focus();
			input.select();
		},

		undoLastSave: function () {
			var self = this;

			var xhr = new XMLHttpRequest();
			xhr.open('POST', wooceData.ajaxUrl, true);
			xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
			xhr.onload = function () {
				if (xhr.status === 200) {
					try {
						var resp = JSON.parse(xhr.responseText);
						if (resp.success) {
							self.draft = {};
							self.clearAllLocalCss();
							self.updateUnsavedIndicator();
							self.showToast('Previous save restored', 'success');
							location.reload();
						} else {
							self.showToast(resp.data.message || 'Nothing to undo.', 'info');
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

		showPanel: function () {
			var panel = document.getElementById('wooce-editor-panel');
			if (!panel) return;

			this.syncToolbarHeight();
			panel.classList.add('wooce-open');
			panel.setAttribute('aria-hidden', 'false');
		},

		dismissPanel: function () {
			var panel = document.getElementById('wooce-editor-panel');
			if (!panel) return;

			panel.classList.remove('wooce-open');
			panel.setAttribute('aria-hidden', 'true');
			this.removeHighlight();
			this.currentTarget = null;
			this.currentWidget = null;
			this.currentSlotId = null;
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

			var hasBg = slotDef.props.indexOf('background-color') !== -1;
			var textHex = hasBg ? this.contrastText(hex) : hex;

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
						if (hasBg && (prop === 'color' || prop === 'fill')) {
							css += prop + ': ' + textHex + ' !important; ';
							return;
						}
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

		contrastText: function (hex) {
			try {
				var rgb = this.hexToRgb(hex);
				var linear = [rgb.r, rgb.g, rgb.b].map(function (c) {
					c = c / 255;
					return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
				});
				var luminance = 0.2126 * linear[0] + 0.7152 * linear[1] + 0.0722 * linear[2];
				return luminance > 0.179 ? '#111111' : '#ffffff';
			} catch (e) {
				return '#ffffff';
			}
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
			var badge = document.querySelector('.wooce-panel-contrast');

			if (badge) {
				if (ratio >= 4.5) {
					badge.textContent = 'Readable on this background (' + ratio.toFixed(1) + ':1)';
					badge.style.color = '#46b450';
				} else if (ratio >= 3) {
					badge.textContent = 'Barely readable (' + ratio.toFixed(1) + ':1)';
					badge.style.color = '#f0ad4e';
				} else {
					badge.textContent = 'Hard to read (' + ratio.toFixed(1) + ':1)';
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
			if (badge) {
				var hasDraft = Object.keys(this.draft).length > 0;
				badge.style.display = hasDraft ? 'inline' : 'none';
			}

			var saveBtn = document.querySelector('.wooce-save-btn');
			if (saveBtn) {
				var hasDrafts = Object.keys(this.draft).length > 0;
				saveBtn.disabled = !hasDrafts;
			}
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