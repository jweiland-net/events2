.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt

Dynamic page title
==================

In normal case you will only see the page title of the detail page. It would be much better to show
event title and event date in title. You can realize that with following TypoScript:

.. code-block:: typoscript

  config.titleTagFunction = JWeiland\Events2\UserFunc\SetTitleForDetailPage->render
