/**
 * Gravity Forms Tripleseat Add-On — UTM / GCLID capture.
 *
 * Reads marketing parameters from the URL on every page load and persists them in a
 * first-party cookie (last-touch). On a later page load the add-on's server-side
 * `gform_field_value_<param>` filters read this cookie to populate the matching hidden
 * Gravity Forms fields, so campaign data survives even when the form is several clicks
 * from the landing page. Config is injected via wp_localize_script as `gfTripleseatUTM`.
 */
(function () {
	'use strict';

	var config = window.gfTripleseatUTM || {};
	var cookieName = config.cookie || 'gf_ts_utm';
	var params = config.params || [
		'utm_source',
		'utm_medium',
		'utm_campaign',
		'utm_term',
		'utm_content',
		'gclid'
	];
	var days = parseInt(config.days, 10) || 30;

	function readCookie() {
		var match = document.cookie.match(
			new RegExp('(?:^|; )' + cookieName.replace(/([.*+?^${}()|[\]\\])/g, '\\$1') + '=([^;]*)')
		);
		if (!match) {
			return {};
		}
		try {
			return JSON.parse(decodeURIComponent(match[1])) || {};
		} catch (e) {
			return {};
		}
	}

	function writeCookie(data) {
		var expires = new Date();
		expires.setTime(expires.getTime() + days * 24 * 60 * 60 * 1000);
		var secure = window.location.protocol === 'https:' ? '; secure' : '';
		document.cookie =
			cookieName +
			'=' +
			encodeURIComponent(JSON.stringify(data)) +
			'; expires=' +
			expires.toUTCString() +
			'; path=/; samesite=lax' +
			secure;
	}

	function captureFromUrl() {
		if (!window.location.search) {
			return;
		}

		var query = new URLSearchParams(window.location.search);
		var stored = readCookie();
		var changed = false;

		params.forEach(function (param) {
			var value = query.get(param);
			if (value !== null && value !== '') {
				stored[param] = value;
				changed = true;
			}
		});

		if (changed) {
			writeCookie(stored);
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', captureFromUrl);
	} else {
		captureFromUrl();
	}
})();
