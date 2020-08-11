.. include:: ../../../Includes.txt

.. _clearCache:

========================================
Clearing the cache after editing records
========================================

Events2 has a built-in mechanism that takes care of clearing the cache after manipulation of Event records.

When a list or detail view is rendered on a page, a cache tag in format ``tx_events2_pid_PID`` (where PID is
the uid of the events storage folder) is added. Each time an event record is edited, deleted or created, this
cache entry is flushed. No additional cache configuration is needed if only the Event plugins are used.

If you use other ways of displaying news records, the cache is not flushed automatically.

This can be done automatically by using this command in the PageTsConfig:

``TCEMAIN.clearCacheCmd = 123,124,125``

The code needs to be added to the sys folder where the event records are edited. Change the example page ids to
the ones which should be cleared.
