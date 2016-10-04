.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../_IncludedDirectives.rst

.. _developer-editMarker:

Edit Marker
===========

Maps2 comes with an editPoi widget, which you can insert in your template. The widget will automatically
insert some hidden fields which will be filled with the current location of the marker. The
website visitor can move that marker around via Drag 'n Drop or while clicking somewhere on the map.

.. code-block:: html

   <maps2:widget.editPoi poiCollection="{company.txMaps2Uid}" override="{settings: {mapWidth: '100%', mapHeight: '300'}}" />

There are 3 hidden fields which will be rendered.

.. code-block:: html

   <input name="tx_maps2[__identity]" value="2" type="hidden">
   <input id="latitude-73" name="tx_maps2[latitude]" value="51.091152" type="hidden">
   <input id="longitude-73" name="tx_maps2[longitude]" value="7.545384" type="hidden">

Now it's time to process the fields in your controller.

.. code-block:: php

   /**
    * initialize create action
    * allow modification of submodel
    *
    * @return void
    */
   public function initializeCreateAction()
   {
     $maps2Request = GeneralUtility::_POST('tx_maps2');
     if (isset($maps2Request)) {
       $company = $this->request->getArgument('company');
       $company['txMaps2Uid'] = $maps2Request;
       $this->request->setArgument('company', $company);
     }
     $this->arguments->getArgument('company')->getPropertyMappingConfiguration()->allowModificationForSubProperty('txMaps2Uid');
     $this->arguments->getArgument('company')->getPropertyMappingConfiguration()
       ->allowProperties('txMaps2Uid')
       ->forProperty('txMaps2Uid')->allowProperties('latitude', 'longitude', '__identity');
   }
