.. include:: ../../Includes.txt

.. _events2Record:

Event record
============

The Event record is the most important record in this extension.

.. t3-field-list-table::
 :header-rows: 1

 - :Field:
         Field
   :Description:
         Description
 - :Field:
         Event type
   :Description:
         There are currently three different kinds of types available
         Choose one of Single, Recurring and Duration.
 - :Field:
         Top of list
   :Description:
         The event will be always on top of list in frontend. You can assign
         them a CSS class to highlight them in Fluid-Template, if you want.
 - :Field:
         Title
   :Description:
         A short title of the event.
 - :Field:
         Event begin
   :Description:
         Only available for: Single and Duration.
         Define the first date, when this event starts.
 - :Field:
         Event end
   :Description:
         Only available for: Duration.
         Define the last day of this event.
 - :Field:
         Time
   :Description:
         Add a time record, to define times for:
         Begin, Entry, Duration and end. This record is a default for all
         recurring days, but can be overwritten by other time records.
 - :Field:
         Recurring end
   :Description:
         Normally a recurring is unlimited. The dates will be created for the next 6
         month. With this setting you can assign a date where recurring should end.
 - :Field:
         Same day
   :Description:
         After activating this checkbox the form will be reloaded and you have the
         possibility to assign multiple time records for one day. But be careful, if
         you define other time records for a special weekday below, it will overwrite
         this setting completely.
 - :Field:
         Multiple times on same day
   :Description:
         See above. Same day.
 - :Field:
         Recurring
   :Description:
         How often should this event repeated. Example: Each **1st** monday a month
 - :Field:
         Weekday
   :Description:
         How often should this event repeated. Example: Each 1st **monday** a month
 - :Field:
         Different times each weekday
   :Description:
         You can set another time for a specified weekday. Be careful: This setting will
         overwrite the setting of multiple times above completely, if it matches.
 - :Field:
         Recurring weeks
   :Description:
         If you change that value the complete form will reload and you can set a recurring over weeks.
 - :Field:
         Recurring months
   :Description:
         If you change that value the complete form will reload and you can set a recurring over months.
 - :Field:
         Exceptions
   :Description:
         With this records you can define some specials for your recurring event.
         You can add or remove a special date for your recurring. Further you can define some
         additional information or change the times for a special date.
 - :Field:
         Event teaser
   :Description:
         This is a short version (1 or 2 sentences) of your detail information.
         It will be displayed in listings. If not given the first 100 letters of
         detail information will be displayed
 - :Field:
         Detail information
   :Description:
         This detail text will be displayed at the detail page of the event record.
 - :Field:
         Free entry
   :Description:
         If a visitor can access this event for free activate this checkbox.
 - :Field:
         Ticket Link
   :Description:
         A link to the organizer, where you can buy tickets.
 - :Field:
         Location
   :Description:
         This is a required field. Where will this event take place.
 - :Field:
         Organizer
   :Description:
         This is a required field. How will organize this event.
 - :Field:
         Images
   :Description:
         Add one or more images to your event. You can resize them by TS-Settings
 - :Field:
         Link to YouTube
   :Description:
         If you have created a little presentation video for this event, you
         can assign the link to YouTube here. Hint: Only YouTube-Links are valid.
 - :Field:
         Download Links
   :Description:
         Add some images or brochures with more information as PDF here.

Type: Single
------------
Choose type single, if you want to create a record which takes place at
only one day. But you have the possibility to create individual dates over the
exception tab.

Type: Recurring
---------------
With this type you will get access to an additional tab called Recurring. At this
tab you can define start and end of recurring and of cause at which weekdays this
event should take place.

Example: Each 1st and 3rd Tuesday and Friday a month

Or, you can define a recurring over weeks

Example: Each 3rd week.

Type: Duration
--------------
This is a special type for trips or holidays. Such kinds of events will always have
a start and end date and will be displayed like this in frontend:

12.04.2016-16.04.2016

At such dates you can't attend at 14.04.2016. You have to take place the full
duration of this event.
