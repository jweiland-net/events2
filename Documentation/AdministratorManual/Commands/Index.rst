.. include:: ../../Includes.txt

.. _commands:

========
Commands
========

events2:rebuild
===============

In earlier versions of events2 some day records are wrong because of problems with daylight saving times. In the
meantime we have solved that problem and created a `repair` command whose name is now `rebuild`.

If you expect still some problems in day generation, it may make sense to execute this command or, if you
want to cleanup day table in general.
If your day table is very big, it may make sense to exclude this table from SQL export/migration. On target
project/server you just need to execute this command to re-build all missing day records again.

Since `events2` version 8.0.0 this CLI command is also schedulable (can be configured in scheduler module).

.. tip::

   Instead of the scheduler task `Re-Create day records` this command will additionally TRUNCATE the complete day table!

.. important::

   As day table will be TRUNCATED it is possible that there are temporarily no events in frontend visible!
