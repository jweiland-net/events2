.. include:: ../../../Includes.txt

.. _youTubeViewHelper:

==========================
CreateYoutubeUrlViewHelper
==========================

It searches the given link for Youtube ID and attaches the ID to the embedded Youtube Link.

Examples
========

Link: https://www.youtube.com/watch?v=B5gDI2h0F20
Youtube ID: B5gDI2h0F20
Embedded link: //www.youtube.com/embed/B5gDI2h0F20

Basic example
"""""""""""""

.. code-block:: html

   <iframe width="560" height="315" src="{e2:createYoutubeUri(link: event.videoLink.link)}" frameborder="0" allowfullscreen></iframe>
