.. include:: ../../Includes.txt

.. _typoScript:

==========
TypoScript
==========

View
====

view.templateRootPaths
""""""""""""""""""""""

Default: Value from Constants *EXT:events2/Resources/Private/Templates/*

You can override our Templates with your own SitePackage extension. We prefer to change this value in TS Constants.

view.partialRootPaths
"""""""""""""""""""""

Default: Value from Constants *EXT:events2/Resources/Private/Partials/*

You can override our Partials with your own SitePackage extension. We prefer to change this value in TS Constants.

view.layoutsRootPaths
"""""""""""""""""""""

Default: Value from Constants *EXT:events2/Resources/Layouts/Templates/*

You can override our Layouts with your own SitePackage extension. We prefer to change this value in TS Constants.

Persistence
===========

persistence.storagePid
""""""""""""""""""""""

Set this value to a Storage Folder (PID) where you have stored the event records. If you have stored Organizers and
Locations in another Storage Folder, you have to add theses PIDs here, to.

Example: 21,45,3234

persistence.classes.*.newRecordStoragePid
"""""""""""""""""""""""""""""""""""""""""

With events2 you can allow your website users to create new events. Instead of creating all these new records
into your default storage it may be helpful to separate these records in its own Storage Folder, validate them and
move them into the correct Storage Folder.

By default a new event record (created by Frontend) will be stored in the first Storage Folder defined in
*persistence.storagePid*

persistence.classes.JWeiland\Events2\Domain\Model\Event.newRecordStoragePid = 34
persistence.classes.JWeiland\Events2\Domain\Model\Location.newRecordStoragePid = 543

MVC
===

mvc.callDefaultActionIfActionCantBeResolved
"""""""""""""""""""""""""""""""""""""""""""

Default: 1

If an action was missing in a link we will not show an error. Instead we will try using the default action. So,
if only a controller is given in URL, we will use Action *list* for the View.

Settings
========

settings.rootCategory
"""""""""""""""""""""

Default: 0

Only valid for search plugin and if you create new events in Frontend.

As a TYPO3 Backend User can choose nearly every category from each category tree layer we have added *rootCategory*
as a further restriction to show only direct child categories of *rootCategory*. All selected categories in deeper
category tree will not be visible in Frontend. Else it would break our Search Plugin, where you can select
a category first and afterwards a sub-category.

The *rootCategory* has nothing to do with *rootUid* of ExtensionSettings. *rootUid* reduces the category tree
for Users in the TYPO3 Backend.

setting.pidOfDetailPage
"""""""""""""""""""""""

Default: 0

Often it is useful to move the detail view onto a separate page for design/layout reasons.

settings.pidOfSearchPage
""""""""""""""""""""""""

Default: 0

Often it is useful to move the search view onto a separate page for design/layout reasons.

settings.pidOfLocationPage
""""""""""""""""""""""""""

Default: 0

Often it is useful to move the location view onto a separate page for design/layout reasons.

settings.pidOfListPage
""""""""""""""""""""""

Default: 0

If you use one of the above settings, it would be useful to define the *pidOfListPage*, too, so that
a link back to list works like expected.


settings.includeDeTranslationForCalendar
""""""""""""""""""""""""""""""""""""""""

Default: 0

This is only for our german friends of events2. We are using the calendar of jquery UI Framework, where all
the month names and weekdays are in english translation only. If you set this to 1, we will include a german
translation file. Adapt this mechanism for other translations.

settings.userGroup
""""""""""""""""""

Default: 0

If you set this value to an UID of a Frontend Usergroup we will show a link in list view, where all
users of this usergroup can edit and create new events. This will only work, if a user record was assigned to
an events2 organizer.

settings.remainingLetters
"""""""""""""""""""""""""

Default: 250

Only valid for frontend creation

To reduce the input of Frontend Users while creating new event records, you can set *remainingLetter* to an
amount of max. letters. With help of a JS-Script we show a little counter for the remaining letters.

settings.list.image
"""""""""""""""""""

Default: 50*50

Configuration for images in list view. It currently contains:
width, height, maxWidth, maxHeight, minWidth, minHeight

settings.latest.amountOfRecordsToShow
"""""""""""""""""""""""""""""""""""""

How many records should be displayed in latest view.
By default there is no PageBrowser for latest view.

settings.pageBrowser.*
""""""""""""""""""""""

itemsPerPage
------------
Amount of records on a page

insertAbove
-----------
Show a pageBrowser on top of the list

insertBelow
-----------
Show a pageBrowser below the list

maximumNumberOfLinks
--------------------
How many page-links should be shown?

settings.show.image
"""""""""""""""""""

Default: 200*150

Configuration for images in detail view. It currently contains:
width, height, maxWidth, maxHeight, minWidth, minHeight

_LOCAL_LANG.*.*
"""""""""""""""

As an integrator you can override each key of language file:

EXT:events2/Resources/Private/Language/locallang.xlf

Example:

plugin.tx_events2._LOCAL_LANG.de.listMyEvents = Zeige meine Veranstaltungen

_CSS_DEFAULT_STYLE
""""""""""""""""""

This will include a default CSS Style to show a red border around input fields in Frontend,
if an events2 field was filled with an invalid value.

If you have your own CSS we prefer to remove this setting:

plugin.events2._CSS_DEFAULT_STYLE >

