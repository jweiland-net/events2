..  include:: /Includes.rst.txt


..  _installation:

============
Installation
============

Composer
========

If your TYPO3 installation works in composer mode, please execute following command:

..  code-block:: bash

    composer req jweiland/events2
    vendor/bin/typo3 extension:setup --extension=events2

If you work with DDEV please execute this command:

..  code-block:: bash

    ddev composer req jweiland/events2
    ddev exec vendor/bin/typo3 extension:setup --extension=events2

ExtensionManager
================

On non composer based TYPO3 installations you can install `events2` still over the ExtensionManager:

..  rst-class:: bignums

1.  Login

    Login to backend of your TYPO3 installation as an administrator or system maintainer.

2.  Open ExtensionManager

    Click on `Extensions` from the left menu to open the ExtensionManager.

3.  Update Extensions

    Choose `Get Extensions` from the upper selectbox and click on the `Update now` button at the upper right.

4.  Install `events2`

    Use the search field to find `events2`. Choose the `events2` line from the search result and click on the cloud
    icon to install `events2`.

Scheduler Task
==============

You have to add the Scheduler Task *Re-Create day records* which will be executed each day.
As events2 works within a timeframe, each day the oldest day-record have to be removed and the future day-record
have to be created.

If you do not install this task it may happen that you will not see any event record on your website after 6 months.

Next step
=========

:ref:`Configure events2 <configuration>`.
