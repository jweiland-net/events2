..  include:: /Includes.rst.txt


..  _convertToJsonViewHelper:

=======================
ConvertToJsonViewHelper
=======================

We have some JS in events2 to search for Sub-Categories and other various Ajax-Calls. To handle
them we add some environment specific variables to template like host name, current storage PIDs and
TS settings.

You can use this VH to convert an array into JSON format to have variables in a better
accessible format for JavaScript.

Example
=======

..  code-block:: html

    <div class="events2calendar" data-environment="{environment->e2:convertToJson()}"></div>
