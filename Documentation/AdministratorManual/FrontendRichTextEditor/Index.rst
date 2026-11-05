:navigation-title: Frontend rich text editor

..  include:: /Includes.rst.txt
..  _frontend-rich-text-editor:

=====================================
Rich text editor in the frontend form
=====================================

..  versionadded:: 11.2.0

    Textarea elements of the event form based on EXT:form can be edited
    with a CKEditor 5.

Textarea elements of the event form based on EXT:form (site setting
`events2.form.useExtForm`) can be edited with a small CKEditor 5. Frontend
users can format the text, and formatting added by editors in the backend
is kept when the event is edited in the frontend. By default, the element
`detail-description` (column `detail_information`) gets the editor.
Further elements can be added by configuration.

The feature is disabled by default. Without it, all elements are plain
textareas as before.

..  contents::
    :caption: Table of contents
    :local:

..  _frontend-rich-text-editor-requirements:

Requirements for the frontend rich text editor
==============================================

*   The event form based on EXT:form (site setting
    `events2.form.useExtForm`).
*   The system extension EXT:rte_ckeditor
    (:composer:`typo3/cms-rte-ckeditor`). The editor is not activated if
    it is not installed.

No build step (npm, webpack) is needed. The CKEditor 5 ES modules,
translations and styles shipped with EXT:rte_ckeditor are used.

..  _frontend-rich-text-editor-site-settings:

Site settings of the frontend rich text editor
==============================================

The following settings are provided by the site set `jweiland/events2`
and are available in TypoScript as
:typoscript:`plugin.tx_events2.settings.new.richText`, see
:ref:`typoscript-new-richtext-enable`.

..  confval-menu::
    :name: events2-rich-text-settings
    :display: table
    :type:
    :default:

    ..  confval:: events2.form.richText.enable
        :name: events2-form-richtext-enable
        :type: bool
        :default: `false`

        Use CKEditor 5 of EXT:rte_ckeditor for the rich text elements of
        the event form based on EXT:form.

    ..  confval:: events2.form.richText.elements
        :name: events2-form-richtext-elements
        :type: string
        :default: `detail-description`

        Comma-separated identifiers of the form elements which get the rich
        text editor. Elements with :yaml:`dataType: richText` in their
        :yaml:`dbMapping` get the editor, too.

..  _frontend-rich-text-editor-enable:

Enable the frontend rich text editor
====================================

Enable the site setting
:confval:`events2.form.richText.enable <events2-form-richtext-enable>`
of the site set `jweiland/events2`.

That is all for the description: the element `detail-description` is the
default of the site setting
:confval:`events2.form.richText.elements <events2-form-richtext-elements>`.
This works for overridden copies of :file:`NewEvent.form.yaml` as well, as
long as they use this identifier.

..  _frontend-rich-text-editor-example-description:

Example: Enable the rich text editor for the description
--------------------------------------------------------

..  code-block:: yaml
    :caption: config/sites/<my-site>/settings.yaml

    events2:
      form:
        richText:
          enable: true

..  _frontend-rich-text-editor-elements:

Rich text elements of the event form
====================================

An element of the form is a rich text element if

*   its identifier is listed in the site setting
    :confval:`events2.form.richText.elements <events2-form-richtext-elements>`
    or
*   it has :yaml:`dataType: richText` in its :yaml:`dbMapping`.

If the editor is enabled, events2 configures each rich text element
automatically:

*   The :yaml:`dataType` of the element is :yaml:`richText`, so the edit
    form gets the stored HTML instead of plain text.
*   The mapping of the element in the `SaveEventFinisher` gets
    :yaml:`sanitizeRichText: true` (instead of
    :yaml:`convertPlainTextToHtml` or :yaml:`normalizePlainText`).
*   While rendering, the textarea gets the attribute
    :html:`data-events2-rich-text` with the options of the editor,
    including the allowed tags of its column (see
    :ref:`frontend-rich-text-editor-toolbar`). The assets are only loaded
    on pages of the form which contain a rich text element. Each textarea
    gets its own editor.

If the editor is disabled, rich text elements fall back to plain text:
:yaml:`dataType: plainText` and :yaml:`convertPlainTextToHtml` instead of
:yaml:`sanitizeRichText`.

..  important::

    The database column of a rich text element should be a rich text field
    in TCA (:php:`'enableRichtext' => true`), like `detail_information`.
    Otherwise, the backend shows the HTML source of the value. The column
    `teaser`, for example, is a plain text field with a maximum of 255
    characters and is **not** a suitable target, unless you change its TCA.

The editor of an element is configured for the column
:yaml:`properties.dbMapping.column` of the table
:yaml:`properties.dbMapping.table`. The table is optional and defaults to
`tx_events2_domain_model_event`. Set it only for elements which are stored
in another table, so the toolbar matches the allowed tags of that column.
Use the same table as the mapping of the element in the
`SaveEventFinisher`:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/Form/NewEvent.form.yaml (excerpt)

    - label: 'Notes of the organizer'
      type: Textarea
      identifier: organizer-notes
      properties:
        dbMapping:
          table: tx_events2_domain_model_organizer
          column: tx_myext_notes
          dataType: richText

..  _frontend-rich-text-editor-example-additional-element:

Example: Add a rich text editor to an additional form element
-------------------------------------------------------------

Add an own textarea to your copy of :file:`NewEvent.form.yaml` and map it
to an own column of the event table, which you have configured with
:php:`'enableRichtext' => true` in TCA:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/Form/NewEvent.form.yaml (excerpt)

    - label: 'Program'
      type: Textarea
      identifier: program
      properties:
        dbMapping:
          column: tx_myext_program
          # Optional: Instead of listing "program" in the site setting
          dataType: richText

Map the element in the `SaveEventFinisher` options of the table
`tx_events2_domain_model_event`:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/Form/NewEvent.form.yaml (excerpt)

    finishers:
      1:
        identifier: SaveEventFinisher
        options:
          4:
            table: tx_events2_domain_model_event
            elements:
              program:
                mapOnDatabaseColumn: tx_myext_program

Then either keep :yaml:`dataType: richText` in the form definition or add
the identifier to the site setting:

..  code-block:: yaml
    :caption: config/sites/<my-site>/settings.yaml

    events2:
      form:
        richText:
          enable: true
          elements: 'detail-description, program'

..  _frontend-rich-text-editor-assets:

Loaded files of the frontend rich text editor
=============================================

The import map of the CKEditor modules is defined in
:file:`EXT:events2/Configuration/JavaScriptModules.php` and rendered by the
:php:`PageRenderer` of TYPO3 (:html:`<script type="importmap">`,
respecting the Content Security Policy of the site). The ES module
:file:`EXT:events2/Resources/Public/JavaScript/rich-text-editor.js` creates
an editor for each textarea with the attribute
:html:`data-events2-rich-text`. It loads the UI translation of the current
site language. The style sheets
:file:`EXT:rte_ckeditor/Resources/Public/Css/editor.css` and
:file:`EXT:events2/Resources/Public/Css/RichTextEditor.css` are added with
the :php:`AssetCollector`.

The files are about 1.6 MB uncompressed and about 400 KB compressed with
gzip.

..  _frontend-rich-text-editor-toolbar:

Toolbar of the frontend rich text editor
========================================

The toolbar is built from the tags which are allowed for the column of
the element (see :ref:`frontend-rich-text-editor-allowed-markup`). With
the default configuration, it contains:

*   Heading: Paragraph, Heading 2, Heading 3 (Heading 1 is used for the
    title of the event)
*   Bold, Italic
*   Bulleted list, numbered list
*   Link with the protocols `http`, `https`, `mailto` and `tel` and the
    option :guilabel:`Open in a new tab`
*   Undo, Redo

..  versionadded:: 11.2.0

    The headings and toolbar items follow the allowed tags of the
    :ref:`ModifyRichTextSanitizerConfigurationEvent
    <frontend-rich-text-editor-sanitizer-event>`.

The editor gets one heading option for each allowed element of
:html:`<h1>` to :html:`<h6>`. The titles of the headings are translated
by CKEditor. The heading dropdown is only shown if at least one heading is
allowed. The toolbar items are only shown if their element is allowed:

*   :guilabel:`Bold`: :html:`<strong>`
*   :guilabel:`Italic`: :html:`<em>`
*   :guilabel:`Bulleted list`: :html:`<ul>`
*   :guilabel:`Numbered list`: :html:`<ol>`
*   :guilabel:`Link`: :html:`<a>`

:guilabel:`Undo` and :guilabel:`Redo` are always available. Allowed
elements without a toolbar item, like :html:`<u>` or
:html:`<blockquote>`, are kept when the value is saved, but can not be
selected in the editor.

Toolbar items which do not fit into the width of the form are grouped into
a dropdown. The toolbar can be focused with :kbd:`Alt` + :kbd:`F10`.

..  _frontend-rich-text-editor-allowed-markup:

Allowed markup of the frontend rich text editor
===============================================

The submitted HTML is sanitized on the server with
:composer:`typo3/html-sanitizer`, as a request can bypass the editor. By
default, only these tags are allowed:

*   :html:`<p>`, :html:`<br>`, :html:`<strong>`, :html:`<em>`,
    :html:`<h2>`, :html:`<h3>`, :html:`<ul>`, :html:`<ol>`, :html:`<li>`
*   :html:`<a>` with :html:`href` (`http`, `https`, `mailto`, `tel`) and
    :html:`target="_blank"`, which gets :html:`rel="noopener noreferrer"`

:html:`<b>` is converted to :html:`<strong>`, :html:`<i>` to :html:`<em>`
and :html:`<h1>`, :html:`<h4>`, :html:`<h5>`, :html:`<h6>` to
:html:`<p>`. All attributes like :html:`class`, :html:`style` or
:html:`on*` are removed. Links with other protocols like `javascript:` or
`data:` are removed, their text is kept. :html:`<script>`,
:html:`<style>`, :html:`<iframe>`, images, forms and similar elements are
removed including their content. The text of all other elements is kept.
Other block elements like :html:`<div>`, :html:`<blockquote>` or table
cells become paragraphs. Text and inline formatting outside of blocks is
wrapped into a paragraph like the editor does, so :html:`<strong>`,
:html:`<em>` and links are kept.

The tags can be modified with the PSR-14 event
:php:`ModifyRichTextSanitizerConfigurationEvent`, see
:ref:`frontend-rich-text-editor-sanitizer-event`.

..  _frontend-rich-text-editor-t3-links:

Links to records (t3://) in the frontend rich text editor
---------------------------------------------------------

Links like `t3://page?uid=12` or `t3://file?uid=1234` can only be added in
the backend. They are kept if exactly the same link is already part of the
value stored in the column of the record which is updated. New or modified
`t3://` links are removed, their text is kept. For records of the event
table, the event of the edit form is used. For other tables, the `uid` of
the :yaml:`whereClause` of the finisher (mode :yaml:`update`) is used.

Afterwards, the value is transformed with the RTE configuration of the
backend (:php:`RteHtmlParser`), so it is stored the same way as if it was
saved in the backend.

..  _frontend-rich-text-editor-sanitizer-event:

Modify the allowed markup of the frontend rich text editor
==========================================================

..  versionadded:: 11.2.0

    The PSR-14 event :php:`ModifyRichTextSanitizerConfigurationEvent`
    allows to modify the tags of the rich text sanitizer and of the
    toolbar of the frontend editor.

The PSR-14 event
:php:`\JWeiland\Events2\Event\ModifyRichTextSanitizerConfigurationEvent`
is dispatched for each rich text element:

*   While rendering the form element, to build the headings and toolbar
    items of the editor (see :ref:`frontend-rich-text-editor-toolbar`).
    Table and column are taken from :yaml:`properties.dbMapping.table`
    (default: `tx_events2_domain_model_event`) and
    :yaml:`properties.dbMapping.column` of the form element.
*   Each time a submitted value which contains HTML is sanitized. Values
    without any tag are converted like plain text and do not dispatch the
    event.

The event receives the defaults, which are defined as constants in
:php:`\JWeiland\Events2\Sanitizer\Visitor\RichTextVisitor`. So one
listener configures both the editor and the sanitizing on the server.

..  note::

    Only elements with a toolbar item can be selected in the editor:
    headings :html:`<h1>` to :html:`<h6>`, :html:`<strong>`, :html:`<em>`,
    :html:`<ul>`, :html:`<ol>` and :html:`<a>`. Other allowed elements, like
    :html:`<u>` or :html:`<blockquote>`, are kept if they are already part
    of the value, for example because they were added by an editor in the
    backend, or if they are submitted with a request.

..  _frontend-rich-text-editor-sanitizer-event-methods:

Methods of the ModifyRichTextSanitizerConfigurationEvent
--------------------------------------------------------

:php:`getAllowedTags()` / :php:`setAllowedTags(array $allowedTags)`
    Elements which are kept. Default: :html:`<p>`, :html:`<br>`,
    :html:`<strong>`, :html:`<em>`, :html:`<h2>`, :html:`<h3>`,
    :html:`<ul>`, :html:`<ol>`, :html:`<li>` and :html:`<a>`.

:php:`getMappedTags()` / :php:`setMappedTags(array $mappedTags)`
    Elements which are renamed, as array of source tag name to target tag
    name, for example :php:`'b' => 'strong'`. Default: :html:`<b>` to
    :html:`<strong>`, :html:`<i>` to :html:`<em>` and :html:`<h1>`,
    :html:`<h4>`, :html:`<h5>`, :html:`<h6>` to :html:`<p>`.

:php:`getRemovedTags()` / :php:`setRemovedTags(array $removedTags)`
    Elements which are removed including their content, for example
    :html:`<script>`, :html:`<img>`, :html:`<form>` or :html:`<video>`.

:php:`getBlockTags()` / :php:`setBlockTags(array $blockTags)`
    Elements which start a new block, for example :html:`<div>`,
    :html:`<blockquote>` or :html:`<td>`. A block element which is not
    allowed becomes a paragraph if it does not contain other block
    elements. Otherwise, it is unwrapped and only its content is kept.

:php:`getTable()`
    Table of the column the HTML is stored in, for example
    `tx_events2_domain_model_event`. Empty if unknown.

:php:`getColumn()`
    Column the HTML is stored in, for example `detail_information`. Empty
    if unknown.

..  _frontend-rich-text-editor-sanitizer-event-rules:

Rules for the configuration of the rich text sanitizer
------------------------------------------------------

After all listeners are called, the configuration is normalized:

*   Tag names are converted to lower case. Invalid tag names are ignored.
*   :html:`<p>` is always allowed, as inline content outside of blocks is
    wrapped into paragraphs.
*   Elements with active or embedded content can never be allowed:
    :html:`<applet>`, :html:`<base>`, :html:`<embed>`, :html:`<frame>`,
    :html:`<frameset>`, :html:`<iframe>`, :html:`<link>`, :html:`<math>`,
    :html:`<meta>`, :html:`<noscript>`, :html:`<object>`,
    :html:`<script>`, :html:`<style>`, :html:`<svg>` and
    :html:`<template>`. They are always removed including their content.
*   Removed tags take precedence over allowed tags.
*   A mapping is only used if its target is allowed and its source is
    neither allowed nor removed. So allowing :html:`<h4>` is enough to keep
    it, the default mapping of :html:`<h4>` to :html:`<p>` is skipped.
*   Allowed elements never get any attribute. The only exception is
    :html:`<a>`, which keeps a checked :html:`href` and
    :html:`target="_blank"`.

After sanitizing, the value is transformed with the RTE configuration of
the backend (:php:`RteHtmlParser`). An element allowed by the event must
be allowed by the RTE configuration of the column as well. The default RTE
configuration keeps :html:`<h4>`, for example.

..  _frontend-rich-text-editor-sanitizer-event-example:

Example: Allow h4 in the description of events
----------------------------------------------

The following event listener allows :html:`<h4>` for the column
`detail_information` of the event table only. The editor of the
description gets the additional heading :guilabel:`Heading 4`, and the
heading is kept when the event is saved. All other rich text elements
keep the default configuration.

..  literalinclude:: _snippets/_AllowHeading4EventListener.php
    :language: php
    :caption: EXT:my_sitepackage/Classes/EventListener/AllowHeading4EventListener.php

The attribute :php:`#[AsEventListener]` registers the listener, see
:ref:`t3coreapi:EventDispatcher`.

..  _frontend-rich-text-editor-no-javascript:

Frontend form without JavaScript
================================

Without JavaScript, the plain textarea is shown. For new events, plain text
can be entered as before. When editing an event, the textarea contains the
stored HTML. Values without any tag are converted like the plain text
description.
