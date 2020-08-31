.. include:: ../Includes.txt

.. _installation:

============
Installation
============

Installation Type
=================

Composer
""""""""

You can install events2 with following shell command:

.. code-block:: bash

   composer req jweiland/events2

Extensionmanager
""""""""""""""""

If you want to install events2 traditionally with Extensionmanager, follow these steps:

#. Visit ExtensionManager

#. Switch over to `Get Extensions`

#. Search for `events2`

#. Install extension

DEV Version (GIT)
"""""""""""""""""

You can install the latest DEV Version with following GIT command:

.. code-block:: bash

   git clone https://github.com/jweiland-net/events2.git

Scheduler Task
==============

You have to add the Scheduler Task *Re-Create day records* which will be executed each day.
As events2 works within a timeframe, each day the oldest day-record have to be removed and the future day-record
have to be created.

If you do not install this task it may happen that you will not see any event record on your website after 6 months.
