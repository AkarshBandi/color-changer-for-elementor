(function ($) {
	'use strict';

	var wizard = {
		currentStep: 1,
		totalSteps: 4,
		scanOffset: 0,
		scanFound: {},
		scanDone: false,
		productHtml: null,

		init: function () {
			this.showStep(1);
			this.bindEvents();
			this.startScan();
		},

		bindEvents: function () {
			var self = this;

			$(document).on('click', '.wooce-wizard-next', function () {
				self.nextStep();
			});

			$(document).on('click', '.wooce-wizard-prev', function () {
				self.prevStep();
			});

			$(document).on('click', '.wooce-toggle-btn', function () {
				$(this).toggleClass('toggled');
				$('.wooce-split-column.after').toggleClass('wooce-after-toggled');
			});

			$(document).on('change', '#wooce_dequeue_all', function () {
				if (!$(this).is(':checked')) {
					$('.wooce-diagram-overlay.core').addClass('visible');
				} else {
					$('.wooce-diagram-overlay.core').removeClass('visible');
				}
			});

			$(document).on('click', '.wooce-launch-editor', function () {
				self.completeOnboarding(function () {
					window.location.href = $(this).data('editor-url');
				}.bind(this));
			});

			$(document).on('click', '.wooce-launch-settings', function () {
				self.completeOnboarding(function () {
					window.location.href = $(this).data('settings-url');
				}.bind(this));
			});

			$(document).on('submit', '.wooce-email-form', function (e) {
				e.preventDefault();
				var email = $(this).find('input[type="email"]').val();
				if (email) {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'wooce_save_email',
							email: email,
							nonce: wooceData ? wooceData.nonce : ''
						},
						success: function () {
							$('.wooce-email-thanks').show();
							$('.wooce-email-form').hide();
						}
					});
				}
			});

			$(document).on('click', '.wooce-wizard-skip-link', function (e) {
				e.preventDefault();
				var link = this;
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wooce_dismiss_onboarding',
						nonce: wooceData ? wooceData.nonce : ''
					},
					complete: function () {
						window.location.href = $(link).attr('href');
					}
				});
			});
		},

		showStep: function (n) {
			this.currentStep = n;
			$('.wooce-wizard-step').removeClass('active');
			$('.wooce-wizard-step[data-step="' + n + '"]').addClass('active');

			$('.wooce-wizard-dot').each(function () {
				var idx = parseInt($(this).data('step'), 10);
				if (idx === n) {
					$(this).addClass('active').removeClass('completed');
				} else if (idx < n) {
					$(this).addClass('completed').removeClass('active');
				} else {
					$(this).removeClass('active completed');
				}
			});

			$('.wooce-wizard-progress-text').text('Step ' + n + ' of ' + this.totalSteps);

			$('.wooce-wizard-prev').css('visibility', n > 1 ? 'visible' : 'hidden');
			$('.wooce-wizard-next').text(n < this.totalSteps ? 'Next →' : 'Finish');

			if (n === 2 && !this.productHtml) {
				this.loadABTest();
			}

			if (n === 4) {
				this.startConfetti();
			}
		},

		nextStep: function () {
			if (this.currentStep === this.totalSteps) {
				this.completeOnboarding(function () {
					window.location.href = wooceData ? wooceData.siteUrl : '/';
				});
				return;
			}
			if (this.currentStep < this.totalSteps) {
				this.showStep(this.currentStep + 1);
			}
		},

		prevStep: function () {
			if (this.currentStep > 1) {
				this.showStep(this.currentStep - 1);
			}
		},

		startScan: function () {
			var self = this;
			this.scanOffset = 0;
			this.scanFound = {};
			this.scanDone = false;

			var doBatch = function () {
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wooce_scan_progress',
						offset: self.scanOffset,
						nonce: wooceData ? wooceData.nonce : ''
					},
					success: function (resp) {
						if (!resp.success) {
							$('.wooce-scan-status').text('Scan failed. Please try again.');
							return;
						}

						var data = resp.data;

						if (data.found) {
							$.each(data.found, function (i, item) {
								if (!self.scanFound[item.widget_type]) {
									self.scanFound[item.widget_type] = item;
									var html = '<div class="wooce-scan-item"><span class="dashicons dashicons-yes-alt"></span> ' + item.label + ' <span style="color:#50575e;">found on ' + item.count + ' page(s)</span></div>';
									$('.wooce-scan-results').append(html);
								}
							});
						}

						if (data.done) {
							self.scanDone = true;
							$('.wooce-scan-status').text('Scan complete! Found ' + Object.keys(self.scanFound).length + ' element type(s).');
							setTimeout(function () {
								self.nextStep();
							}, 800);
						} else {
							self.scanOffset = data.next_offset;
							setTimeout(doBatch, 300);
						}
					},
					error: function () {
						$('.wooce-scan-status').text('Connection error. Retrying...');
						setTimeout(doBatch, 1500);
					}
				});
			};

			doBatch();
		},

		loadABTest: function () {
			var self = this;

			$('.wooce-split-body.before').html('<div style="padding:40px;text-align:center;color:#50575e;">Loading...</div>');
			$('.wooce-split-body.after').html('<div style="padding:40px;text-align:center;color:#50575e;">Loading...</div>');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wooce_wizard_ab_test',
					nonce: wooceData ? wooceData.nonce : ''
				},
				success: function (resp) {
					if (resp.success && resp.data) {
						$('.wooce-split-body.before').html(resp.data.before);
						$('.wooce-split-body.after').html(resp.data.after);
						self.productHtml = true;
					} else {
						$('.wooce-split-body.before').html('<div style="padding:40px;text-align:center;color:#b32d2e;">Could not load product preview.</div>');
						$('.wooce-split-body.after').html('<div style="padding:40px;text-align:center;color:#b32d2e;">Could not load product preview.</div>');
					}
				},
				error: function () {
					$('.wooce-split-body.before').html('<div style="padding:40px;text-align:center;color:#b32d2e;">Connection error.</div>');
					$('.wooce-split-body.after').html('<div style="padding:40px;text-align:center;color:#b32d2e;">Connection error.</div>');
				}
			});
		},

		startConfetti: function () {
			if ($('.wooce-confetti-container').length) {
				return;
			}

			var colors = ['#2271b1', '#46b450', '#f0ad4e', '#b32d2e', '#826eb4', '#00b9eb'];
			var container = $('<div class="wooce-confetti-container"></div>');
			$('body').append(container);

			for (var i = 0; i < 60; i++) {
				var piece = $('<div class="wooce-confetti-piece"></div>');
				var color = colors[Math.floor(Math.random() * colors.length)];
				var left = Math.random() * 100;
				var size = 6 + Math.random() * 8;
				var duration = 2 + Math.random() * 2;
				var delay = Math.random() * 2;
				var drift = -60 + Math.random() * 120;

				piece.css({
					'background': color,
					'left': left + '%',
					'width': size + 'px',
					'height': size * (0.5 + Math.random()) + 'px',
					'--fall-duration': duration + 's',
					'--fall-delay': delay + 's',
					'--drift': drift + 'px',
					'border-radius': Math.random() > 0.5 ? '50%' : '0'
				});

				container.append(piece);
			}

			setTimeout(function () {
				container.remove();
			}, 5000);
		},

		completeOnboarding: function (callback) {
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'wooce_complete_onboarding',
					nonce: wooceData ? wooceData.nonce : '',
					dequeue_core: $('#wooce_dequeue_all').is(':checked') ? 'yes' : 'no',
					dequeue_blocks: $('#wooce_dequeue_all').is(':checked') ? 'yes' : 'no'
				},
				complete: function () {
					if (typeof callback === 'function') {
						callback();
					} else {
						window.location.href = wooceData ? wooceData.siteUrl : '/';
					}
				}
			});
		}
	};

	$(document).ready(function () {
		if ($('.wooce-wizard-wrap').length) {
			wizard.init();
		}
	});

})(jQuery);
