.. include:: ../../Includes.txt

.. _howToStart:

============
How to start
============

This walkthrough will help you to implement the extension events2 at your
TYPO3 site. The installation is covered :ref:`here <installation>`.

Create the records
------------------
Before any events2 record can be shown in the frontend, those need to be
created.

#. Create a new sysfolder and switch to the list module. (Of
   course you can also use an existing sysfolder).

#. Switch to **List module**

#. Use the icon in the topbar "Create new record" and search for "Events2" and its
   record "Event".

#. Click on "Event" to create a new event record. Choose an event type and
   fill as many fields you want. The required fields are highlighted in red.

Add a plugin to a page
----------------------
A plugin is used to render a defined selection of records in the frontend.
Follow this steps to add a plugin to a page:

Events
^^^^^^

#. Create a new page with a title like "Events" which will be used to show
   your created events records.

#. Add a new content element and select the entry "Insert Plugin"

#. Switch to the tab "Plugin" where you can define the plugin settings and
   set selected plugin to "Events".

#. Save the plugin.

Events: Calendar
^^^^^^^^^^^^^^^^

#. Create a new page with a title like "Events" which will be used to show
   your created events records.

#. Add a new content element and select the entry "Insert Plugin"

#. Switch to the tab "Plugin" where you can define the plugin settings and
   set selected plugin to "Events: Calendar".

#. Save the plugin.

Events: Search
^^^^^^^^^^^^^^

#. Create a new page with a title like "Events" which will be used to show
   your created events records.

#. Add a new content element and select the entry "Insert Plugin"

#. Switch to the tab "Plugin" where you can define the plugin settings and
   set selected plugin to "Events: Search".

#. Save the plugin.
