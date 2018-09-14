var Events2 = {
    selectorPlugin: ".tx-events2",
    selectorCreatePlugin: ".tx-events2-create",
    selectorSearchPlugin: ".tx-events2-search",
    selectorPluginVariables: "#events2DataElement",
    selectorSearchPluginVariables: "#events2SearchDataElement",
    selectorCshDialog: "#dialogHint",
    selectorCshButton: "span.csh",

    dateFormat: "dd.mm.yy"
};

Events2.initialize = function() {
    Events2.$events2Plugins = jQuery(Events2.selectorPlugin);
    Events2.$events2CreatePlugins = jQuery(Events2.selectorCreatePlugin);
    Events2.$events2SearchPlugins = jQuery(Events2.selectorSearchPlugin);
    Events2.$cshDialog = Events2.$events2CreatePlugins.find(Events2.selectorCshDialog);
    Events2.$cshButtons = Events2.$events2CreatePlugins.find(Events2.selectorCshButton);

    Events2.initializeDialogBoxForContextSensitiveHelp();
    Events2.initializeDatePicker();
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
    if (Events2.hasEvents2CreatePlugins()) {
        jQuery(".addDatePicker").datepicker({
            dateFormat: Events2.dateFormat
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

Events2.initialize();






var jsVariables = jQuery("#events2DataElement").data("variables");
var jsSearchVariables = jQuery("#events2SearchDataElement").data("variables");

// get remaining letters for teaser
var $teaser = jQuery("#teaser"); // that's the textarea
var $remainingChars = $("#remainingChars"); // that's the text element below the textarea
$remainingChars.text(jsVariables.localization.remainingText + ": " + jsVariables.settings.remainingLetters);
$teaser.on("keyup", function () {
    var value = $(this).val();
    var len = value.length;
    var maxLength = jsVariables.settings.remainingLetters;

    $(this).val(value.substring(0, maxLength));
    $remainingChars.text(jsVariables.localization.remainingText + ": " + (maxLength - len));
});

// create autocomplete for location selector
jQuery("#autocompleteLocation").autocomplete({
    source: function (request, response) {
        var siteUrl = location.protocol + "//" + location.hostname + (location.port ? ":" + location.port : "");
        $.ajax({
            url: siteUrl + "?eID=events2findLocations", dataType: "json", data: {
                tx_events2_events: {
                    arguments: {
                        locationPart: request.term
                    }
                }
            }, success: function (data) {
                response(data);
            }
        });
    }, minLength: 2, response: function (event, ui) {
        if (ui.content.length === 0) {
            jQuery("#locationStatus").text(jsVariables.localization.locationFail).removeClass("locationOk locationFail").addClass("locationFail");
        }
    }, select: function (event, ui) {
        if (ui.item) {
            jQuery("#locationStatus").text("").removeClass("locationOk locationFail").addClass("locationOk");
            jQuery("#location").val(ui.item.uid);
        }
    }
}).focusout(function () {
    if (jQuery("#autocompleteLocation").val() == "") {
        jQuery("#locationStatus").text("").removeClass("locationOk locationFail");
        jQuery("#location").val("");
    }
});

function renderSubCategory() {
    jQuery("#searchSubCategory").empty().attr("disabled", "disabled");
    var $searchMainCategory = jQuery("#searchMainCategory");

    if ($searchMainCategory.val() !== "0") {
        var siteUrl = location.protocol + "//" + location.hostname + (location.port ? ":" + location.port : "");
        jQuery.ajax({
            type: 'GET',
            url: siteUrl,
            dataType: 'json',
            data: {
                id: jsSearchVariables.siteId,
                type: 1372255350,
                tx_events2_events: {
                    objectName: 'FindSubCategories',
                    arguments: {
                        category: $searchMainCategory.val()
                    }
                }
            }, success: function (categories) {
                fillSubCategories(categories);
            }, error: function (xhr, error) {
                console.log(error);
            }
        });
    }
}

function fillSubCategories(categories) {
    var count = 0;
    var selected = "";
    var $searchSubCategory = jQuery("#searchSubCategory");
    $searchSubCategory.append("<option value=\"0\"></option>");
    for (var property in categories) {
        if (categories.hasOwnProperty(property)) {
            count++;
            if (jsSearchVariables.search.subCategory !== null && property === jsSearchVariables.search.subCategory.uid) {
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
}

if (jQuery("#events2SearchForm").length) {
    jQuery("#searchMainCategory").on("change", function () {
        renderSubCategory();
    });
    renderSubCategory();
}
