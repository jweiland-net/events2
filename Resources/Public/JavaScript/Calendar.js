/**
 * JavaScript for plugin events2_calendar
 *
 * Initialize Events2 Calendar
 *
 * @param $element
 * @param environment contains settings, current PageId, extConf and current tt_content record
 * @constructor
 */
function Events2Calendar ($element, environment) {
  let me = this;

  // 2016-10-24 will be 23.10.2016T22:00:00Z. getDate() returns: 24
  me.currentDate = new Date(environment.year + '-' + environment.month + '-' + environment.day + 'T00:00:00');
  me.environment = environment;
  me.allDays = [];
  me.highlightedDays = [];

  /**
   * Get current URL with additional parameters
   *
   * @return string
   */
  me.getCurrentUrl = function () {
    if (window.location.origin) {
      return window.location.origin;
    } else {
      return window.location.protocol + '//' + window.location.host;
    }
  };

  /**
   * get days for month
   * this starts an ajax call to the server and make them globally available
   *
   * @param {object} picker
   * @param {int} month
   * @param {int} year
   * @param {string} storagePages
   * @param {string} categories
   * @return void
   */
  me.fillPickerWithDaysOfMonth = function (picker, month, year, storagePages, categories) {
    let additionalParameters = {
      'month': month + 1,
      'year': year,
      'categories': categories,
      'storagePages': storagePages
    };

    fetch(me.getCurrentUrl(), {
      headers: {
        'Content-Type': 'application/json',
        'ext-events2': 'getDaysForMonth'
      },
      method: 'POST',
      body: JSON.stringify(additionalParameters)
    }).then(response => {
      if (response.ok && response.status === 200) {
        return response.json();
      }
      return Promise.reject(response);
    }).then(days => {
      // make global available. Needed for event render:day
      me.allDays = days;
      me.highlightedDays = Array.from(me.allDays, (day) => day.dayOfMonth);
      picker.setHighlightedDays(me.getHighlightedDays(days, month, year));
    }).catch(error => {
      console.warn('Request error', error);
    });
  };

  /**
   * Get highlighted days which will be clickable for detailed list-view.
   * No holidays will be added
   *
   * @param {object} days
   * @param {int} month
   * @param {int} year
   * @returns array
   */
  me.getHighlightedDays = function (days, month, year) {
    let highlightedDays = [];

    days.forEach(day => {
      if (!day.isHoliday) {
        highlightedDays.push(new Date(year, month, day.dayOfMonth, 0, 0, 0));
      }
    });

    return highlightedDays;
  };

  me.initializeDatePicker = function ($element) {
    let startDate = new Date(
      parseInt(me.environment.year),
      parseInt(me.environment.month) - 1,
      parseInt(me.environment.day), 0, 0, 0
    );

    let picker = new Litepicker({
      element: $element,
      inlineMode: true,
      startDate: startDate,
      lang: 'de-DE',
      format: 'DD.MM.YYYY'
    });

    picker.on('change:month', (date, calendarIdx) => {
      me.fillPickerWithDaysOfMonth(
        picker,
        date.getMonth(),
        date.getFullYear(),
        me.environment.storagePids,
        me.environment.settings.categories
      );
    });

    picker.on('before:show', (el) => {
      me.fillPickerWithDaysOfMonth(
        picker,
        me.currentDate.getMonth(),
        me.currentDate.getFullYear(),
        me.environment.storagePids,
        me.environment.settings.categories
      );
    });

    picker.on('preselect', (date1, date2) => {
      me.getUriForDayAndRedirect(date1);
    });

    picker.on('render:day', (dayElement, date) => {
      if (me.allDays !== []) {
        me.allDays.forEach(day => {
          if (day.dayOfMonth === parseInt(dayElement.innerHTML)) {
            day.additionalClasses.forEach(className => {
              dayElement.classList.add(className);
            });
          }
        });
      }
    });

    // In case of showInline either "show" nor "render" event will be called. Initialize them manually
    picker.hide();
    picker.show();
  };

  me.isInHighlightedDays = function (date) {
    let checkDay = date.getDate();
    return me.highlightedDays.includes(checkDay);
  };

  me.getUriForDayAndRedirect = function (date) {
    if (me.isInHighlightedDays(date)) {
      let additionalParameters = {
        'day': date.getDate(),
        'month': (date.getMonth() + 1),
        'year': date.getFullYear(),
        'pidOfListPage': me.environment.pidOfListPage
      };

      fetch(me.getCurrentUrl(), {
        headers: {
          'Content-Type': 'application/json',
          'ext-events2': 'getUriForDay'
        },
        method: 'POST',
        body: JSON.stringify(additionalParameters)
      }).then(response => {
        if (response.ok && response.status === 200) {
          return response.json();
        }
        return Promise.reject(response);
      }).then(data => {
        if (data.uri !== '') {
          console.log('Redirect to: ' + data.uri);
          window.location.href = data.uri;
        }
      }).catch(error => {
        console.warn('Request error', error);
      });
    }
  };

  me.initializeDatePicker($element);
}

Array.from(document.querySelectorAll('div.events2calendar')).forEach($element => {
  new Events2Calendar(
    $element,
    JSON.parse($element.getAttribute('data-environment'))
  );
});
