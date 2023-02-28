..  include:: /Includes.rst.txt


..  _events2Record:

============
Event record
============

Columns
=======

Top of List
-----------

Default: false

If activated this event will always be on top of list. If you have multiple events marked *top_of_list*
events2 will order them by title DESC.

..  hint::

    If you mark a recurring event with a lot of dates as `top of list`, this record with ALL of its
    dates will move to top. So it may happen, that the next event will be shown on page 2 or 3 in PageBrowser.
    In such cases we prefer to set on of the `merge` Features in plugin configuration.

Title
-----

Default: empty

The title of the event.

Event Type
----------

Default: single

There are currently three different kinds of event types available. Choose one

**single**

This event will be visible for just one day. It is not possible to add or remove any days

**duration**

Think at holidays. You're away from 17.07.2020 - 21.07.2020. So it is not possible for others to take part on
your holidays at 20.07.2020. This is the main difference to recurring events where you can create different time
records for each day.

**recurring**

Think at a meeting two times a week. With recurring events you can create events like 1st and 3rd monday and friday
a week, beginning at 08:00 and 16:00 o'clock except 24.12. because of X-Mas but additional at 17.07.2020, because
of a special guest and please use a different time slot, if event matches friday.

Path Segment
------------

Default: An URL interpretable string of column title

Path segment for speaking URLs

Event Begin
-----------

Default: today

For single event: Date of Event
For duration  event: First date of Event
For recurring event: First date to start day generation

Event End
---------

Default: empty

For duration event: Last date of Event

End of recurring
----------------

Default: empty

For recurring event: Last date of day generation

Time
----

Add a time record, to define times for:
Begin, Entry, Duration and End. This record is a default for all
recurring days, but can be overwritten by other time records.

Same Day
--------

Default: false

After activating this checkbox the form will be reloaded and you have the
possibility to assign multiple time records for one day. But be careful, if
you define other time records for a special weekday below, it will overwrite
this setting completely.

Multiple Times
--------------

Default: empty

See description for column *same_day*

Recurring
---------

How often should this event repeated.

Example: Each **1st** and **3rd** monday a month

Weekday
-------

How often should this event be repeated.

Example: Each 1st **monday** and **wednesday** a month

Different times each weekday
----------------------------

You can set another time for a specified weekday. Be careful: This setting will
overwrite the setting of multiple times above completely, if it matches.

Recurring weeks
---------------

If you change that value the complete form will reload and you can set a recurring over weeks.

Recurring months
----------------

If you change that value the complete form will reload and you can set a recurring over months.

Exceptions
----------

With this records you can define some specials for your recurring event.
You can add or remove a special date for your recurring. Further you can define some
additional information or change the times for a special date.

Teaser
------

This is a short version (1 or 2 sentences) of your detail information.
It will be displayed in listings. If not given the first 100 letters of
detail information will be displayed

Detail information
------------------

This detail text will be displayed at the detail page of the event record.

Free entry
----------

If a visitor can access this event for free activate this checkbox.

Ticket Link
-----------

A link to the organizer, where you can buy tickets.

Location
--------

Where will this event take place?

This is a required field, if it was set as *required* in Extension Settings.

Organizer
---------

Who will organize this event?

This is a required field, if it was set as *required* in Extension Settings.

Images
------

Add one or more images to your event. You can resize them by TS-Settings

Link to YouTube
---------------

If you have created a little presentation video for this event, you
can assign the link to YouTube here. Hint: Only YouTube-Links are valid.

Download Links
--------------

Add some brochures with more information as PDF or similar here.
