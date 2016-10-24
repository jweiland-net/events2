/**
 * Initialize Events2 Calendar
 *
 * @param $element
 * @param environment contains settings, current PageId, extConf and current tt_content record
 * @constructor
 */
function Events2Calendar($element, environment) {
	// 2016-10-24 will be 23.10.2016T22:00:00Z. getDate() returns: 24
	this.currentDate = new Date(environment.year + "-" + environment.month + "-" + environment.day + "T00:00:00");
	this.environment = environment;
	this.siteUrl = environment.siteUrl + "index.php";
	this.categories = environment.settings.categories;

	// getMonth (0-11)
	var days = this.getDaysForMonth(
		this.currentDate.getMonth() + 1,
		this.currentDate.getFullYear(),
		environment.storagePids,
		environment.pidOfListPage
	);

	this.activateDatePicker($element, days);
}

Events2Calendar.prototype.activateDatePicker = function($element, days) {
	var environment = this.environment;
	var getDaysForMonth = this.getDaysForMonth;
	var getProperty = this.getProperty;

	if (environment.settings.includeDeTranslationForCalendar) {
		jQuery.datepicker.setDefaults(jQuery.datepicker.regional["de"]);
	}

	$element.datepicker({
		dateFormat: "dd.mm.yy",
		defaultDate: environment.day + "." + environment.month + "." + environment.year,
		beforeShowDay: function(date) {
			if (days == null) {
				return [false, "", ""];
			}
			if (days.hasOwnProperty(date.getDate())) {
				var title = getProperty(days, date, "title");
				var className = getProperty(days, date, "class");
				if (title) {
					return [true, className, title];
				} else {
					return [false, className, ""];
				}
			} else {
				return [false, "", ""];
			}
		},
		onSelect: function(dateText, inst) {
			if (days != null && days.hasOwnProperty(inst.currentDay)) {
				window.location.href = days[inst.currentDay][0]["uri"];
			}
		},
		onChangeMonthYear: function(year, month, inst) {
			// month (1-12)
			days = getDaysForMonth(
				month,
				year,
				environment.storagePids,
				environment.pidOfListPage
			);
		}
	});
};

/**
 * Get property of event record of a given date
 *
 * @param days
 * @param date
 * @param property
 *
 * @return string
 */
Events2Calendar.prototype.getProperty = function(days, date, property) {
	var value = '';
	for (var i = 0; i < days[date.getDate()].length; i++) {
		if (days[date.getDate()][i].hasOwnProperty(property)) {
			value = days[date.getDate()][i][property];
			break;
		}
	}
	return value;
};

/**
 * get days for month
 * this starts an ajax call to the server and make them globally available
 *
 * @param month
 * @param year
 * @param storagePids
 * @param pidOfListPage
 * @return array
 */
Events2Calendar.prototype.getDaysForMonth = function(month, year, storagePids, pidOfListPage) {
	var days = null;

	jQuery.ajax({
		type: 'GET',
		url: this.siteUrl,
		async: false,
		dataType: 'json',
		data: {
			eID: 'events2findDaysForMonth',
			tx_events2_events: {
				arguments: {
					categories: this.categories,
					month: month,
					year: year,
					storagePids: storagePids,
					pidOfListPage: pidOfListPage
				}
			}
		},
		success: function(json) {
			days = json;
		},
		error: function(xhr, error) {
			console.log(error);
		}
	});

	return days;
};

var $element;
var environment;
jQuery("div.events2calendar").each(function() {
	$element = jQuery(this);
	var environment = $element.data("environment");
	new Events2Calendar($element, environment);
});
