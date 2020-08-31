.. include:: ../../Includes.txt

.. _dynamicPageTitle:

==================
Dynamic page title
==================

In normal case you only will see something like "detail view" on detail pages page-title.
If you want to change that title to current events title incl. its date you can mak use
of new TYPO3 page-title providers (since TYPO3 9.4). Luckily events2 come with its own provider to
realize a pretty nice detail page-title for you with following TypoScript:

.. code-block:: typoscript

   config.pageTitleProviders {
     events2 {
       provider = JWeiland\Events2\PageTitleProvider\Events2PageTitleProvider
       # Please add these providers, to be safe loading events2 provider before these two.
       before = record, seo
     }
   }
