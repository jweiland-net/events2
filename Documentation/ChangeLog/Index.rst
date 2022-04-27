.. include:: ../Includes.txt


.. _changelog:

=========
ChangeLog
=========

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
