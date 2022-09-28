.. include:: ../Includes.txt

.. _faq:

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

We build the day records while saving an event record in backend. Further we re-build them while executing
the scheduler task `Re-Create day records` and while executing the command `events2:rebuild`.


Can I export events?
====================

No. Currently you have to create your own export mechanism. But as we have implemented such a solution already for one
of our customers you can ask us at: projects@jweiland.net


Can I import events?
====================

Yes. But currently only XML files are allowed. Please add Scheduler Task `Import events` and set a filepath
to import. Examples of XML files are available at https://github.com/jweiland-net/events2/tree/master/Tests/Functional/Fixtures/XmlImport.

We will validate the imported file against configuration in `EXT:events2/Resources/Public/XmlImportValidator.xsd`.

All errors will be logged in a `Messages.txt` in same directory of imported XML file.


Namespace change of search plugin
=================================

In Extbase a link will be created by its Plugin Namespace like *tx_events2_calendar* or *tx_events2_list*. As we have
designed plugin `Search form` as its own Plugin it will only react and build links based on plugin
namespace `tx_events2_searchform`. But this is a problem. To build correct links within the search results plugin
which should be compatible with our events2 list Plugin we have to change its plugin namespace to `tx_events2_list`.

That's why we set the plugin namespace of search results plugin back to namespace of events2 list plugin:

plugin.tx_events2_searchresults.view.pluginNamespace = tx_events2_list

We prefer to not change that value.


File Uploads
============

If you need the image rights from the uploader for uploaded images you should install EXT:checkfaluploads and
add following line into FormFields template:

.. code-block:: html

   <f:form.checkbox value="1" name="event[images][{index}][rights]" checked="" />

In our TypeConverter we check for installed checkfaluploads and add an error message, if checkbox was not activated

If you want to delete an image, you can add following line into FormFields template:

.. code-block:: html

   <f:form.checkbox value="1" name="event[images][{index}][delete]" checked="" />

