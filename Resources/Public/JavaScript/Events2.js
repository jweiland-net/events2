var Events2 = {
    selectorPlugin: ".tx-events2",
    selectorCreatePlugin: ".tx-events2-create",
    selectorSearchPlugin: ".tx-events2-search",
    selectorPluginVariables: "#events2DataElement",
    selectorCshDialog: "#dialogHint",
    selectorCshButton: "span.csh",
    selectorRemainingChars: ".remainingChars",
    selectorAutoCompleteLocation: "#autoCompleteLocation",
    selectorAutoCompleteLocationHelper: "#autoCompleteLocationHelper",
    selectorSearchMainCategory: "#searchMainCategory",

    dateFormat: "dd.mm.yy"
};

Events2.initialize = function() {
    // initializing jQuery elements
    Events2.$events2Plugins = jQuery(Events2.selectorPlugin);
    Events2.$events2CreatePlugins = jQuery(Events2.selectorCreatePlugin);
    Events2.$events2SearchPlugins = jQuery(Events2.selectorSearchPlugin);
    Events2.$cshDialog = Events2.$events2CreatePlugins.find(Events2.selectorCshDialog);
    Events2.$cshButtons = Events2.$events2CreatePlugins.find(Events2.selectorCshButton);
    Events2.$remainingCharsContainer = jQuery(Events2.selectorRemainingChars);
    Events2.$autoCompleteLocation = jQuery(Events2.selectorAutoCompleteLocation);
    Events2.$autoCompleteLocationHelper = jQuery(Events2.selectorAutoCompleteLocationHelper);
    Events2.$searchMainCategory = jQuery(Events2.selectorSearchMainCategory);

    // initializing variables
    Events2.pluginVariables = jQuery(Events2.selectorPluginVariables).data("variables");

    // initializing features
    Events2.initializeDialogBoxForContextSensitiveHelp();
    Events2.initializeRemainingLetters();
    Events2.initializeDatePicker();
    Events2.initializeAutoCompleteForLocation();
    Events2.initializeSubCategoriesForSearch();
};

/**
 * Test, if there are events2 plugins defined in DOM
 *
 * @returns {boolean}
 */
Events2.hasEvents2Plugins = function() {
    return !!Events2.$events2Plugins.length;
};

/**
 * Test, if there are events2 create plugins defined in DOM
 *
 * @returns {boolean}
 */
Events2.hasEvents2CreatePlugins = function() {
    return !!Events2.$events2CreatePlugins.length;
};

/**
 * Test, if there are events2 search plugins defined in DOM
 *
 * @returns {boolean}
 */
Events2.hasEvents2SearchPlugins = function() {
    return !!Events2.$events2SearchPlugins.length;
};

/**
 * Test, if there are some textareas in form with remaining chars feature
 *
 * @returns {boolean}
 */
Events2.hasRemainingCharsContainer = function() {
    return !!Events2.$remainingCharsContainer.length;
};

/**
 * Test, if there is an AutoComplete for location available in template
 *
 * @returns {boolean}
 */
Events2.hasAutoCompleteLocation = function() {
    return !!Events2.$autoCompleteLocation.length;
};

/**
 * Test, if localization of pluginVariables is initialized
 *
 * @returns {boolean}
 */
Events2.isLocalizationInitialized = function() {
    return Events2.pluginVariables.hasOwnProperty("localization");
};

/**
 * Test, if settings of pluginVariables are initialized
 *
 * @returns {boolean}
 */
Events2.isSettingsInitialized = function() {
    return Events2.pluginVariables.hasOwnProperty("settings");
};

/**
 * Test, if main category is available in search template
 *
 * @returns {boolean}
 */
Events2.hasSearchMainCategory = function() {
    return !!Events2.$searchMainCategory.length;
};

/**
 * Test, if all CSH related elements are defined in DOM
 *
 * @returns {boolean}
 */
Events2.hasCshElements = function() {
    if (Events2.hasEvents2CreatePlugins()) {
        if (!!Events2.$cshDialog.length && !!Events2.$cshButtons.length) {
            return true;
        } else {
            console.log("We are on the create form, but we can not find any CSH buttons or dialogs. Feature deactivated.");
            return false;
        }
    } else {
        return false;
    }
};

/**
 * Initialize dialog box for CSH
 * Currently used in create form for new events
 */
Events2.initializeDialogBoxForContextSensitiveHelp = function() {
    if (!Events2.hasCshElements()) {
        return;
    }

    Events2.$cshDialog.dialog({
        autoOpen: false,
        height: 150,
        width: 300,
        modal: true
    });
    Events2.$cshButtons.css("cursor", "pointer").on("click", Events2.attachClickEventToCsh);
};

/**
 * Initialize DatePicker for elements with class: addDatePicker
 */
Events2.initializeDatePicker = function() {
    if (Events2.hasEvents2CreatePlugins() || Events2.hasEvents2SearchPlugins()) {
        jQuery(".addDatePicker").datepicker({
            dateFormat: Events2.dateFormat
        });
    }
};

/**
 * Initialize remaining letters for teaser in create form
 */
Events2.initializeRemainingLetters = function() {
    if (Events2.hasRemainingCharsContainer()) {
        if (!Events2.isLocalizationInitialized()) {
            console.log("Variable localization of pluginVariables is not available. Please check your templates");
        } else if (!Events2.isSettingsInitialized()) {
            console.log("Variable settings of pluginVariables is not available. Please check your templates");
        } else {
            Events2.$remainingCharsContainer.each(function() {
                var $remainingCharsContainer = jQuery(this);
                var $textarea = jQuery("#" + $remainingCharsContainer.data('id'));
                $remainingCharsContainer.text(Events2.pluginVariables.localization.remainingText + ": " + Events2.pluginVariables.settings.remainingLetters);

                $textarea.on("keyup", function() {
                    var value = $(this).val();
                    var len = value.length;
                    var maxLength = Events2.pluginVariables.settings.remainingLetters;

                    $(this).val(value.substring(0, maxLength));
                    $remainingCharsContainer.text(
                        Events2.pluginVariables.localization.remainingText + ": " + (maxLength - len)
                    );
                });
            });
        }
    }
};

/**
 * Initialize AutoComplete for location
 */
Events2.initializeAutoCompleteForLocation = function() {
    if (Events2.hasEvents2CreatePlugins() && Events2.hasAutoCompleteLocation()) {
        $locationStatus = jQuery("<span />").attr("class", "locationStatus");
        Events2.$autoCompleteLocation.after($locationStatus);

        Events2.$autoCompleteLocation.autocomplete({
            source: function(request, response) {
                var siteUrl = location.protocol + "//" + location.hostname + (location.port ? ":" + location.port : "");
                $.ajax({
                    url: siteUrl + "?eID=events2findLocations",
                    dataType: "json",
                    data: {
                        tx_events2_events: {
                            arguments: {
                                locationPart: request.term
                            }
                        }
                    },
                    success: function (data) {
                        response(data);
                    }
                });
            }, minLength: 2, response: function (event, ui) {
                if (ui.content.length === 0) {
                    Events2.$autoCompleteLocation
                        .siblings(".locationStatus")
                        .eq(0)
                        .text(Events2.pluginVariables.localization.locationFail)
                        .removeClass("locationOk locationFail")
                        .addClass("locationFail");
                }
            }, select: function (event, ui) {
                if (ui.item) {
                    Events2.$autoCompleteLocation
                        .siblings(".locationStatus")
                        .eq(0)
                        .text("")
                        .removeClass("locationOk locationFail")
                        .addClass("locationOk");
                    Events2.$autoCompleteLocationHelper.val(ui.item.uid);
                }
            }
        }).focusout(function () {
            if (Events2.$autoCompleteLocation.val() === "") {
                Events2.$autoCompleteLocation
                    .siblings(".locationStatus")
                    .eq(0)
                    .text("")
                    .removeClass("locationOk locationFail");
                Events2.$autoCompleteLocationHelper.val("");
            }
        });
    }
};

/**
 * Attach click event to CSH buttons
 * It updates the text of the dialog box before it pops up.
 */
Events2.attachClickEventToCsh = function(event) {
    var property = jQuery(event.target).data("property");
    Events2.$cshDialog.find("p").text(jQuery("#hidden_" + property).text());
    Events2.$cshDialog.dialog("open");
};

/**
 * Initialize sub-categories of search plugin
 */
Events2.initializeSubCategoriesForSearch = function() {
    if (Events2.hasEvents2SearchPlugins() && Events2.hasSearchMainCategory()) {
        Events2.$searchMainCategory.on("change", function () {
            Events2.renderSubCategory();
        });
        Events2.renderSubCategory();
    }
};

/**
 * Search for sub-categories, if a main category was selected
 */
Events2.renderSubCategory = function() {
    jQuery("#searchSubCategory").empty().attr("disabled", "disabled");

    if (Events2.$searchMainCategory.val() !== "0") {
        var siteUrl = location.protocol + "//" + location.hostname + (location.port ? ":" + location.port : "");
        jQuery.ajax({
            type: 'GET',
            url: siteUrl,
            dataType: 'json',
            data: {
                id: Events2.pluginVariables.data.pid,
                type: 1372255350,
                tx_events2_events: {
                    objectName: 'FindSubCategories',
                    arguments: {
                        category: Events2.$searchMainCategory.val()
                    }
                }
            }, success: function (categories) {
                Events2.fillSubCategories(categories);
            }, error: function (xhr, error) {
                if (error === "parsererror") {
                    console.log("It seems that you have activated Debugging mode in TYPO3. Please deactivate it to remove ParseTime from request");
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
Events2.fillSubCategories = function(categories) {
    var count = 0;
    var selected = "";
    var $searchSubCategory = jQuery("#searchSubCategory");
    $searchSubCategory.append("<option value=\"0\"></option>");
    for (var property in categories) {
        if (categories.hasOwnProperty(property)) {
            count++;
            if (Events2.pluginVariables.search.subCategory !== null && Events2.pluginVariables.search.subCategory.uid === parseInt(property)) {
                selected = "selected=\"selected\"";
            } else {
                selected = "";
            }
            $searchSubCategory.append("<option " + selected + " value=\"" + property + "\">" + categories[property] + "</option>");
        }
    }
    if (count) {
        $searchSubCategory.removeAttr("disabled");
    }
};

Events2.initialize();
