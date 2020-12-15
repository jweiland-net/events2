.. include:: ../../Includes.txt

.. _extensionSettings:

==================
Extension Settings
==================

Some general settings for events2 can be configured in *Admin Tools -> Settings*.

The settings are divided into several tabs and described here in detail:

Tab: Basic
==========

poiCollectionPid
""""""""""""""""

Default: 0

Only valid, if you have installed EXT:maps2, too.

While creating location records we catch the address and automatically create a maps2 record
for you. Define a storage PID where we should store these records.

rootUid
"""""""

Default: 0

If you have many sys_category records with huge trees in your TYPO3 project, it may make sense to
reduce the category trees in our Plugins to a parent category UID (root UID).

recurringPast
"""""""""""""

Default: 3

Our events2 works within a timeframe. This means, that you have to set an earliest start (in months) for generated
day records in past. A value of 3 means 3 months in past. Older events will not be shown in frontend anymore, but are
still callable by a Google Search.

recurringFuture
"""""""""""""""

Default: 6

Our events2 works within a timeframe. This means, that you have to set a latest end (in months) for generated
day records in future. A value of 6 means 6 months in future. Events above 6 month in future will not be shown
in frontend.

defaultCountry
""""""""""""""

Default: empty

While creating location records we also create a record for EXT:maps2 while saving.
If you're only working for one specific country while creating/editing locations
in backend, it may be helpful to set country property with this default country. So you only need
to add street and city to find a POI. If you need POIs from all over the world, please keep this field empty.

xmlImportValidatorPath
""""""""""""""""""""""

Default: EXT:events2/Resources/Public/XmlImportValidator.xsd

If you use our XML importer we will validate your XML structure against a XSD file. So, if you use name-Tag for
categories the import will fail, because XSD knows that only a title-Tag is valid.

By default organizer, location and categories are mandatory while import. If you set organizer and/or
location as non-mandatory in Extension Setting this has no effect to the importer. Please make a copy of
our XSD file and add the modifications you need and set new file path into this variable.

You can prefix the path with EXT:

organizerIsRequired
"""""""""""""""""""

Default: false

If you want, you can set column *Organizer* as required. That way an editor has to fill this column.

locationIsRequired
""""""""""""""""""

Default: false

If you want, you can set column *Location* as required. That way an editor has to fill this column.

Tab: Email
==========

emailFromAddress
""""""""""""""""

Default: empty (use value from INSTALL_TOOL)

With events2 you can give your website visitors the possibility to create new
events. These created records will be hidden by default. Add an email address
of the sender, if a new record was created over the frontend.

emailFromName
"""""""""""""

Default: empty (use value from INSTALL_TOOL)

With events2 you can give your website visitors the possibility to create new
events. These created records will be hidden by default. Add a name
of the sender, if a new record was created over the frontend.

emailToAddress
""""""""""""""

Default: empty

With events2 you can give your website visitors the possibility to create new
events. These created records will be hidden by default. Add an email address
of the receiver, if a new record was created over the frontend.

emailToName
"""""""""""

Default: empty

With events2 you can give your website visitors the possibility to create new
events. These created records will be hidden by default. Add a name
of the receiver, if a new record was created over the frontend.
