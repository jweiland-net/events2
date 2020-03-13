.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../../Includes.txt

.. _ts:

TypoScript
==========

This page is divided into the following sections which are all configurable by using TypoScript:

.. only:: html

   .. contents::
        :local:
        :depth: 1


Plugin settings
---------------
This section covers all settings, which can be defined in the plugin itself.

.. important:: Every setting can also be defined by TypoScript.

Properties
^^^^^^^^^^

.. container:: ts-properties

  =============================== ========
  Property                        Type
  =============================== ========
  rootCategory_                   integer
  pidOfDetailPage_                integer
  pidOfSearchPage_                integer
  pidOfLocationPage_              integer
  pidOfListPage_                  integer
  mergeRecurringEvents_           false
  userGroup_                      false
  list_                           array
  latest_                         array
  pageBrowser_                    array
  show_                           array
  =============================== ========

.. _rootCategory:

rootCategory
""""""""""""
This is only needed by plugin "Event: search". An integrator can select
the categories in Events plugin which should be available in the filters
selectbox for main categories. If you have huge category trees it may
happen that the integrator selects sub-categories which should not happen.
Please define the root UID of all main categories here. So, if an
integrator selects wrong categories they will be removed from listing.

.. _pidOfDetailPage:

pidOfDetailPage
"""""""""""""""
If you want to have an individual detail page for events, you can defined the
page UID here.

.. _pidOfSearchPage:

pidOfSearchPage
"""""""""""""""
If you want to have an individual search page for events, you can defined the
page UID here.

.. _pidOfLocationPage:

pidOfLocationPage
"""""""""""""""""
If you want to have an individual location page for events, you can defined the
page UID here.

.. _pidOfListPage:

pidOfListPage
"""""""""""""
If you have defined a detail page, you should also set pidOfListPage to have
proper links back to the list view of event records.

.. _mergeRecurringEvents:

mergeRecurringEvents
""""""""""""""""""""
If you have recurring events, this option will group event records and will only show the next date of the
events instead of all following dates.

.. _userGroup:

userGroup
"""""""""
If you want to allow your frontend users the creation of new events, you
should create a new FE-Group for them and place the UID of this group here.

.. _list:

list
""""
Special configuration for list view.

image
-----
Configuration for images in list view. It currently contains:
width, height, maxWidth, maxHeight, minWidth, minHeight

amountOfRecordsToShow
---------------------
How many records should be displayed in list view for each page in pageBrowser

.. _latest:

latest
""""""
Special configuration for latest view.

amountOfRecordsToShow
---------------------
How many records should be displayed in latest view for each page in pageBrowser

.. _pageBrowser:

pageBrowser
"""""""""""
Define settings for pageBrowser

itemsPerPage
------------
Amount of records on a page

insertAbove
-----------
Show a pageBrowser on top of the list

insertBelow
-----------
Show a pageBrowser below the list

maximumNumberOfLinks
--------------------
How many page-links should be shown?

.. _show:

show
""""
Special configuration for detail view.

image
-----
Configuration for images in detail view. It currently contains:
width, height, maxWidth, maxHeight, minWidth, minHeight
