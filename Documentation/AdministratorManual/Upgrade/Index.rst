..  include:: /Includes.rst.txt


..  _upgrade:

=======
Upgrade
=======

If you upgrade/update EXT:events2 to a newer version, please read this section carefully!


Update to Version 9.0.0
=======================

This version is NOT compatible with TYPO3 10 anymore.

TCA option `cruser_id` has been removed with TYPO3 12. As we have also removed
that field from all EXT:events2 tables, you will not see who has created
a record on TYPO3 11 instances. PLease use events2 8.* if you still need
that information.

Update to Version 8.0.0
=======================

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
