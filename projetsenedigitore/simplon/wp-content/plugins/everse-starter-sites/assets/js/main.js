jQuery(document).ready(function ($) {
	var EverseStarterSites = function (container) {
		this.container = $(container);
		this.itemContainer = this.container.closest(".theme-wrap");
		this.selectedImportID = container.val();

		this.initialize();
	};

	EverseStarterSites.prototype = {
		xhr: {},
		loader: $('<span class="spinner is-active"></span>'),
		hideButton: $(".ess-import-button"),

		initialize: function () {
			this._display_popup();
		},

		_display_popup: function () {
			var $self = this;

			var popupContainer = $("#ess-demo-popup-content");

			var demoName = $self.itemContainer.find(".theme-name").text();

			var popupOptions = $.extend({
				dialogClass: "wp-dialog ess-demo-popup-content",
				resizable: false,
				height: "350",
				width: "600",
				modal: true,
				title: demoName,
				buttons: [
					{
						text: everse_starter_sites.strings.cancel,
						click: function () {
							$(this).dialog("close");
						},
					},
					{
						text: everse_starter_sites.strings.yes,
						class: "button  button-primary ess-import-button",
						click: function () {
							$(this).dialog("close");
							$self.startContentImport(
								$self.selectedImportID,
								$self.itemContainer
							);
						},
					},
				],
			});

			popupContainer.dialog(popupOptions);

			this.ajaxCall($self.selectedImportID, popupContainer);
		},

		startContentImport: function (demo_id, itemContainer) {
			var that = this;

			itemContainer
				.closest(".theme-browser.rendered")
				.find(".theme-wrap")
				.hide();

			itemContainer.animate(
				{
					opacity: 0,
				},
				500,
				"swing",
				function () {
					itemContainer.animate(
						{
							opacity: 1,
						},
						500
					);
				}
			);

			itemContainer.show();

			itemContainer.find(".ss-import-plugin-data").remove();

			// Prepare data for the AJAX call
			var data = new FormData();
			data.append("action", "ESS_import_demo_data");
			data.append("security", everse_starter_sites.ajax_nonce);
			data.append("selected", demo_id);

			that.ImportStart(data);
		},

		ImportStart: function (data) {
			var that = this;

			$.ajax({
				method: "POST",
				url: everse_starter_sites.ajax_url,
				data: data,
				processData: false,
				contentType: false,
				beforeSend: function () {
					$(".ess_ajax-loader").show();
				},
			})
				.done(function (response) {
					if (
						"undefined" !== typeof response.status &&
						"pluginsInstalled" === response.status
					) {
						let $FormData = new FormData();
						$FormData.append("action", "ESS_import_content");
						$FormData.append("security", everse_starter_sites.ajax_nonce);
						$FormData.append("selected", data.get("selected"));
						that.ImportStart($FormData);
					} else if (
						"undefined" !== typeof response.status &&
						"newAJAX" === response.status
					) {
						that.ImportStart(data);
					} else if (
						"undefined" !== typeof response.status &&
						"customizerAJAX" === response.status
					) {
						var $FormData = new FormData();
						$FormData.append("action", "ess_import_customizer_data");
						$FormData.append("security", everse_starter_sites.ajax_nonce);

						that.ImportStart($FormData);
					} else if (
						"undefined" !== typeof response.status &&
						"afterAllImportAJAX" === response.status
					) {
						var newData = new FormData();
						newData.append("action", "ess_after_import_data");
						newData.append("security", everse_starter_sites.ajax_nonce);

						that.ImportStart(newData);
					} else if ("undefined" !== typeof response.message) {
						$(".ess-ajax-response").append("<p>" + response.message + "</p>");
						$(".ess_ajax-loader").hide();
						$(document).scrollTop($("#wpwrap").offset().top);
					} else {
						$(".ess-ajax-response").append(
							'<div class="notice  notice-error  is-dismissible"><p>' +
								response +
								"</p></div>"
						);
						$(".ess_ajax-loader").hide();
						$(document).scrollTop($("#wpwrap").offset().top);
					}
				})
				.fail(function (error) {
					console.log(error);
					$(".ess-ajax-response").append(
						'<div class="notice  notice-error  is-dismissible"><p>Error: ' +
							error.statusText +
							" (" +
							error.status +
							")" +
							"</p></div>"
					);
					$(".ess_ajax-loader").hide();
					$(document).scrollTop($("#wpwrap").offset().top);
				});
		},

		ajaxCall: function (demo_id, popupContainer) {
			setTimeout(function () {
				$(".button.button-primary").attr("disabled", "disabled");
			}, 100);

			this.hideButton.addClass("disabled");

			popupContainer.html(this.loader);

			if (
				"object" === typeof this.xhr &&
				"function" === typeof this.xhr.state &&
				"pending" === this.xhr.state()
			) {
				return;
			}

			var that = this;

			var data = {
				action: "ess_ajax_get_demo_data",
				ajax_nonce: everse_starter_sites.ajax_nonce,
				demo_name: demo_id,
			};

			that.xhr = $.ajax({
				url: everse_starter_sites.ajax_url,
				data: data,
				type: "POST",
				beforeSend: function (xhr) {
					that.hideButton.addClass("disabled not-click-able");
				},
				success: function (response) {
					setTimeout(function () {
						var currentPreivewImage =
							response.preview_image || everse_starter_sites.theme_screenshot;
						var proLabel = true;

						previewImage =
							'<div class="ess-image-container"><img src="' +
							currentPreivewImage +
							'" alt="' +
							response.file_name +
							'"></div>';

						popupContainer.html(previewImage);

						if (
							response.is_learndash &&
							typeof response.is_learndash != "undefined"
						) {
							$(".ess-image-container").after(
								'<div id="required-third-party-plugins-wrapper"></div>'
							);
							$("#required-third-party-plugins-wrapper").append(
								everse_starter_sites.strings.learndash_text
							);
							$("#required-third-party-plugins-wrapper").append(
								'<ul id="required-trird-party-plugins-list"></ul>'
							);

							for (var i = 0; i < response.learndash_plugins.length; i++) {
								$("#required-trird-party-plugins-list").append(
									'<li class="whoWrap" data-name="' +
										response.learndash_plugins[i].name +
										'"><a href="' +
										response.learndash_plugins[i].url +
										'" target="_blank">' +
										response.learndash_plugins[i].name +
										"</a></li>"
								);
								$("#required-trird-party-plugins-list").append("</li>");
							}
						}

						if (response.is_pro && proLabel) {
							popupContainer
								.prev()
								.find(".ui-dialog-title")
								.append(
									'<span class="theme-pro-label">' +
										everse_starter_sites.strings.pro_label +
										"</span>"
								);
							proLabel = false;
						}

						if (response.is_pro && !response.is_activated) {
							popupContainer.dialog({
								height: "auto",
								buttons: [
									{
										text: everse_starter_sites.strings.cancel,
										click: function () {
											$(this).dialog("close");
										},
									},
									{
										text: everse_starter_sites.strings.proBtnText,
										class: "button  button-primary ess-pro-button",
										click: function () {
											$(this).dialog("close");
											window.open(
												everse_starter_sites.strings.proBtnURL,
												"_blank"
											);
										},
									},
								],
							});

							$(".ess-image-container").after(
								'<div id="required-plugins-wrapper"></div>'
							);
							$("#required-plugins-wrapper").append(
								"<p>" + everse_starter_sites.strings.pro_heading + "</p>"
							);

							setTimeout(function () {
								$(".button.button-primary").removeAttr("disabled", "disabled");
							}, 100);
						} else if (response.is_pro && response.is_activated) {
							popupContainer.dialog({
								height: "auto",
								buttons: [
									{
										text: everse_starter_sites.strings.cancel,
										click: function () {
											$(this).dialog("close");
										},
									},
									{
										text: everse_starter_sites.strings.yes,
										class: "button  button-primary ess-import-button",
										click: function () {
											$(this).dialog("close");
											that.startContentImport(
												that.selectedImportID,
												that.itemContainer
											);
										},
									},
								],
							});

							if (response.required_plugins) {
								that.ajax_plugins_list(response.required_plugins);
							}

							setTimeout(function () {
								$(".button.button-primary").removeAttr("disabled", "disabled");
							}, 100);
						} else {
							if (response.required_plugins) {
								that.ajax_plugins_list(response.required_plugins);
							}

							popupContainer.dialog({
								height: "auto",
							});

							setTimeout(function () {
								$(".button.button-primary").removeAttr("disabled", "disabled");
							}, 100);
						}
					}, 1000);
				},

				error: function error(response) {
					popupContainer.html(response);
					setTimeout(function () {
						$(".button.button-primary").removeAttr("disabled", "disabled");
					}, 100);
				},
			});
		},

		ajax_plugins_list: function (response) {
			var that = this;

			if (typeof response == "object" && typeof response != "undefined") {
				$(".ess-image-container").after(
					'<div id="required-plugins-wrapper"></div>'
				);
				$("#required-plugins-wrapper").append(
					everse_starter_sites.strings.plugins_title
				);

				$("#required-plugins-wrapper").append(
					'<ul id="required-plugins-list"></ul>'
				);

				for (var j in response) {
					$("#required-plugins-list").append(
						'<li class="whoWrap" data-name="' +
							response[j].name +
							'" data-slug="' +
							response[j].slug +
							'">' +
							response[j].name +
							" - <span>" +
							response[j].status +
							"</span></li>"
					);
					$("#required-plugins-list").append("</li>");
				}

				$(".ess-import-button").removeAttr("disabled", "disabled");
			}
		},
	};

	$(".ss-import-plugin-data").on("click", function () {
		new EverseStarterSites($(this));
	});

	var ESSFilterCategories = function () {
		this.initialize();
	};

	ESSFilterCategories.prototype = {
		initialize: function () {
			this.filterCategories();
			this.SearchFilter();
		},

		SearchFilter: function () {
			$(".ess-search-input").on("keyup", function (event) {
				if (0 < $(this).val().length) {
					$(".ess-item-container").find(".theme-wrap").hide();
					$(".ess-item-container")
						.find(
							'.theme-wrap[data-name*="' + $(this).val().toLowerCase() + '"]'
						)
						.show();
				} else {
					$(".ess-item-container").find(".theme-wrap").show();
				}
			});
		},

		filterCategories: function () {
			var $self = this,
				$items = $(".ess-item-container").find(".theme-wrap"),
				fadeinClass = "ess-is-fadein";

			$(".ess-nav-link").on("click", function (event) {
				event.preventDefault();

				// Remove 'active' class from the previous nav list items.
				$(this).parent().siblings().removeClass("active");

				// Add the 'active' class to this nav list item.
				$(this).parent().addClass("active");

				var category = $(this).attr("href").split("#")[1];

				// show/hide the right items, based on category selected
				var $container = $(".ess-item-container");
				$container.css("min-width", $container.outerHeight());

				var promise = $self.animate(category, $items);

				promise.done(function () {
					$container.removeAttr("style");
				});
			});
		},

		fadeOut: function ($items) {
			var $self = this,
				fadeOut = "ess-is-fadeout",
				animationDuration = 200,
				dfd = jQuery.Deferred();

			$items.addClass(fadeOut);

			setTimeout(function () {
				$items.removeClass(fadeOut).hide();
				dfd.resolve();
			}, animationDuration);

			return dfd.promise();
		},

		fadeIn: function (category, dfd, $items) {
			var $self = this,
				fadeinClass = "ess-is-fadein",
				animationDuration = 200,
				filter = category ? '[data-categories*="' + category + '"]' : "div";

			if ("all" === category) {
				filter = "div";
			}

			$items.filter(filter).show().addClass("ess-is-fadein");

			setTimeout(function () {
				$items.removeClass(fadeinClass);

				dfd.resolve();
			}, animationDuration);
		},

		animate: function (category, $items) {
			var $self = this,
				dfd = jQuery.Deferred();

			var promise = $self.fadeOut($items);

			promise.done(function () {
				$self.fadeIn(category, dfd, $items);
			});

			return dfd;
		},
	};

	new ESSFilterCategories($(this));
});
