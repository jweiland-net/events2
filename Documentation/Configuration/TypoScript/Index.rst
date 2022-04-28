.. include:: ../../Includes.txt

.. _typoScript:

==========
TypoScript
==========

All following TypoScript configuration consists in `plugin.tx_events2`

view
====

templateRootPaths
-----------------

Default: `EXT:events2/Resources/Private/Templates/`

Example: `plugin.tx_events2.view.templateRootPaths.40 = EXT:site_package/Resources/Private/Templates/`

You can override our Templates with your own SitePackage extension.

partialRootPaths
----------------

Default: `EXT:events2/Resources/Private/Partials/`

Example: `plugin.tx_events2.view.partialRootPaths.40 = EXT:site_package/Resources/Private/Partials/`

You can override our Partials with your own SitePackage extension.

layoutsRootPaths
----------------

Default: `EXT:events2/Resources/Private/Layouts/`

Example: `plugin.tx_events2.view.layoutsRootPaths.40 = EXT:site_package/Resources/Private/Layouts/`

You can override our Layouts with your own SitePackage extension. We prefer to change this value in TS Constants.

persistence
===========

storagePid
----------

Default: empty

Example: `plugin.tx_events2.persistence.storagePid = 12,32,48`

Set this value to a Storage Folder where you have stored the event records.

.. important::

   If you have stored Organizers and Locations in another Storage Folder, you have to add theses
   PIDs here, too.

.. tip::

   If you use creation of events over frontend plugin, new records will be stored in first PID found
   in storagePid. To store record in other storage PIDs you need following configuration

   .. code-block:: typoscript

      plugin.tx_events2.persistence.classes.JWeiland\Events2\Domain\Model\Event.newRecordStoragePid = 34
      plugin.tx_events2.persistence.classes.JWeiland\Events2\Domain\Model\Location.newRecordStoragePid = 543

settings
========

rootCategory
------------

Default: empty

Example: `plugin.tx_events2.settings.rootCategory = 15`

Only valid for search form and management plugin.

As a TYPO3 Backend User can choose nearly every category from each category tree layer we have added *rootCategory*
as a further restriction to show only direct child categories of *rootCategory*. All selected categories in deeper
category tree will not be visible in Frontend. Else it would break our Search Plugin, where you can select
a category first and afterwards a sub-category.

The *rootCategory* has nothing to do with *rootUid* of ExtensionSettings. *rootUid* reduces the category tree
for users in the TYPO3 backend.

pidOfDetailPage
---------------

Default: 0

Example: `plugin.tx_events2.settings.pidOfDetailPage = 123`

Often it is useful to move the detail view onto a separate page for design/layout reasons.

pidOfSearchResults
------------------

Default: 0

Example: `plugin.tx_events2.settings.pidOfSearchResults = 213`

If you have plugin for search results on a different page then plugin for search form you can
set the page UID with search result plugin here.

pidOfLocationPage
-----------------

Default: 0

Example: `plugin.tx_events2.settings.pidOfLocationPage = 132`

Often it is useful to move the location view onto a separate page for design/layout reasons.

pidOfListPage
-------------

Default: 0

Example: `plugin.tx_events2.settings.pidOfListPage = 231`

If you use one of the above settings, it would be useful to define the *pidOfListPage*, too, so that
a link back to list works like expected.

userGroup
---------

Default: 0

Example: `plugin.tx_events2.settings.userGroup = 21`

If you set this value to an UID of a frontend usergroup we will show a link in list view, where all
users of this usergroup can edit and create events. This will only work, if a user record was assigned to
an events2 organizer.

remainingLetters
----------------

Default: 250

Example: `plugin.tx_events2.settings.remainingLetters = 120`

Only valid wile creating new event record in frontend. Management plugin.

To prevent inserting a lot of text into various text fields like teaser in frontend plugin,
you can reducr the amount of allowed letters here. With help of a little JavaScript events2 will show
a little countdown how many remaining letters are allowed to enter.

latest
------

Special properties for list latest view:

amountOfRecordsToShow
~~~~~~~~~~~~~~~~~~~~~

Default: 7

Example: `plugin.tx_events2.settings.latest.amountOfRecordsToShow = 12`

How many records should be displayed in latest view.

.. important::

   There is no PageBrowser for latest view! So please be careful setting this value to a very high value.
   It can slow down rendering a lot.

pageBrowser
-----------

Special setting for PageBrowser

itemsPerPage
~~~~~~~~~~~~

Default: 15

Example: `plugin.tx_events2.settings.pageBrowser.itemsPerPage = 5`

Max amount of records to show for each page in PageBrowser.

insertAbove
~~~~~~~~~~~

Removed with events2 version 7.0.0.

Please edit fluid template and move PageBrowser on your own.

insertBelow
~~~~~~~~~~~

Removed with events2 version 7.0.0.

Please edit fluid template and move PageBrowser on your own.

maximumNumberOfLinks
--------------------

Removed with events2 version 7.0.0.

The new PageBrowser does not have any numbered page links like 1, 2, 3, 4.

As a developer you can use `PostProcessFluidVariablesEvent` to implement another
PageBrowser solution and reactivate this option here.

_LOCAL_LANG
-----------

As an integrator you can override each language key with TypoScript. For frontend events2 uses this file:

`EXT:events2/Resources/Private/Language/locallang.xlf`

Example: `plugin.tx_events2._LOCAL_LANG.de.listMyEvents = Zeige meine Veranstaltungen`
