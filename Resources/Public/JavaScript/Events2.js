var jsVariables = jQuery("#events2DataElement").data("variables");
var jsSearchVariables = jQuery("#events2SearchDataElement").data("variables");

// add datePicker to date-fields
jQuery("#eventBegin, #eventEnd, #searchEventBegin, #searchEventEnd").datepicker({
    dateFormat: "dd.mm.yy"
});

// initialize dialog box
jQuery("#dialogHint").dialog({
    autoOpen: false, height: 150, width: 300, modal: true
});

// show dialog box on click
jQuery("span.csh")
    .css("cursor", "pointer")
    .click(function () {
        var property = jQuery(this).data("property");
        jQuery("#dialogHint p").text(jQuery("#hidden_" + property).text());
        jQuery("#dialogHint").dialog("open");
    });

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
