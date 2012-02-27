<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$TCA["tx_wecservant_minopp"] = Array (
	"ctrl" => $TCA["tx_wecservant_minopp"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,name,description,ministry_uid,contact_uid,location,times_needed,priority,skills,contact_info,misc_description,qualifications,starttime,endtime"
	),
	"feInterface" => $TCA["tx_wecservant_minopp"]["feInterface"],
	"columns" => Array (
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_wecservant_minopp',
				'foreign_table_where' => 'AND tx_wecservant_minopp.pid=###CURRENT_PID### AND tx_wecservant_minopp.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),		
		'hidden' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		"starttime" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.starttime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"default" => "0",
				"checkbox" => "0"
			)
		),
		"endtime" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:lang/locallang_general.php:LGL.endtime",
			"config" => Array (
				"type" => "input",
				"size" => "8",
				"max" => "20",
				"eval" => "date",
				"checkbox" => "0",
				"default" => "0",
				"range" => Array (
					"upper" => mktime(0,0,0,12,31,2020),
					"lower" => mktime(0,0,0,date("m")-1,date("d"),date("Y"))
				)
			)
		),
		"name" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.name",
			"config" => Array (
				"type" => "input",
				"size" => "32",
				"max" => "48",
				"eval" => "trim",
			)
		),
		"description" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.description",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "3",
				"wizards" => Array(
					"_PADDING" => 2,
					"RTE" => Array(
						"notNewRecords" => 1,
						"RTEonly" => 1,
						"type" => "script",
						"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
						"icon" => "wizard_rte2.gif",
						"script" => "wizard_rte.php",
					),
				),
			)
		),
		"ministry_uid" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.ministry_uid",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "fe_groups",
				"foreign_table_where" => "AND fe_groups.wecgroup_type=1 ORDER BY fe_groups.title",
				"size" => 10,
				"autoSizeMax" => 10,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"contact_uid" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.contact_uid",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "fe_users",
				"foreign_table_where" => "AND fe_users.tx_wecservant_is_contact!= 0 ORDER BY fe_users.name",
				"size" => 10,
				"autoSizeMax" => 10,
				"minitems" => 0,
				"maxitems" => 1,
			)
		),
		"location" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.location",
			"config" => Array (
				"type" => "input",
				"size" => "32",
				"max" => "64",
			)
		),
		"times_needed" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.times_needed",
			"config" => Array (
				"type" => "input",
				"size" => "32",
				"max" => "64",
			)
		),
		"priority" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.priority",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.priority.I.0", "0"),
					Array("LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.priority.I.1", "1"),
					Array("LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.priority.I.2", "2"),
					Array("LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.priority.I.3", "3"),
					Array("LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.priority.I.4", "4"),
					Array("LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.priority.I.5", "5"),
				),
				"size" => 1,
				"maxitems" => 1,
			)
		),
		"skills" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.skills",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "tx_wecservant_skills",
				"foreign_table_where" => " AND tx_wecservant_skills.pid=###CURRENT_PID### ORDER BY tx_wecservant_skills.name",
				"size" => 14,
				"autoSizeMax" => 20,
				"minitems" => 0,
				"maxitems" => 100,
				'MM' => 'tx_wecservant_skills_mm',
			)
		),
		"contact_info" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.contact_info",
			"config" => Array (
				"type" => "text",
				"cols" => "40",
				"rows" => "4",
			)
		),
		"misc_description" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.misc_description",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "3",
				"wizards" => Array(
					"_PADDING" => 2,
					"RTE" => Array(
						"notNewRecords" => 1,
						"RTEonly" => 1,
						"type" => "script",
						"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
						"icon" => "wizard_rte2.gif",
						"script" => "wizard_rte.php",
					),
				),
			)
		),
		"qualifications" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.qualifications",
			"config" => Array (
				"type" => "text",
				"cols" => "30",
				"rows" => "3",
				"wizards" => Array(
					"_PADDING" => 2,
					"RTE" => Array(
						"notNewRecords" => 1,
						"RTEonly" => 1,
						"type" => "script",
						"title" => "Full screen Rich Text Editing|Formatteret redigering i hele vinduet",
						"icon" => "wizard_rte2.gif",
						"script" => "wizard_rte.php",
					),
				),
			)
		),
		't3ver_label' => Array (
			'displayCond' => 'FIELD:t3ver_label:REQ:true',
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.versionLabel',
			'config' => Array (
				'type'=>'none',
				'cols' => 27
			)
		),		
/*
		"grouptype" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp.grouptype",
			"config" => Array (
				"type" => "select",
				"foreign_table" => "tx_wecgroup_type",
				"foreign_table_where" => "ORDER BY tx_wecgroup_type.uid",
				"size" => 1,
				"minitems" => 0,
				"maxitems" => 1,
				"default" => '1',
			)
		),
*/
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;3-3-3, l18n_parent, l18n_diffsource, hidden,name, description;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|link|image]:rte_transform[mode=ts], ministry_uid, contact_uid, location, times_needed, priority, skills, contact_info, misc_description;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|link|image]:rte_transform[mode=ts], qualifications;;;richtext[paste|bold|italic|underline|formatblock|class|left|center|right|orderedlist|unorderedlist|link|image]:rte_transform[mode=ts], starttime;;;;1-1-1, endtime")
	),
	"palettes" => Array (
		"1" => Array("showitem" => ""),
		"3" => Array("showitem" => "t3ver_label,l18n_parent,l18n _diffsource"),	
	)
);



$TCA["tx_wecservant_skills"] = Array (
	"ctrl" => $TCA["tx_wecservant_skills"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "hidden,name,description,group_by,sort_order,required_group"
	),
	"feInterface" => $TCA["tx_wecservant_skills"]["feInterface"],
	"columns" => Array (
		'sys_language_uid' => Array (
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.language',
			'config' => Array (
				'type' => 'select',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => Array(
					Array('LLL:EXT:lang/locallang_general.php:LGL.allLanguages',-1),
					Array('LLL:EXT:lang/locallang_general.php:LGL.default_value',0)
				)
			)
		),
		'l18n_parent' => Array (
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 1,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.l18n_parent',
			'config' => Array (
				'type' => 'select',
				'items' => Array (
					Array('', 0),
				),
				'foreign_table' => 'tx_wecservant_minopp',
				'foreign_table_where' => 'AND tx_wecservant_minopp.pid=###CURRENT_PID### AND tx_wecservant_minopp.sys_language_uid IN (-1,0)',
			)
		),
		'l18n_diffsource' => Array (
			'config' => Array (
				'type' => 'passthrough'
			)
		),		
		'hidden' => Array (
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.php:LGL.hidden',
			'config' => Array (
				'type' => 'check',
				'default' => '0'
			)
		),
		"name" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_skills.name",
			"config" => Array (
				"type" => "input",
				"size" => "30",
			)
		),
		"description" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_skills.description",
			"config" => Array (
				"type" => "text",
				"cols" => "35",
				"rows" => "4",
			)
		),
		"group_by" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_skills.group_by",
			"config" => Array (
				"type" => "input",
				"size" => "30",
			)
		),
		"sort_order" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_skills.sort_order",
			"config" => Array (
				"type" => "input",
				"size" => "5",
			)
		),
		"required_group" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_skills.required_group",
			"config" => Array (
				"type" => "input",
				"size" => "5",
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "sys_language_uid;;;;3-3-3, l18n_parent, l18n_diffsource,hidden, name;;;;1-1-1, description, group_by, sort_order,required_group")
	),
	"palettes" => Array (
		"1" => Array("showitem" => ""),
		"3" => Array("showitem" => "l18n_parent,l18n _diffsource"),	
	)
);

$TCA["tx_wecgroup_type"] = Array (
	"ctrl" => $TCA["tx_wecgroup_type"]["ctrl"],
	"interface" => Array (
		"showRecordFieldList" => "name,description"
	),
	"feInterface" => $TCA["tx_wecgroup_type"]["feInterface"],
	"columns" => Array (
		"name" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecgroup_type.name",
			"config" => Array (
				"type" => "input",
				"size" => "16",
				"max" => "48",
				"eval" => "trim",
			)
		),
		"description" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecgroup_type.description",
			"config" => Array (
				"type" => "text",
				"cols" => "32",
				"rows" => "4",
			)
		),
	),
	"types" => Array (
		"0" => Array("showitem" => "name;;;;1-1-1, description")
	),
	"palettes" => Array (
		"1" => Array("showitem" => "")
	)
);
?>