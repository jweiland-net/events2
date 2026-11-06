:navigation-title: Commands

..  include:: /Includes.rst.txt
..  _commands:

===================
Commands of events2
===================

events2 provides the following console commands. All of them are
schedulable: Add a task of the class :guilabel:`Execute console commands`
in the module :guilabel:`System > Scheduler`, choose the command and fill
in its arguments and options.

..  versionchanged:: 12.0.0

    The scheduler tasks :guilabel:`Re-Create day records` and
    :guilabel:`Import events` have been removed. Use the commands
    `events2:rebuild --no-truncate` and `events2:import` instead. See
    :ref:`upgrade-12-0-0-scheduler`.

..  contents::
    :caption: Table of contents
    :local:

..  _commands-rebuild:

Re-create day records with events2:rebuild
==========================================

The command searches all current and future events and re-creates their
day records. Execute it each day to move the timeframe of the day records
(see :ref:`installation-day-records`), if you expect problems in the day
records or if you want to clean up the day table in general.

If your day table is very big, it may make sense to exclude this table
from SQL export or migration. On the target server you just need to
execute this command to re-build all missing day records again.

..  code-block:: bash

    # Re-create the day records event by event
    vendor/bin/typo3 events2:rebuild --no-truncate

    # TRUNCATE the day table first and re-create all day records
    vendor/bin/typo3 events2:rebuild

`--no-truncate`
    Keeps the day table and re-creates the day records event by event. The
    events stay visible in the frontend while the command is running. Use
    this option for recurring executions with the scheduler or a cron
    job.

..  important::

    Without the option `--no-truncate` the command TRUNCATES the complete
    day table first. Until it has finished, there are no or only some
    events visible in the frontend.

..  _commands-import:

Import events with events2:import
=================================

..  versionadded:: 12.0.0

    The command replaces the scheduler task :guilabel:`Import events`.

The command imports the events of an XML file into a storage folder. The
file is validated against the XSD file of the extension setting
`xmlImportValidatorPath` (see :ref:`extensionSettings`). Examples of XML
files are available in the folder
`Tests/Functional/Fixtures/XmlImport <https://github.com/jweiland-net/events2/tree/main/Tests/Functional/Fixtures/XmlImport>`__
of events2.

..  code-block:: bash

    vendor/bin/typo3 events2:import 1:/event_import/Import.xml 42

`path`
    Combined identifier of the XML file in the format
    `[storage UID]:[path to file]`, for example
    `1:/event_import/Import.xml`. Required.

`storagePid`
    UID of the page (storage folder) where the imported records are
    stored. Has to be greater than `0`. Required.

The messages of the import are shown on the command line and written to
the file :file:`Messages.txt` next to the XML file. After a successful
import the XML file is deleted. If the XML file does not exist, the
command ends successfully without doing anything. So you can schedule the
command and upload a new file whenever you want to import events.

..  _commands-export:

Export events with events2:export
=================================

The command sends the events of the given storage folders to another
TYPO3 instance with the system extension EXT:reactions.

..  code-block:: bash

    vendor/bin/typo3 events2:export \
        'https://example.org/typo3/reaction/[identifier]' \
        '[secret]' \
        '12,34' \
        --categories='5,6'

`url`
    URL of the reaction of the target instance. Copy it from the module
    of EXT:reactions. Required.

`secret`
    Secret of the reaction. It is shown only once while creating the
    reaction in the target instance. Required.

`storagePages`
    Comma-separated UIDs of the storage folders to collect the events
    from. Required.

`--categories`, `-c`
    Comma-separated UIDs of categories to filter the collected events.
