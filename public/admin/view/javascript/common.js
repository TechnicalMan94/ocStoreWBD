function getURLVar(key) {
	var value = [];

	var query = String(document.location).split('?');

	if (query[1]) {
		var part = query[1].split('&');

		for (i = 0; i < part.length; i++) {
			var data = part[i].split('=');

			if (data[0] && data[1]) {
				value[data[0]] = data[1];
			}
		}

		if (value[key]) {
			return value[key];
		} else {
			return '';
		}
	}
}

(function($) {
	if (!$ || typeof bootstrap === 'undefined') {
		return;
	}

	function pluginBridge(name, Constructor) {
		$.fn[name] = function(option) {
			return this.each(function() {
				var instance = Constructor.getOrCreateInstance(this, typeof option === 'object' ? option : {});

				if (typeof option === 'string') {
					var method = option === 'destroy' ? 'dispose' : option;

					if (typeof instance[method] === 'function') {
						instance[method]();
					}
				}
			});
		};
	}

	pluginBridge('tooltip', bootstrap.Tooltip);
	pluginBridge('popover', bootstrap.Popover);
	pluginBridge('modal', bootstrap.Modal);
	pluginBridge('collapse', bootstrap.Collapse);
	pluginBridge('tab', bootstrap.Tab);
	pluginBridge('dropdown', bootstrap.Dropdown);

	$.fn.modal = function(option) {
		return this.each(function() {
			var instance = bootstrap.Modal.getOrCreateInstance(this, typeof option === 'object' ? option : {});

			if (typeof option === 'string') {
				var method = option === 'destroy' ? 'dispose' : option;

				if (typeof instance[method] === 'function') {
					instance[method]();
				}
			} else if (!option || option.show !== false) {
				instance.show();
			}
		});
	};

	$.fn.button = function(action) {
		return this.each(function() {
			var $button = $(this);

			if (action === 'loading') {
				$button.data('reset-text', $button.html());
				$button.html($button.attr('data-loading-text') || $button.data('loading-text') || $button.html()).prop('disabled', true);
			}

			if (action === 'reset') {
				$button.html($button.data('reset-text') || $button.html()).prop('disabled', false);
			}
		});
	};

	function normalizeLegacyIcons(context) {
		var icons = {
			'fa-dashboard': 'bi-speedometer2',
			'fa-tags': 'bi-tags',
			'fa-book': 'bi-book',
			'fa-puzzle-piece': 'bi-puzzle',
			'fa-television': 'bi-display',
			'fa-shopping-cart': 'bi-cart',
			'fa-user': 'bi-person',
			'fa-share-alt': 'bi-share',
			'fa-cog': 'bi-gear',
			'fa-bar-chart': 'bi-bar-chart'
		};

		$('.fa', context || document).each(function() {
			var element = this;

			$.each(icons, function(oldClass, newClass) {
				if ($(element).hasClass(oldClass)) {
					$(element).removeClass('fa ' + oldClass + ' fw').addClass('bi ' + newClass);
				}
			});
		});
	}

	$.fn.datetimepicker = function(option) {
		if (typeof flatpickr === 'undefined') {
			return this;
		}

		return this.each(function() {
			var $element = $(this);
			var format = option && option.format ? option.format : $element.find('input').attr('data-date-format') || $element.attr('data-date-format') || 'YYYY-MM-DD';
			var enableTime = /H|h|m/.test(format);
			var noCalendar = option && option.pickDate === false;
			var dateFormat = format
				.replace('YYYY', 'Y')
				.replace('YY', 'y')
				.replace('DD', 'd')
				.replace('D', 'j')
				.replace('MM', 'm')
				.replace('M', 'n')
				.replace('HH', 'H')
				.replace('H', 'H')
				.replace('mm', 'i');
			var input = $element.is('input') ? this : $element.find('input').get(0);
			var locale = document.documentElement.lang || 'en';
			var config = {
				allowInput: true,
				dateFormat: noCalendar ? 'H:i' : dateFormat,
				enableTime: enableTime || noCalendar,
				noCalendar: noCalendar,
				time_24hr: true
			};

			if (flatpickr.l10ns && flatpickr.l10ns[locale]) {
				config.locale = locale;
			}

			if (input) {
				flatpickr(input, config);
			}
		});
	};

	window.normalizeLegacyAdminIcons = normalizeLegacyIcons;
})(window.jQuery);

$(document).ready(function() {
	window.normalizeLegacyAdminIcons();
	$('[data-original-title]').each(function() {
		if (!$(this).attr('title')) {
			$(this).attr('title', $(this).attr('data-original-title'));
		}
	});
	$('.dropdown-menu-left').removeClass('dropdown-menu-left').addClass('dropdown-menu-start');
	$('.dropdown-menu-right').removeClass('dropdown-menu-right').addClass('dropdown-menu-end');
	$('.breadcrumb > li').addClass('breadcrumb-item');
	$('.nav-tabs > li').addClass('nav-item');
	$('.nav-tabs > li > a').addClass('nav-link').each(function() {
		if ($(this).parent().hasClass('active')) {
			$(this).addClass('active');
		}
	});
	$('.collapse.in').removeClass('in').addClass('show');

	//Form Submit for IE Browser
	$('button[type=\'submit\']').on('click', function() {
		$("form[id*='form-']").submit();
	});

	// Highlight any found errors
	$('.text-danger').each(function() {
		var element = $(this).parent().parent();

		if (element.hasClass('form-group')) {
			element.addClass('has-error');
		}
	});

	// tooltips on hover
	$('[data-bs-toggle=\'tooltip\']').tooltip({container: 'body', html: true});

	// Makes tooltips work on ajax generated content
	$(document).ajaxStop(function() {
		window.normalizeLegacyAdminIcons();
		$('[data-original-title]').each(function() {
			if (!$(this).attr('title')) {
				$(this).attr('title', $(this).attr('data-original-title'));
			}
		});
		$('[data-bs-toggle=\'tooltip\']').tooltip({container: 'body'});
	});

	// https://github.com/opencart/opencart/issues/2595
	$.event.special.remove = {
		remove: function(o) {
			if (o.handler) {
				o.handler.apply(this, arguments);
			}
		}
	}
	
	// tooltip remove
	$('[data-bs-toggle=\'tooltip\']').on('remove', function() {
		$(this).tooltip('destroy');
	});

	// Tooltip remove fixed
	$(document).on('click', '[data-bs-toggle=\'tooltip\']', function(e) {
		$('body > .tooltip').remove();
	});
	
	$('#button-menu').on('click', function(e) {
		e.preventDefault();
		
		$('#column-left').toggleClass('active');
	});

	// Set last page opened on the menu
	$('#menu a[href]').on('click', function() {
		sessionStorage.setItem('menu', $(this).attr('href'));
	});

	if (!sessionStorage.getItem('menu')) {
		$('#menu #dashboard').addClass('active');
	} else {
		// Sets active and open to selected page in the left column menu.
		$('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parent().addClass('active');
	}
	
	$('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parents('li > a').removeClass('collapsed');
	
	$('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parents('ul').addClass('show');
	
	$('#menu a[href=\'' + sessionStorage.getItem('menu') + '\']').parents('li').addClass('active');
	
	// Image Manager
	$(document).on('click', 'a[data-oc-toggle=\'image\']', function(e) {
		var $element = $(this);
		var popover = bootstrap.Popover.getInstance(this);

		e.preventDefault();

		// destroy all image popovers
		$('a[data-oc-toggle="image"]').popover('destroy');

		// remove flickering (do not re-add popover when clicking for removal)
		if (popover) {
			return;
		}

		$element.popover({
			html: true,
			placement: 'right',
			trigger: 'manual',
			content: function() {
				return '<button type="button" id="button-image" class="btn btn-primary"><i class="bi bi-pencil-square"></i></button> <button type="button" id="button-clear" class="btn btn-danger"><i class="bi bi-trash"></i></button>';
			}
		});

		$element.popover('show');

		$('#button-image').on('click', function() {
			var $button = $(this);
			var $icon   = $button.find('> i');

			$('#modal-image').remove();

			$.ajax({
				url: 'index.php?route=common/filemanager&user_token=' + getURLVar('user_token') + '&target=' + $element.parent().find('input').attr('id') + '&thumb=' + $element.attr('id'),
				dataType: 'html',
				beforeSend: function() {
					$button.prop('disabled', true);
					if ($icon.length) {
						$icon.attr('class', 'bi bi-arrow-clockwise fa-spin');
					}
				},
				complete: function() {
					$button.prop('disabled', false);

					if ($icon.length) {
						$icon.attr('class', 'bi bi-pencil-square');
					}
				},
				success: function(html) {
					$('body').append('<div id="modal-image" class="modal">' + html + '</div>');

					bootstrap.Modal.getOrCreateInstance(document.getElementById('modal-image')).show();
				}
			});

			$element.popover('destroy');
		});

		$('#button-clear').on('click', function() {
			$element.find('img').attr('src', $element.find('img').attr('data-placeholder'));

			$element.parent().find('input').val('');

			$element.popover('destroy');
		});
	});
});

// Autocomplete */
(function($) {
	$.fn.autocomplete = function(option) {
		return this.each(function() {
			var $this = $(this);
			var $dropdown = $('<ul class="dropdown-menu" />');

			this.timer = null;
			this.items = [];

			$.extend(this, option);

			$this.attr('autocomplete', 'off');

			// Focus
			$this.on('focus', function() {
				this.request();
			});

			// Blur
			$this.on('blur', function() {
				setTimeout(function(object) {
					object.hide();
				}, 200, this);
			});

			// Keydown
			$this.on('keydown', function(event) {
				switch(event.keyCode) {
					case 27: // escape
						this.hide();
						break;
					default:
						this.request();
						break;
				}
			});

			// Click
			this.click = function(event) {
				event.preventDefault();

				var value = $(event.target).parent().attr('data-value');

				if (value && this.items[value]) {
					this.select(this.items[value]);
				}
			}

			// Show
			this.show = function() {
				var pos = $this.position();

				$dropdown.css({
					top: pos.top + $this.outerHeight(),
					left: pos.left
				});

				$dropdown.show();
			}

			// Hide
			this.hide = function() {
				$dropdown.hide();
			}

			// Request
			this.request = function() {
				clearTimeout(this.timer);

				this.timer = setTimeout(function(object) {
					object.source($(object).val(), $.proxy(object.response, object));
				}, 200, this);
			}

			// Response
			this.response = function(json) {
				var html = '';
				var category = {};
				var name;
				var i = 0, j = 0;

				if (json.length) {
					for (i = 0; i < json.length; i++) {
						// update element items
						this.items[json[i]['value']] = json[i];

						if (!json[i]['category']) {
							// ungrouped items
							html += '<li data-value="' + json[i]['value'] + '"><a href="#" class="dropdown-item">' + json[i]['label'] + '</a></li>';
						} else {
							// grouped items
							name = json[i]['category'];
							if (!category[name]) {
								category[name] = [];
							}

							category[name].push(json[i]);
						}
					}

					for (name in category) {
						html += '<li class="dropdown-header">' + name + '</li>';

						for (j = 0; j < category[name].length; j++) {
							html += '<li data-value="' + category[name][j]['value'] + '"><a href="#" class="dropdown-item ps-4">' + category[name][j]['label'] + '</a></li>';
						}
					}
				}

				if (html) {
					this.show();
				} else {
					this.hide();
				}

				$dropdown.html(html);
			}

			$dropdown.on('click', '> li > a', $.proxy(this.click, this));
			$this.after($dropdown);
		});
	}
})(window.jQuery);
