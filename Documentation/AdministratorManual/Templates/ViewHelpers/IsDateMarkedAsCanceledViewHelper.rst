.. include:: ../../../Includes.txt

.. _isDateMarkedAsCanceledViewHelper:

================================
IsDateMarkedAsCanceledViewHelper
================================

Instead of looping all exceptions for a specific day in Fluid Template and check if there is
an exception of type *remove* we have created this ViewHelper. Just give it an event and a
date (\DateTime) and you will know, if this event was canceled for specifix date.

Example
=======

Code: ::

   <strong>
     {f:if(condition: '{e2:isDateMarkedAsCanceled(event: day.event, date: date)}', then: '<s>')}
     {date->f:format.date(format: '%A, %d.%m.%Y')}
     {f:if(condition: '{e2:isDateMarkedAsCanceled(event: day.event, date: date)}', then: '</s>')}
   </strong>
