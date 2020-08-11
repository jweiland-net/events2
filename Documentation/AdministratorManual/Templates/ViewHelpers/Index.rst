.. include:: ../../../Includes.txt

.. _viewHelpers:

==========================
ViewHelpers of EXT:events2
==========================

ViewHelpers are used to add logic inside the view.
There basic things like if/else conditions, loops and so on. The system extension fluid has the most important ViewHelpers already included.

To be able to use a ViewHelper in your template, you need to follow always the same structure which is:

.. code-block:: html

   <f:fo>bar</f:fo>

This would call the ViewHelper fo of the namespace **f** which stands for fluid.
If you want to use ViewHelpers from other extensions you need to add the namespace
declaration at the beginning of the template. The namespace declaration for the events2 extension is:

.. code-block:: html

   {namespace e2=JWeiland\Events2\ViewHelpers}


Now you can use a ViewHelper of events2 with a code like:

.. code-block:: html

   <e2:trimExplode><!-- some comment --></e2:trimExplode>


If you want to know what a ViewHelper does, it is very easy to find the related PHP class by looking at the namespace and the name of the ViewHelper.
Having e.g. ``JWeiland\Events2\ViewHelpers`` and ``convertToJson`` you will find the class at ``events2\\Classes\ViewHelpers\\ConvertToJsonViewHelper.php``.

The most of awesome thing is that you can use ViewHelpers of any extension in any other template by just adding another namespace declaration like:

.. code-block:: html

   {namespace something=OtherNamespace\OtherExtension\ViewHelpers}


and call the ViewHelper like

.. code-block:: html

   <something:NameOfTheViewHelper />


All ViewHelpers
---------------

.. toctree::
   :maxdepth: 2

   Widget/ICalendarViewHelper
   ConvertToJsonViewHelper
   CreateYoutubeUrlViewHelper
   FeUserViewHelper
   GetAllowedCategoriesViewHelper
   GetExceptionsFromEventForSpecificDateViewHelper
   GetMergedEventTimesViewHelper
   IsDateMarkedAsCanceledViewHelper
