.. include:: ../../../Includes.txt

.. _getAllowedCategoriesViewHelper:

==============================
GetAllowedCategoriesViewHelper
==============================

Ok, following scenario: You have an events2 Plugin where you have selected the categories
*house* and *tree*. Further you have an event which is assigned to the categories *performance*,
*cinema* and *tree*. Of cause this event will be shown in Frontend, because category *tree* matches.

BUT: If you want to show the first possible category in Frontend, it is possible that *performance*
will be shown, which is not allowed by the Plugin.

This ViewHelper creates an intersection of both category selections which contains
the allowed categories only.

Example
=======

Code: ::

   <f:for each="{e2:getAllowedCategories(event: event, pluginCategories: settings.categories)}" as="category" iteration="iterator">
     <f:if condition="{iterator.isFirst}">{category.title}</f:if>
   </f:for>
