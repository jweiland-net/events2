.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../_IncludedDirectives.rst

.. _developer-override:

Override
========

In your own extension you can create a map with help of our widget ViewHelpers.
Both Widgets **EditPoi** and **PoiCollection** comes with an ``override`` attribute.
In our AbstractController we prefill the Fluid variable **environment** with the values
of Extension configuration, Settings, current Page ID and current tt_content record.

Variable **environment**

Array keys:

* settings
* contentRecord
* id
* extConf

In Fluidtemplate we convert that array into JSON format, so that JavaScript can access
that array as object. With help of jQuery **extends** we merge our environment vars
with the overrides you can define recursive.

Override strokeColor to blue and set mapWidth and mapHeight:

.. code-block:: html

   <maps2:widget.poiCollection poiCollection="{myDomainObject.myPoiCollectionUid}" override="{settings: {mapWidth: '100%', mapHeight: '300'}, extConf: {strokeColor: 'blue'}}" />
