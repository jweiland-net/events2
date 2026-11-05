:navigation-title: TypoScript

..  include:: /Includes.rst.txt
..  _typoScript:

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

..  versionchanged:: 12.0.0

    events2 does not set the keys `1` of `templateRootPaths` and
    `partialRootPaths` anymore. The templates of the Management plugin
    are located in the regular template folders now, see
    :ref:`frontend-management-templates`.

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

..  important::

    If you have stored Organizers and Locations in another Storage Folder, you have to add theses
    PIDs here, too.

..  tip::

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

pidOfManagementPage
-------------------

Default: 0

Example: `plugin.tx_events2.settings.pidOfManagementPage = 312`

Page UID with the Management plugin. Set it with the site setting
`events2.pidOfManagementPage`. The links to create and edit events in the
detail view and the links of the mails of the event form point to this
page. See :ref:`frontend-management-setup`.

..  _typoscript-settings-usergroup:

userGroup
---------

..  confval:: settings.userGroup
    :name: events2-typoscript-settings-usergroup
    :type: int
    :default: 0

    Example:

    ..  code-block:: typoscript
        :caption: EXT:my_sitepackage/Configuration/Sets/MySite/setup.typoscript

        plugin.tx_events2.settings.userGroup = 21

    UID of the frontend user group, which is allowed to create and edit
    events in the frontend. Set it with the site setting
    `events2.form.userGroup` of the site set `jweiland/events2`.

    Members of this group need a related organizer in their frontend user
    record. They see a link to create a new event and, for their own
    events, a link to edit the event in the detail view. Only they can
    create new events, also backend administrators can not. With the
    default value `0` nobody can create events in the frontend.

    The value is read from the TypoScript setup only, for the Management
    plugin and for the event form of EXT:form. A value in
    :typoscript:`plugin.tx_events2_management.settings.userGroup`
    overrides the value of :typoscript:`plugin.tx_events2.settings.userGroup`.
    Values of the FlexForm or of the settings of other plugins are not
    respected. See :ref:`frontend-management-access`.

    ..  versionchanged:: 12.0.0

        New events can only be created and listed by members of this user
        group. Before, backend administrators could create events without
        being a member. The value is read from the TypoScript setup only.

..  _typoscript-settings-removed:

Removed settings
----------------

..  versionchanged:: 12.0.0

    The settings `remainingLetters` and `new.uploadFolder` of the removed
    Extbase based event form have been removed together with the site
    settings `events2.form.remainingLetters` and
    `events2.form.uploadFolder`. See :ref:`upgrade-12-0-0-settings`.

new
---

Special properties for the event form of the Management plugin, see
:ref:`frontend-management`:

..  _typoscript-new-richtext-enable:

richText.enable
~~~~~~~~~~~~~~~

..  versionadded:: 11.2.0

    The value is set by the new site setting
    :confval:`events2.form.richText.enable <events2-form-richtext-enable>`.

Default: `0`

Example:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/Sets/MySite/setup.typoscript

    plugin.tx_events2.settings.new.richText.enable = 1

Use a small CKEditor 5 for the rich text elements of the event form based
on EXT:form. Requires EXT:rte_ckeditor. See
:ref:`frontend-rich-text-editor`.

..  _typoscript-new-richtext-elements:

richText.elements
~~~~~~~~~~~~~~~~~

..  versionadded:: 11.2.0

    The value is set by the new site setting
    :confval:`events2.form.richText.elements <events2-form-richtext-elements>`.

Default: `detail-description`

Example:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/Sets/MySite/setup.typoscript

    plugin.tx_events2.settings.new.richText.elements = detail-description, program

Comma-separated identifiers of the form elements which get the rich text
editor. Elements with :yaml:`dataType: richText` in the form definition get
the editor, too. See :ref:`frontend-rich-text-editor-elements`.

latest
------

Special properties for list latest view:

amountOfRecordsToShow
~~~~~~~~~~~~~~~~~~~~~

Default: 7

Example: `plugin.tx_events2.settings.latest.amountOfRecordsToShow = 12`

How many records should be displayed in latest view.

..  important::

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

_LOCAL_LANG
-----------

As an integrator you can override each language key with TypoScript. For frontend events2 uses this file:

`EXT:events2/Resources/Private/Language/locallang.xlf`

Example: `plugin.tx_events2._LOCAL_LANG.de.listMyEvents = Zeige meine Veranstaltungen`
