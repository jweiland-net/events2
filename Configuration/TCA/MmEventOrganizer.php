<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_events2_event_organizer_mm'] = array(
	'ctrl' => $TCA['tx_events2_event_organizer_mm']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden, event, organizer',
	),
	'types' => array(
		'1' => array('showitem' => 'hidden, event, organizer'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
	'columns' => array(
		'hidden' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
			'config' => array(
				'type' => 'check',
			),
		),
		'event' => array(
			'label' => 'Event',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_events2_domain_model_event',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
		'organizer' => array(
			'label' => 'Organizer',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'tx_events2_domain_model_organizer',
				'foreign_table_where' => 'AND 1=1 ORDER BY organizer ASC',
				'size' => 1,
				'minitems' => 1,
				'maxitems' => 1,
			),
		),
		'event_sort' => array(
			'config' => array(
				'type' => 'passthrough',
			),
		),
		'location_sort' => array(
			'config' => array(
				'type' => 'passthrough',
			),
		),
	),
);