.. include:: ../Includes.txt

.. _faq:

===
FAQ
===

Why not using f:form in search form?
====================================

We have tried it without using no_cache, but in some rare situations, when a customer starts two searches,
the second search request shows the results of the first request. Since than we do not add cHash anymore
in search requests.
If we would use POST requests, the URI of the search results is not copyable anymore. There are many people out
there copying the URIs in forums, emails, slack or other tools.
If we would use f:form VH in combination with GET, all hidden information of extbase will be appended to the URI,
which makes the URI extremely long. As there are some browsers limiting the URIs length to 1000 or 3000 we
can not us f:form, to prevent cutting the URI by Browser restrictions.

Translations
============

Events2 is currently not fully multilingual. Our idea was to have one day record for all translations of an
event record. That way we have removed all translation columns from TCA of day table. But since TYPO3 8 and especially
TYPO3 9 the problems in events2 with translation grows.
With version 5.0.0 we have added these language columns back to day table. Now you can create translatable versions
of your event records again, but we have deactivated all day record related columns from form of translated record.
That's because events2 currently does not support multilingual records for day table. That should be OK for
most cases, but it is not possible to have different dates or date configuration for different translations.

Wrong links in calendar
=======================

We found out that building up to 31 links in Events2 calendar needs nearly 300ms. Incl. 200ms for TYPO3 initialization
is half a second. With half a second delay swiping through the month feels really slow. That's why we have
disabled human readable links inside this calendar.

Where are the day records are build?
====================================

We build the day records while saving an event record in backend. Further we re-build them while executing
the scheduler task *Re-Create day records* and while executing the command *events2:rebuild*

Can I export events?
====================

No. Currently you have to create your own export mechanism

Can I import events?
====================

Yes. But currently only XML files are allowed. Please add Scheduler Task *Import events* and set a filepath
to import. Examples of XML files are available at https://github.com/jweiland-net/events2/tree/master/Tests/Functional/Fixtures/XmlImport.

We will validate the imported file against configuration in EXT:events2/Resources/Public/XmlImportValidator.xsd.

All errors will be logged in a Messages.txt in same directory of imported XML file.

Exception thrown in Location/Show.html
======================================

If you have NOT installed maps2 you may get following exception while visiting
Show template of Location:

.. code-block:: html

   TYPO3Fluid\Fluid\Core\ViewHelper\Exception
   Undeclared arguments passed to ViewHelper
   JWeiland\Maps2\ViewHelpers\Widget\PoiCollectionViewHelper:
   poiCollection, override. Valid arguments are: customWidgetId

Problem is, that TYPO3 will always parse everything in Fluid-Templates. That's why also
the maps2 widget will be tried to parse although it is part of a false f:if condition and will
not be rendered.

Please copy Location/Show.html into your own SitePackage extension and remove following
lines:

.. code-block:: html

   <f:if condition="{location.txMaps2Uid}">
     <maps2:widget.poiCollection poiCollection="{location.txMaps2Uid}" override="{settings: {mapWidth: '100%', mapHeight: '300', zoom: '14'}}" />
   </f:if>

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

