..  include:: /Includes.rst.txt


..  _moderationTokenViewHelper:

=========================
ModerationTokenViewHelper
=========================

Returns the `moderationToken` for the activation link of an event. The
actions `confirmActivation` and `activate` of the Management plugin are
allowed with a valid `moderationToken` or for backend administrators only.

Use this ViewHelper in templates without a controller, which could assign
the token, e.g. the mail template of the EmailToReceiver finisher of
EXT:form. It returns an empty string, if the event does not exist.

..  versionchanged:: 12.0.0

    The token is created by
    :php:`JWeiland\Events2\Service\ManagementAccessService` instead of
    the removed :php:`EventModerationService`. The format of the token is
    unchanged, so activation links of already sent mails stay valid.

Arguments
=========

event
    The event object or the UID of the event.

Basic example
=============

..  code-block:: html

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
