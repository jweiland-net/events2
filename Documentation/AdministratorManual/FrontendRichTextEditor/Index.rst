..  include:: /Includes.rst.txt


..  _frontend-rich-text-editor:

=====================================
Rich text editor in the frontend form
=====================================

..  versionadded:: 11.2.0

Textarea elements of the form based on EXT:form (site setting
`events2.form.useExtForm`) can be edited with a small CKEditor 5. So
frontend users can format the text and formatting added by editors in
backend is kept, when the event is edited in frontend. By default the
element `detail-description` (column `detail_information`) gets the editor.
Further elements can be added by configuration.

The feature is disabled by default. Without it, all elements are plain
textareas as before.

Requirements
============

*   The form based on EXT:form (site setting `events2.form.useExtForm`).
*   The system extension `rte_ckeditor` (`typo3/cms-rte-ckeditor`). The
    editor is not activated, if it is not installed.

No build step is needed. The CKEditor 5 ES modules, translations and styles
shipped with EXT:rte_ckeditor are used.

Enable the editor
=================

Enable the site setting `events2.form.richText.enable` of the site set
`jweiland/events2` (TypoScript
:ref:`plugin.tx_events2.settings.new.richText.enable <typoscript-new-richtext-enable>`).

That is all for the description: the element `detail-description` is the
default of the site setting `events2.form.richText.elements`. This works
for overridden copies of :file:`NewEvent.form.yaml` as well, as long as
they use this identifier.

Rich text elements
==================

An element of the form is a rich text element, if

*   its identifier is listed in the site setting
    `events2.form.richText.elements` (TypoScript
    :ref:`settings.new.richText.elements <typoscript-new-richtext-elements>`,
    comma separated) or
*   it has `dataType: richText` in its `dbMapping`.

If the editor is enabled, events2 configures each rich text element
automatically:

*   The `dataType` of the element is `richText`, so the edit form gets the
    stored HTML instead of plain text.
*   The mapping of the element in the `SaveEventFinisher` gets
    `sanitizeRichText: true` (instead of `convertPlainTextToHtml` or
    `normalizePlainText`).
*   While rendering, the textarea gets the attribute `data-events2-rich-text`
    with the options of the editor. The assets are loaded on the pages of
    the form only, which contain a rich text element. Each textarea gets
    its own editor.

If the editor is disabled, rich text elements fall back to plain text:
`dataType: plainText` and `convertPlainTextToHtml` instead of
`sanitizeRichText`.

..  important::

    The database column of a rich text element should be a rich text field
    in TCA (`'enableRichtext' => true`), like `detail_information`. Else the
    backend shows the HTML source of the value. The `teaser` for example
    is a plain text field with a maximum of 255 characters and is NOT a
    suitable target, unless you change its TCA.

Example: description (default)
------------------------------

..  code-block:: yaml
    :caption: config/sites/<site>/settings.yaml

    events2:
      form:
        richText:
          enable: true

Example: an additional element
------------------------------

Add an own textarea to your copy of :file:`NewEvent.form.yaml` and map it
to an own column of the event table, which you have configured with
`'enableRichtext' => true` in TCA:

..  code-block:: yaml
    :caption: Your copy of NewEvent.form.yaml

    - label: 'Program'
      type: Textarea
      identifier: program
      properties:
        dbMapping:
          column: tx_myext_program
          # Optional: Instead of listing "program" in the site setting
          dataType: richText

    # In the SaveEventFinisher mapping of tx_events2_domain_model_event
    elements:
      program:
        mapOnDatabaseColumn: tx_myext_program

Then either keep `dataType: richText` in the form definition or add the
identifier to the site setting:

..  code-block:: yaml
    :caption: config/sites/<site>/settings.yaml

    events2:
      form:
        richText:
          enable: true
          elements: 'detail-description, program'

Loaded files
============

The import map of the CKEditor modules is defined in
:file:`Configuration/JavaScriptModules.php` of events2 and rendered by the
PageRenderer of TYPO3 (`<script type="importmap">`, respecting the
Content Security Policy of the site). The ES module
:file:`EXT:events2/Resources/Public/JavaScript/rich-text-editor.js` creates
an editor for each textarea with the attribute `data-events2-rich-text`. It
loads the UI translation of the current site language. The styles
:file:`EXT:rte_ckeditor/Resources/Public/Css/editor.css` and
:file:`EXT:events2/Resources/Public/Css/RichTextEditor.css` are added with
the AssetCollector.

The files are about 1.6 MB uncompressed and about 400 KB compressed with
gzip.

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
value stored in the column of the record, which is updated. New or
modified `t3://` links are removed, their text is kept. For records of the
event table the event of the edit form is used. For other tables the
`uid` of the `whereClause` of the finisher (mode `update`) is used.

Afterwards the value is transformed with the RTE configuration of the
backend (RteHtmlParser), so it is stored the same way as if it was saved in
backend.

Without JavaScript
==================

Without JavaScript the plain textarea is shown. For new events, plain text
can be entered as before. When editing an event, the textarea contains the
stored HTML. Values without any tag are converted like the plain text
description.
