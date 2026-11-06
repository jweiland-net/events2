:navigation-title: createYoutubeUri

..  include:: /Includes.rst.txt
..  _youTubeViewHelper:

==========================
CreateYoutubeUriViewHelper
==========================

The ViewHelper `e2:createYoutubeUri` searches the given link for the
YouTube ID and returns the embedded YouTube link of this ID.

Link: `https://www.youtube.com/watch?v=B5gDI2h0F20`

YouTube ID: `B5gDI2h0F20`

Embedded link: `//www.youtube.com/embed/B5gDI2h0F20`

..  versionchanged:: 12.0.0

    The argument `link` is optional. If it is not set, the tag content
    is used, so the ViewHelper can be used in inline syntax.

..  _youTubeViewHelper-arguments:

Arguments of the CreateYoutubeUriViewHelper
===========================================

`link`
    The YouTube link or the YouTube ID. If not set, the tag content is
    used.

..  _youTubeViewHelper-example:

Example for the CreateYoutubeUriViewHelper
==========================================

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Templates/Video/Show.html (excerpt)

    {namespace e2=JWeiland\Events2\ViewHelpers}

    <iframe width="560"
            height="315"
            src="{e2:createYoutubeUri(link: event.videoLink.link)}"
            allowfullscreen></iframe>

    <!-- Inline syntax -->
    <iframe width="560"
            height="315"
            src="{event.videoLink.link -> e2:createYoutubeUri()}"
            allowfullscreen></iframe>
