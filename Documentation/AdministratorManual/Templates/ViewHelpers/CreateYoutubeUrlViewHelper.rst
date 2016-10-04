CreateYoutubeUrlViewHelper
--------------------------

It searches the given link for Youtube ID and attaches the ID to the embedded Youtube Link.

Examples
^^^^^^^^

Given link A: https://www.youtube.com/watch?v=B5gDI2h0F20
Given link B: https://www.youtube.com/watch?v=B5gDI2h0F20
Given link C: https://www.youtube.com/watch?v=B5gDI2h0F20
Given link D: https://www.youtube.com/watch?v=B5gDI2h0F20
Youtube ID: B5gDI2h0F20
Embedded link: //www.youtube.com/embed/B5gDI2h0F20

Basic example
"""""""""""""

Code: ::

  <iframe width="560" height="315" src="{e2:createYoutubeUri(link: event.videoLink.link)}" frameborder="0" allowfullscreen></iframe>
