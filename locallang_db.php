<?php
/**
 * Language labels for database tables/fields belonging to extension "wec_servant"
 *
 * This file is detected by the translation tool.
 */

$LOCAL_LANG = Array (
	"default" => Array (
		'tt_content.list_type_pi1' => 'WEC Servant Matcher',
		'fe_users.tx_wecservant_is_contact' => 'Is Ministry Contact',

		'tx_wecservant_minopp' => 'Ministry Opportunity',
		'tx_wecservant_minopp.name' => 'Name Of Ministry Opportunity',
		'tx_wecservant_minopp.description' => 'Description',
		'tx_wecservant_minopp.ministry_uid' => 'Ministry',
		'tx_wecservant_minopp.contact_uid' => 'Contact Person',
		'tx_wecservant_minopp.contact_email_val' => 'Contact Email',
		'tx_wecservant_minopp.ministry_info' => 'Ministry',
		'tx_wecservant_minopp.location' => 'Location (building/place/room/etc)',
		'tx_wecservant_minopp.times_needed' => 'Days/Times Needed',
		'tx_wecservant_minopp.priority.I.0' => 'None',
		'tx_wecservant_minopp.priority.I.1' => 'Normal',
		'tx_wecservant_minopp.priority.I.2' => 'Urgent',
		'tx_wecservant_minopp.priority.I.3' => 'High',
		'tx_wecservant_minopp.priority.I.4' => 'Low',
		'tx_wecservant_minopp.priority.I.5' => 'Special',
		'tx_wecservant_minopp.priority' => 'Priority Of Need',
		'tx_wecservant_minopp.skills' => 'Skills/Interests Needed',
		'tx_wecservant_minopp.contact_info' => 'Contact Information (if needed)',
		'tx_wecservant_minopp.misc_description' => 'Misc. Description',
		'tx_wecservant_minopp.qualifications' => 'Qualifications',
		'tx_wecservant_minopp.grouptype' => 'Group Type',

		'tx_wecservant_skills' => 'Ministry Skills',
		'tx_wecservant_skills.name' 		=> 'Skill/Interest name',
		'tx_wecservant_skills.description' 	=> 'Any additional description',
		'tx_wecservant_skills.group_by' 	=> 'Group this by (optional)',
		'tx_wecservant_skills.sort_order' 	=> 'Sort order (1 = lowest)',
		'tx_wecservant_skills.required_group' => 'Required Group (0=no,1=yes,2=default)?',

		'wec_servant.pi_flexform.sheet_main' 	=> 'Main',
		'wec_servant.pi_flexform.title' 			=> 'Title of Servant Matcher:',
		'wec_servant.pi_flexform.canFindByMinistry'	=> 'Allow To "Find By Ministry" (dropdown)',
		'wec_servant.pi_flexform.canFindByMinistries'	=> 'Allow To "Find By Ministries" (checkbox)',
		'wec_servant.pi_flexform.canFindByPriority'	=> 'Allow To "Find By Priority/Needs"',
		'wec_servant.pi_flexform.canFindByLastAdded'=> 'Allow To "Find By Last Added"',
		'wec_servant.pi_flexform.canFindBySkills'	=> 'Allow To "Find By Skills"',
		'wec_servant.pi_flexform.template_file' 	=> 'Template file',

		'wec_servant.pi_flexform.sheet_options' => 'Options',
		'wec_servant.pi_flexform.how_can_respond' => 'How Can Respond?',
			'wec_servant.pi_flexform.how_can_respond.choice1' 	=> 'No Form',
			'wec_servant.pi_flexform.how_can_respond.choice2' 	=> 'Interest Form Only',
			'wec_servant.pi_flexform.how_can_respond.choice3' 	=> 'Commitment Form Only',
			'wec_servant.pi_flexform.how_can_respond.choice4' 	=> 'Interest + Commitment',
		'wec_servant.pi_flexform.see_all_ministries' => 'Show Ministry List At First (otherwise hide)?',
		'wec_servant.pi_flexform.see_all_skills' => 'Show Skills List At First (otherwise hide)?',
		'wec_servant.pi_flexform.show_all_entry' => 'Show All Of Description?',
		'wec_servant.pi_flexform.require_login_to_signup' => 'Require Login For Signup/Commitment?',
		'wec_servant.pi_flexform.can_save_for_later' => 'Can Save For Later?',

		'wec_servant.pi_flexform.sheet_fields' => 'Fields',
		'wec_servant.pi_flexform.display_oppfields'   => 'Fields to DISPLAY for showing opportunities',
		'wec_servant.pi_flexform.required_formfields' => 'Fields REQUIRED for signup form',
		'wec_servant.pi_flexform.display_formfields'  => 'Fields to DISPLAY for signup form',

		'wec_servant.pi_flexform.sheet_group' 	=> 'Administrator',
		'wec_servant.pi_flexform.administrator_group' => 'Administrator(s) UID or username (sep. by comma)',
		'wec_servant.pi_flexform.contact_name' 		=> 'Contact name for outgoing email',
		'wec_servant.pi_flexform.contact_email' 	=> 'Contact email (i.e., admin@web.com) for outgoing email',
		'wec_servant.pi_flexform.notify_email' 		=> 'Notify email to receive ALL contacts',
		'wec_servant.pi_flexform.grouptype_name' 	=> 'Group type name',

		'wec_servant.pi_flexform.sheet_preview' => 'Preview',
		'wec_servant.pi_flexform.is_preview' 		=> 'Is This A Preview Only?',
		'wec_servant.pi_flexform.preview_title' 	=> 'Title Of Preview:',
		'wec_servant.pi_flexform.preview_button' 	=> 'Preview Button Name:',
		'wec_servant.pi_flexform.preview_howmany' 	=> 'How Many To Preview (0 = just a link)?',
		'wec_servant.pi_flexform.preview_description_length' => 'Length for Description Field?',
		'wec_servant.pi_flexform.preview_ministry' 	=> 'Which Ministry To Preview?',
			'wec_servant.pi_flexform.no_ministry'			=> 'No Ministry',
		'wec_servant.pi_flexform.previewdata_PID'	=> 'Choose Servant Matcher Data Folder',
		'wec_servant.pi_flexform.previewlink_PID'	=> 'Choose Servant Matcher Page To Link To',

		'wec_servant.pi_flexform.sheet_text' => 'Text',
		'wec_servant.pi_flexform.header_text' 		=> 'Header Text On Main Screen (HTML ok)',
		'wec_servant.pi_flexform.signup_instructions' => 'Contact Form: Header/Instructions (optional)',
		'wec_servant.pi_flexform.signup_emailHeader' => 'Contact Email: Header text (optional)',
		'wec_servant.pi_flexform.signup_emailFooter' => 'Contact Email: Footer text (optional)',

		'fe_groups.tx_wecgroup_type' => 'Type of Group',
		'tx_wecgroup_type' => 'Group Type',
		'tx_wecgroup_type.name' => 'Name of Group Type',
		'tx_wecgroup_type.description' => 'Description of Group Type',
	),
);
?>