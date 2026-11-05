..  include:: /Includes.rst.txt


..  _rich-text-description:

=================================================
Rich text editor for the description in frontend
=================================================

..  versionadded:: 11.2.0

The description of the form based on EXT:form (site setting
`events2.form.useExtForm`) can be edited with a small CKEditor 5. So
frontend users can format the text and formatting added by editors in
backend is kept, when the event is edited in frontend.

The feature is disabled by default. Without it, the description is a
plain textarea as before.

Requirements
============

*   The form based on EXT:form (site setting `events2.form.useExtForm`).
*   The system extension `rte_ckeditor` (`typo3/cms-rte-ckeditor`). The
    feature is not activated, if it is not installed.

No build step is needed. The CKEditor 5 ES modules, translations and styles
shipped with EXT:rte_ckeditor are used.

Enable the editor
=================

Enable the site setting `events2.form.richTextDescription` of the site set
`jweiland/events2` (TypoScript
:ref:`plugin.tx_events2.settings.new.richTextDescription <typoscript-new-richtextdescription>`).

The element of the form is found by its identifier `detail-description`. If
you use your own copy of :file:`NewEvent.form.yaml`, keep this identifier
and the mapping of `detail-description` to the column `detail_information`
in the `SaveEventFinisher`. events2 switches the element and the mapping
automatically, if the feature is enabled:

*   The textarea gets the attribute `data-events2-rich-text` with the options
    of the editor.
*   The `dataType` of the element becomes `richText`, so the edit form gets
    the stored HTML instead of plain text.
*   The mapping in the `SaveEventFinisher` uses `sanitizeRichText` instead of
    `convertPlainTextToHtml`.

Loaded files
============

The import map of the CKEditor modules is defined in
:file:`Configuration/JavaScriptModules.php` of events2 and rendered by the
PageRenderer of TYPO3 (`<script type="importmap">`, respecting the
Content Security Policy of the site). The ES module
:file:`EXT:events2/Resources/Public/JavaScript/rich-text-editor.js` creates
the editor. It loads the UI translation of the current site language. The
styles :file:`EXT:rte_ckeditor/Resources/Public/Css/editor.css` and
:file:`EXT:events2/Resources/Public/Css/RichTextEditor.css` are added with
the AssetCollector.

The files are loaded on the pages of the event form only. They are about
1.6 MB uncompressed and about 400 KB compressed with gzip.

Toolbar
=======

*   Heading: Paragraph, Heading 2, Heading 3 (Heading 1 is used for the title
    of the event)
*   Bold, Italic
*   Bulleted list, numbered list
*   Link with protocols http, https, mailto and tel and the option "Open in a
    new tab"
*   Undo, Redo

Toolbar items, which do not fit into the width of the form, are grouped
into a dropdown. The toolbar can be focused with :kbd:`Alt` + :kbd:`F10`.

Allowed markup
==============

The submitted HTML is sanitized on the server, as a request can bypass
the editor. Only these tags are allowed:

*   `p`, `br`, `strong`, `em`, `h2`, `h3`, `ul`, `ol`, `li`
*   `a` with `href` (http, https, mailto, tel) and `target="_blank"`, which
    gets `rel="noopener noreferrer"`

`b` is converted to `strong`, `i` to `em` and `h1`, `h4`, `h5`, `h6` to `p`.
All attributes like `class`, `style` or `on*` are removed. Links with other
protocols like `javascript:` or `data:` are removed, their text is kept.
`script`, `style`, `iframe`, images, forms and similar elements are removed
including their content. The text of all other elements is kept.
Other block elements like `div`, `blockquote` or table cells become
paragraphs. Text and inline formatting outside of blocks is wrapped into a
paragraph like the editor does, so `<strong>`, `<em>` and links are kept.

Links to records (`t3://`)
--------------------------

Links like `t3://page?uid=12` or `t3://file?uid=1234` can only be added in
backend. They are kept, if exactly the same link is already part of the
stored description of the event. New or modified `t3://` links are removed,
their text is kept.

Afterwards the description is transformed with the RTE configuration of the
backend (RteHtmlParser), so it is stored the same way as if it was saved in
backend.

Without JavaScript
==================

Without JavaScript the plain textarea is shown. For new events, plain text
can be entered as before. When editing an event, the textarea contains the
stored HTML. Values without any tag are converted like the plain text
description.
