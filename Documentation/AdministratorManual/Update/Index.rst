.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt

Updating
--------

If you update EXT:events2 to a newer version, please read this section carefully!

Update to Version 4.1.1
^^^^^^^^^^^^^^^^^^^^^^^

With version 4.1.0 we have added the path_segment for slugs in event records, but first with version 4.1.1 we
have added the Updater for this field.
Please visit InstallTool and execute the updater for Slugs in event records.

Update to Version 4.0.0
^^^^^^^^^^^^^^^^^^^^^^^

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
^^^^^^^^^^^^^^^^^^^^^^^

New Feature:
We have added 3 new getters to Time object:
-> getTimeEntryAsDateTime
-> getTimeBeginAsDateTime
-> getTimeEndAsDateTime

These are very helpful as you now can format them with f:format.date() VH

Update to Version 3.3.1
^^^^^^^^^^^^^^^^^^^^^^^

With version 3.3.1 we have added a new column ``same_day_time`` to day table. It helps us to GROUP BY days with
multiple time records for one day, if mergeEventsAtSameDate is set in plugin.

Please update DB in Installtool and start scheduler task to re-generate all day records.

Update to Version 3.2.0
^^^^^^^^^^^^^^^^^^^^^^^

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
^^^^^^^^^^^^^^^^^^^^^^^

With version 3.1.0 we have made a little but breaking change:

In earlier versions you may have done:

<f:form.textarea class="form-control addRemainingCharsCheck" id="teaser" property="teaser" rows="5" cols="50" />

This will not work anymore, as it would be very hard for integrator to place the container for remaining chars
correctly. Please change this part to:

Code: ::

 <div class="form-group row">
   <div class="col-sm-4"></div>
   <div class="col-sm-8">
     <span class="remainingChars" data-id="teaser"></span>
   </div>
 </div>


Use data-id to relate it to a textarea id-attribute.

Update to Version 3.0.0
^^^^^^^^^^^^^^^^^^^^^^^

* We have removed GetEventDates VH and implemented a completely new way to get future event dates.
* We have removed MicroStart VH
* We have removed MicroStop VH
* We have removed Sort VH, as sorting in VH is always a bad idea.

In previous versions we had the problem, that an added day as exception will never be of type Day. It is of
type Exception with different properties then a Day, which made us many headaches in Fluid-Templates. In
GetDateTime VH we had streamlined Day and Exception records into an array which contains all needed
information. You can't control and you may not understand how this array was build. With version 2.4.0 we have
solved this problem and simplified Fluid template a lot. Instead of modifying the DB tables and models we have
reduced some method-calls down to the DateTime representation of Day and Exception records:

* Renamed EventService::getSortedTimesForDay to EventService::getSortedTimesForDate
  * Property $day changed from type Day to \DateTime
* Renamed EventService::getDifferentTimesForDay to EventService::getDifferentTimesForDate
  * Property $day changed from type Day to \DateTime
* Renamed EventService::getTimesForDay to EventService::getTimesForDate
  * Property $day changed from type Day to \DateTime
* Renamed EventService::getExceptionsForDay to EventService::getExceptionsForDate
  * Property $day changed from type Day to \DateTime

So, please check your own extensions, if you make use of these methods. To solve the problem from above, we have
implemented two new ViewHelpers. Please have a look into our new templates and update your own templates
to the new structure:

* New VH: GetExceptionsFromEventForSpecificDate
* New VH: IsDateMarkedAsCanceled

Event property `download_links` can now collect more then one link. Please update DB in Installtool and clear
system caches.

Now you can create events with a recurring of one or more months.

We have removed all methods to get time records for an event from DayRelationService as we have all these
methods in EventService already.

Update to Version 2.4.0
^^^^^^^^^^^^^^^^^^^^^^^

Version 2.4.0 is now TYPO3 9 compatible. We also have removed compatibility to TYPO3 6 and 7.

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
TYPO3 7.6. Please change all e2:link.typolink VHs of your templates into f:link.typolink.
