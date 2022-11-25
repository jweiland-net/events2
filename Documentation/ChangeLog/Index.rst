.. include:: ../Includes.txt


.. _changelog:

=========
ChangeLog
=========

**Version 8.1.5**

- Bugfix: Use interface_exists instead of class_exists in Services.php

**Version 8.1.4**

- Bugfix: Load IndexerHook for solr individual in Services.php

**Version 8.1.3**

- Bugfix: Add compatibility to solr version 11.2 in IndexerHook

**Version 8.1.2**

- Bugfix: Events2PageTitleProvider only works on detail plugin
- Bugfix: getCountry must return country as INT as it contains the record UID

**Version 8.1.1**

- Start event search uncache (no_cache removed) as search results plugin is uncached by default

**Version 8.1.0**

- Add EXT:form implementation to create events in FE
- Use DB result to render options for location selector in search form

**Version 8.0.1**

- Add flexform for search results plugin

**Version 8.0.0**

- Add TYPO3 10/11 compatibility
- Add PHP 7.4, 8.0 and 8.1 compatibility
- Suggest EXT:maps2 in version 10
- Add "lang=en" to all templates
- Use \DateTimeImmutable instead of \DateTime
- Migrate ICalWidget to ICalController
- Create Ajax Request to retrieve events in month
- Migrate PoiCollectionWidget to Fluid Partial
- Replace jquery with VanillaJS
- Replace jquery DatePicker with LitePicker
- Move controller constructor arguments back to inject methods
- Migrate SCA to individual plugins
- Migrate list* actions to one global list action
- Update plugin preview for backend
- Add translation for PageBrowser
- Add TYPO3 logger for event2 warnings and errors
- Remove ToolTip JS. Implement a plain CSS solution
- Replace jquery autocomplete with a VanillaJS solution
- Add exceptions to DayGenerator to prevent false entries
- Prevent processing of translated records. l10n_mode=exclude
- Add workspace support
- Set organizer filter from POST to GET
- Add isset() to various variables
