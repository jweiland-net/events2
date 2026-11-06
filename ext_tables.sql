#
# Table structure for table 'tx_events2_domain_model_event'
#
CREATE TABLE tx_events2_domain_model_event
(
	import_id varchar(255) DEFAULT '' NOT NULL,

	KEY language_identifier (l10n_parent, sys_language_uid, t3ver_wsid, deleted)
);

#
# Table structure for table 'tx_events2_domain_model_location'
#
CREATE TABLE tx_events2_domain_model_location
(
	# ISO 3166-1 alpha-2 code. 3 chars, as the upgrade wizard has to read
	# the former UIDs of static_countries (up to 3 digits) after the schema update.
	# TODO: Change to varchar(2) with next major version, when all installations
	# have run the upgrade wizard events2_migrateLocationCountry
	country varchar(3) DEFAULT '' NOT NULL
);

#
# Table structure for table 'tx_events2_domain_model_day'
#
CREATE TABLE tx_events2_domain_model_day
(
	is_removed_date    tinyint(1) unsigned DEFAULT '0' NOT NULL,
	def_lang_event_uid int(11) unsigned DEFAULT '0' NOT NULL,

	KEY                keyForDay (day),
	KEY                keyForEvent (event),
	KEY                keyForDefLang (def_lang_event_uid),
	KEY                booster (event,pid,hidden,day,sort_day_time,day_time,tstamp,crdate,uid)
);

#
# Table structure for table 'tx_events2_domain_model_time'
#
CREATE TABLE tx_events2_domain_model_time
(
	type varchar(50) DEFAULT '' NOT NULL,

	KEY  eventType (event,type),
	KEY  exceptionType (exception,type)
);
