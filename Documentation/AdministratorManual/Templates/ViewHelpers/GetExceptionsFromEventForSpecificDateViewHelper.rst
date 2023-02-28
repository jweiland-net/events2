..  include:: /Includes.rst.txt


..  _getExceptionsFromEventForSpecificDateViewHelper:

===============================================
GetExceptionsFromEventForSpecificDateViewHelper
===============================================

In case of recurring events you can have multiple exception types like *add*, *remove*, *info* and *time*.
Each of these exceptions are related to a specific date.

With this ViewHelper you can get all exceptions for a specific date. Further you can select, if you want
all exceptions or only exceptions of a specific exception type.

Example
=======

..  code-block:: html

    <f:for each="{e2:getExceptionsFromEventForSpecificDate(event: day.event, date: date, type: 'add,time,info')}" as="exception">
      <em>{exception.exceptionDetails -> f:format.html(parseFuncTSPath: 'lib.parseFunc') -> f:format.nl2br()}</em>
    </f:for>
