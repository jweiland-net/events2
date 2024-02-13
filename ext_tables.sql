#
# Table structure for table 'tx_events2_domain_model_event'
#
CREATE TABLE tx_events2_domain_model_event (
  event_type varchar(255) DEFAULT '' NOT NULL,
  top_of_list tinyint(1) unsigned DEFAULT '0' NOT NULL,
  title varchar(255) DEFAULT '' NOT NULL,
  path_segment varchar(2048) DEFAULT '' NOT NULL,
  teaser varchar(255) DEFAULT '' NOT NULL,
  event_begin int(11) DEFAULT '0' NOT NULL,
  event_end int(11) DEFAULT '0' NOT NULL,
  event_time int(11) unsigned DEFAULT '0',
  same_day tinyint(1) unsigned DEFAULT '0' NOT NULL,
  multiple_times int(11) unsigned DEFAULT '0' NOT NULL,
  xth int(11) DEFAULT '0' NOT NULL,
  weekday int(11) DEFAULT '0' NOT NULL,
  different_times int(11) unsigned DEFAULT '0' NOT NULL,
  each_weeks int(11) DEFAULT '0' NOT NULL,
  each_months int(11) DEFAULT '0' NOT NULL,
  recurring_end int(11) DEFAULT '0' NOT NULL,
  exceptions int(11) DEFAULT '0' NOT NULL,
  detail_information text,
  free_entry tinyint(1) unsigned DEFAULT '0' NOT NULL,
  ticket_link varchar(11) DEFAULT '' NOT NULL,
  days int(11) unsigned DEFAULT '0' NOT NULL,
  location int(11) unsigned DEFAULT '0',
  organizers int(11) unsigned DEFAULT '0',
  images int(11) unsigned DEFAULT '0',
  video_link varchar(11) DEFAULT '' NOT NULL,
  download_links varchar(255) DEFAULT '' NOT NULL,
  import_id varchar(255) DEFAULT '' NOT NULL,

  KEY path_segment (path_segment(185), uid)
);

#
# Table structure for table 'tx_events2_domain_model_day'
#
CREATE TABLE tx_events2_domain_model_day (
  day int(11) unsigned DEFAULT '0' NOT NULL,
  day_time int(11) unsigned DEFAULT '0' NOT NULL,
  sort_day_time int(11) unsigned DEFAULT '0' NOT NULL,
  same_day_time int(11) unsigned DEFAULT '0' NOT NULL,
  is_removed_date tinyint(1) unsigned DEFAULT '0' NOT NULL,
  event int(11) unsigned DEFAULT '0' NOT NULL,

  KEY keyForDay (day),
  KEY booster (pid,event,hidden,day,sort_day_time,day_time,tstamp,crdate,uid)
);

#
# Table structure for table 'tx_events2_domain_model_time'
#
CREATE TABLE tx_events2_domain_model_time (
  type varchar(50) DEFAULT '' NOT NULL,
  weekday varchar(10) DEFAULT '' NOT NULL,
  time_begin varchar(5) DEFAULT '' NOT NULL,
  time_entry varchar(5) DEFAULT '' NOT NULL,
  duration varchar(5) DEFAULT '' NOT NULL,
  time_end varchar(5) DEFAULT '' NOT NULL,
  event int(11) unsigned DEFAULT '0' NOT NULL,
  exception int(11) unsigned DEFAULT '0' NOT NULL,

  KEY eventType (event,type)
);

#
# Table structure for table 'tx_events2_domain_model_exception'
#
CREATE TABLE tx_events2_domain_model_exception (
  exception_type varchar(255) DEFAULT '' NOT NULL,
  exception_date int(11) DEFAULT '0' NOT NULL,
  exception_time int(11) unsigned DEFAULT '0',
  exception_details text,
  show_anyway tinyint(1) unsigned DEFAULT '0' NOT NULL,
  event int(11) unsigned DEFAULT '0' NOT NULL,

  KEY events (event)
);

#
# Table structure for table 'tx_events2_domain_model_location'
#
CREATE TABLE tx_events2_domain_model_location (
  location varchar(255) DEFAULT '' NOT NULL,
  street varchar(255) DEFAULT '' NOT NULL,
  house_number varchar(10) DEFAULT '' NOT NULL,
  zip varchar(10) DEFAULT '' NOT NULL,
  city varchar(255) DEFAULT '' NOT NULL,
  country int(11) unsigned DEFAULT '0',
  link int(11) unsigned DEFAULT '0'
);

#
# Table structure for table 'tx_events2_domain_model_organizer'
#
CREATE TABLE tx_events2_domain_model_organizer (
  organizer varchar(255) DEFAULT '' NOT NULL,
  hide_in_filter tinyint(1) DEFAULT '0' NOT NULL,
  link int(11) unsigned DEFAULT '0'
);

#
# Table structure for table 'tx_events2_domain_model_link'
#
CREATE TABLE tx_events2_domain_model_link (
  title varchar(255) DEFAULT '' NOT NULL,
  link varchar(255) DEFAULT '' NOT NULL
);

#
# Table structure for table 'tx_events2_domain_model_holiday'
#
CREATE TABLE tx_events2_domain_model_holiday (
  title varchar(255) DEFAULT '' NOT NULL,
  day int(2) unsigned DEFAULT '0' NOT NULL,
  month int(2) unsigned DEFAULT '0' NOT NULL
);

#
# Table structure for table 'tx_events2_event_organizer_mm'
#
CREATE TABLE tx_events2_event_organizer_mm (
  uid_local int(11) unsigned DEFAULT '0' NOT NULL,
  uid_foreign int(11) unsigned DEFAULT '0' NOT NULL,
  sorting int(11) unsigned DEFAULT '0' NOT NULL,
  sorting_foreign int(11) unsigned DEFAULT '0' NOT NULL,

  KEY uid_local (uid_local),
  KEY uid_foreign (uid_foreign)
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
  tx_events2_organizer int(11) unsigned DEFAULT '0' NOT NULL
);
