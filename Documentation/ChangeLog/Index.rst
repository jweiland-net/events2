..  include:: /Includes.rst.txt


..  _changelog:

=========
ChangeLog
=========

Version 12.0.0
==============

This version contains breaking changes, please read
:ref:`upgrade-12-0-0`.

*   [!!!][TASK] Use the form based on EXT:form for the frontend management
    only. The site setting `events2.form.useExtForm` is removed and
    :file:`OverrideFormSettings.yaml` of EXT:form is always registered.
    events2 does not set the keys `1` of `templateRootPaths` and
    `partialRootPaths` anymore. The templates
    :file:`Management/ListMyEvents.html`, :file:`Management/Perform.html`
    and the partial :file:`Management/UserEditLinks.html` moved from
    :file:`Resources/Private/FormFramework/` to the regular
    :file:`Templates/` and :file:`Partials/` folders.
*   [!!!][TASK] Remove the Extbase form of the management plugin: actions
    `new`, `create`, `edit`, `update` and `delete`, their templates and
    partials, validators, type converter, event listeners, middleware,
    traits, site settings `events2.form.uploadFolder` and
    `events2.form.remainingLetters`, the TypoScript settings
    `remainingLetters`, `new.uploadFolder`, `_LOCAL_LANG` of
    `rightsMessage` and `_CSS_DEFAULT_STYLE` and the labels used by the
    Extbase form only. Links to create or edit events point to the action
    `perform` now. See :ref:`upgrade-12-0-0-actions`.
*   [!!!][TASK] Merge :php:`EventModerationService` into
    :php:`ManagementAccessService`. The moderation token is unchanged, so
    activation links of sent mails stay valid. See
    :ref:`frontend-management-access`.
*   [!!!][TASK] Replace all icons by SVG files registered with the
    :php:`SvgIconProvider`. The TCA uses icon identifiers in
    `typeicon_classes` instead of `iconfile`. See
    :ref:`upgrade-12-0-0-icons`.
*   [FEATURE] Render rich text elements in the mails of the event form
    sanitized as HTML and as plain text with the new
    :ref:`FormatRichTextViewHelper <formatRichTextViewHelper>`.
*   [TASK] Localize the hint icon of the event form.
*   [BUGFIX] Restrict creation of events to the user group. New events
    can only be created by logged-in frontend users of the user group of
    :confval:`settings.userGroup <events2-typoscript-settings-usergroup>`
    with an organizer, also for backend administrators. The
    `SaveEventFinisher` of EXT:form checks the same rules before anything
    is stored.
*   [!!!][TASK] Simplify the access rules of the frontend management.
    :php:`ManagementAccessService` reads the user group from TypoScript
    and the event to edit from the query parameter
    `tx_events2_management[event]` only. Backend administrators can not
    list own events anymore and need the `moderationToken` to activate
    an event. Edit forms are prefilled for allowed users only. Editing
    an event keeps its organizers.
*   [BUGFIX] Prefill the download link of the edit form on all database
    platforms. :file:`NewEvent.form.yaml` uses the expression `in` with
    `intExplodeValue: 1` instead of `inSet`. Update copies of the form.
*   [BUGFIX] Offer categories in the event form, if
    `settings.new.selectableCategoriesForNewEvents` is missing or empty.
    Before, a missing setting threw a :php:`TypeError`. Now all direct
    children of :typoscript:`settings.rootCategory` are offered. Without
    both settings, no categories are offered. The categories are labelled
    in the current language. The deprecated fallback to
    `settings.selectableCategoriesForNewEvents` (since 9.1.9) is removed,
    see :ref:`upgrade-12-0-0-settings`.
*   [BUGFIX] Limit the teaser of the event form to 255 characters, the
    `max` of the column `teaser` in TCA. The element `short-description`
    of :file:`NewEvent.form.yaml` gets the attribute `maxlength` and the
    validator `StringLength`. Each textarea with a `maxlength` shows the
    remaining characters (new label `remainingLetters`). Existing longer
    teasers are kept, but have to be shortened when the event is edited
    in the frontend. Update copies of the form, see
    :ref:`upgrade-12-0-0-form-definition`.
*   [BUGFIX] Separate list items in the plain text of descriptions.
*   [BUGFIX] Build the absolute URL of the JSON-LD image correctly.
*   [!!!][FEATURE] Replace EXT:static_info_tables with the country API of
    TYPO3. The country of a location is stored as ISO 3166-1 alpha-2 code.
    The new upgrade wizard `events2_migrateLocationCountry` migrates
    existing locations. :php:`Location::getCountry()` returns the ISO
    code, the new :php:`Location::getCountryObject()` the country object
    of TYPO3. The extension setting `defaultCountry` expects an ISO code
    and has to be changed manually. With EXT:maps2 the ISO code instead
    of the country name is part of the geocoded address. See
    :ref:`upgrade-12-0-0-country`.
*   [!!!][FEATURE] Replace the scheduler tasks "Re-Create day records"
    and "Import events" with the schedulable commands
    `events2:rebuild --no-truncate` and `events2:import`. Existing tasks
    are not migrated. See :ref:`upgrade-12-0-0-scheduler`.
*   [!!!][TASK] Remove the static TypoScript template "Events". Use the
    site set `jweiland/events2`. The exception `1580294227` for a missing
    TypoScript template is not thrown anymore. See
    :ref:`upgrade-12-0-0-site-set`.
*   [!!!][TASK] Use the Extbase option `ignoreFlexFormSettingsIfEmpty` for
    the fallback of empty FlexForm values to TypoScript. Remove
    :php:`TypoScriptService` and :php:`InjectTypoScriptServiceTrait`. See
    :ref:`upgrade-12-0-0-flexform`.
*   [!!!][TASK] Remove the ViewHelpers `e2:convertToJson` and
    `e2:cropString`. The argument `link` of `e2:createYoutubeUri` is
    optional and can be passed as tag content. See
    :ref:`upgrade-12-0-0-viewhelpers`.
*   [!!!][TASK] Remove the outdated upgrade wizards
    `events2_migrateDetailInformations`, `events2_migrateOrganizer` and
    `events2_moveFlexFormFields`. See :ref:`upgrade-12-0-0-before`.
*   [!!!][TASK] Rely on the system TCA columns created by TYPO3.
    `starttime`, `endtime`, `fe_group` and `editlock` of translations are
    not synchronized with the default language anymore. Remove
    :php:`getDeleted()` and :php:`setDeleted()` of all domain models and
    the key `deleted` of the export. Day records map `crdate` and
    `tstamp` for the iCal download. See
    :ref:`upgrade-12-0-0-translations`.
*   [!!!][TASK] Read the TCA with the TCA Schema API of TYPO3. Some
    classes got the :php:`TcaSchemaFactory` as new constructor argument.
    See :ref:`upgrade-12-0-0-developer`.
*   [TASK] Register the commands with the attribute :php:`#[AsCommand]`
    and remove obsolete entries from :file:`Services.yaml`.
*   [DOCS] Describe the frontend management and its access rules, see
    :ref:`frontend-management`.
*   [DOCS] Describe the commands of events2 and the site set, see
    :ref:`commands` and :ref:`installation-site-set`.

Version 11.2.0
==============

*   [FEATURE] Add a rich text editor (CKEditor 5) for elements of the
    event form based on EXT:form. Enable it with the new site setting
    :confval:`events2.form.richText.enable <events2-form-richtext-enable>`.
    By default, the element `detail-description` gets the editor. Further
    elements can be added with the site setting
    :confval:`events2.form.richText.elements <events2-form-richtext-elements>`
    or with :yaml:`dataType: richText` in the form definition. It uses the
    CKEditor 5 modules of EXT:rte_ckeditor without a build step. The
    submitted HTML is sanitized with an allowlist and stored like in the
    backend. See :ref:`frontend-rich-text-editor`.
*   [FEATURE] Make tags of the rich text sanitizer configurable. The new
    PSR-14 event :php:`ModifyRichTextSanitizerConfigurationEvent` allows
    to modify the allowed, mapped, removed and block elements of the
    sanitizer, for example per table and column. Elements with active
    content like :html:`<script>` or :html:`<iframe>` can never be
    allowed. See :ref:`frontend-rich-text-editor-sanitizer-event`.
*   [FEATURE] Build the toolbar of the frontend rich text editor from the
    allowed tags of the column. Each allowed heading :html:`<h1>` to
    :html:`<h6>` gets a heading option, and the items for bold, italic,
    lists and links are only shown if their elements are allowed. The new
    optional :yaml:`dbMapping.table` of a form element defines the table
    of the column, if it is not the event table. See
    :ref:`frontend-rich-text-editor-toolbar`.

Version 11.1.1
==============

*   [BUGFIX] Store frontend description like backend RTE. Teaser and detail
    information of the frontend form are stored in the format of the
    backend RTE (CKEditor and RteHtmlParser) now: `<br />` without a
    following newline, paragraphs separated by CRLF and only `&`, `<` and
    `>` encoded. Before, `<f:format.html>` (lib.parseFunc_RTE) turned the
    newline after each `<br />` into a new paragraph. Already stored
    descriptions keep the old format until the event is saved again.
*   [BUGFIX] Decode HTML entities when converting stored descriptions back
    to plain text in the edit form, in `ConvertHtmlToPlainTextViewHelper`
    and in the JSON-LD description. Words around `<br />` and between
    paragraphs are not glued together in the JSON-LD description anymore.
*   [BUGFIX] Store teaser of frontend form as plain text. The teaser is a
    plain text column without RTE. The form based on EXT:form stores it like
    the backend textarea now: tags stripped, CRLF line breaks, trimmed and
    not HTML encoded. If you have overridden :file:`NewEvent.form.yaml`,
    replace `convertPlainTextToHtml: true` of `short-description` in the
    SaveEventFinisher with `normalizePlainText: true`. Teasers stored as HTML
    before are still shown and prefilled as plain text.
*   [BUGFIX] Keep non-breaking spaces outside of JSON-LD. `&nbsp;` is kept
    as non-breaking space in the edit form and in
    `ConvertHtmlToPlainTextViewHelper` and stored as `&nbsp;` again like the
    backend RTE does. The JSON-LD description gets normal spaces. Lines and
    paragraphs containing nothing but (non-breaking) spaces like
    `<p>&nbsp;</p>` are removed.

Version 11.1.0
==============

Please read :ref:`upgrade-11-1-0` before updating, if you have overridden
the notification mail templates.

*   [FEATURE] Secure activation of events with confirm page. The activation
    link of the notification mails opens the new action `confirmActivation`
    now. It shows a summary of the event and activates it via POST only, so
    mail security scanners and link previews can not activate events
    anymore. `activate` redirects GET requests to the confirm page.
*   [SECURITY] `confirmActivation` and `activate` are allowed with a valid
    `moderationToken` of the mail link or for backend administrators only.
    Owners of an event can not activate their own events anymore.
*   [FEATURE] Add `ModerationTokenViewHelper` to create the
    `moderationToken` in templates without controller like the mail
    template of the EXT:form finisher.

Version 11.0.2
==============

*   [BUGFIX] Do not validate the event in `ManagementController::performAction()`.
    The action only renders the form. Validating the whole object graph
    (e.g. an empty link of the related location) prevented editing an event.

Version 11.0.1
==============

*   [TASK] Remove obsolete 0 to NULL conversion of page settings

..  _changelog-11-0-0:

Version 11.0.0
==============

Please read :ref:`upgrade-11-0-0` before updating.

*   [!!!][TASK] Use Extbase file upload handling for images
*   [!!!][TASK] Use upload and delete fields for images in form
*   [TASK] Add checkfaluploads validator to image uploads
*   [FEATURE] Add site setting for the image upload folder
*   [TASK] Add tests for the image upload event listeners

Version 10.2.12
===============

*   [SECURITY] Sanitize action name and harden event UID resolution

Version 10.2.11
===============

*   [BUGFIX] Restore RTE formatting and links in event detail view

Version 10.2.10
===============

*   [BUGFIX] Show plain text when editing teaser/description

Version 10.2.9
==============

*   [BUGFIX] Resolve location label in event edit form
*   [BUGFIX] Render teaser/detailInformation as plain text
*   [BUGFIX] Suppress redundant generic error flash message

Version 10.2.8
==============

*   [BUGFIX] Resolve selectable categories in editAction like newAction

Version 10.2.7
==============

*   [BUGFIX] Fix organizer select id to match JavaScript filter
*   [BUGFIX] Keep filter and search arguments during pagination
*   [TASK] Turn plugin namespaces into a class constant in GetPostPagination

Version 10.2.6
==============

*   [BUGFIX] Prevent copied event records from reusing day relations of the
    original event

Version 10.2.5
==============

*   [BUGFIX] Replace hardcoded tab labels with localized labels in tt_content

Version 10.2.4
==============

*   [BUGFIX] Prevent duplicate day records when rebuilding event days

Version 10.2.3
==============

*   [TASK] Add additional database indexes for events table

Version 10.2.2
==============

*   [TASK] Respect search form values in paginator of search results

Version 10.2.1
==============

*   [TASK] Migrate attendance mode to TCA type "radio"

Version 10.2.0
==============

*   [FEATURE] PSR-14 event: allow early QueryBuilder modification in DayRepository

Version 10.1.3
==============

*   [TASK] Do not collect hidden time records from exceptions
*   [BUGFIX] Show time from event exception record in frontend

Version 10.1.2
==============

*   [TASK] Add functional test for CreateYoutubeUri class
*   [TASK] Update createYouTubeVH to allow further YouTube URLs
*   [TASK] Add setSettings to ICalController and SearchController, too
*   [DOCS] It is the events2:rebuild command. Update upgrade instruction.
*   [DOCS] Add info for events2 10.0 to upgrade section

Version 10.1.1
==============

*   [BUGFIX] Skip imported image, if not exits on remote host (XML Importer)

Version 10.1.0
==============

*   [FEATURE] Add property "attendance mode" to event

Version 10.0.11
===============

*   [BUGFIX] Correct day_time_sort column entry while processing duration events

Version 10.0.10
===============

*   [TASK] Prefix all upgrade wizards with [events2]

Version 10.0.9
==============

*   [BUGFIX] Wrap doctrine lastInsertId in try/catch

Version 10.0.8
==============

*   [BUGFIX] Register UpgradeWizards via DI
*   [TASK] Remove Upgrade of SwitchableControllerActions

Version 10.0.7
==============

*   [BUGFIX] Fixed Property Mapper with CheckFal validation issues

Version 10.0.6
==============

*   Upgrade removed TYPO3 core method in JSON importer

Version 10.0.5
==============

*   Create fake TYPO3_REQUEST in CLI mode

Version 10.0.4
==============

*   Set search form results to POST request
*   Repair sub-category, location and get-days AJAX calls
*   Repair JS to show/click on events in calendar
*   Repair GetLocations middleware
*   Do not mark RestrictAccessEventListener as readonly
*   Repair warning icon in form templates
*   Use findAll from LocationRepo in ManagementController

Version 10.0.3
==============

*   Never let DataHandler create day records in any case
*   Add startingpoint to events2 plugins

Version 10.0.2
==============

*   Do not respect BE_USER workspace in CLI environment
*   Improve performance a lot while re-creating day-records (CLI)
*   Prevent usage of Extbase UriBuilder in events2 middleware

Version 10.0.1
==============

*   Add plugin to ContentElement UpgradeWizard
*   Allow comma seperated list of storage PIDs

Version 10.0.0
==============

This version adds TYPO3 13 compatibility with various improvements
and modernizations.

Breaking Changes
----------------

*   [!!!] Remove TYPO3 12 LTS compatibility
*   [!!!] Raise minimum PHP version to match TYPO3 13 requirements

Features and Improvements
-----------------------

*   [FEATURE] Add TYPO3 13 LTS compatibility
*   [TASK] Improve testing suite with workspace and versioning tests
*   [TASK] Add various functional tests for improved stability
*   [TASK] Optimize timezone handling in tests using TYPO3 configuration
*   [TASK] Improve code quality with early returns and better class documentation

Note: This version requires TYPO3 13 LTS and drops support for previous TYPO3 versions.
If you need TYPO3 12 LTS compatibility, please use events2 version 9.x

Version 9.2.1
=============

*   [BUGFIX] Use category UID from sys_category_record_mm instead of sys_reaction

Version 9.2.0
=============

*   [FEATURE] Accessibility: Add aria-label to calendar button next/previous
*   [BUGFIX] Import: Keep category UID of importing server, if found
*   [FEATURE] You can now define a parent category for imported categories
*   [FEATURE] Allow filtering exported events by category UIDs

Version 9.1.10
==============

*   [BUGFIX] Add management PID to userEditLinks partial when using EXT:form
*   [BUGFIX] Declare sys_language_uid editable for everyone

Version 9.1.9
=============

*   [BUGFIX] Correct overwriting partial template of UserEditLinks
*   [BUGFIX] Allow creation of path segment also for hidden event records
*   [BUGFIX] Repair validation of event begin
*   [TASK] Deprecate `settings.selectableCategoriesForNewEvents`
*   [FEATURE] Implement new setting `settings.new.selectableCategoriesForNewEvents`
*   [TASK] Repair func. and unit tests

Version 9.1.8
=============

*   [BUGFIX] Make sure to have just one / between base URL and public image URL

Version 9.1.7
=============

*   [BUGFIX] Add needed language columns to event export. This will allow
             DayRelationService to create the needed day records.

Version 9.1.6
=============

*   [BUGFIX] Prevent double slash in public image URI while export
*   [BUGFIX] Import categories into table sys_category
*   [BUGFIX] Add import_id to imported event records
*   [BUGFIX] Also find hidden events while import to prevent duplicates
*   [BUGFIX] Use 0 for timestamp if date is empty string while importing events
*   [BUGFIX] Check datamap before importing category/organizer/location again

Version 9.1.5
=============

*   [BUGFIX] Add start/end time to location export

Version 9.1.4
=============

*   [TASK] Add a lot more logging information for event export

Version 9.1.3
=============

*   [BUGFIX] Do not import category start/end time, as it was not part of
             extbase category model

Version 9.1.2
=============

*   [BUGFIX] Convert start-end-time to ISO before export

Version 9.1.1
=============

*   [BUGFIX] Decode typolink before resolving file UID

Version 9.1.0
=============

*   [FEATURE] Add events2 exporter
*   [FEATURE] Add events2 JSON importer

Version 9.0.9
=============

*   [BUGFIX] Missed to mark a functional test as "test"

Version 9.0.8
=============

*   [BUGFIX] Creating events in FE results into error because of wrong whereClause
*   [BUGFIX] Make sure building unique path segments while importing events
*   [BUGFIX] Allow BE admins to activate FE edited event records
*   [TASK] Repair and re-activate unit tests
*   [TASK] Start repairing some func tests

Version 9.0.7
=============

*   [TASK] Check array keys before access in XmlImporter
*   [TASK] Mark Importer classes as public: true in Services.yaml

Version 9.0.6
=============

*   [SECURITY] Cached Action -> possible sensitive information disclosure
*   [TASK] Escape % in LIKE queries

Version 9.0.5
=============

*   [TASK] Slow COUNT query. Update booster index.

Version 9.0.4
=============

*   [BUGFIX] Fixed issue with timestamp string to int casting in TimeFactory
*   [BUGFIX] Fixed issue with plugin if detail page is not selected

Version 9.0.3
=============

*   [BUGFIX] Define action listMyEvents as uncached

Version 9.0.2
=============

*   [BUGFIX] fixed broken backend template preview if more than 10 categories
    selected
*   [TASK] Added GetFirstImage function in Event Model to avoid iteration in Fluid

Version 9.0.1
=============

*   Bugfix: Repair scheduler task for re-generate day records

Version 9.0.0
=============

This is just a plain upgrade without any new features

*   [TASK] Add TYPO3 12 compatibility
*   [TASK] Remove TYPO3 11 compatibility
*   [TASK] Remove TYPO3 10 compatibility

Version 8.3.5
=============

*   [BUGFIX] Search for events2 plugins with LIKE search

Version 8.3.4
=============

*   [BUGFIX] Check migration of sDEFAULT to sDEF for all possible plugins

Version 8.3.3
=============

*   [BUGFIX] Initialize properties of event for better PHP 7.4 compatibility

Version 8.3.2
=============

*   [BUGFIX] Use sDEF instead of sDEFAULT

Version 8.3.1
=============

*   [BUGFIX] Never add day records of simple events if out of timeframe

Version 8.3.0
=============

*   [FEATURE] Add new setting to configure property "categories" as mandatory.

Version 8.2.1
=============

*   [BUGFIX] Export data in valid ical format

Version 8.2.0
=============

*   [BUGFIX] Remove tablename from exception TCA to be compatible with
    TYPO3 update 11.5.26
*   [FEATURE] Simplify override of pagination in TypoScript

Version 8.1.6
=============

*   Set indent size of docs to 4 spaces
*   Apply new steructure to documentation
*   Add "Reset" Button to LitePicker

Version 8.1.5
=============

*   Bugfix: Use interface_exists instead of class_exists in Services.php

Version 8.1.4
=============

*   Bugfix: Load IndexerHook for solr individual in Services.php

Version 8.1.3
=============

*   Bugfix: Add compatibility to solr version 11.2 in IndexerHook

Version 8.1.2
=============

*   Bugfix: Events2PageTitleProvider only works on detail plugin
*   Bugfix: getCountry must return country as INT as it contains the record UID

Version 8.1.1
=============

*   Start event search uncache (no_cache removed) as search results plugin is uncached by default

Version 8.1.0
=============

*   Add EXT:form implementation to create events in FE
*   Use DB result to render options for location selector in search form

Version 8.0.1
=============

*   Add flexform for search results plugin

Version 8.0.0
=============

*   Add TYPO3 10/11 compatibility
*   Add PHP 7.4, 8.0 and 8.1 compatibility
*   Suggest EXT:maps2 in version 10
*   Add "lang=en" to all templates
*   Use \DateTimeImmutable instead of \DateTime
*   Migrate ICalWidget to ICalController
*   Create Ajax Request to retrieve events in month
*   Migrate PoiCollectionWidget to Fluid Partial
*   Replace jquery with VanillaJS
*   Replace jquery DatePicker with LitePicker
*   Move controller constructor arguments back to inject methods
*   Migrate SCA to individual plugins
*   Migrate list* actions to one global list action
*   Update plugin preview for backend
*   Add translation for PageBrowser
*   Add TYPO3 logger for event2 warnings and errors
*   Remove ToolTip JS. Implement a plain CSS solution
*   Replace jquery autocomplete with a VanillaJS solution
*   Add exceptions to DayGenerator to prevent false entries
*   Prevent processing of translated records. l10n_mode=exclude
*   Add workspace support
*   Set organizer filter from POST to GET
*   Add isset() to various variables
