.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../_IncludedDirectives.rst

Updating
--------
If you update EXT:events2 to a newer version, please read this section carefully!

Update to Version 2.0.0
^^^^^^^^^^^^^^^^^^^^^^^

Version 2.0.0 will come with some new cols and we have removed some cols. So please be careful while comparing
database with TCA definition after upgrading.

.. important::

   Please do **not** delete cols in installtool after installing the new version! Only add the new fields,
   than go into extensionmanager, select events2 and start the upgrade script. Delete the old cols in installtool
   only, if the upgrade script symbol will not appear in extensionmanager anymore.
