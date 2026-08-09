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

			$(document).on('click', '.eccw-wizard-next', function () {
				self.nextStep();
			});

			$(document).on('click', '.eccw-wizard-prev', function () {
				self.prevStep();
			});

			$(document).on('click', '.eccw-toggle-btn', function () {
				$(this).toggleClass('toggled');
				$('.eccw-split-column.after').toggleClass('eccw-after-toggled');
			});

			$(document).on('change', '#eccw_dequeue_all', function () {
				if (!$(this).is(':checked')) {
					$('.eccw-diagram-overlay.core').addClass('visible');
				} else {
					$('.eccw-diagram-overlay.core').removeClass('visible');
				}
			});

			$(document).on('click', '.eccw-launch-editor', function () {
				self.completeOnboarding(function () {
					window.location.href = $(this).data('editor-url');
				}.bind(this));
			});

			$(document).on('click', '.eccw-launch-settings', function () {
				self.completeOnboarding(function () {
					window.location.href = $(this).data('settings-url');
				}.bind(this));
			});

			$(document).on('submit', '.eccw-email-form', function (e) {
				e.preventDefault();
				var email = $(this).find('input[type="email"]').val();
				if (email) {
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'eccw_save_email',
							email: email,
							nonce: eccwData ? eccwData.nonce : ''
						},
						success: function () {
							$('.eccw-email-thanks').show();
							$('.eccw-email-form').hide();
						}
					});
				}
			});

			$(document).on('click', '.eccw-wizard-skip-link', function (e) {
				e.preventDefault();
				var link = this;
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'eccw_dismiss_onboarding',
						nonce: eccwData ? eccwData.nonce : ''
					},
					complete: function () {
						window.location.href = $(link).attr('href');
					}
				});
			});
		},

		showStep: function (n) {
			this.currentStep = n;
			$('.eccw-wizard-step').removeClass('active');
			$('.eccw-wizard-step[data-step="' + n + '"]').addClass('active');

			$('.eccw-wizard-dot').each(function () {
				var idx = parseInt($(this).data('step'), 10);
				if (idx === n) {
					$(this).addClass('active').removeClass('completed');
				} else if (idx < n) {
					$(this).addClass('completed').removeClass('active');
				} else {
					$(this).removeClass('active completed');
				}
			});

			$('.eccw-wizard-progress-text').text('Step ' + n + ' of ' + this.totalSteps);

			$('.eccw-wizard-prev').css('visibility', n > 1 ? 'visible' : 'hidden');
			$('.eccw-wizard-next').text(n < this.totalSteps ? 'Next →' : 'Finish');

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
					window.location.href = eccwData ? eccwData.siteUrl : '/';
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
						action: 'eccw_scan_progress',
						offset: self.scanOffset,
						nonce: eccwData ? eccwData.nonce : ''
					},
					success: function (resp) {
						if (!resp.success) {
							$('.eccw-scan-status').text('Scan failed. Please try again.');
							return;
						}

						var data = resp.data;

						if (data.found) {
							$.each(data.found, function (i, item) {
								if (!self.scanFound[item.widget_type]) {
									self.scanFound[item.widget_type] = item;
									var html = '<div class="eccw-scan-item"><span class="dashicons dashicons-yes-alt"></span> ' + item.label + ' <span style="color:#50575e;">found on ' + item.count + ' page(s)</span></div>';
									$('.eccw-scan-results').append(html);
								}
							});
						}

						if (data.done) {
							self.scanDone = true;
							$('.eccw-scan-status').text('Scan complete! Found ' + Object.keys(self.scanFound).length + ' element type(s).');
							setTimeout(function () {
								self.nextStep();
							}, 800);
						} else {
							self.scanOffset = data.next_offset;
							setTimeout(doBatch, 300);
						}
					},
					error: function () {
						$('.eccw-scan-status').text('Connection error. Retrying...');
						setTimeout(doBatch, 1500);
					}
				});
			};

			doBatch();
		},

		loadABTest: function () {
			var self = this;

			$('.eccw-split-body.before').html('<div style="padding:40px;text-align:center;color:#50575e;">Loading...</div>');
			$('.eccw-split-body.after').html('<div style="padding:40px;text-align:center;color:#50575e;">Loading...</div>');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'eccw_wizard_ab_test',
					nonce: eccwData ? eccwData.nonce : ''
				},
				success: function (resp) {
					if (resp.success && resp.data) {
						$('.eccw-split-body.before').html(resp.data.before);
						$('.eccw-split-body.after').html(resp.data.after);
						self.productHtml = true;
					} else {
						$('.eccw-split-body.before').html('<div style="padding:40px;text-align:center;color:#b32d2e;">Could not load product preview.</div>');
						$('.eccw-split-body.after').html('<div style="padding:40px;text-align:center;color:#b32d2e;">Could not load product preview.</div>');
					}
				},
				error: function () {
					$('.eccw-split-body.before').html('<div style="padding:40px;text-align:center;color:#b32d2e;">Connection error.</div>');
					$('.eccw-split-body.after').html('<div style="padding:40px;text-align:center;color:#b32d2e;">Connection error.</div>');
				}
			});
		},

		startConfetti: function () {
			if ($('.eccw-confetti-container').length) {
				return;
			}

			var colors = ['#2271b1', '#46b450', '#f0ad4e', '#b32d2e', '#826eb4', '#00b9eb'];
			var container = $('<div class="eccw-confetti-container"></div>');
			$('body').append(container);

			for (var i = 0; i < 60; i++) {
				var piece = $('<div class="eccw-confetti-piece"></div>');
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
					action: 'eccw_complete_onboarding',
					nonce: eccwData ? eccwData.nonce : '',
					dequeue_blocks: $('#eccw_dequeue_all').is(':checked') ? 'yes' : 'no'
				},
				complete: function () {
					if (typeof callback === 'function') {
						callback();
					} else {
						window.location.href = eccwData ? eccwData.siteUrl : '/';
					}
				}
			});
		}
	};

	$(document).ready(function () {
		if ($('.eccw-wizard-wrap').length) {
			wizard.init();
		}
	});

})(jQuery);
