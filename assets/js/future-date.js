/**
 * Gravity Forms Tripleseat Add-On — future-only date fields.
 *
 * Uses Gravity Forms' official `gform_datepicker_options_pre_init` filter to set the
 * datepicker's minimum date to today for any Date field carrying the `gf-future-date`
 * CSS class. Server-side validation is the real guard; this is UX only.
 */
(function () {
	'use strict';

	function register() {
		if (typeof gform === 'undefined' || typeof gform.addFilter !== 'function') {
			return false;
		}

		var cssClass = (window.gfTripleseatDate && window.gfTripleseatDate.cssClass) || 'gf-future-date';

		gform.addFilter('gform_datepicker_options_pre_init', function (optionsObj, formId, fieldId, $element) {
			var optedIn = false;

			if ($element && typeof $element.closest === 'function') {
				optedIn = $element.closest('.gfield').hasClass(cssClass);
			} else {
				var container = document.getElementById('field_' + formId + '_' + fieldId);
				optedIn = !!container && container.classList.contains(cssClass);
			}

			if (optedIn) {
				optionsObj.minDate = 0; // today; disallow past dates
			}

			return optionsObj;
		});

		return true;
	}

	if (!register()) {
		document.addEventListener('DOMContentLoaded', register);
	}
})();
