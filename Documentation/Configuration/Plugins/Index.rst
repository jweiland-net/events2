..  include:: /Includes.rst.txt


..  _plugins:

========
Plugins
========

List all events
===============

Choose view
-----------

Deprecated, as switchableControllerActions are deprecated in TYPO3 core since TYPO3 11.

Please use `List type` instead.

List type
---------

You can choose between following list types:

*   List events: Shows a list of events incl. a PageBrowser
*   List latest events: Just the next x events without PageBrowser
*   List events of today: Shows only events of today
*   List events of this week (Mo-Su). On tuesday (Tu-Su). On friday (Fr-Su)
*   List events next 4 weeks: Show events from today until 4 weeks

Page UID of detail page
-----------------------

Default: 0

Often it is useful to move the detail view onto a separate page for design/layout reasons.

Page UID of list page
---------------------

Default: 0

If you have configured a detail page, this option will help you to link back to the list page.

Page UID for the event location
-------------------------------

Default: 0

Often it is useful to move the location view onto a separate page for design/layout reasons.

PID of search page
------------------

Removed with events2 8.0.0.

Please use one of the search plugins instead or re-add that settings with TypoScript.

Categories
----------

Default: empty

Reduce the set of events in Frontend to the selected categories.

It's an OR combination.

List amount of records
----------------------

Default: 0 (means use default from TypoScript which is 7)

Only valid for *latest view*

Reduce the set of events in Frontend to X records.

Merge events with multiple times
--------------------------------

Default: false

By default a created event with multiple time records for one day will be shown in list view with
multiple entries, too. Activating this feature will merge an events with multiple time records
to just one entry in Frontend.

Example: 17.07.2020 with 08:00, 12:00 and 16:00 will create 1 visible entry in Frontend list.

By default we add an additional link to detail view for further information.

Merge recurring events
----------------------

Default: false

By default recurring events performed on multiple days, will be shown in list view with
multiple entries, too. Activating this feature will merge an event with multiple day records
to just one entry in Frontend. If this property was activated the property
*Merge events with multiple times* has no effect anymore.

Example: 17.07.2020, 18.07.2020 and 20.07.2020 will be merged to 17.07.-20.07.2020.

As you see in example there is no information about missing 19.07.2020. That's why we
add an additional link to detail view to show the exact dates.

We prefer using this property only for latest view, as it may confuse website visitors
with durational events.

Pre filter by organizer
-----------------------

Default: empty

You can reduce the list of events in Frontend to a specific Organizer.

Show organizer filter selection
-------------------------------

Default: empty

You can activate an Organizer Selector in Frontend where the website visitor can
reduce the events to its preferred Organizer.

..  important::

    The Selector is only visible as long as there are at least 2 Organizers available.

Display
-------

Default: Show both

Splits the event output. That way it's possible to show event images as f.e. Flexslider
in header and somewhere below (in an additional Events Plugin) the event with setting
*only events*

**Show both**

One plugin shows images and event.

**Show images only**

One plugin shows the images of an event only. Useful to slide images in header.

**Show Event only**

One plugin shows the event data (no images).

Selectable categories for new Events
------------------------------------

Removed with events2 version 8.0.0.

All options to create new events was moved into Management plugin.

Show single event
=================

Page UID of list page
---------------------

Default: 0

If you have configured a detail page, this option will help you to link back to the list page.

Page UID for the event location
-------------------------------

Default: 0

Often it is useful to move the location view onto a separate page for design/layout reasons.

Display
-------

Default: Show both

Splits the event output. That way it's possible to show event images as f.e. Flexslider
in header and somewhere below (in an additional Events Plugin) the event with setting
*only events*

**Show both**

One plugin shows images and event.

**Show images only**

One plugin shows the images of an event only. Useful to slide images in header.

**Show Event only**

One plugin shows the event data (no images).

Events2: Calendar
=================

Categories
----------

Default: empty (Show all events)

You can reduce the set of events shown in calendar to a list of given categories.

This is an OR-Query.

Search Form
===========

Use this plugin, to show a search form. Please consider to also add and configure the search results plugin.

PID of search results
---------------------

Default: 0

Please set the page UID where you have added the events2 plugin for search results.

Main categories
---------------

Default: empty

Select all categories which should be selectable as main categories in search form template.

All selected categories will be checked by events2 if they are direct child categories
of a given root category defined in TypoScript *rootCategory*. So, if an editor selects sub-sub-categories
of configured *rootCategory* these will NOT be shown in Selector of search form.

If you want search form to not use Category Selectors in Frontend you have to remove
this part from Fluid-Template within your own SitePackage.

Search Results
==============

Add this plugin to show the search results, coming from search form plugin.

Manage Events
=============

With this plugin registered FE users can create and manage their own events in frontend.

Selectable categories for new Events
------------------------------------

Default: empty

Here you can select the categories, which a website visitor can choose from while
creating new Events in Frontend.

Hint for Integrators: As an editor can select all categories from each layer of the
category tree, we have implemented a further restriction in TS *rootCategory*. That way
only chosen categories as direct child categories of *rootCategory* (Parent Category)
will be selectable in Frontend form.
