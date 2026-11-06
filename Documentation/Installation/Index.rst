:navigation-title: Installation

..  include:: /Includes.rst.txt
..  _installation:

============
Installation
============

..  contents::
    :caption: Table of contents
    :local:

..  _installation-composer:

Install events2 with Composer
=============================

If your TYPO3 installation works in composer mode, please execute
following command:

..  code-block:: bash

    composer req jweiland/events2
    vendor/bin/typo3 extension:setup --extension=events2

If you work with DDEV please execute this command:

..  code-block:: bash

    ddev composer req jweiland/events2
    ddev exec vendor/bin/typo3 extension:setup --extension=events2

..  _installation-extension-manager:

Install events2 with the Extension Manager
==========================================

On non composer based TYPO3 installations you can install `events2` still
over the Extension Manager:

..  rst-class:: bignums

1.  Login

    Login to backend of your TYPO3 installation as an administrator or
    system maintainer.

2.  Open the Extension Manager

    Click on :guilabel:`Admin Tools > Extensions` to open the Extension
    Manager.

3.  Update extensions

    Choose :guilabel:`Get Extensions` from the upper select box and click on
    the button :guilabel:`Update now` at the upper right.

4.  Install `events2`

    Use the search field to find `events2`. Choose the `events2` line from
    the search result and click on the cloud icon to install `events2`.

..  _installation-site-set:

Add the site set of events2
===========================

..  versionchanged:: 12.0.0

    The static TypoScript template :guilabel:`Events` has been removed.
    The TypoScript of events2 is provided by the site set
    `jweiland/events2` only.

Add the site set `jweiland/events2` as dependency to the site set of your
site package or select it in the site configuration in
:guilabel:`Site Management > Sites`:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/Sets/MySite/config.yaml

    name: my-vendor/my-site
    label: My site
    dependencies:
      - jweiland/events2

Configure events2 with the site settings of the site set, for example the
storage folder of the events (`events2.storagePid`) or the pages with the
plugins (`events2.pidOfDetailPage`). See :ref:`typoScript` and
:ref:`t3coreapi:site-sets`.

..  _installation-day-records:

Re-create the day records each day
==================================

..  versionchanged:: 12.0.0

    The scheduler task :guilabel:`Re-Create day records` has been removed.
    Use the command `events2:rebuild` with the option `--no-truncate`
    instead.

events2 works within a timeframe (see `recurringPast` and
`recurringFuture` in :ref:`extensionSettings`). Each day the oldest day
records have to be removed and the future day records have to be created.

Add a task of the class :guilabel:`Execute console commands` in the
module :guilabel:`System > Scheduler`, choose the command
`events2:rebuild` and enable its option `--no-truncate`. Let the task run
once a day, for example at night. As an alternative, run the command with
a cron job:

..  code-block:: bash

    vendor/bin/typo3 events2:rebuild --no-truncate

If the day records are not re-created regularly, it may happen that you
will not see any event on your website after 6 months. See
:ref:`commands-rebuild`.

..  _installation-next-step:

Next step
=========

:ref:`Configure events2 <configuration>`.
