Widget / ICalendarViewHelper
----------------------------

Use this Widget to create a link to an iCal formatted export
of given event record.

**Type:** Basic

General properties
^^^^^^^^^^^^^^^^^^

.. t3-field-list-table::
 :header-rows: 1

 - :Name: Name:
   :Type: Type:
   :Description: Description:

 - :Name: Event
   :Type: Domain/Model/Event
   :Description: The event record to create an iCal export for

Examples
^^^^^^^^

Basic example
"""""""""""""

Code: ::

  <e2:widget.iCalendar event="{event}" />
