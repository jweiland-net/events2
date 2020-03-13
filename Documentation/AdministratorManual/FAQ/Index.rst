.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt

FAQ
---

Exception thrown in Location/Show.html
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

If you have NOT installed maps2 you may get following exception while visiting
Show template of Location:

Code: ::

 TYPO3Fluid\Fluid\Core\ViewHelper\Exception
 Undeclared arguments passed to ViewHelper
 JWeiland\Maps2\ViewHelpers\Widget\PoiCollectionViewHelper:
 poiCollection, override. Valid arguments are: customWidgetId

Problem is, that TYPO3 will always parse everything in Fluid-Templates. That's why also
the maps2 widget will be tried to parse although it is part of a false f:if condition and will
not be rendered.

Please copy Location/Show.html into your own SitePackage extension and remove following
lines:

Code: ::

 <f:if condition="{location.txMaps2Uid}">
   <maps2:widget.poiCollection poiCollection="{location.txMaps2Uid}" override="{settings: {mapWidth: '100%', mapHeight: '300', zoom: '14'}}" />
 </f:if>

