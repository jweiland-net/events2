.. include:: ../../../Includes.txt

.. _getMergedEventTimesViewHelper:

=============================
GetMergedEventTimesViewHelper
=============================

For each event you can create a general time record, multiple time records for same day, different
time records for each weekday and you can create a different time records for a specific date as
exception.

As you see it's hard to see with time record is valid, which has priority and which time record
has which sorting.

With this ViewHelper you only set an event and a date (\DateTime) and you will get
the correct time record(s) as array.

Example
=======

.. code-block:: html

   <f:alias map="{times: '{e2:getMergedEventTimes(event: day.event, date: date)}'}">
     <f:for each="{times}" as="time">
       <f:render section="showDateAndTime" arguments="{day: day, date: date, time: time}" />
     </f:for>
   </f:alias>
