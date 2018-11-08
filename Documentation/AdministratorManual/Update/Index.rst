.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../_IncludedDirectives.rst

Updating
--------
If you update EXT:events2 to a newer version, please read this section carefully!

Update to Version 2.3.0
^^^^^^^^^^^^^^^^^^^^^^^

With version 2.3.0 we have rewritten the frontend Events2.js completely. Now it it much more readable and
it prevents executing JavaScript, if it is not valid for current view. That's why we have some changes in our
templates:

* Remove all siteId variables. Please use {data.pid} instead
* Remove all jsSearchVariables. They are now all available in just jsVariables
* All jsVariables are removed from Templates and Partials and has to be in all Layout file now
* The <div> for remainingChars has been removed as it will be created dynamically with JS now
* CSS class addRemainingCharsCheck activates max chars feature for textareas automatically
* The <span> for locationStatus has been removed as it will be created dynamically with JS now
* Change CSS class powermail_input to form-control in Event/FormFields Partial
* Move records for selectboxes in search plugin from {data} to {selectorData}
* Make data with current tt_content record available in jsVariables, too.
* We have added two more Layouts. Please adjust the new path in your templates.

We have removed TypoLinkCodecService as it was only needed by our own TypoLink VH and is part of TYPO3
since TYPO3 7.*.

Organizer and Location are not required anymore by default. If you still need them required
go into ExtensionManager and set them as required. This change results into some further changes
to our templates:

* Add if to render section "location" only if a location is available
* Add if to render section "googleRoute" only if a location is available
* Add if to render section "organizer" only if a organizer is available
* Add if to location to prevent rendering footer for each event in list
* Move <p>-Tag, for editing your own records in FE, inside of the if
* Add if to organizer and location in Create.html
* Add if to organizer and location in Update.html

We have moved all email settings in ExtConf to new tab "Email"

EXT maps2 is not a hard-coded dependency to events2 anymore, but we still suggest it in ext_emconf.php.

Update to Version 2.0.0
^^^^^^^^^^^^^^^^^^^^^^^

Version 2.0.0 will come with some new cols and we have removed some cols. So please be careful while comparing
database with TCA definition after upgrading.

.. important::

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
TYPO3 7.6. Please change all e2:link.typolink VHs of your templates into
f:link.typolink.
