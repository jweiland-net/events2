..  include:: /Includes.rst.txt


..  _changelog:

=========
ChangeLog
=========

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
