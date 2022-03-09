/**
 * Initialize Events2 Calendar
 *
 * @param $element
 * @param environment contains settings, current PageId, extConf and current tt_content record
 * @constructor
 */
function Events2Calendar($element, environment) {
    let me = this;

    // 2016-10-24 will be 23.10.2016T22:00:00Z. getDate() returns: 24
    me.currentDate = new Date(environment.year + "-" + environment.month + "-" + environment.day + "T00:00:00");
    me.environment = environment;

    /**
     * get days for month
     * this starts an ajax call to the server and make them globally available
     *
     * @param {object} picker
     * @param {int} month
     * @param {int} year
     * @param {string} storagePids
     * @param {string} categories
     * @param {int} pidOfListPage
     * @return void
     */
    me.fillPickerWithDaysOfMonth = function(picker, month, year, storagePids, categories, pidOfListPage) {
        let additionalParameters = [
            "eID=events2findDaysForMonth",
            "tx_events2_events[arguments][categories]=" + categories,
            "tx_events2_events[arguments][month]=" + (month + 1),
            "tx_events2_events[arguments][year]=" + year,
            "tx_events2_events[arguments][storagePids]=" + storagePids,
            "tx_events2_events[arguments][pidOfListPage]=" + pidOfListPage,
        ];

        fetch(me.getCurrentUrlWithAdditionalParameters(additionalParameters.join("&")), {
            headers: {
                'Content-Type': 'application/json'
            }
        }).then(response => {
            if (response.ok && response.status === 200) {
                return response.json();
            }
            return Promise.reject(response);
        }).then(data => {
            let highlightedDays = [];
            data.forEach(entry => {
                highlightedDays.push(new Date(year, month, entry.dayOfMonth, 0, 0, 0))
            });
            picker.setHighlightedDays(highlightedDays)
        }).catch(error => {
            console.warn('Request error', error);
        });
    };

    me.initializeDatePicker = function($element) {
        let environment = me.environment;

        let startDate = new Date(
            parseInt(environment.year),
            parseInt(environment.month) - 1,
            parseInt(environment.day), 0, 0, 0
        );

        let picker = new Litepicker({
            element: $element,
            inlineMode: true,
            startDate: startDate,
            lang: "de-DE",
            format: "DD.MM.YYYY"
        });

        picker.on("change:month", (date, calendarIdx) => {
            me.fillPickerWithDaysOfMonth(
                picker,
                date.getMonth(),
                date.getFullYear(),
                environment.storagePids,
                environment.settings.categories,
                environment.pidOfListPage
            );
        });

        picker.on("before:show", (el) => {
            me.fillPickerWithDaysOfMonth(
                picker,
                me.currentDate.getMonth(),
                me.currentDate.getFullYear(),
                environment.storagePids,
                environment.settings.categories,
                environment.pidOfListPage
            );
        });

        picker.on('preselect', (date1, date2) => {
            console.log("SELECTED");
        });

        /*picker.on("render:day", (day, date) => {

            console.log(day);
        });*/

        // In case of showInline either show nor render event will be called. Initialize them manually
        picker.hide();
        picker.show();
    };

    /**
     * Get current URL with additional parameters
     *
     * @param {string} additionalParameters
     * @return string
     */
    me.getCurrentUrlWithAdditionalParameters = function (additionalParameters) {
        let href = window.location.href;
        if (href.indexOf("?") > -1) {
            return href + "&" + additionalParameters;
        } else {
            return href + "?" + additionalParameters;
        }
    }

    me.initializeDatePicker($element);
}

Array.from(document.querySelectorAll("div.events2calendar")).forEach($element => {
    new Events2Calendar(
        $element,
        JSON.parse($element.getAttribute('data-environment'))
    );
});
