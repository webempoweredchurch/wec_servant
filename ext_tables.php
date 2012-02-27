<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

$tempColumns = Array (
    "tx_wecservant_is_contact" => Array (
        "exclude" => 1,
        "label" => "LLL:EXT:wec_servant/locallang_db.php:fe_users.tx_wecservant_is_contact",
        "config" => Array (
            "type" => "check",
        )
    ),
);
t3lib_div::loadTCA("fe_users");
t3lib_extMgm::addTCAcolumns("fe_users",$tempColumns,1);
t3lib_extMgm::addToAllTCAtypes("fe_users","tx_wecservant_is_contact");


t3lib_extMgm::allowTableOnStandardPages("tx_wecservant_minopp");
t3lib_extMgm::addToInsertRecords("tx_wecservant_minopp");

$TCA["tx_wecservant_minopp"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_minopp",
		"label" => "name",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"languageField" => "sys_language_uid",
		"transOrigPointerField" => "l18n_parent",
		"transOrigDiffSourceField" => "l18n_diffsource",		
		"default_sortby" => "ORDER BY name",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden",
			"starttime" => "starttime",
			"endtime" => "endtime",
		),
		"versioningWS" => TRUE,
		'versioning_followPages' => TRUE,		
		"origUid" => "t3_origuid",		
		"shadowColumnsForNewPlaceholders" => "sys_language_uid,l18n_parent",		
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."res/icon_tx_wecservant_minopp.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "starttime, endtime, disabled, name, description, ministry_uid, contact_uid, location, times_needed, priority, skills, contact_info, misc_description, qualifications, sys_language_uid, l18n_parent,  l18n_diffsource",
	)
);

t3lib_extMgm::allowTableOnStandardPages("tx_wecservant_skills");
t3lib_extMgm::addToInsertRecords("tx_wecservant_skills");

$TCA["tx_wecservant_skills"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecservant_skills",
		"label" => "name",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"languageField" => "sys_language_uid",
		"transOrigPointerField" => "l18n_parent",
		"transOrigDiffSourceField" => "l18n_diffsource",		
		"default_sortby" => "ORDER BY name",
		"delete" => "deleted",
		"enablecolumns" => Array (
			"disabled" => "hidden",
		),
		"versioningWS" => TRUE,
		'versioning_followPages' => TRUE,		
		"origUid" => "t3_origuid",		
		"shadowColumnsForNewPlaceholders" => "sys_language_uid,l18n_parent",		
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."res/icon_tx_wecservant_skills.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "disabled, name, description,group_by,sort_order,required_group, sys_language_uid, l18n_parent,  l18n_diffsource",
	)
);

$tempColumns2 = Array (
	"wecgroup_type" => Array (
		"label" => "LLL:EXT:wec_servant/locallang_db.php:fe_groups.tx_wecgroup_type",
		"config" => Array (
			"type" => "select",
			"items" => Array (
				Array('',0),
			),
			"foreign_table" => "tx_wecgroup_type",
			"foreign_table_where" => "ORDER BY tx_wecgroup_type.uid",
			"size" => 1,
			"minitems" => 0,
			"maxitems" => 1,
		)
	),
);
t3lib_div::loadTCA("fe_groups");
t3lib_extMgm::addTCAcolumns("fe_groups",$tempColumns2,1);
t3lib_extMgm::addToAllTCAtypes("fe_groups","wecgroup_type");


//t3lib_extMgm::allowTableOnStandardPages("tx_wecgroup_type");
$TCA["tx_wecgroup_type"] = Array (
	"ctrl" => Array (
		"title" => "LLL:EXT:wec_servant/locallang_db.php:tx_wecgroup_type",
		"label" => "name",
		"tstamp" => "tstamp",
		"crdate" => "crdate",
		"cruser_id" => "cruser_id",
		"sortby" => "sorting",
		"rootLevel" => 1, // is on root of site (needs to be because above needs to know where it is)
		"dynamicConfigFile" => t3lib_extMgm::extPath($_EXTKEY)."tca.php",
		"iconfile" => t3lib_extMgm::extRelPath($_EXTKEY)."res/icon_tx_wecservant_type.gif",
	),
	"feInterface" => Array (
		"fe_admin_fieldList" => "name, description",
	)
);

t3lib_div::loadTCA('tt_content');
$TCA['tt_content']['types']['list']['subtypes_excludelist'][$_EXTKEY.'_pi1'] = 'layout,select_key,pages,recursive';

t3lib_extMgm::addPlugin(Array('LLL:EXT:wec_servant/locallang_db.php:tt_content.list_type_pi1', $_EXTKEY.'_pi1'),'list_type');

$TCA["tt_content"]["types"]["list"]["subtypes_addlist"][$_EXTKEY."_pi1"]="pi_flexform";
t3lib_extMgm::addPiFlexFormValue($_EXTKEY."_pi1", "FILE:EXT:wec_servant/flexform_ds.xml");

t3lib_extMgm::addStaticFile($_EXTKEY,'static/ts/','WEC Servant Matcher (old) template');
t3lib_extMgm::addStaticFile($_EXTKEY,'static/tsnew/','WEC Servant Matcher template');

if (TYPO3_MODE=="BE")    $TBE_MODULES_EXT["xMOD_db_new_content_el"]["addElClasses"]["tx_wecservant_pi1_wizicon"] = t3lib_extMgm::extPath($_EXTKEY).'pi1/class.tx_wecservant_pi1_wizicon.php';

?>