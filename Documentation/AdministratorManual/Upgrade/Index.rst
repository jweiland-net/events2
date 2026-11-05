:navigation-title: Upgrade

..  include:: /Includes.rst.txt
..  _upgrade:

=======
Upgrade
=======

If you upgrade/update EXT:events2 to a newer version, please read this
section carefully!

..  contents::
    :caption: Table of contents
    :local:
    :depth: 1

..  _upgrade-12-0-0:

Update to version 12.0.0
========================

This version contains breaking changes. The Extbase based event form of
the Management plugin has been removed. New and edited events are
submitted with the event form based on EXT:form
(:file:`NewEvent.form.yaml` with the finisher `SaveEventFinisher`) only.
All access rules of the frontend management are checked by the new
:php:`JWeiland\Events2\Service\ManagementAccessService`, which replaces
:php:`JWeiland\Events2\Service\EventModerationService`. See
:ref:`frontend-management`.

Please go through all steps below, if you use the Management plugin, have
overridden its templates, mail templates or labels, or use PHP classes of
events2 in your own code.

..  _upgrade-12-0-0-settings:

Remove obsolete settings
------------------------

The following site settings of the site set `jweiland/events2` have been
removed. Remove them from the :file:`settings.yaml` of your sites:

*   `events2.form.useExtForm`: The event form based on EXT:form is always
    used now.
*   `events2.form.uploadFolder`: The images of the event form based on
    EXT:form are stored in the folder of the option
    :yaml:`saveToFileMount` of the form definition.
*   `events2.form.remainingLetters`

The following TypoScript has been removed. Remove your own overrides of
it:

*   :typoscript:`plugin.tx_events2.settings.remainingLetters`
*   :typoscript:`plugin.tx_events2.settings.new.uploadFolder`
*   :typoscript:`plugin.tx_events2._LOCAL_LANG.de.rightsMessage` and
    :typoscript:`plugin.tx_events2._LOCAL_LANG.default.rightsMessage`
*   :typoscript:`plugin.tx_events2._CSS_DEFAULT_STYLE` with the styles of
    the CSS class `f3-form-error`
*   :typoscript:`plugin.tx_events2.settings.selectableCategoriesForNewEvents`:
    Deprecated since version 9.1.9. Use
    :typoscript:`plugin.tx_events2.settings.new.selectableCategoriesForNewEvents`
    or the site setting `events2.form.selectableCategoriesForNewEvents`.
    Old FlexForm values are moved by the upgrade wizard of events2.

:typoscript:`plugin.tx_form.settings.yamlConfigurations.1661247670`
(:file:`EXT:events2/Configuration/Form/OverrideFormSettings.yaml`) is
always set now, not only if `events2.form.useExtForm` was enabled.

events2 does not set
:typoscript:`plugin.tx_events2.view.templateRootPaths.1` and
:typoscript:`plugin.tx_events2.view.partialRootPaths.1` anymore. The keys
`0` and `2` are unchanged. If you have used the key `1` for your own
paths, it is not overwritten by events2 anymore.

..  _upgrade-12-0-0-actions:

Removed actions of the Management plugin
----------------------------------------

The actions `new`, `create`, `edit`, `update` and `delete` of the
Management plugin have been removed. The plugin provides the actions
`listMyEvents`, `perform`, `confirmActivation` and `activate` only.
Requests for unknown actions are handled by the default action
`listMyEvents`.

Events can not be deleted in the frontend anymore.

Update links in your own templates:

*   Links to the action `new` link to the action `perform` without
    argument.
*   Links to the action `edit` link to the action `perform` with the
    argument `event`. The event is read from the query parameter
    `tx_events2_management[event]` only, so do not submit it in a POST
    body:

    ..  code-block:: html
        :caption: EXT:my_sitepackage/Resources/Private/Templates/Day/Show.html (excerpt)

        <f:link.action action="perform"
                       controller="Management"
                       pageUid="{settings.pidOfManagementPage}"
                       arguments="{event: event, timestamp: '0'}">
            {f:translate(key: 'update')}
        </f:link.action>

*   Remove links to the actions `create`, `update` and `delete`.

The section `NewEdit` of :file:`Templates/Day/Show.html` links both
entries to the action `perform` on the page of
`settings.pidOfManagementPage` now. Before, the edit link pointed to the
current page.

..  _upgrade-12-0-0-routing:

Update the route enhancer of the Management plugin
--------------------------------------------------

Replace routes for `Management::new` and `Management::edit` with routes
for `Management::perform` and remove the routes for `Management::create`,
`Management::update` and `Management::delete`:

..  code-block:: yaml
    :caption: config/sites/my-site/config.yaml (excerpt)

    routeEnhancers:
      Events2Management:
        type: Extbase
        extension: Events2
        plugin: Management
        routes:
          - routePath: '/events/m/lme'
            _controller: 'Management::listMyEvents'
          - routePath: '/events/m/p'
            _controller: 'Management::perform'
          - routePath: '/event/m/p/{event_title}'
            _controller: 'Management::perform'
            _arguments:
              event_title: event
          - routePath: '/event/m/c/{event_title}'
            _controller: 'Management::confirmActivation'
            _arguments:
              event_title: event
          - routePath: '/event/m/a/{event_title}'
            _controller: 'Management::activate'
            _arguments:
              event_title: event
        defaultController: 'Management::listMyEvents'
        aspects:
          event_title:
            type: PersistedHiddenAliasMapper
            tableName: tx_events2_domain_model_event
            routeFieldName: path_segment

The action `perform` has no Extbase argument `event` anymore, the event
form reads the UID from the query. Hidden events are mapped by Extbase for
the action `confirmActivation` only (before: `edit`, `update` and
`confirmActivation`).

..  _upgrade-12-0-0-templates:

Check your template overrides
-----------------------------

The following templates and partials have been removed. Delete your
overrides of them:

*   :file:`Resources/Private/Templates/Management/New.html`
*   :file:`Resources/Private/Templates/Management/Edit.html`
*   :file:`Resources/Private/Templates/Management/Create.html`
*   :file:`Resources/Private/Templates/Management/Update.html`
*   :file:`Resources/Private/Partials/Management/FormFields.html`
*   :file:`Resources/Private/Partials/FormErrors.html`

The templates of the Management plugin for EXT:form have been moved into
the regular template folders. Their names relative to the template root
path are unchanged, so your overrides still apply:

..  list-table::
    :header-rows: 1

    *   -   Before (in :file:`Resources/Private/`)
        -   Now (in :file:`Resources/Private/`)
    *   -   :file:`FormFramework/Templates/Management/ListMyEvents.html`
        -   :file:`Templates/Management/ListMyEvents.html`
    *   -   :file:`FormFramework/Templates/Management/Perform.html`
        -   :file:`Templates/Management/Perform.html`
    *   -   :file:`FormFramework/Partials/Management/UserEditLinks.html`
        -   :file:`Partials/Management/UserEditLinks.html`

Please compare your overrides of :file:`ListMyEvents.html` and
:file:`UserEditLinks.html` with the original files, especially if you have
overridden the versions of the Extbase form, which had the same names.

The templates of EXT:form stay in :file:`Resources/Private/FormFramework/`:
:file:`Partials/Field/Field.html`, :file:`Partials/Form/Navigation.html`,
:file:`Partials/Events2Location.html` and
:file:`Templates/Finishers/Email/Default.html`. The new file
:file:`Templates/Finishers/Email/Default.txt` is the text part of the
mail.

..  _upgrade-12-0-0-mail:

Use the FormatRichTextViewHelper in own mail templates
------------------------------------------------------

The mail of the event form shows rich text elements formatted and
sanitized now. If you use your own copy of
:file:`Templates/Finishers/Email/Default.html` or
:file:`Default.txt`, render values of elements with
:yaml:`dataType: richText` with the new
:ref:`FormatRichTextViewHelper <formatRichTextViewHelper>`. See
:ref:`frontend-management-mail`.

..  _upgrade-12-0-0-access:

Changed access rules
--------------------

The rules of the frontend management have been simplified, see
:ref:`frontend-management-access`:

*   New events can only be created by logged-in frontend users of the
    user group of :confval:`settings.userGroup
    <events2-typoscript-settings-usergroup>` with a related organizer.
    Backend administrators can no longer create events in the frontend.
*   The list of own events (`listMyEvents`) follows the same rule.
    Backend administrators can not list events anymore.
*   Backend administrators can still edit every existing event. The
    organizers of the event are kept now. Before, an administrator
    removed the owner of the event.
*   The action `activate` requires a valid `moderationToken`, also for
    backend administrators. They can still open `confirmActivation`
    without token; the confirmation page renders a fresh token into its
    form.
*   The user group is read from the TypoScript setup only
    (:typoscript:`plugin.tx_events2_management.settings.userGroup`
    before :typoscript:`plugin.tx_events2.settings.userGroup`). Values of
    the FlexForm or of other plugin settings are not respected anymore.
    Set the user group in the site setting `events2.form.userGroup` or
    in TypoScript.
*   The edit form is only prefilled, if the current user is allowed to
    edit the event. The finisher `SaveEventFinisher` checks the same
    rules before anything is stored, also if the form definition is used
    outside of the Management plugin. If access is denied, it throws a
    :php:`TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException`.

The format of the `moderationToken` is unchanged. Activation links of
already sent mails stay valid.

..  _upgrade-12-0-0-activation-form:

Send the moderationToken in own activation forms
------------------------------------------------

If you have overridden :file:`Templates/Management/ConfirmActivation.html`,
make sure that the form of the action `activate` submits the
`moderationToken`, which is assigned to the template:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Templates/Management/ConfirmActivation.html (excerpt)

    <f:form action="activate" controller="Management" method="post">
        <f:form.hidden name="event" value="{event.uid}"/>
        <f:form.hidden name="moderationToken" value="{moderationToken}"/>
        <f:form.submit value="{f:translate(key: 'confirmActivation.submit')}"/>
    </f:form>

Without the token the activation is denied with the message of the label
`invalidModerationToken`, also for backend administrators.

..  _upgrade-12-0-0-form-definition:

Update own copies of NewEvent.form.yaml
---------------------------------------

The element `download-link` of :file:`NewEvent.form.yaml` used the
expression `inSet` with swapped arguments. The download link of an event
was only found, if the event had exactly one download link, and SQLite
threw an exception. If you use an own copy of the form definition,
change the expression to `in` with :yaml:`intExplodeValue: 1`:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/Form/NewEvent.form.yaml (excerpt)

    - label: 'Download Link'
      type: Text
      identifier: download-link
      defaultValue: ''
      properties:
        dbMapping:
          relation:
            labelColumn: link
            table: tx_events2_domain_model_link
            expressions:
              - column: uid
                expression: in
                value: '{event:download_links}'
                intExplodeValue: 1

The expression `inSet` is still supported for own form elements. Only
the methods `eq`, `neq`, `lt`, `lte`, `gt`, `gte`, `in`, `notIn` and
`inSet` of the ExpressionBuilder are allowed in
:yaml:`dbMapping.relation.expressions` now, other expressions are
ignored. See :ref:`frontend-management-form-definition`.

The teaser (element `short-description`, column `teaser`) is limited to
255 characters now, the `max` of the column in TCA. Add the attribute
`maxlength` and the validator `StringLength` to your own copy:

..  code-block:: yaml
    :caption: EXT:my_sitepackage/Configuration/Form/NewEvent.form.yaml (excerpt)

    - label: 'Short description'
      type: Textarea
      identifier: short-description
      defaultValue: ''
      properties:
        dbMapping:
          column: teaser
          dataType: plainText
        elementDescription: 'Short description'
        fluidAdditionalAttributes:
          maxlength: 255
      validators:
        - identifier: StringLength
          options:
            maximum: 255

Existing teasers with more than 255 characters are not changed. If such
an event is edited in the frontend, the teaser has to be shortened before
the event can be saved.

Each textarea with a `maxlength` shows the remaining characters below the
field. The text comes from the new label `remainingLetters` of
:file:`locallang.xlf`, where `%s` is the number of remaining characters.

..  _upgrade-12-0-0-labels:

Removed labels
--------------

The following labels have been removed from
:file:`Resources/Private/Language/locallang.xlf` and
:file:`de.locallang.xlf`. Remove your overrides of them in
`_LOCAL_LANG` or in own language files:

`create`, `deleteImage`, `remainingLetters`, `cshTitle`, `cshTeaser`,
`cshEventBegin`, `cshEventEnd`, `cshTimeBegin`, `cshCategories`,
`cshLocation`, `cshVideo`, `cshImages`, `eventCreated`, `eventUpdated`,
`eventDeleted`, `email.subject.create`, `email.subject.update`,
`validator.event.categories.isMandatory` and
`validator.event.videoLink.notYouTube`.

..  _upgrade-12-0-0-icons:

Replaced icons
--------------

All icons of events2 are SVG files now and are registered with the
:php:`SvgIconProvider`. The TCA of the tables uses the icon identifiers in
`typeicon_classes` instead of paths in `iconfile`.

The following files have been removed. Files with the same name and the
extension `.svg` replace the PNG files:

*   :file:`calendar_single.png`, :file:`calendar_recurring.png`,
    :file:`calendar_duration.png`
*   :file:`exception_add.png`, :file:`exception_info.png`,
    :file:`exception_remove.png`, :file:`exception_time.png`
*   :file:`tx_events2_domain_model_link.png`,
    :file:`tx_events2_domain_model_location.png`,
    :file:`tx_events2_domain_model_organizer.png`,
    :file:`tx_events2_domain_model_time.png`
*   :file:`plugin_wizard.svg` (replaced by :file:`plugin.svg`, the
    identifier `ext-events2-wizard-icon` is unchanged)
*   :file:`tx_events2_domain_model_category.gif` (without replacement,
    use a core icon like `mimetypes-x-sys_category`)

New icon identifiers are `ext-events2-record-event`,
`ext-events2-record-holiday`, `ext-events2-record-link`,
`ext-events2-record-location`, `ext-events2-record-organizer` and
`ext-events2-record-time`. The existing identifiers
`ext-events2-calendar-*` and `ext-events2-exception-*` point to the SVG
files now.

If you use the paths of the removed files directly, for example in own
templates or in TCA overrides with `iconfile`, use the icon identifiers
or the new `.svg` files instead.

..  _upgrade-12-0-0-developer:

Removed PHP classes and changed API
-----------------------------------

The following classes have been removed without replacement:

*   :php:`JWeiland\Events2\Domain\Validator\EventValidator`
*   :php:`JWeiland\Events2\Domain\Validator\CategoryMandatoryValidator`
*   :php:`JWeiland\Events2\Property\TypeConverter\DateTimeImmutableConverter`
    incl. its global registration as Extbase type converter for
    :php:`DateTimeImmutable`
*   :php:`JWeiland\Events2\Middleware\AttachOrganizerToEventMiddleware`
    (middleware identifier `jweiland/events2/attach-organizer`)
*   The event listeners :php:`AddOrganizerToEventRecordEventListener`,
    :php:`ApplyLocationAsMandatoryIfNeededEventListener`,
    :php:`ApplyUploadFolderEventListener` (identifier
    `events2/apply-upload-folder`),
    :php:`CheckFalUploadsEventListener` (identifier
    `events2/check-fal-uploads`),
    :php:`SetDateFormatForPropertyMappingEventListener` and
    :php:`UpdateEventPathSegmentEventListener`. If you have ordered your
    own event listeners with `before` or `after` relative to them, please
    update them.
*   The traits :php:`InjectDayRelationServiceTrait`,
    :php:`InjectLocationRepositoryTrait` and
    :php:`InjectPersistenceManagerTrait`. Inject the services with
    constructor injection instead.

:php:`JWeiland\Events2\Service\EventModerationService` has been merged
into :php:`JWeiland\Events2\Service\ManagementAccessService`. It
contains all rules of the frontend management and provides this public
API:

:php:`getEventUid(ServerRequestInterface $request): int`
    Returns the UID of the event to edit from the query parameter
    `tx_events2_management[event]`. Arrays, values which are no integer
    and values lower than 1 result in `0`, which means: create a new
    event.

:php:`getDenialReason(ServerRequestInterface $request): string`
    Returns the label key of the reason, why the current user is not
    allowed to create, list or edit events, or an empty string, if
    access is allowed. Without event in the query the rule for creating
    and listing events applies, else the rule for editing this event.
    Events which do not exist and events of other organizers both result
    in `unauthorizedOrganizerForEdit`. The user group is read from the
    TypoScript setup.

:php:`canConfirmActivation(int $eventUid, string $moderationToken): bool`
    Backend administrators for existing events or everyone with a valid
    token.

:php:`canActivateEvent(int $eventUid, string $moderationToken): bool`
    Everyone with a valid token only.

:php:`generateModerationToken(int $eventUid): string`
    Returns the token for the activation link, or an empty string, if
    the event does not exist.

Migrate your own code, which uses :php:`EventModerationService` of
events2 11.2.0:

..  list-table::
    :header-rows: 1

    *   -   Before (:php:`EventModerationService`)
        -   Now (:php:`ManagementAccessService`)
    *   -   :php:`generateModerationToken(int $eventUid)`
        -   :php:`generateModerationToken(int $eventUid)`
    *   -   :php:`isValidModerationToken(string $moderationToken, int $eventUid)`
        -   :php:`canActivateEvent(int $eventUid, string $moderationToken)`.
            Mind the changed order of the arguments.
    *   -   :php:`getValidModerationToken(RequestInterface $request, int $eventUid)`
        -   :php:`canActivateEvent()` or :php:`canConfirmActivation()`
            with the `moderationToken` of the request. To create a token
            for a template, use :php:`generateModerationToken()`.

In templates, create the token for the activation link with the
:ref:`ModerationTokenViewHelper <moderationTokenViewHelper>`.

The action :php:`ManagementController::performAction()` has no argument
`event` anymore. The event listener
:php:`RegisterHiddenEventEventListener` registers hidden events for the
action `confirmActivation` only.

The event model :php:`JWeiland\Events2\Domain\Model\Event` has no
:php:`#[FileUpload]` attribute at the property `$images` and no
:php:`CategoryMandatoryValidator` at the property `$categories` anymore.
If you have extended the model or use it in an own Extbase form, add
your own validation and upload configuration.

The JavaScript variables of :php:`AbstractController::getJsVariables()`
do not contain `localization.remainingText` anymore.
:file:`Events2.js` does not initialize the counter of remaining letters
and the date picker of the removed Extbase form anymore. The form based
on EXT:form loads the new ES module :file:`remaining-letters.js` for
textareas with a `maxlength` instead, see
:ref:`upgrade-12-0-0-form-definition`.

..  _upgrade-12-0-0-caches:

Flush caches after the update to 12.0.0
---------------------------------------

As PHP classes, templates, icons and TypoScript have changed, please flush
all caches in :guilabel:`Admin Tools > Maintenance` after the update.

..  _upgrade-11-1-0:

Update to Version 11.1.0
========================

..  note::

    The templates :file:`Create.html` and :file:`Update.html` of the
    Extbase form mentioned below have been removed with events2 12.0.0,
    see :ref:`upgrade-12-0-0-templates`.

Mail security scanners and link previews (e.g. Microsoft Safe Links) open
the links of a mail automatically. Before this version, the activation link
of the notification mail activated the event with a simple GET request.
Further, every owner of an event was able to activate his own event and
bypass the moderation.

The activation link opens the new action `confirmActivation` of the
Management plugin now. It shows a summary of the event and a button, which
activates the event via POST. The links are signed with a
`moderationToken`. `confirmActivation` and `activate` are allowed with a
valid `moderationToken` or for backend administrators only. The token signs
the event UID and stays valid until the event was deleted.

Activation links of mails sent before this update do not contain a
`moderationToken`. They are denied, unless they are opened by a backend
administrator logged in into the backend.

..  _upgrade-11-1-0-templates:

Overridden mail templates
-------------------------

If you have overridden one of these templates, please change the
activation link to the action `confirmActivation` and add the argument
`moderationToken`:

*   :file:`Resources/Private/Templates/Management/Create.html`
*   :file:`Resources/Private/Templates/Management/Update.html`
*   :file:`Resources/Private/FormFramework/Templates/Finishers/Email/Default.html`

In :file:`Create.html` and :file:`Update.html` the controller assigns the
variable `moderationToken`:

..  code-block:: html

    <f:link.action pageUid="{settings.pidOfManagementPage}"
                   action="confirmActivation"
                   controller="Management"
                   absolute="1"
                   arguments="{event: event, moderationToken: moderationToken}">
        Datensatz freischalten
    </f:link.action>

The mail template of the EXT:form finisher has no controller. Create the
token with the new :ref:`ModerationTokenViewHelper <moderationTokenViewHelper>`:

..  code-block:: html

    {namespace e2=JWeiland\Events2\ViewHelpers}

    <f:variable name="event">{finisherVariableProvider.SaveEvent.insertedUids.tx_events2_domain_model_event}</f:variable>
    <f:variable name="moderationToken">{e2:moderationToken(event: event)}</f:variable>

    <f:link.action pageUid="{pidOfManagementPage}"
                   action="confirmActivation"
                   controller="Management"
                   extensionName="Events2"
                   pluginName="Management"
                   absolute="1"
                   arguments="{event: event, moderationToken: moderationToken}">
        Datensatz freischalten
    </f:link.action>

If you have overridden the template paths of the Management plugin, please
copy the new template :file:`Management/ConfirmActivation.html`, too.

..  _upgrade-11-1-0-routing:

Route enhancer
--------------

If you have configured a route for `Management::activate`, please add a
route for `Management::confirmActivation`, too. The `moderationToken` is
added as query parameter:

..  code-block:: yaml

    -
      routePath: '/event/m/c/{event_title}'
      _controller: 'Management::confirmActivation'
      _arguments:
        event_title: event

..  _upgrade-11-0-0:

Update to Version 11.0.0
========================

..  note::

    The Extbase based event form, its image upload, the site setting
    `events2.form.uploadFolder` and the support of checkfaluploads have
    been removed with events2 12.0.0. The steps below are only relevant,
    if you stay on version 11. See :ref:`upgrade-12-0-0`.

This version contains breaking changes. The images of the event form of
the Management plugin are uploaded with the file upload handling of
Extbase now. The own type converter for uploads has been removed. Please
go through all steps below, if you use the Extbase based event form of
the Management plugin or have extended the event model.

The form based on EXT:form (site setting `events2.form.useExtForm` with
:file:`NewEvent.form.yaml`) is not affected by this change.

..  _upgrade-11-0-0-limits:

New limits for uploaded images
------------------------------

The image upload of the event form accepts:

*   up to 5 images per event,
*   up to 4 MB per image,
*   files with the extensions `gif`, `jpg`, `jpeg` and `png` only.

Before, all file extensions of
`$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']` were allowed and
there was no limit for the file size. Images violating these limits are
rejected with a validation error of the form.

The names of uploaded files get a random suffix. Deleting an image in
the form deletes the file, too.

..  _upgrade-11-0-0-upload-folder:

New site setting for the upload folder
--------------------------------------

The folder of the uploaded images can be configured with the new site
setting `events2.form.uploadFolder` now. It is mapped to the TypoScript
setting `settings.new.uploadFolder`.
The default value `1:user_upload/tx_events2/` keeps the former folder.
The folder is created, if it does not exist.

..  _upgrade-11-0-0-templates:

Template overrides of FormFields.html
-------------------------------------

If you have overridden :file:`Partials/Management/FormFields.html` in
your site package, please compare it with the original file:

*   The section `UploadImage` and its five indexed upload fields
    `event[images][0]` to `event[images][4]` have been removed.

*   Use one upload field for all images:

    ..  code-block:: html

        <f:form.upload property="images" multiple="1"/>

*   Render a delete checkbox for each existing image:

    ..  code-block:: html

        <f:for each="{event.images}" as="image">
            <f:form.uploadDeleteCheckbox property="images"
                                         fileReference="{image}"/>
        </f:for>

*   The form needs the attribute `enctype="multipart/form-data"`. The
    original templates :file:`Templates/Management/New.html` and
    :file:`Templates/Management/Edit.html` already have it.

..  _upgrade-11-0-0-model:

Changed API of the event model
------------------------------

:php:`Event::getImages()` returns an
:php:`ObjectStorage<FileReference>` instead of an array now. Index access
like `{event.images.0}` in Fluid templates does not work anymore. Use
`{event.firstImage}` or iterate over the images with :html:`<f:for>`.

:php:`Event::getOriginalImages()` has been removed. Use
:php:`Event::getImages()` instead. The new methods
:php:`Event::addImage()` and :php:`Event::removeImage()` add or remove a
single image.

..  _upgrade-11-0-0-checkfaluploads:

Rights checkbox of checkfaluploads
----------------------------------

The templates of events2 do not contain a rights checkbox for the
uploaded images. If `jweiland/checkfaluploads` is installed, events2
adds its validator to the image uploads of the actions `create` and
`update` of the Management plugin. Version 6.x of checkfaluploads is
suggested, as the validator needs its API for the file upload handling
of Extbase.

In this case add the rights checkbox and the rights message to your
template override of :file:`Partials/Management/FormFields.html`:

..  code-block:: html

    <html xmlns:cfu="http://typo3.org/ns/JWeiland/Checkfaluploads/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <f:form.checkbox property="images.rights" id="imageRights" value="1"/>
    <label for="imageRights"><cfu:imageRightsMessage/></label>

The checkbox has to be submitted with the name
`tx_events2_management[event][images][rights]`. Without this field every
image upload stops with exception `1788270392`. If the checkbox is not
checked, the form shows a validation error. See the
`developer manual of checkfaluploads
<https://docs.typo3.org/p/jweiland/checkfaluploads/main/en-us/Developer/Index.html>`__
for details.

..  _upgrade-11-0-0-labels:

Changed labels
--------------

The labels `error.upload`, `error.uploadRights`, `error.fileExtension`
and `rightsMessage` have been removed from
:file:`Resources/Private/Language/locallang.xlf`. For the rights message
use the ViewHelper :html:`<cfu:imageRightsMessage/>` of checkfaluploads.
If you have overridden these labels with `_LOCAL_LANG`, you can remove
these overrides.

The new label `deleteImage` is used for the delete checkbox of an image.
The text of `cshImages` has been updated to the new limits.

..  _upgrade-11-0-0-developer:

Removed PHP classes and event listeners
---------------------------------------

*   The type converter
    :php:`JWeiland\Events2\Property\TypeConverter\UploadMultipleFilesConverter`
    has been removed.

*   The PSR-14 event :php:`JWeiland\Events2\Event\PostCheckFileReferenceEvent`
    has been removed without replacement. To change the upload
    configuration of the images, register an event listener for
    :php:`JWeiland\Events2\Event\PreProcessControllerActionEvent` and
    modify the file upload configuration of the argument `event`.

*   The event listeners `AssignMediaTypeConverterEventListener`
    (identifier `events2/assignMediaTypeConverter`) and
    `DeleteUploadedFilesOnErrorEventListener` (identifier
    `events2/deleteUploadedFilesOnError`) have been removed. If you have
    ordered your own event listeners with `before` or `after` relative to
    these identifiers, please update them.

*   Two new event listeners for :php:`PreProcessControllerActionEvent`
    are registered for the actions `create` and `update` of the
    Management plugin. Use their identifiers to order your own event
    listeners:

    `events2/apply-upload-folder`
        Sets the upload folder of the images to the value of
        `settings.new.uploadFolder`.

    `events2/check-fal-uploads`
        Adds the validator of checkfaluploads to the image uploads, if
        checkfaluploads is installed.

..  _upgrade-11-0-0-caches:

Flush caches
------------

As PHP classes, templates and TypoScript have changed, please flush all
caches in :guilabel:`Admin Tools > Maintenance` after the update.

Update to Version 10.2.4
========================

Due to an issue in the DayRecordService, records in the day table that use
enable fields, such as `hidden`, `starttime`, or `endtime`, were not removed
when new day records were generated for a given event. As a result, the day
table grows significantly with each execution of the `Re-Create day records`
scheduler task.

After updating to version 10.2.4, please run the
TYPO3 command `events2:rebuild`.

If you are not familiar with the CLI, you can alternatively truncate
the `tx_events2_domain_model_day` table and then run
the `Re-Create day records` scheduler task again.

Update to Version 10.0.0
========================

Because of the new column `def_lang_event_uid` in `tx_events2_domain_model_day`
you will get various duplicate event records in your list views. Please
execute the command `events2:rebuild` after the update to fix this issue!

Update to Version 9.1.9
=======================

We have moved TypoScript setting `settings.selectableCategoriesForNewEvents`
 into `settings.new.selectableCategoriesForNewEvents`. If you have defined
 that setting in management plugin please execute the events2 upgrade wizard.

Update to Version 9.0.8
=======================

Because of missing uniqueness while generating path segments for imported
event records we had to update the constructor arguments of
GeneratePathSegmentEvent. First argument has be be array of
parameters we get from SlugHelper->generate and second argument
is the SlugHelper itself.

Update to Version 9.0.4
=======================

We have marked the FeUser ViewHelper as deprecated. As a replacement for
access restrictions there is a new `AccessRightsEventListener` catching
invalid requests. You should update your own templates accordingly.

Because of an "edit" and "activate" linking problem in information mail, we
had to store the event non-hidden in database first and had to hide the event
after the mailing in a second step. Else the event slug can not be added to
route enhancer. Please make sure that your own modifications
to `plugin.tx_events.settings.overrideForm.finishers.*` configuration is still
working.

There is a new `PersistedHiddenAliasMapper` available to allow generating
slugs to hidden event records. Needed for info mails.

..  code-block:: yaml

    Events2Management:
      type: Extbase
      extension: Events2
      plugin: Management
      routes:
        - routePath: '/events/m/lme'
          _controller: 'Management::listMyEvents'
        - routePath: '/events/m/p'
          _controller: 'Management::perform'
        -
          routePath: '/event/m/p/{event_title}'
          _controller: 'Management::perform'
          _arguments:
            event_title: event
        -
          routePath: '/event/m/a/{event_title}'
          _controller: 'Management::activate'
          _arguments:
            event_title: event
      defaultController: 'Management::listMyEvents'
      aspects:
        event_title:
          type: PersistedHiddenAliasMapper
          tableName: tx_events2_domain_model_event
          routeFieldName: path_segment

We render our own template for `errorAction` which results in much nicer
flash messages output. Please be prepared to override them accordingly.

Update to Version 9.0.0
=======================

This version is NOT compatible with TYPO3 10 and TYPO3 11 anymore.

We have upper case first char of all fluid sections `<f:section name="Date">`.
Please make sure to adopt that in your extending templates.

Update to Version 8.3.2
=======================

There is an UpgradeWizard to convert `sDEFAULT` in FlexForms to `sDEF`, but
with each update of an events2 plugin `sDEFAULT` was written to DB again. We
have updated that page name to `sDEF` in our FlexForms, now. Please execute the
UpgradeWizard again.

Upgrade to Version 8.0.0
========================

This version is NOT compatible with PHP versions lower than 7.4!

We have updated over 10.000 lines of code. Please click the `flush cache` button in installtool.

New Plugins
-----------

As `switchableControllerActions` are deprecated since TYPO3 11 we have migrated them to individual plugins.
Please execute UpgradeWizard `events2MoveFlexFormFields` to migrate existing plugins. Please backup your project
before executing this Wizard!

With this change we have migrated all list*Actions into one listAction. Please have a look into our templates
and add the missing conditions to your templates.

We have renamed EventController to ManagementController. So please rename the templates accordingly.

We have split the SearchController into SearchForm- and SearchResultController. So please rename the templates
accordingly.

https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/10.3/Deprecation-89463-SwitchableControllerActions.html


Fluid Widget replacement
------------------------

Fluid widget functionality was completely removed in TYPO3 11.

Instead of `ICalWidget` please use link to action of our new ICalController:

..  code-block:: html

    <f:link.action action="download" controller="ICal" target="_blank" arguments="{dayUid: day.uid}">
      {f:translate(key: 'export')}
    </f:link.action>

Instead of `PoiCollectionWizard` in Location/Show.html please migrate to:

..  code-block:: html

    <f:render partial="Maps2/PoiCollection"
              section="showMap"
              arguments="{poiCollections: {0: location.txMaps2Uid}, override: {settings: {mapWidth: '100%', mapHeight: '300', zoom: '14'}}}" />

and check, if path to EXT:maps2 partial still matches your needs:

..  code-block:: typoscript

    plugin.tx_events2.view.partialRootPaths.2 = EXT:maps2/Resources/Private/Partials/

https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/11.0/Breaking-92529-AllFluidWidgetFunctionalityRemoved.html


jquery removed
--------------

We have removed the use of jquery and migrated all code to VanillaJS. So please check
`page.includeJSFooter` and `page.includeCSS` if they are matching the needs of your project.


Update to Version 7.1.0
=======================

As f:widget.paginate is deprecated and POST requests are not allowed anymore in this widget, we have migrated
the f:widget.paginate widget to new TYPO3 Pagination API.

Please remove the f:widget.paginate from Templates and insert this code:

..  code-block:: html

    <f:render partial="Component/Pagination"
              arguments="{pagination: pagination, paginator: paginator, actionName: actionName}" />


Upgrade to Version 7.0.0
========================

Nearly all Controller Actions contains a call to the new TYPO3 EventDispatcher now. That way we have moved a lot of
logic of the event controllers into EventListeners. All Extbase SignalSlot have been removed.

The Action ``listSearchResults`` was moved from event controller into day controller. Please move
your own ``ListSearchResults`` template from ``Event`` into ``Day`` folder and update controller name
in ``Search/Show.html``:

``<input type="hidden" name="tx_events2_events[controller]" value="Day" />``

All deprecated methods and version_compare lines have been removed.

``edit`` action is defined as ``uncached`` in ext_localconf.php now.

You have to remove existing Re-Generate tasks from scheduler and create them again. That's because of changed
contructor arguments.

Update to Version 6.3.0
=======================

It is now possible to assign multiple organizers to an event record. That's why we have added a new MM table
and a new column ``organizers`` which has to be created by ``Analyze database`` button in Installtool. Please
execute events2 upgrade wizard to migrate all current relations into this new table. If UpgradeWizard was
executed successfully you can remove the old ``organizer`` column.

Please update following parts in your templates:

..  code-block:: html

    <f:if condition="{e2:feUser(field: 'tx_events2_organizer')} == {event.organizer.uid}">

to

..  code-block:: html

    <f:if condition="{event.isCurrentUserAllowedOrganizer}">

We have changed the column `detail_informations` of event table to `detail_information`. Please execute
UpgradeWizard to move all data into this new column. Old calls to getDetailInformations are still possible but
deprecated and removed with events2 7.0.0.

In XSD file for XML import we have changed `organizer` to `organizers`. Please update your XML file generation
or load an old XSD file in Extension Settings of Installtool. But with 7.0.0 we will not support the old
`organizer` property while XML import anymore.

Update to Version 6.2.6
=======================

We have changed the keys for various used hooks from incremented numbered to string based keys in ext_localconf.php.
As a developer you should check, if your own implementation is still working.

Update to Version 6.2.4
=======================

We have changed some method arguments, please flush cache in InstallTool

Update to Version 6.1.1
=======================

We have changed data type of country to INT in DB. Please start compare database in INstalltool.

Upgrade to Version 6.0.0
========================

With events2 6.0.0 we have removed TYPO3 8 compatibility and add TYPO3 10 compatibility.
We have not added any new features.

Because of incompatibility we have created a new pageTitleProvider as replacement for pageTitleUserfunc.

Upgrade to Version 5.0.0
========================

With this release we have implemented a better multilingual support. We are not done, but as long as DayGenerator
still has problems with languages we have marked a lot of fields in translated events as readonly.

If you use events2 with one language only, there is no problem to upgrade to 5.0.0.

Update to Version 4.1.1
=======================

With version 4.1.0 we have added the path_segment for slugs in event records, but first with version 4.1.1 we
have added the Updater for this field.
Please visit InstallTool and execute the updater for Slugs in event records.

Upgrade to Version 4.0.0
========================

If you don't use the event2 API or you don't use your own XmlImporter an Update shouldn't be a problem
for you.

We have modified ``findHiddenEntryByUid`` to ``findHiddenEntry`` in AbstractController. With the new argument
you can now select hidden records by any property you want.

We have added a lot of strict_types. Please check your extension code accordingly, if you have modified events2.

We have added Task object as second argument to constructor of AbstractImporter. Therefor we have removed
all arguments of ``importer`` method of XmlImporter.

Method ``initialize`` is not required by ImporterInterface and will not be called anymore. Please move initialization
process into Constructor directly.

Update to Version 3.7.0
=======================

New Feature:
We have added 3 new getters to Time object:

*   getTimeEntryAsDateTime
*   getTimeBeginAsDateTime
*   getTimeEndAsDateTime

These are very helpful as you now can format them with f:format.date() VH

Update to Version 3.3.1
=======================

With version 3.3.1 we have added a new column ``same_day_time`` to day table. It helps us to GROUP BY days with
multiple time records for one day, if mergeEventsAtSameDate is set in plugin.

Please update DB in Installtool and start scheduler task to re-generate all day records.

Update to Version 3.2.0
=======================

With version 3.2.0 we have completely rewritten DayRepository to work with
Doctrine/Core QueryBuilder now. We have added functional tests to be sure
to have same results as in previous versions.

We have renamed mergeEvents checkbox in FlexForm to mergeRecurringEvents. Maybe you
have to reactivate that checkbox.

In previous versions we have grouped events in ListLatest view automatically for you. Now you
have to manual activate mergeRecurringEvents to group them.

We have moved static TypoScript to another location. Please use update wizard in ExtensionManager to update paths.

We have removed mergeEvents option from ExtensionManager. If you have set this option please
re-create your records with CLI or scheduler task.

Update to Version 3.1.0
=======================

With version 3.1.0 we have made a little but breaking change:

In earlier versions you may have done:

<f:form.textarea class="form-control addRemainingCharsCheck" id="teaser" property="teaser" rows="5" cols="50" />

This will not work anymore, as it would be very hard for integrator to place the container for remaining chars
correctly. Please change this part to:

..  code-block:: html

    <div class="form-group row">
      <div class="col-sm-4"></div>
      <div class="col-sm-8">
        <span class="remainingChars" data-id="teaser"></span>
      </div>
    </div>


Use data-id to relate it to a textarea id-attribute.

Upgrade to Version 3.0.0
========================

*   We have removed GetEventDates VH and implemented a completely new way to get future event dates.
*   We have removed MicroStart VH
*   We have removed MicroStop VH
*   We have removed Sort VH, as sorting in VH is always a bad idea.

In previous versions we had the problem, that an added day as exception will never be of type Day. It is of
type Exception with different properties then a Day, which made us many headaches in Fluid-Templates. In
GetDateTime VH we had streamlined Day and Exception records into an array which contains all needed
information. You can't control and you may not understand how this array was build. With version 2.4.0 we have
solved this problem and simplified Fluid template a lot. Instead of modifying the DB tables and models we have
reduced some method-calls down to the DateTime representation of Day and Exception records:

*   Renamed EventService::getSortedTimesForDay to EventService::getSortedTimesForDate
    *   Property $day changed from type Day to \DateTime
*   Renamed EventService::getDifferentTimesForDay to EventService::getDifferentTimesForDate
    *   Property $day changed from type Day to \DateTime
*   Renamed EventService::getTimesForDay to EventService::getTimesForDate
    *   Property $day changed from type Day to \DateTime
*   Renamed EventService::getExceptionsForDay to EventService::getExceptionsForDate
    *   Property $day changed from type Day to \DateTime

So, please check your own extensions, if you make use of these methods. To solve the problem from above, we have
implemented two new ViewHelpers. Please have a look into our new templates and update your own templates
to the new structure:

*   New VH: GetExceptionsFromEventForSpecificDate
*   New VH: IsDateMarkedAsCanceled

Event property `download_links` can now collect more then one link. Please update DB in Installtool and clear
system caches.

Now you can create events with a recurring of one or more months.

We have removed all methods to get time records for an event from DayRelationService as we have all these
methods in EventService already.

Update to Version 2.4.0
=======================

Version 2.4.0 is now TYPO3 9 compatible. We also have removed compatibility to TYPO3 6 and 7.

Update to Version 2.3.0
=======================

With version 2.3.0 we have rewritten the frontend Events2.js completely. Now it it much more readable and
it prevents executing JavaScript, if it is not valid for current view. That's why we have some changes in our
templates:

*   Remove all siteId variables. Please use {data.pid} instead
*   Remove all jsSearchVariables. They are now all available in just jsVariables
*   All jsVariables are removed from Templates and Partials and has to be in all Layout file now
*   The <div> for remainingChars has been removed as it will be created dynamically with JS now
*   CSS class addRemainingCharsCheck activates max chars feature for textareas automatically
*   The <span> for locationStatus has been removed as it will be created dynamically with JS now
*   Change CSS class powermail_input to form-control in Event/FormFields Partial
*   Move records for selectboxes in search plugin from {data} to {selectorData}
*   Make data with current tt_content record available in jsVariables, too.
*   We have added two more Layouts. Please adjust the new path in your templates.

We have removed TypoLinkCodecService as it was only needed by our own TypoLink VH and is part of TYPO3
since TYPO3 7.*.

Organizer and Location are not required anymore by default. If you still need them required
go into ExtensionManager and set them as required. This change results into some further changes
to our templates:

*   Add if to render section "location" only if a location is available
*   Add if to render section "googleRoute" only if a location is available
*   Add if to render section "organizer" only if a organizer is available
*   Add if to location to prevent rendering footer for each event in list
*   Move <p>-Tag, for editing your own records in FE, inside of the if
*   Add if to organizer and location in Create.html
*   Add if to organizer and location in Update.html

We have moved all email settings in ExtConf to new tab "Email"

EXT `maps2` is not a hard-coded dependency to events2 anymore, but we still suggest it in ext_emconf.php.

Upgrade to Version 2.0.0
========================

Version 2.0.0 will come with some new cols and we have removed some cols. So please be careful while comparing
database with TCA definition after upgrading.

..  important::

    Please do **not** delete cols in InstallTool after installing the new version! Only add the new fields,
    than go into Extensionmanager, select events2 and start the upgrade script. Delete the old cols in InstallTool
    only, if the upgrade script symbol will not appear in Extensionmanager anymore.

We have removed ShowEventDatesViewHelper, because it was sometimes too hard to change that template. So we have
moved that widget into a normal ViewHelper. Please use GetEventDatesViewHelper instead, you can find an example in
Partials/Event/Properties.html.

In case of our new database structure we have removed our e2:widget.paginate ViewHelper.
Please update all templates to use the original f:widget.paginate ViewHelper of fluid and maybe remove
the maxRecords attribute.

The labels of the show action selectbox in Plugin (switchableControllerActions) has changed. We
have added the new action showByDate for DayController. So you have to open each plugin
and set show action again.

After all these changes you have to re-create all day records. The easiest way to do so is:
Create scheduler task of type "Create/Update Days" if not already exists.
Execute that task.

We have removed our own TypoLink ViewHelper as it is not needed anymore since
TYPO3 7.6. Please change all e2:link.typolink VHs of your templates into f:link.typolink.
