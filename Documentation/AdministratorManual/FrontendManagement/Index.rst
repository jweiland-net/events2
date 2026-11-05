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
    Renders the event form based on EXT:form. Without the argument
    `event` a new event is created. With the argument `event` the given
    event is edited.

`confirmActivation`
    Target of the activation link of the mail. Shows a summary of the
    event and a button to activate it.

`activate`
    Activates the event. Accepts POST requests of the button of
    `confirmActivation` only.

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
    administrators can no longer create new events in the frontend.

Create an event
    Only logged-in frontend users which are members of the configured
    user group (:confval:`settings.userGroup
    <events2-typoscript-settings-usergroup>`) and have an organizer. There
    is no exception for backend administrators, because a new event would
    be stored without organizer. Without a configured user group nobody
    can create events.

Edit an event
    The owner of the event: a frontend user of the configured user group,
    whose organizer is assigned to the event. Hidden events can be edited,
    too. A backend administrator, who is logged in into the backend, can
    edit every existing event.

List own events
    Frontend users which are allowed to create events, and backend
    administrators.

Activate an event
    Everyone with the activation link of the mail. The link contains a
    signed `moderationToken` for this event. Backend administrators can
    activate events without token. Owners of an event never get a token,
    so they can not bypass the moderation.

If access is denied, the plugin shows a localized flash message, for
example "You're not allowed to create event records.". The labels are
`userGroupNotConfigured`, `pluginNeedsLogin`, `notAllowedToCreate`,
`missingOrganizerForCreate`, `unauthorizedOrganizerForEdit` and
`invalidModerationToken` of
:file:`EXT:events2/Resources/Private/Language/locallang.xlf`.

The finisher `SaveEventFinisher` checks the same rules for creating and
editing again before anything is stored, even if the form definition is
used outside of the Management plugin, for example in a form content
element. If access is denied, the finisher throws a
:php:`TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException` and
nothing is stored.

..  _frontend-management-images:

Image upload of the event form
==============================

The form definition :file:`NewEvent.form.yaml` contains two elements of
type `ImageUpload` for files of the MIME types `image/jpg`, `image/jpeg`
and `image/png`. The files are stored in the folder
`1:/Extensions/events2/` (option :yaml:`saveToFileMount`). To change these
options or to add a mandatory checkbox for the image rights, use your own
copy of the form definition.

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
