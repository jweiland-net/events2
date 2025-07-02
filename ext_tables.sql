#
# Table structure for table 'tx_events2_domain_model_event'
#
CREATE TABLE tx_events2_domain_model_event
(
	import_id varchar(255) DEFAULT '' NOT NULL,

	KEY       path_segment (path_segment(185), uid)
);

#
# Table structure for table 'tx_events2_domain_model_day'
#
CREATE TABLE tx_events2_domain_model_day
(
	is_removed_date    tinyint(1) unsigned DEFAULT '0' NOT NULL,
	def_lang_event_uid int(11) unsigned DEFAULT '0' NOT NULL,

	KEY                keyForDay (day),
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
