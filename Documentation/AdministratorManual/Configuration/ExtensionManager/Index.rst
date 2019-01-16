.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../_IncludedDirectives.rst

.. _extensionManager:

Extension Manager
-----------------

Some general settings can be configured in the Extension Manager.
If you need to configure those, switch to the module "Extension Manager",
select the extension "**|extension_key|**" and press on the configure-icon!

The settings are divided into several tabs and described here in detail:

Properties
^^^^^^^^^^

.. container:: ts-properties

  ====================== ======== =========
  Property                Tab      Default
  ====================== ======== =========
  poiCollectionPid_       basic    0
  rootUid_                basic    0
  recurringPast_          basic    3
  recurringFuture_        basic    6
  defaultCountry_         basic
  emailFromAddress_       basic
  emailFromName_          basic
  emailToAddress_         basic
  emailToName_            basic
  ====================== ======== =========

.. _extensionManager_poiCollectionPid_:

poiCollectionPid
""""""""""""""""

While creating organizers we catch the address and create a maps2 record
automatically for you. Define a storage PID where we should store these records.

.. _extensionManager_rootUid:

rootUid
"""""""

If you have many sys_category records with huge trees it would be good to
reduce such category trees to specified root UID.

.. _extensionManager_recurringPast:

recurringPast
"""""""""""""

We can't create the day records for a recurring event for an unlimited
time. This would cost too much performance and will create too much day records.
With this setting you can reduce the generation to a specified amount of month.

.. _extensionManager_recurringFuture:

recurringFuture
"""""""""""""""

We can't create the day records for a recurring event for an unlimited
time. This would cost too much performance and will create too much day records.
With this setting you can reduce the generation to a specified amount of month.

.. _extensionManager_defaultCountry:

defaultCountry
""""""""""""""

While creating event locations we also create a record for EXT:maps2 while saving.
If you're only working for one specified country while creating/editing locations
in backend it would be cool to set a country by default. So you only need
to add street and city to find a POI. If you need POIs from all over the world,
please keep this field empty.

.. _extensionManager_emailFromAddress:

emailFromAddress
""""""""""""""""

With events2 you can give your website visitors the possibility to create new
events. These created records will be hidden by default. Add an email address
of the sender, if a new record was created over the frontend.

.. _extensionManager_emailFromName:

emailFromName
"""""""""""""

With events2 you can give your website visitors the possibility to create new
events. These created records will be hidden by default. Add a name
of the sender, if a new record was created over the frontend.

.. _extensionManager_emailToAddress:

emailToAddress
""""""""""""""

With events2 you can give your website visitors the possibility to create new
events. These created records will be hidden by default. Add an email address
of the receiver, if a new record was created over the frontend.

.. _extensionManager_emailToName:

emailToName
"""""""""""

With events2 you can give your website visitors the possibility to create new
events. These created records will be hidden by default. Add a name
of the receiver, if a new record was created over the frontend.
