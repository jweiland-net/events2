/**
 * JavaScript for plugin events2_search and events2_management
 *
 * Initialize Events2 in general
 *
 * @param $element
 * @constructor
 */
let Events2 = function ($element) {
  let me = this;

  me.$element = $element;
  me.selectorCreatePlugin = '.tx-events2-create';
  me.selectorSearchPlugin = '.tx-events2-search';
  me.selectorPluginVariables = '.events2DataElement';
  me.selectorRemainingChars = '.remainingChars';
  me.selectorAutoCompleteLocation = '#autoCompleteLocation';
  me.selectorAutoCompleteLocationHelper = '.autoCompleteLocationHelper';
  me.selectorSearchMainCategory = '.searchMainCategory';
  me.dateFormat = 'DD.MM.YYYY';

  /**
   * Check type of Plugin. Event, Create or Search
   *
   * @returns {string}
   */
  me.getPluginType = function ($element) {
    if ($element.classList.contains('tx-events2-create')) {
      return 'create';
    } else if ($element.classList.contains('tx-events2-search')) {
      return 'search';
    } else {
      return 'event';
    }
  };

  /**
   * Test, if element is of Type "Event Plugin"
   *
   * @returns {boolean}
   */
  me.isEventPlugin = function ($element) {
    return me.getPluginType($element) === 'event';
  };

  /**
   * Test, if element is of Type "Create Plugin"
   *
   * @returns {boolean}
   */
  me.isCreatePlugin = function ($element) {
    return me.getPluginType($element) === 'create';
  };

  /**
   * Test, if element is of Type "Search Plugin"
   *
   * @returns {boolean}
   */
  me.isSearchPlugin = function ($element) {
    return me.getPluginType($element) === 'search';
  };

  /**
   * Test, if there are some textareas in form with remaining chars feature
   *
   * @returns {boolean}
   */
  me.hasRemainingCharsContainer = function () {
    return me.$remainingCharsContainer !== null;
  };

  /**
   * Test, if there is an AutoComplete for location available in template
   *
   * @returns {boolean}
   */
  me.hasAutoCompleteLocation = function () {
    return me.$autoCompleteLocation !== null;
  };

  /**
   * Test, if localization of pluginVariables is initialized
   *
   * @returns {boolean}
   */
  me.isLocalizationInitialized = function () {
    return me.pluginVariables.hasOwnProperty('localization');
  };

  /**
   * Test, if settings of pluginVariables are initialized
   *
   * @returns {boolean}
   */
  me.isSettingsInitialized = function () {
    return me.pluginVariables.hasOwnProperty('settings');
  };

  /**
   * Test, if main category is available in search template
   *
   * @returns {boolean}
   */
  me.hasSearchMainCategory = function () {
    return me.$searchMainCategory !== null;
  };

  /**
   * Initialize possible organizer selectbox in events2 list view
   */
  me.initializeOrganizerFilters = function () {
    let $organizerForm = document.querySelector('form#eventFilter');
    if ($organizerForm !== null) {
      let $organizerSelectBox = $organizerForm.querySelector('select#organizer');
      if ($organizerSelectBox !== null) {
        $organizerSelectBox.addEventListener('change', event => {
          event.preventDefault();
          if (event.target.value === '') {
            window.location.href = $organizerForm.getAttribute('action');
          } else {
            window.location.href = event.target.value;
          }
        });
      }
    }
  };

  /**
   * Initialize DatePicker for input elements with class: addDatePicker
   */
  me.initializeDatePicker = function () {
    document.querySelectorAll('.addDatePicker').forEach($inputWithDatePicker => {
      new Litepicker({
        element: $inputWithDatePicker,
        format: me.dateFormat,
        singleMode: true,
        resetButton: true
      });
    });
  };

  /**
   * Initialize remaining letters for teaser in create form
   */
  me.initializeRemainingLetters = function () {
    if (me.hasRemainingCharsContainer()) {
      if (!me.isLocalizationInitialized()) {
        console.log('Variable localization of pluginVariables is not available. Please check your templates');
      } else if (!me.isSettingsInitialized()) {
        console.log('Variable settings of pluginVariables is not available. Please check your templates');
      } else {
        me.$remainingCharsContainer.forEach($remainingCharsContainer => {
          let $textarea = document.querySelector('#' + $remainingCharsContainer.getAttribute('data-id'));
          $remainingCharsContainer.innerText = me.pluginVariables.localization.remainingText + ': ' + me.pluginVariables.settings.remainingLetters;

          $textarea.addEventListener('keyup', () => {
            let value = $textarea.value;
            let len = value.length;
            let maxLength = me.pluginVariables.settings.remainingLetters;

            $textarea.value = value.substring(0, maxLength);
            $remainingCharsContainer.innerText = me.pluginVariables.localization.remainingText + ': ' + (maxLength - len);
          });
        });
      }
    }
  };

  /**
   * Initialize AutoComplete for location
   */
  me.initializeAutoCompleteForLocation = function () {
    if (me.hasAutoCompleteLocation()) {
      let siteUrl = location.protocol + '//' + location.hostname + (location.port ? ':' + location.port : '');

      /* Yes, the constructor was written in lower case */
      const autoCompleteJS = new autoComplete({
        selector: me.selectorAutoCompleteLocation,
        placeHolder: 'Search for Locations...',
        data: {
          src: async () => {
            try {
              // Loading placeholder text
              me.$autoCompleteLocation.setAttribute('placeholder', 'Loading...');
              // Fetch External Data Source
              const source = await fetch(
                siteUrl, {
                  headers: {
                    'Content-Type': 'application/json',
                    'ext-events2': 'getLocations'
                  },
                  method: 'POST',
                  body: JSON.stringify({
                    events2SearchLocation: autoCompleteJS.input.value,
                  }),
                });
              const locations = await source.json();
              // Post Loading placeholder text
              me.$autoCompleteLocation.setAttribute('placeholder', autoCompleteJS.placeHolder);
              // Returns Fetched data
              return locations;
            } catch (error) {
              return error;
            }
          },
          keys: ['uid', 'label'],
          cache: false,
          resultsList: {
            noResults: false,
            maxResults: 15,
            tabSelect: true
          },
          resultItem: {
            highlight: {
              render: true
            }
          },
          events: {
            input: {
              focus: () => {
                if (autoCompleteJS.input.value.length) {
                  autoCompleteJS.start();
                }
              }
            }
          }
        }
      });

      autoCompleteJS.input.addEventListener('selection', (event) => {
        const feedback = event.detail;
        autoCompleteJS.input.blur();
        autoCompleteJS.input.value = feedback.selection.value[feedback.selection.key];
        me.$autoCompleteLocationHelper.value = feedback.selection.value['uid'];
      });
    }
  };

  /**
   * Initialize sub-categories of search plugin
   */
  me.initializeSubCategoriesForSearch = function () {
    if (me.hasSearchMainCategory()) {
      me.$searchMainCategory.addEventListener('change', () => {
        me.renderSubCategory();
      });
      me.renderSubCategory();
    }
  };

  /**
   * Search for sub-categories, if a main category was selected
   */
  me.renderSubCategory = function () {
    if (document.querySelector('#searchSubCategory').value === '') {
      document.querySelector('#searchSubCategory').setAttribute('disabled', 'disabled');
    }

    if (me.$searchMainCategory.value !== '0') {
      let siteUrl = location.protocol + '//' + location.hostname + (location.port ? ':' + location.port : '');
      fetch(siteUrl + '?events2Category=' + me.$searchMainCategory.value, {
        headers: {
          'Content-Type': 'application/json',
          'ext-events2': 'getSubCategories'
        }
      }).then(response => {
        if (response.ok && response.status === 200) {
          return response.json();
        }
        return Promise.reject(response);
      }).then(categories => {
        me.fillSubCategories(categories);
      }).catch(error => {
        console.warn('Request error', error);
      });
    }
  };

  /**
   * Use categories to fill selector for sub-categories
   *
   * @param categories
   */
  me.fillSubCategories = function (categories) {
    let count = 0;
    let option = false;
    let selected = '';
    let $searchSubCategory = document.querySelector('#searchSubCategory');
    let firstOption = document.createElement('option');

    firstOption.setAttribute('value', '0');
    $searchSubCategory.appendChild(firstOption);

    for (let i = 0; i < categories.length; i++) {
      count++;
      option = document.createElement('option');
      if (
        me.pluginVariables.search.subCategory !== null
        && me.pluginVariables.search.subCategory.uid === parseInt(categories[i].uid)
      ) {
        option.setAttribute('selected', 'selected');
      }
      option.setAttribute('value', categories[i].uid);
      option.innerText = categories[i].label;
      $searchSubCategory.appendChild(option);
    }

    if (count) {
      $searchSubCategory.removeAttribute('disabled');
    }
  };

  me.pluginVariables = JSON.parse(
    $element.querySelector(me.selectorPluginVariables).getAttribute('data-variables')
  );

  me.initializeOrganizerFilters();
  if (me.isCreatePlugin($element)) {
    me.$remainingCharsContainer = $element.querySelectorAll(me.selectorRemainingChars);
    me.$autoCompleteLocation = $element.querySelector(me.selectorAutoCompleteLocation);
    me.$autoCompleteLocationHelper = $element.querySelector(me.selectorAutoCompleteLocationHelper);
    me.initializeRemainingLetters();
    me.initializeDatePicker();
    me.initializeAutoCompleteForLocation();
  } else if (me.isSearchPlugin($element)) {
    me.$searchMainCategory = $element.querySelector(me.selectorSearchMainCategory);
    me.initializeDatePicker();
    me.initializeSubCategoriesForSearch();
  }
};

document.querySelectorAll('.tx-events2').forEach($element => {
  let events2 = new Events2($element);
});
