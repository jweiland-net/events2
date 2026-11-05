:navigation-title: formatRichText

..  include:: /Includes.rst.txt
..  _formatRichTextViewHelper:

========================
FormatRichTextViewHelper
========================

..  versionadded:: 12.0.0

Renders the submitted value of a rich text element of the event form
(:yaml:`properties.dbMapping.dataType: richText`) in the mails of the
finisher `EmailToReceiver` of EXT:form.

The value is sanitized with the same configuration as on saving, see
:ref:`frontend-rich-text-editor-sanitizer-event`. The table is taken from
:yaml:`properties.dbMapping.table` (default:
`tx_events2_domain_model_event`), the column from
:yaml:`properties.dbMapping.column` of the form element. So the moderator
sees the formatting which will be stored, without attributes of the
editor and without active content. Links with `t3://` are removed, as they
can not be resolved in a mail.

..  _formatRichTextViewHelper-arguments:

Arguments of the FormatRichTextViewHelper
=========================================

`element`
    The form element of the value, usually `{formValue.element}` of
    :html:`<formvh:renderAllFormValues>`. Required.

`value`
    The submitted value, usually `{formValue.processedValue}`. Required.

`format`
    `html` (default) returns the sanitized HTML. `plaintext` returns plain
    text with line breaks for the text part of a mail. The plain text is
    not escaped, so do not use it in HTML.

..  _formatRichTextViewHelper-example:

Example for the FormatRichTextViewHelper
========================================

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/FormFramework/Templates/Finishers/Email/Default.html (excerpt)

    {namespace e2=JWeiland\Events2\ViewHelpers}

    <e2:formatRichText element="{formValue.element}"
                       value="{formValue.processedValue}"/>

..  code-block:: text
    :caption: EXT:my_sitepackage/Resources/Private/FormFramework/Templates/Finishers/Email/Default.txt (excerpt)

    <e2:formatRichText element="{formValue.element}" value="{formValue.processedValue}" format="plaintext"/>
