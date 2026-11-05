:navigation-title: Frontend management

..  include:: /Includes.rst.txt
..  _frontend-management:

=========================================
Create and edit events in the frontend
=========================================

..  versionchanged:: 12.0.0

    The Extbase based event form of the Management plugin has been
    removed. New and edited events are submitted with the event form based
    on EXT:form only. The site setting `events2.form.useExtForm` does not
    exist anymore. See :ref:`upgrade-12-0-0`.

With the plugin :guilabel:`Manage events` (Management plugin) logged-in
frontend users create and edit their own events. The plugin renders the
form definition :file:`EXT:events2/Configuration/Form/NewEvent.form.yaml`
of the system extension EXT:form. Its finisher `SaveEventFinisher` stores
the event, and a mail with an activation link is sent to a moderator. The
event stays hidden until it was activated.

..  contents::
    :caption: Table of contents
    :local:

..  _frontend-management-actions:

Actions of the Management plugin
================================

`listMyEvents`
    Default action. Lists the events of the organizer of the current
    frontend user with a link to create a new event and a link to edit
    each event.

`perform`
    Renders the event form based on EXT:form. Without an event in the
    query parameter `tx_events2_management[event]` a new event is
    created. With an event UID in this query parameter the given event is
    edited. The action itself has no Extbase argument `event`; the event
    is read from the query only, also by the finisher `SaveEventFinisher`
    and while prefilling the edit form. Arrays, values which are no
    integer and values lower than 1 are treated as "create a new event".

`confirmActivation`
    Target of the activation link of the mail. Shows a summary of the
    event and a button to activate it.

`activate`
    Activates the event. Accepts POST requests of the button of
    `confirmActivation` only. The request has to contain a valid
    `moderationToken`.

Requests for other actions, like the removed actions `new`, `create`,
`edit`, `update` and `delete`, are handled by the default action
`listMyEvents`.

..  _frontend-management-setup:

Set up the frontend management
==============================

#.  Create a frontend user group for the users which are allowed to
    create events.

#.  Assign the frontend users to this group. Each of these users needs a
    related organizer record in the field :guilabel:`Organizer` of the
    frontend user record. New events get this organizer.

#.  Set the UID of the frontend user group in the site setting
    `events2.form.userGroup` of the site set `jweiland/events2`. It is
    available in TypoScript as :confval:`settings.userGroup
    <events2-typoscript-settings-usergroup>`.

#.  Create a page for the Management plugin and set its UID in the site
    setting `events2.pidOfManagementPage`. The links to create and edit
    events in the detail view and the links of the mail point to this
    page.

#.  Set the storage folder of the new events in the site setting
    `events2.storagePidForFrontendCreatedEvents` and the selectable
    categories in the site setting
    `events2.form.selectableCategoriesForNewEvents`.

#.  Add the plugin :guilabel:`Manage events` to the page and restrict
    the page or the content element to logged-in frontend users.

..  _frontend-management-access:

Access rules of the frontend management
=======================================

..  versionchanged:: 12.0.0

    All rules are checked by one central service,
    :php:`JWeiland\Events2\Service\ManagementAccessService`. It replaces
    :php:`JWeiland\Events2\Service\EventModerationService`. Backend
    administrators can no longer create or list events in the frontend,
    and they need the `moderationToken` to activate an event.

Create an event
    Only logged-in frontend users which are members of the configured
    user group (:confval:`settings.userGroup
    <events2-typoscript-settings-usergroup>`) and have an organizer. There
    is no exception for backend administrators, as they have no
    organizer. Without a configured user group nobody can create events.

List own events
    The same rule as for creating events. Backend administrators can not
    list events, as they have no organizer.

Edit an event
    The owner of the event: a frontend user of the configured user group,
    whose organizer is assigned to the event. Hidden events can be edited,
    too. A backend administrator, who is logged in into the backend, can
    edit every existing event. The organizers of an edited event are kept,
    so an administrator does not remove the owner of the event. Events
    which do not exist and events of other organizers are denied with the
    same message.

Open the confirmation page of an activation
    Everyone with the activation link of the mail. The link contains a
    signed `moderationToken` for this event. Backend administrators can
    open the confirmation page of every existing event without token.

Activate an event
    Only with a valid `moderationToken`, also for backend administrators.
    The confirmation page always renders a fresh token into its form, so
    the token protects the POST request against cross-site request
    forgery (CSRF). Owners of an event never get a token, so they can not
    bypass the moderation.

The user group is read from the TypoScript setup only:
:typoscript:`plugin.tx_events2_management.settings.userGroup` overrides
:typoscript:`plugin.tx_events2.settings.userGroup`. Values of the
FlexForm or of the settings of other plugins are not respected.

If access is denied, the plugin shows a localized flash message, for
example "You're not allowed to create event records.". The labels are
`userGroupNotConfigured`, `pluginNeedsLogin`, `notAllowedToCreate`,
`missingOrganizerForCreate`, `unauthorizedOrganizerForEdit` and
`invalidModerationToken` of
:file:`EXT:events2/Resources/Private/Language/locallang.xlf`.

The event form can be used outside of the Management plugin, for example
in a form content element. The same rules are checked there, too:

*   The edit form is only prefilled with the values of the event, if
    the current user is allowed to edit it.
*   The finisher `SaveEventFinisher` checks the rules before anything is
    stored. If access is denied, it throws a
    :php:`TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException`
    and nothing is stored.

..  _frontend-management-images:

Image upload of the event form
==============================

The form definition :file:`NewEvent.form.yaml` contains two elements of
type `ImageUpload` for files of the MIME types `image/jpg`, `image/jpeg`
and `image/png`. The files are stored in the folder
`1:/Extensions/events2/` (option :yaml:`saveToFileMount`). To change these
options, use your own copy of the form definition.

To get the image rights from the uploader, install the suggested
extension `jweiland/checkfaluploads` (version 6.0.0 or higher) and add a
checkbox with :yaml:`checkboxType: uploadRights` and
:yaml:`referenceUploadIdentifier` to your copy of the form definition. See
the documentation of checkfaluploads for details.

..  _frontend-management-form-definition:

Own copy of the form definition
===============================

If you use an own copy of :file:`NewEvent.form.yaml`, the following
applies to the prefilling of the edit form:

*   Elements with :yaml:`properties.dbMapping.relation` read related
    records with a list of :yaml:`expressions`. Only these methods of
    the ExpressionBuilder are allowed as :yaml:`expression`: `eq`, `neq`,
    `lt`, `lte`, `gt`, `gte`, `in`, `notIn` and `inSet`. Other
    expressions are ignored.
*   Values like `{event:download_links}` are replaced by the column of
    the event record. With :yaml:`intExplodeValue: 1` (or
    :yaml:`strExplodeValue: 1`) a comma-separated value is used as list
    of integers (or strings), for example for the expression `in`.

..  versionchanged:: 12.0.0

    The element `download-link` uses the expression `in` with
    :yaml:`intExplodeValue: 1` instead of `inSet`. See
    :ref:`upgrade-12-0-0-form-definition`.

..  _frontend-management-templates:

Templates of the frontend management
====================================

..  versionchanged:: 12.0.0

    The templates of the Management plugin are located in the regular
    template folders. The TypoScript of events2 does not set
    :typoscript:`plugin.tx_events2.view.templateRootPaths.1` and
    :typoscript:`partialRootPaths.1` anymore.

Templates of the Management plugin
    :file:`Resources/Private/Templates/Management/ListMyEvents.html`,
    :file:`Resources/Private/Templates/Management/Perform.html`,
    :file:`Resources/Private/Templates/Management/ConfirmActivation.html`
    and the partial
    :file:`Resources/Private/Partials/Management/UserEditLinks.html`.
    Override them with
    :typoscript:`plugin.tx_events2.view.templateRootPaths` and
    :typoscript:`partialRootPaths` of your site package.

Templates of EXT:form
    The partials of the form elements and the mail templates of the
    finisher `EmailToReceiver` are located in
    :file:`Resources/Private/FormFramework/`:
    :file:`Partials/Field/Field.html`,
    :file:`Partials/Form/Navigation.html`,
    :file:`Partials/Events2Location.html` and
    :file:`Templates/Finishers/Email/Default.html` with the text part
    :file:`Templates/Finishers/Email/Default.txt`. Override them in your
    copy of the form definition or in the EXT:form configuration.

..  _frontend-management-mail:

Rich text elements in the mail
------------------------------

..  versionadded:: 12.0.0

    The mail templates of the event form render rich text elements with
    the :ref:`FormatRichTextViewHelper <formatRichTextViewHelper>`.

The moderator gets the submitted values of rich text elements (elements
with :yaml:`dataType: richText`, see :ref:`frontend-rich-text-editor`)
formatted and sanitized with the same configuration as on saving. The
text part of the mail contains plain text with line breaks. Links with
`t3://` are removed, as they can not be resolved in a mail.

If you use your own mail template, render rich text elements with the
ViewHelper, too:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/FormFramework/Templates/Finishers/Email/Default.html (excerpt)

    <f:if condition="{formValue.element.properties.dbMapping.dataType} == 'richText'">
        <f:then>
            <e2:formatRichText element="{formValue.element}"
                               value="{formValue.processedValue}"/>
        </f:then>
        <f:else>
            <f:format.nl2br>{formValue.processedValue}</f:format.nl2br>
        </f:else>
    </f:if>
