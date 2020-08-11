.. include:: ../../../Includes.txt

.. _commands:

========
Commands
========

events2:rebuild
===============

In earlier versions of events2 some day records are wrong because of problems with daylight saving times. In the
meantime we have solved that problem and created a repair command whose name is now rebuild-command.

If you expect still some problems in day generation it may make sense to execute this command or, if you
want to cleanup day table in general. But maybe your day table is too big for your export. In that case
you can rebuild the day records on new server.

Instead of the scheduler task *Re-Create day records* this command will additionally TRUNCATE the complete day table.

Be careful: As day table will be TRUNCATED it is possible that there are temporarily no events in frontend visible.
