..  include:: /Includes.rst.txt


..  _faq:

===
FAQ
===

Translations
============

If you translate an event into a new language, events2 will not create day records for this language. That's because
you may have changed the `event_type` of an event. In that case events2 can not associate a day record of
default language to day records of the translated record. As a solution we have disabled the translation feature
for day table completely.

This approach has one disadvantage:

You currently can't search for translated events in frontend search.


Where are the day records build?
================================

We build the day records while saving an event record in backend. Further
we re-build them while executing the command `events2:rebuild`, see
:ref:`commands-rebuild`.


Can I export events?
====================

Yes. The command `events2:export` sends the events of the given storage
folders to another TYPO3 instance with the system extension EXT:reactions,
see :ref:`commands-export`.


Can I import events?
====================

Yes. But currently only XML files are allowed. Execute the command
`events2:import` with the combined identifier of the XML file and the UID
of the storage folder, or add it as scheduler task
:guilabel:`Execute console commands`. See :ref:`commands-import`.

We will validate the imported file against
:file:`EXT:events2/Resources/Public/XmlImportValidator.xsd`.

All messages will be logged in a :file:`Messages.txt` in same directory of
imported XML file.


Namespace change of search plugin
=================================

In Extbase a link will be created by its Plugin Namespace like *tx_events2_calendar* or *tx_events2_list*. As we have
designed plugin `Search form` as its own Plugin it will only react and build links based on plugin
namespace `tx_events2_searchform`. But this is a problem. To build correct links within the search results plugin
which should be compatible with our events2 list Plugin we have to change its plugin namespace to `tx_events2_list`.

That's why we set the plugin namespace of search results plugin back to namespace of events2 list plugin:

..  code-block:: typoscript

    plugin.tx_events2_searchresults.view.pluginNamespace = tx_events2_list

We prefer to not change that value.


..  _faq-file-uploads:

File uploads
============

The event form of the Management plugin is based on EXT:form. Images are
uploaded with its `ImageUpload` elements into the folder
`1:/Extensions/events2/`. If you need the image rights from the uploader,
install `jweiland/checkfaluploads` and add its rights checkbox to your own
copy of the form definition. See :ref:`frontend-management-images`.
