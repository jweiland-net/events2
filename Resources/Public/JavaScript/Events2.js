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
  me.selectorAutoCompleteLocation = '.autoCompleteLocation';
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
    return !!me.$remainingCharsContainer.length;
  };

  /**
   * Test, if there is an AutoComplete for location available in template
   *
   * @returns {boolean}
   */
  me.hasAutoCompleteLocation = function () {
    return !!me.$autoCompleteLocation.length;
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
    return !!me.$searchMainCategory.length;
  };

  /**
   * Initialize DatePicker for input elements with class: addDatePicker
   */
  me.initializeDatePicker = function () {
    document.querySelectorAll('.addDatePicker').forEach($inputWithDatePicker => {
      new Litepicker({
        element: $inputWithDatePicker,
        format: me.dateFormat,
        singleMode: true
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
          let $textarea = document.querySelector('#' + $remainingCharsContainer.data('id'));
          $remainingCharsContainer.text(me.pluginVariables.localization.remainingText + ': ' + me.pluginVariables.settings.remainingLetters);

          $textarea.addEventListener('keyup', () => {
            let value = $textarea.value;
            let len = value.length;
            let maxLength = me.pluginVariables.settings.remainingLetters;

            $textarea.value = value.substring(0, maxLength);
            $remainingCharsContainer.text(
              me.pluginVariables.localization.remainingText + ': ' + (maxLength - len)
            );
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
      let $locationStatus = document.createElement('span');
      $locationStatus.setAttribute('class', 'locationStatus');
      me.$autoCompleteLocation.after($locationStatus);

      me.$autoCompleteLocation.autocomplete({
        source: function (request, response) {
          let siteUrl = location.protocol + '//' + location.hostname + (location.port ? ':' + location.port : '');
          $.ajax({
            url: siteUrl + '?eID=events2findLocations',
            dataType: 'json',
            data: {
              tx_events2_events: {
                arguments: {
                  search: request.term
                }
              }
            },
            success: function (data) {
              response(data);
            }
          });
        }, minLength: 2, response: function (event, ui) {
          if (ui.content.length === 0) {
            me.$autoCompleteLocation
              .siblings('.locationStatus')
              .eq(0)
              .text(me.pluginVariables.localization.locationFail)
              .removeClass('locationOk locationFail')
              .addClass('locationFail');
          }
        }, select: function (event, ui) {
          if (ui.item) {
            me.$autoCompleteLocation
              .siblings('.locationStatus')
              .eq(0)
              .text('')
              .removeClass('locationOk locationFail')
              .addClass('locationOk');
            me.$autoCompleteLocationHelper.value = ui.item.uid;
          }
        }
      }).focusout(function () {
        if (me.$autoCompleteLocation.value === '') {
          me.$autoCompleteLocation
            .siblings('.locationStatus')
            .eq(0)
            .text('')
            .removeClass('locationOk locationFail');
          me.$autoCompleteLocationHelper.value = '';
        }
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
      jQuery.ajax({
        type: 'GET',
        url: siteUrl,
        dataType: 'json',
        data: {
          id: me.pluginVariables.data.pid,
          type: 1372255350,
          tx_events2_events: {
            objectName: 'FindSubCategories',
            arguments: {
              category: me.$searchMainCategory.value
            }
          }
        }, success: function (categories) {
          me.fillSubCategories(categories);
        }, error: function (xhr, error) {
          if (error === 'parsererror') {
            console.log('It seems that you have activated Debugging mode in TYPO3. Please deactivate it to remove ParseTime from request');
          } else {
            console.log(error);
          }
        }
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
    let selected = '';
    let $searchSubCategory = document.querySelector('#searchSubCategory');
    $searchSubCategory.append('<option value="0"></option>');
    for (let property in categories) {
      if (categories.hasOwnProperty(property)) {
        count++;
        if (me.pluginVariables.search.subCategory !== null && me.pluginVariables.search.subCategory.uid === parseInt(property)) {
          selected = 'selected="selected"';
        } else {
          selected = '';
        }
        $searchSubCategory.append('<option ' + selected + ' value="' + property + '">' + categories[property] + '</option>');
      }
    }
    if (count) {
      $searchSubCategory.removeAttr('disabled');
    }
  };

  me.pluginVariables = $element.querySelector(me.selectorPluginVariables).getAttribute('data-variables');

  if (me.isCreatePlugin($element)) {
    me.$remainingCharsContainer = $element.querySelector(me.selectorRemainingChars);
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
