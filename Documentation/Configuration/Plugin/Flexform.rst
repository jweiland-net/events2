.. include:: ../../Includes.txt

.. _flexform:

========
Flexform
========

Plugin: Events
==============

Choose view
"""""""""""

You can choose between views:

* List events: Shows a list of events incl. a PageBrowser
* List latest events: Just the next x events without PageBrowser
* List events of today: Shows only events of today
* List events of current week (Mo-Su). On tuesday (Tu-Su). On friday (Fr-Su)
* List events next 4 weeks: Show events from today until 4 weeks

PID of detail page
""""""""""""""""""

Default: 0

Often it is useful to move the detail view onto a separate page for design/layout reasons.

PID of list page
""""""""""""""""

Default: 0

Often it is useful to move the search view onto a separate page for design/layout reasons.

PID of location page
""""""""""""""""""""

Default: 0

Often it is useful to move the location view onto a separate page for design/layout reasons.

PID of search page
""""""""""""""""""

Default: 0

If you use one of the above settings, it would be useful to define the *pidOfListPage*, too, so that
a link back to list works like expected.

Categories
""""""""""

Default: empty

Reduce the set of events in Frontend to the selected categories.
It's an OR combination.

List amount of records
""""""""""""""""""""""

Default: 0 (means use default from TS which is 7)

Only valid for *latest view*

Reduce the set of events in Frontend to X records.

Merge events with multiple times
""""""""""""""""""""""""""""""""

Default: false

By default a created event with multiple time records for one day will be shown in list view with
multiple entries, too. Activating this feature will merge an events with multiple time records
to just one entry in Frontend.

Example: 17.07.2020 with 08:00, 12:00 and 16:00 will create 1 visible entry in Frontend list.

By default we add an additional link to detail view for further information.

Merge recurring events
""""""""""""""""""""""

Default: false

By default recurring events performed on multiple days, will be shown in list view with
multiple entries, too. Activating this feature will merge an events with multiple day records
to just one entry in Frontend. If this property was activated the property
*Merge events with multiple times* has no effect anymore.

Example: 17.07.2020, 18.07.2020 and 20.07.2020 will be merged to 17.07.-20.07.2020.

As you see in example there is no information about missing 19.07.2020. That's why we
add an additional link to detail view to show the exact dates.

We prefer using this property only for latest view, as it may confuse website visitors
with durational events.

Pre filter by organizer
"""""""""""""""""""""""

Default: empty

You can reduce the list of events in Frontend to a specific Organizer.

Show organizer filter selection
"""""""""""""""""""""""""""""""

Default: empty

You can activate an Organizer Selector in Frontend where the website visitor can
reduce the events to its preferred Organizer.

The Selector is only visible as long as there are at least 2 Organizers available.

Display
"""""""

Default: Show both

Splits the event output. That way it's possible to show event images as f.e. Flexslider
in header and somewhere below (in an additional Events Plugin) the event with setting
*only events*

**Show both**

One Events Plugin shows images and event.

**Show images only**

One Events Plugin shows the images of an event only. Useful to slide images in header.

**Show Event only**

One Events Plugin shows the event data (no images).

Selectable categories for new Events
""""""""""""""""""""""""""""""""""""

Default: empty

Here you can select the categories, which a website visitor can choose from, while
creating new Events in Frontend.

Hint for Integrators: As the editor can select all categories from each layer of the
category tree, we have implemented a further restriction in TS *rootCategory*. That way
only chosen categories as direct child categories of *rootCategory* (Parent Category)
will be selectable in Frontend Form.

Plugin: Calendar
================

Categories
""""""""""

Default: empty (Show all events)

You can reduce the set of events shown in calendar to a list of given categories.
This is an OR-Query.

Plugin: Search
==============

Main categories
"""""""""""""""

Default: empty

Select all categories which should be shown in Selector of Creation Form in Frontend.

All selected categories will be pre-checked by events2 to be a direct child category
of category defined in TypoScript *rootCategory*. So, if an editor selects sub-sub-categories
of *rootCategory* these will not be shown in Selector of Creation Form.

If you want Plugin Search to not use Category Selectors in Frontend you have to remove
this part from Fluid-Template within your own SitePackage.
