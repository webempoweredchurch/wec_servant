<?php
/***********************************************************************
* Copyright notice
*
* (c) 2005-2010 Christian Technology Ministries International Inc.
* All rights reserved
*
* This file is part of the Web-Empowered Church (WEC)
* (http://WebEmpoweredChurch.org) ministry of Christian Technology Ministries
* International (http://CTMIinc.org). The WEC is developing TYPO3-based
* (http://typo3.org) free software for churches around the world. Our desire
* is to use the Internet to help offer new life through Jesus Christ. Please
* see http://WebEmpoweredChurch.org/Jesus.
*
* You can redistribute this file and/or modify it under the terms of the
* GNU General Public License as published by the Free Software Foundation;
* either version 2 of the License, or (at your option) any later version.
*
* The GNU General Public License can be found at
* http://www.gnu.org/copyleft/gpl.html.
*
* This file is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* This copyright notice MUST APPEAR in all copies of the file!
*************************************************************************/
require_once(PATH_tslib.'class.tslib_pibase.php');

/**
 * Plugin 'WEC Servant Matcher' for the 'wec_servant' extension.
 *
 * @author	Web-Empowered Church Team <devteam(at)webempoweredchurch.org>
 *
 * "...whoever wants to become great among you must be your servant, and whoever
 * wants to be first must be your slave— just as the Son of Man did not come to
 * be served, but to serve, and to give his life as a ransom for many."
 * -- Matthew 20:26b-28
 *
 * The WEC Servant Matcher is a great way to connect people to service
 * opportunities. Specifically,this extension was created for churches or ministries
 * to connect members and attenders to ministry service opportunities. We name anyone
 * in a church who wants to help – servants – and that is where we get the “Servant
 * Matcher” name. Some churches or ministries may call them volunteers or unpaid
 * servants or helpers or  “people to help”. Whatever their title, if you want to
 * connect people who want to help, then this extension will help you do that.
 *
 * This extension is very flexible and allows you to show and connect servants in
 * various ways:
 *  1) List by Ministry – show users all opportunities under a given ministry.
 *  2) Select by Skills -- allow a user to select their skills / spiritual gifts /
 *     passion / talents and then it matches them with ministry opportunities that fit those.
 *  3) List by Ministry Needs – let the user pick from ministry opportunities based
 *     on a given need/priority (i.e., “urgent”, “special”, “high priority”).
 *  4) List by Last Added – allow user to find the ones that were last added so they
 *     can see “new” opportunities.
 * You can actually mix and match opportunities in any combination you want, depending on
 * the size of your church and the number of opportunities available. If you have a larger
 * number of opportunities (i.e., more than 25), you may want to assign skills/gifts to them
 * so people can match opportunities that fit their “call” and passions.
 *
 * Since ministry opportunities can be listed by ministry group, this extension will allow
 * every ministry page that would like to have a “Look For Opportunities”/”How Can I Serve?”
 * to then show all the opportunities to serve in that given ministry. So, your Music/Worship
 * ministry can show all Ministry opportunities associated with it.
 *
 * While in this document, we use the terms servant, ministry, and ministry opportunity, a
 * broader definition might be:
 *	servant: person who wants to help
 *	ministry: category
 *	ministry opportunity: a given opportunity or task that needs someone to help
 */
class tx_wecservant_pi1 extends tslib_pibase {
	var $prefixId = 'tx_wecservant_pi1';		// Same as class name
	var $scriptRelPath = 'pi1/class.tx_wecservant_pi1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'wec_servant';	// The extension key.
	var $id;

	var $servantContentTable	= 'tx_wecservant_minopp';
	var $servantSkillsTable		= 'tx_wecservant_skills';
	var $servantGroupTable 		= 'fe_groups';
	var $servantContactTable	= 'fe_users';
	var $servantSkillsMMTable 	= 'tx_wecservant_skills_mm';
	var $groupTypeTable 		= 'tx_wecgroup_type';

	var $pid_list;		// Page ID for data
	var $cObj; 			// The backReference to the mother cObj object set at call time
	var $userID;		// current UID of user logged in (0 = no user logged in)
	var $userName;		// user account name for logged in user
	var $lastLoginDate;	// when last logged in
	var $templateCode; 	// template code
	var $responseMsg;	// any response message put at top
	var $isAdministrator;// if is an administrator
	var $adminMinistries;  // if can only administrate certain ministries...
	var $isServantContact; // if registered as a servant contact (in fe_user)

	var $db_fields;		// database fields
	var $db_showFields;	// database fields to show
	var $db_showFormFields;	// database fields to show in form

	var $ministryList;	// list of all ministries
	var $skillsList;	// list of all skills
	var $contactList;	// list of all contacts

	var $curMinistryUID;// passed in (POST) ministry UID. If =-1 then show all
	var $curPriority;	// passed in (POST) need/priority level
	var $lastAdded;		// passed in (POST) last added
	var $skillsSearch;	// passed in (POST) array of skills to search for
	var $skillsSearchBy;// how to search for skills (ANY=0, 1=ALL)

	var $maxSkillsPerItem; // max # of skills per item
	var $showInterestForm; 	// if should show interest form
	var $showCommitmentForm;// if should show commitment form
	var $savedMinOpps;	// array of all minOpps interested in that are saved by user
	var $wecgroup_type;	// saved wecgroup_type that get from servantGroupTable+tx_wecgroup_type

	var $versioningEnabled = false; // is the extension 'version' loaded

	/**
	 * Initialize the Plugin
	 *
	 * @param	array		$conf: The PlugIn configuration
	 * @return	void
	 */
	function init($conf) {
		if (!$this->cObj) $this->cObj = t3lib_div::makeInstance('tslib_cObj');

		// ------------------------------------------------------------
		// Initialize vars, structures, arrays, etc.
		// ------------------------------------------------------------
		$this->conf = $conf;				// TypoScript configuration
		$this->pi_setPiVarDefaults();		// GetPut-parameter configuration
		$this->pi_initPIflexForm();			// Initialize the FlexForms array
		$this->pi_loadLL();					// localized language variables

//		$GLOBALS['TSFE']->set_no_cache();
		$this->pi_USER_INT_obj = 1; 		// configure so caching not expected

		$this->isAdministrator = 0;
		$this->isServantContact = 0;
		$this->id = $GLOBALS['TSFE']->id;	// current page id

		$this->maxSkillsPerItem	= 10;

		if (t3lib_extMgm::isLoaded('version')) {
			$this->versioningEnabled = true;
		}
		$this->config['storagePID'] 		= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'storagePID', 'sDEF');
		if ($this->config['storagePID']) 	// can specify in flexform
			$this->pid_list = $this->config['storagePID'];
		else if ($this->conf['pid_list'])	// or specify in TypoScript
			$this->pid_list = $this->conf['pid_list'];
		else
			$this->pid_list = $GLOBALS['TSFE']->id; 	// the default is the current page

		// these are the fields to retrieve, show and use in form
		$this->db_fields 		 = array('uid','name','description','ministry_uid','contact_uid','contact_info','location','times_needed','priority','skills','misc_description','qualifications');
		$this->db_minFields 	 = array('uid','name','description');
		$this->db_contactFields  = array('uid','name','email','telephone','address','city','zone','zip');
		$this->db_showFields 	 = array('uid','name','description','ministry_info','contact_info','contact_phone','contact_email','location','times_needed','priority','skills','misc_description','qualifications');
		$this->db_showFormFields = array('name','email','phone','address','city','state','zip','message','attachment','chosen_minopps');

		// ----------------------------------------------------------------------------------------
		//	Set default USER Info
		// ----------------------------------------------------------------------------------------
		if ($GLOBALS['TSFE']->loginUser) {
			$this->userID = $GLOBALS['TSFE']->fe_user->user['uid'];
			$this->userName = $GLOBALS['TSFE']->fe_user->user['username'];
			$this->userFirstName = $GLOBALS['TSFE']->fe_user->user['first_name'];
			if (!strlen($this->userFirstName)) $this->userFirstName = $this->userName;
			$lastName = $GLOBALS['TSFE']->fe_user->user['last_name'];
		}
		else {	// no user logged in...so clear out vars
			$this->userID = 0;
			$this->userName = "";
			$this->userFirstname = "";
		}

		// setup template file
		$templateflex_file = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'template_file', 'sDEF');
		$this->templateCode = $this->cObj->fileResource($templateflex_file  ? "uploads/tx_wecservant/".$templateflex_file : $this->conf['templateFile']);

		// ------------------------------------------------------------
		// Load in all flexform values
		// ------------------------------------------------------------
		// MAIN
		$this->config['title']				= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'title', 				'sDEF');
		$this->config['canFindByMinistry']	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'canFindByMinistry', 	'sDEF');
		$this->config['canFindByMinistries']= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'canFindByMinistries', 	'sDEF');
		$this->config['canFindByPriority']	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'canFindByPriority', 	'sDEF');
		$this->config['canFindByLastAdded']	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'canFindByLastAdded', 	'sDEF');
		$this->config['canFindBySkills']	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'canFindBySkills', 	'sDEF');
		$this->config['templateFile']		= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'templateFile', 		'sDEF');

		// OPTIONS
		$this->config['how_can_respond']	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'how_can_respond', 	's_options');
		$this->config['see_all_ministries']	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'see_all_ministries', 	's_options');
		$this->config['see_all_skills']		= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'see_all_skills', 		's_options');
		$this->config['show_all_entry']		= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'show_all_entry', 		's_options');
		$this->config['can_save_for_later']	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'can_save_for_later', 	's_options');
		$this->config['require_login_to_signup']	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'require_login_to_signup', 	's_options');

		// FIELDS
		$this->config['display_oppfields'] 	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'display_oppfields', 	's_fields');
		$this->config['required_formfields']= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'required_formfields', 's_fields');
		$this->config['display_formfields']	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'display_formfields', 	's_fields');

		// assign global vars
		$this->showInterestForm = ($this->config['how_can_respond'] == 'interest') || ($this->config['how_can_respond'] == 'both');
		$this->showCommitmentForm = ($this->config['how_can_respond'] == 'commitment') || ($this->config['how_can_respond'] == 'both');

		// make these config an array if it is a comma-separated list
		if (!empty($this->config['display_oppfields']))		$this->config['display_oppfields'] = explode(',', $this->config['display_oppfields']);
		if (!empty($this->config['required_formfields']))	$this->config['required_formfields'] = explode(',', $this->config['required_formfields']);
		if (!empty($this->config['display_formfields']))	$this->config['display_formfields'] = explode(',', $this->config['display_formfields']);

		// ADMIN
		$this->config['administrator_group'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'administrator_group', 's_administrator');
		$this->config['contact_name'] 		= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'contact_name', 's_administrator');
		$this->config['contact_email'] 		= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'contact_email', 's_administrator');
		$this->config['notify_email'] 		= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'notify_email', 's_administrator');
//		$this->config['grouptype_id'] 		= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'grouptype_name', 's_administrator');

		// PREVIEW
		$this->config['is_preview'] 		= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'is_preview', 's_preview');
		$this->config['preview_title'] 		= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'preview_title', 's_preview');
		$this->config['preview_button'] 	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'preview_button', 's_preview');
		$this->config['preview_howmany'] 	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'preview_howmany', 's_preview');
		$this->config['preview_description_length'] 	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'preview_description_length', 's_preview');
		$this->config['preview_ministry'] 	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'preview_ministry', 's_preview');
		$this->config['previewdata_PID'] 	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'previewdata_PID', 's_preview');
		$this->config['previewlink_PID'] 	= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'previewlink_PID', 's_preview');
		if (!$this->config['preview_description_length']) $this->config['preview_description_length'] = 64; // set default
		// set the pid_list if this is a preview
		if ($this->config['previewdata_PID'] && $this->config['is_preview'])
			$this->pid_list = $this->config['previewdata_PID'];

		// TEXT
		$this->config['signup_instructions'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'signup_instructions', 's_text');
		$this->config['signup_emailHeader'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'signup_emailHeader', 's_text');
		$this->config['signup_emailFooter'] = $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'signup_emailFooter', 's_text');
		$this->config['header_text'] 		= $this->pi_getFFvalue($this->cObj->data['pi_flexform'], 'header_text', 's_text');

		// ------------------------------------------------------------
		// Grab List Of All Ministries
		// ------------------------------------------------------------
		$this->ministryList = array();
		$defaultGroupTypeID = 1; //($this->config['grouptype_id']) ? $this->config['grouptype_id'] : 1;
        if ($grpArray = $this->get_groups_by_type($defaultGroupTypeID,"title")) {
        	// store all groups in the ministryList
	        for ($i = 0; $i < count($grpArray); $i++) {
	        	// clean up title and description
    	    	$grpArray[$i]['title'] = stripslashes($grpArray[$i]['title']);
        		$grpArray[$i]['description'] = stripslashes($grpArray[$i]['description']);
           		array_push($this->ministryList,$grpArray[$i]);
	        }
	    }

		// -----------------------------------------------------------
		// Grab List Of All Skills
		// ------------------------------------------------------------
		// We want to narrow down skills to ONLY those Skills that are used in Ministry Opportunities
		$this->skillsList = array();
		$what = '*,uid_foreign';
		$fromDB	= $this->servantSkillsTable . ', ' . $this->servantSkillsMMTable;
		$where  = $this->servantSkillsTable.'.pid IN(' . $this->pid_list . ') AND ' . $this->servantSkillsTable.'.hidden=0';
		$where .= ' AND ' . $this->servantSkillsTable.'.uid = ' . $this->servantSkillsMMTable.'.uid_foreign ';
		$where .= ' AND pid != -1';
		$where .= ' GROUP BY uid';
		$orderBy .= 'sort_order DESC,group_by,name';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($what,$fromDB,$where,"",$orderBy,"");

		if (mysql_error())	t3lib_div::debug(array(mysql_error(),$where));
		// store skills in class array
 		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if ($this->versioningEnabled) {
				// get workspaces Overlay
				$GLOBALS['TSFE']->sys_page->versionOL($this->servantSkillsTable,$row);
			}
			if (is_array($row)) {
				$row['name'] = stripslashes($row['name']);
				$row['description'] = stripslashes($row['description']);
				$row['group_by'] = stripslashes($row['group_by']);
	 			array_push($this->skillsList,$row);
			}
 		}

		// ------------------------------------------------------------
		// Grab List Of All Contacts
		// ------------------------------------------------------------
		$this->contactList = array();
		$where = 'tx_wecservant_is_contact=1 AND disable=0 AND deleted=0';
		$orderBy = 'name';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->servantContactTable,$where,"",$orderBy,"");
		if (mysql_error())	t3lib_div::debug(array(mysql_error(),"SELECT * FROM ".$this->servantContactTable." WHERE ".$where." ORDER BY ".$order_by." LIMIT ".$limit));
 		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
 			if ($row['email']) { // replace @ for email with given emailAt substitute
 				$showEmail = str_replace('@',$this->conf['emailAtSubstitute'],$row['email']);
 				$row['emailVal'] = $row['email'];
				$row['email'] = '<a href="javascript:linkTo_UnCryptMailto(\''.$GLOBALS['TSFE']->encryptEmail('mailto:'.$row['email']).'\');">'.$showEmail.'</a>';
			}
			$row['name'] = stripslashes($row['name']);
			// add current row of data to contact list
 			array_push($this->contactList,$row);
 		}

 		// if want to link to staff directory, then load up info
		$this->staffLink = array();
 		if ($this->conf['staffDirectoryPage']) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,feuser_id','tx_wecstaffdirectory_info',"1=1","",'uid',"");
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),"SELECT * FROM ".$this->servantContactTable." WHERE ".$where." ORDER BY ".$order_by." LIMIT ".$limit));
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res)) {
		 		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					// add current row of data to staff list
					$this->staffLink[$row['feuser_id']] = $row['uid'];
		 		}
		 	}
	 	}

		// ------------------------------------------------------------
		// SET if administrator
		// ------------------------------------------------------------
		if ($this->userID && ($admins = $this->config['administrator_group'])) {
			$adminList = explode(",",$admins);
			foreach ($adminList as $thisAdmin) {
				$thisMinList = 0;
				if ($n = strpos($thisAdmin,":")) {
					$thisMinList = substr($thisAdmin,$n+1);
					$thisMinList = strtr($thisMinList, "_", " ");
					$thisMinList = explode('|',$thisMinList);
					for ($j = 0; $j < count($thisMinList); $j++) {
						$found = -1;
						for ($k = 0; $k < count($this->ministryList); $k++) {
							if (!strcasecmp($this->ministryList[$k]['title'],$thisMinList[$j])) {
								$found = $this->ministryList[$k]['uid'];
								break;
							}
						}
						// replace text with the index in the curMinistryList
						$thisMinList[$j] = $found;
					}
					$thisAdmin = substr($thisAdmin,0,$n);
				}
				if (($thisAdmin == $this->userID) || (!strcasecmp($thisAdmin,$this->userName))) {
					$this->isAdministrator = 1;
					$this->adminMinistries = $thisMinList;
					break;
				}
			}
		}
		// set if this user is servantContact
		if ($this->userID && ($GLOBALS['TSFE']->fe_user->user['tx_wecservant_is_contact']  == 1)) {
			$this->isServantContact = 1;
		}
		// ------------------------------------------------------------
		// Check INCOMING Vars
		// ------------------------------------------------------------
		$this->postvars = t3lib_div::_GP('tx_wecservant');

		// SECURITY FOR ALL INCOMING VARS
		if ($this->postvars) {
			foreach ($this->postvars as $key => $value)
				$this->postvars[$key] = htmlspecialchars(stripslashes($value));
		}

	  	// FIND/SEARCH BY...dropdowns or lists
	  	//-------------------------------------
		if ($v = htmlspecialchars(t3lib_div::_GP('findbyministry'))) {
			$this->curMinistryUID = $v;
		}
		else if ($v =  t3lib_div::_GP('findbyministries')) {
			$this->curMinistryUID = $v;
		}
		else if ($v = $this->conf['showMinistry']) {
			$this->curMinistryUID = $v;
		}
		else if ($v = htmlspecialchars(t3lib_div::_GP('findbyneeds'))) {
			$this->curPriority = $v;
		}
		else if ($v = htmlspecialchars(t3lib_div::_GP('findbylastadded'))) {
			$this->lastAdded = $v;
		}
		else if (t3lib_div::_GP('findbyskills')) {
			$this->skillsSearch = t3lib_div::_GP('findbyskills');
			// SECURITY ON incoming vars
			foreach ($this->skillsSearch as $key => $value)
				$this->skillsSearch[$key] = htmlspecialchars($value);
			$this->skillsSearchBy = htmlspecialchars(t3lib_div::_GP('findall'));
		}

		// ------------------------------------------------------------
		// Handle Admin Actions
		//-------------------------------------------------------------
		if ($this->isAdministrator || $this->isServantContact)	{
	  		$this->action = 'admin';

		  	if (t3lib_div::_GP('adminUpd')) { $this->admin_addUpdMinOpp($this->postvars,$this->postvars['uid']); $this->postvars = 0;}
		  	else if ($v = htmlspecialchars(t3lib_div::_GP('adminDel'))) 	{ $delUID = (intval($v) == $v) ? $v : $this->postvars['uid']; $this->admin_delMinOpp($delUID);	}
		  	else if ($v = htmlspecialchars(t3lib_div::_GP('adminMyList'))) 	{ if ($v != ($this->userID)) {$v=0; $this->action=0;} }
		  	else if ($v = htmlspecialchars(t3lib_div::_GP('adminEdit'))) 	{ $this->postvars = $this->get_by_uid($v); }
		  	else if (t3lib_div::_GP('adminAdd')) 	 { $this->admin_addUpdMinOpp($this->postvars,0);  	}
			else if (t3lib_div::_GP('adminstats'))   { $this->action = 'admin'; }
		  	else if (!$this->isAdministrator) 	 { if (!t3lib_div::_GP('admin')) $this->action = 0; } // only allow non-admins to do above actions
		  	else if (t3lib_div::_GP('admin') == 1) { // if admin=1 then show menu
		  		$this->action = 'admin';
		  	}
		  	else {
		  		$this->action = 0;
		  	}

			//  can edit single
			if (($v = htmlspecialchars(t3lib_div::_GP('edit'))) > 1) {
				$this->postvars = $this->get_by_uid($v);

				// only allow to edit if admin or is owner of current minOpp
				if ($this->isAdministrator || ($this->postvars['contact_uid'] == $this->userID))
					$this->action = 'admin';
				else {
					$this->postvars = 0;
					$this->action = 0;
				}
			}
		}

		// PASS IN showministry to select and show a specific ministry
		//-------------------------------------------------------------------------------
		if (($showministry = htmlspecialchars(t3lib_div::_GP('showministry'))) || ($showministry = htmlspecialchars($this->conf['viewMinistryOpps']))) {
			// convert any _ to space
			$showministry = trim(str_replace('_',' ',$showministry));
			// go through ministry list and try to match...
			if ((($showministry == 'all') || ($showministry == -1)) && !t3lib_div::_GP('findbyministry') && !t3lib_div::_GP('findbyministries'))
				$this->curMinistryUID = -1;
			else {
				foreach ($this->ministryList as $min) {
					if (!strcmp(strtolower($min['title']), strtolower($showministry))) {
						// set curMinistryUID and as long as canFindByMinistry, will be set
						$this->curMinistryUID = $min['uid'];
						break;
					}
				}
			}
		}

		// PASS IN showskills to select and show specific ministry opportunity by skills
		//-------------------------------------------------------------------------------
		if (($showskills = htmlspecialchars(t3lib_div::_GP('showskills_any'))) || ($showskills = htmlspecialchars(t3lib_div::_GP('showskills_all')))) {
			$this->skillsSearchBy = htmlspecialchars(t3lib_div::_GP('showskills_all'));
			// convert any _ to space
			$showskills = trim(str_replace('_',' ',$showskills));
			$matchSkillList = t3lib_div::trimExplode('|',$showskills);
			// go through skills list and try to match...
			$this->skillsSearch = array();
			for ($i = 0; $i < count($matchSkillList); $i++) {
				$thisSkill = $matchSkillList[$i];
				foreach ($this->skillsList as $skl) {
					if (!strcmp(strtolower($skl['name']), strtolower($thisSkill))) {
						array_push($this->skillsSearch, $skl['uid']);
						break;
					}
				}
			}
		}

		// IF MAKING COMMITMENT OR SHOWING INTEREST, THEN SHOW FORM...
		//--------------------------------------------------------------
		if ($this->postvars['makecommitment'] || $this->postvars['showinterest'])
			$this->action = 'signup';

	  	// POSTING/REPLYING TO FORUM OR EDITTING EXISTING MESSAGE...
	  	//----------------------------------------------------------
	  	if (($signupForm = $this->postvars['signupForm']) && ($signupForm >= 1)) {
	  		if (!$this->send_signup_email($this->postvars)) {
	  			if ($this->formErrorText)
	  				$this->action = 'signup'; // show form again if errors
	  		}
		  	else {
		  		$this->action = 'signup_confirm';
		  	}
	  	}

	  	// Handle LOGOUT...
	  	//--------------------------------------------------
	  	if (($isLogout = t3lib_div::_GP('logintype')) == "logout") {
	  		header('Location: '.t3lib_div::locationHeaderURL($this->pi_getPageLink($GLOBALS['TSFE']->id)));
			exit;
	  	}

		if ($this->config['is_preview']) {
			$this->action = 'preview';
		}

		// Handle Printing Page
		if ($printhow = t3lib_div::_GP('printpage')) {
			$printvar = t3lib_div::_GP('pvar');
			switch ($printhow) {
				case 1:	$this->curMinistryUID = $printvar; break;
				case 2: $this->curPriority = $printvar; break;
				case 3: $this->lastAdded = $printvar; break;
				case 4: $this->skillsSearch = $printvar;
						$this->skillsSearchBy = t3lib_div::_GP('skillsby');
						break;
			}
		}

		// Load any saved minOpps
		//-------------------------------------------------
		$this->load_saved_minopp();

		// Do Save or Show Saved or Print Saved...
		//------------------------------------------------
		if (t3lib_div::_GP('printsaved')) {
//			$this->print_saved_minOpp();
		}
		else if ($savedItemVars = t3lib_div::_GP('wecservant_save')) {
			foreach ($savedItemVars as $key => $value)
				$savedItemVars[$key] = htmlspecialchars($value);
			$this->save_minopp($savedItemVars);
		}
		else if (t3lib_div::_GP('save_selected')) { // empty list
			$this->save_minopp(0);
		}

		// Set CSS file(s), if exist
		if (t3lib_extMgm::isLoaded('wec_styles')) {
			require_once(t3lib_extMgm::extPath('wec_styles') . 'class.tx_wecstyles_lib.php');
			$wecStylesLib = t3lib_div::makeInstance('tx_wecstyles_lib');
			$wecStylesLib->includePluginCSS();
		}
		else if ($baseCSSFile = $this->conf['baseCSSFile']) {
			$fileList = array(t3lib_div::getFileAbsFileName($baseCSSFile));
			$fileList = t3lib_div::removePrefixPathFromList($fileList,PATH_site);
			$GLOBALS['TSFE']->additionalHeaderData['wecservant_basecss'] = '<link type="text/css" rel="stylesheet" href="'.$fileList[0].'" />';
		}
		if ($cssFile = $this->conf['cssFile']) {
			$fileList = array(t3lib_div::getFileAbsFileName($cssFile));
			$fileList = t3lib_div::removePrefixPathFromList($fileList,PATH_site);
			$GLOBALS['TSFE']->additionalHeaderData['wecservant_css'] = '<link type="text/css" rel="stylesheet" href="'.$fileList[0].'" />';
		}
	}

	/**
	 * Handles main actions
	 *
	 * @param	string		$content: The PlugIn content
	 * @param	array		$conf: The PlugIn configuration
	 * @return	string		The content that is displayed on the website
	 */
	function main($content,$conf) {
		$this->init($conf);
	    if ($this->conf['isLoaded']!='yes') {
		  t3lib_div::sysLog('Static template not set for ' . $this->extKey . ' on page ID: ' . $GLOBALS['TSFE']->id . ' url: ' . $this->getAbsoluteURL($this->id,t3lib_div::_GET()), $this->extKey, 3);
	      return $this->pi_getLL('errorIncludeStatic');
		}

		$content = "";

		$this->action = (string) $this->action;
		switch ($this->action) {
			case 'signup':
				$content .= $this->display_signup_form();
				break;

			case 'admin':
				$content .= $this->admin_menu();
				break;

			case 'preview':
				$content .= $this->display_preview();
				break;

			case 'signup_confirm':
				$content .= $this->display_signup_confirm($this->postvars);
				break;

			default:
				$content .= $this->display_main();
		}

		return $this->pi_wrapInBaseClass($content);
	}

	/**
	 *==================================================================================
	 * display_main -- show the posts and reply form
	 *
	 * @return string	content to be displayed
	 *==================================================================================
	 */
	function display_main() {
		//
	  	// Build each piece and then display
		//-------------------------------------------------------------------------------
		$subpartMarkerArray = array(); // empty for now
		$minArray = 0;

		// generate the interface
		if ($this->config['title'])
			$markerArray['###TITLE###'] = $this->config['title'];
		else
			$subpartMarkerArray['###SHOW_TITLE###'] = '';
		if ($this->config['header_text'])
			$markerArray['###HEADER_TEXT###'] = $this->config['header_text'];
		else
			$subpartMarkerArray['###SHOW_HEADER_TEXT###'] = '';

		// if saved opps and can save for later, show buttons
		if ($this->savedMinOpps && $this->config['can_save_for_later']) {
			$viewSavedStr = $this->pi_getLL('view_saved_items','View Saved Opportunities');
			if ($viewSavedStr) {
				$savedParam['showsaved'] = 1;
				$markerArray['###VIEW_SAVED_MINOPPS_LINK###'] = '<a class="button" href="'.$this->getAbsoluteURL($this->id,$savedParam).'"><span class="label">'.$viewSavedStr.'</span></a>';
			}
			$printSavedStr = $this->pi_getLL('print_saved_items','Print Saved Opportunities');
			if ($printSavedStr) {
				$savedParam2['printsaved'] = 1;
				$markerArray['###PRINT_SAVED_MINOPPS_LINK###'] = '<a class="button" href="'.$this->getAbsoluteURL($this->id,$savedParam2).'"><span class="label">'.$printSavedStr.'</span></a>';
			}
		}
		// if is admin or creator, can access admin menu
		if ($this->isAdministrator || $this->isServantContact) {
			$adminMenu['admin'] = 1;
			$markerArray['###ADMIN_LINK###'] = '<a class="button" href="'.$this->getAbsoluteURL($this->id,$adminMenu).'"><span class="label">'.$this->pi_getLL('admin_menu_link','admin').'</span></a>';
		}

		// show results if a find by ministry/ministries is returned
		if ((($this->config['canFindByMinistry'] || $this->config['canFindByMinistries']) && $this->curMinistryUID) || ($this->curMinistryUID == -1)) {
			$minArray = $this->get_by_ministry($this->curMinistryUID);
			$markerArray['###DISPLAY_RESULTS###'] = $this->show_items($minArray);

			$resultsHeader = $this->pi_getLL('find_by_ministry_results','Results For Find By Ministry:') . ' ';
			if ($this->curMinistryUID == -1) {
				$ministryHeader = $this->pi_getLL('find_by_ministry_results_all', 'All ministries');
				$resultsHeader = '';
			}
			else if (is_array($this->curMinistryUID	) && (count($this->curMinistryUID) > 1)) {
				$ministryHeader =  $this->pi_getLL('find_by_ministries_header','Multiple Ministries');
			}
			else {
				 $ministryHeader = $minArray[0]['ministry_info'];
			}
			$markerArray['###DISPLAY_RESULTS_HEADER###'] = '<h3>' .  $resultsHeader . $ministryHeader . ' (' . count($minArray) . ' found)' . '</h3>';
		}

		// Find By Ministry
		if ($this->config['canFindByMinistry']) {
			$markerArray['###FIND_BY_MINISTRY_HEADER###'] = $this->pi_getLL('find_by_ministry','Search By Ministry');
			$markerArray['###FIND_BY_MINISTRY###'] = $this->find_by_ministry();
		}

		// Find by Ministries
		if ($this->config['canFindByMinistries']) {
			$markerArray['###FIND_BY_MINISTRIES_HEADER###'] = $this->pi_getLL('find_by_ministries','Search By Ministries');
			$markerArray['###FIND_BY_MINISTRIES###'] = $this->find_by_ministries();
			if (!$this->config['see_all_ministries'] || is_array($minArray)) {
				$markerArray['###TOGGLE_FIND_MINISTRIES###'] = '<div id="hide_findbyministries" class="showBlock">';
				$markerArray['###TOGGLE_FIND_MINISTRIES###'] .= '<a class="button floatLeft" href="#" onclick="toggleSection(\'hide_findbyministries\');toggleSection(\'findbyministries\');return false;"><span class="label">'.$this->pi_getLL('show_ministries_on','click to select by ministries').'</span></a></div>';
				$markerArray['###TOGGLE_FIND_MINISTRIES###'] .= '<div id="findbyministries" class="hidden">';
				$markerArray['###TOGGLE_FIND_MINISTRIES###'] .= '<a class="button floatLeft" href="#" onclick="toggleSection(\'hide_findbyministries\');toggleSection(\'findbyministries\');return false;"><span class="label">'.$this->pi_getLL('show_ministries_off','hide ministries list').'</span></a>';
				$markerArray['###TOGGLE_FIND_MINISTRIES_OFF###'] = '</div>';
			}
		}
		else
			$subpartMarkerArray['###SHOW_FIND_MINISTRIES###'] = '';

		// Find By Skills
		if ($this->config['canFindBySkills']) {
			$markerArray['###FIND_BY_SKILLS_HEADER###'] = $this->pi_getLL('find_by_skills','Search By Skills:');
			$markerArray['###FIND_BY_SKILLS###'] = $this->find_by_skills($this->skillsSearch);
			// allow a toggle for the skills since can be so big. Only show if selected or if doing skills search
			if (!$this->config['see_all_skills'] || $this->skillsSearch) {
	 			$markerArray['###TOGGLE_FIND_SKILLS###'] = '<div id="hide_findbyskills" class="showBlock">';
				$markerArray['###TOGGLE_FIND_SKILLS###'] .= '<a class="button floatLeft" href="#" onclick="toggleSection(\'hide_findbyskills\');toggleSection(\'findbyskills\');return false;"><span class="label">'.$this->pi_getLL('show_skills_on',"click to select by skills").'</span></a></div>';
				$markerArray['###TOGGLE_FIND_SKILLS###'] .= '<div id="findbyskills" class="hidden">';
				$markerArray['###TOGGLE_FIND_SKILLS###'] .= '<a class="button floatLeft" href="#" onclick="toggleSection(\'hide_findbyskills\');toggleSection(\'findbyskills\');return false;"><span class="label">'.$this->pi_getLL('show_skills_off',"hide skills list").'</span></a>';
				$markerArray['###TOGGLE_FIND_SKILLS_OFF###'] = '</div>';
			}

			if ($this->skillsSearch) {
				$minArray = $this->get_by_skills($this->skillsSearch, $this->skillsSearchBy);
				if ($minArray != -1)  { // if we have results
					// show the listing
					$markerArray['###DISPLAY_RESULTS###'] = $this->show_items($minArray);

					// now find the skills so can display...
					$skillsStr = "";
					foreach ($this->skillsSearch as $skl)
						foreach ($this->skillsList as $sklFind)
							if ($sklFind['uid'] == $skl)
								$skillsStr .= (strlen($skillsStr) ? "," : "") . $sklFind['name'];
					// now display the results
					$markerArray['###DISPLAY_RESULTS_HEADER###'] = '<h3>' . $this->pi_getLL('find_by_skills_results','Results For Find By Skills').' ['.$skillsStr.']: ('.count($minArray).' found)</h3>';
					if (sizeof($minArray) == 0) { // show the skills list if nothing found
						$markerArray['###TOGGLE_FIND_SKILLS###'] = "";
					}
				}
				else { // error message...show skills again with message
					$markerArray['###TOGGLE_FIND_SKILLS###'] = "";
				}
			}
		}
		else
			$subpartMarkerArray['###SHOW_FIND_SKILLS###'] = '';

		// Find By Needs
		if ($this->config['canFindByPriority']) {
			$markerArray['###FIND_BY_NEEDS_HEADER###'] = $this->pi_getLL('find_by_priority','Search By Need');
			$markerArray['###FIND_BY_NEEDS###'] = $this->find_by_needs();
			if ($this->curPriority) {
				$minArray = $this->get_by_needs($this->curPriority);
				$markerArray['###DISPLAY_RESULTS###'] = $this->show_items($minArray);
				$priLevel = $this->pi_getLL('priorityLevel'.$this->curPriority,'');
				$markerArray['###DISPLAY_RESULTS_HEADER###'] = '<h3>'.$this->pi_getLL('find_by_needs_results','Results For Find By Needs:').' '.$priLevel.' ('.count($minArray).' found)</h3>';
			}
		}

		// Find Last Added...
		if ($this->config['canFindByLastAdded']) {
			$markerArray['###FIND_BY_LASTADDED_HEADER###'] = $this->pi_getLL('find_by_last_added','Search By Last Added');
			$markerArray['###FIND_BY_LASTADDED###'] = $this->find_by_lastadded();
			if ($this->lastAdded) {
				$limit = 20;
				switch ($this->lastAdded) {
					case 2: $timeAgo = mktime(0,0,0,date('m')-1,date('d'),date('y')); break; // 1 month
					case 3: $timeAgo = mktime(0,0,0,date('m')-3,date('d'),date('y')); break; // 3 months
					case 4: $timeAgo = mktime(0,0,0,date('m')-6,date('d'),date('y')); break; // 6 months
					case 1:	$timeAgo = mktime(0,0,0,date('m'),date('d')-7,date('y')); break; // 1 week
					default: // > 9 then will show however many in lastAdded#
							$timeAgo = mktime(0,0,0,date('m'),date('d'),date('y')-1);
							$limit = $this->lastAdded;
							break;
				}
				$minArray = $this->get_by_lastAdded($timeAgo, $limit);
				$markerArray['###DISPLAY_RESULTS###'] =$this->show_items($minArray);
				$markerArray['###DISPLAY_RESULTS_HEADER###'] = '<h3>' . $this->pi_getLL('find_by_lastadded_results','The Following Were Last Added:').' ('.count($minArray).' found)</h3>';
			}
		}

		if ($this->responseMsg)
			$markerArray['###RESPONSE_MSG_TEXT###'] = $this->responseMsg;
		else
			$subpartMarkerArray['###SHOW_RESPONSE_MSG###'] = '';

		// special buttons only (no dropdown) that can be added to template
		$savedParam3['findbylastadded'] = 10;
		$markerArray['###LAST_10_ADDED_BTN###'] = '<a class="button" href="' . $this->getAbsoluteURL($this->id,$savedParam3) . '"><span class="label">' . $this->pi_getLL('last_10_added_btn','Find Newest Opportuntities') . '</span></a>';
		$savedParam4['findbyneeds'] = 2;
		$markerArray['###FIND_URGENT_NEEDS_BTN###'] = '<a class="button" href="' . $this->getAbsoluteURL($this->id,$savedParam4) . '"><span class="label">' . $this->pi_getLL('find_urgent_needs_btn','Find Urgent Needs') . '</span></a>';

		// add special javascript code for whole page...
		$jsCode = '
			<script language="JavaScript" type="text/javascript">
				//<![CDATA[
				function getElementsByClassName(clsName,tag){
					var retVal = new Array();
					if (tag == null) { tag="*"; }
					var elements = document.getElementsByTagName(tag);
					for(var i = 0;i < elements.length;i++) {
						var classes = elements[i].className.split(" ");
						for (var j = 0; j < classes.length; j++){
							if (classes[j] == clsName)
								retVal.push(elements[i]);
						}
					}
					return retVal;
				}
				function toggleSection(whatToToggle) {
					toggleWhat = document.getElementById(whatToToggle);
					if (!toggleWhat)
						return false;
					if (toggleWhat.className.indexOf("showBlock") != -1)
						toggleWhat.className = "hidden";
					else
						toggleWhat.className = "showBlock";
				}
				function printItems() {
					toggle_divs = getElementsByClassName("yesprint","div");
					for (i = 0; i < toggle_divs.length; i++) {
						toggle_divs[i].style.display = "block";
					}
					toggleoff_divs = getElementsByClassName("noprint","div");
					for (i = 0; i < toggleoff_divs.length; i++)	{
						toggleoff_divs[i].style.display = "none";
					}
					return false;
				}
				//]]>
			</script>';
		$markerArray['###JAVASCRIPT_CODE###'] = $jsCode;
		if (t3lib_div::_GP('showsaved')) {
			$minArray = $this->get_saved_minopp();
			$markerArray['###DISPLAY_RESULTS###'] =$this->show_items($minArray);
			$markerArray['###DISPLAY_RESULTS_HEADER###'] = $this->pi_getLL('find_by_saved_results','Your Saved Ministry Opportunities:');

			if (count($minArray)) {
				// show interested/committed buttons for saved so can do ALL
				if ($this->showInterestForm) {
					$extraParams['tx_wecservant[showinterest]'] = -1;
					$theURL = $this->getAbsoluteURL($this->id,$extraParams);
				   	$markerArray['###SIGNUP_BTN###'] = '<a class="button" href="' . $theURL . '"><span class="label subscribeIcon">' . $this->pi_getLL('interest_btn','I Am Interested') . '</span></a>';
				}
			   	if ($this->showCommitmentForm) {
					$extraParams2['tx_wecservant[makecommitment]'] = -1;
					$theURL = $this->getAbsoluteURL($this->id,$extraParams2);
				   	$markerArray['###COMMITMENT_BTN###'] = '<a class="button" href="' . $theURL . '"><span class="label subscribeIcon">' . $this->pi_getLL('commitment_btn','Make Commitment') . '</span></a>';
				}
			}
		}
		// if we are printing the saved opportunities...show them here...
		if (t3lib_div::_GP('printsaved')) {
			$minArray = $this->get_saved_minopp();
			unset($markerArray); // clear out everything else
			$markerArray['###JAVASCRIPT_CODE###'] = $jsCode;
			$markerArray['###DISPLAY_RESULTS_HEADER###'] = $this->pi_getLL('find_by_saved_results','Your Saved Ministry Opportunities:');
			$markerArray['###DISPLAY_RESULTS###'] = $this->show_items($minArray);
		}

		// if printpage, then the results were set by ministry, priority, or skills above
		if (t3lib_div::_GP('printpage')) {
			$markerArray['###DISPLAY_RESULTS###'] =$this->show_items($minArray);
		}
		// add print button and capability
		if (t3lib_div::_GP('printsaved') || t3lib_div::_GP('printpage')) {

			$markerArray['###JAVASCRIPT_CODE###'] = $jsCode . $this->add_print_button();
		}
		// Show Print Page button -- if we have results, and are not being shown the "saved"/"printed" results
		if ((strlen($markerArray['###DISPLAY_RESULTS###']) > 0) && !t3lib_div::_GP('printsaved') && !t3lib_div::_GP('showsaved')) {
			$printHow = 0;
			$printVars = 0;
			if ($printVars = $this->curMinistryUID) {
				$printHow = 1;
			}
			else if ($printVars = $this->curPriority) {
				$printHow = 2;
			}
			else if ($printVars = $this->lastAdded) {
				$printHow = 3;
			}
			else if ($printVars = $this->skillsSearch) {
				$printHow = 4;
				$param['skillsby'] = $this->skillsSearchBy;
			}

			$params['printpage'] = $printHow;
			$params['pvar'] = $printVars;
//			$markerArray['###PRINT_BUTTON###'] = '<div class="button"><a href="'.$this->getAbsoluteURL($this->id,$params).'">'.$this->pi_getLL('print_button','Print Page').'</a></div>';
			$markerArray['###PRINT_BUTTON###'] = '<a class="button" href="" onclick="printItems();window.print();return false;"><span class="label">' . $this->pi_getLL('print_button','Print Page') . '</span></a>';
		}

		// show save selected / clear selected / select all buttons at bottom
		$markerArray['###SAVE_SEL_BTN###'] = '';
		$markerArray['###CLEAR_SEL_BTN###'] = '';
		$markerArray['###SELECT_ALL_BTN###'] = '';

		// handle single view here...
		if ($thisUID = $this->postvars['single']) {
			$minArray = array($this->get_by_uid($thisUID));
			$markerArray['###DISPLAY_RESULTS_HEADER###'] = $this->pi_getLL('view_single_results','');
			$markerArray['###DISPLAY_RESULTS###'] = $this->show_items($minArray, true);
		}
		// now read in the part of the template file with the PAGE subtemplatename
		$template = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_PAGE###');

		// then substitute all the markers in the template into appropriate places
		$content = $this->cObj->substituteMarkerArrayCached($template,$markerArray,$subpartMarkerArray, array());

		// clear out any empty template fields
		$content = preg_replace('/###.*?###/', '', $content);

		return $content;
	}

	/**
	 *	SHOW_ITEMS: Show minOpp items that are passed in
	 *
	 *  @param  array	$minArray array of items to show
	 *  @param  integer	$showAll if should force to show the whole entry or not
	 *	@return	string	content of interface to find
	 */
	function show_items($minArray, $showAll = false) {
		if (!$minArray)
			return $this->pi_getLL('no_entries_found','No entries were found');

		$showButtons = t3lib_div::_GP('printsaved') ? false : true;
		if (t3lib_div::_GP('showplain')) $showButtons = false;
		$templateName = !$this->postvars['single'] ? '###TEMPLATE_DISPLAYITEM###' : '###TEMPLATE_SINGLE###';
		$itemTemplate = $GLOBALS['TSFE']->cObj->getSubpart($this->templateCode, $templateName);

		$content = '<div class="tx-wecservant-spacer"> </div>';
		$content .= '<form class="selectForm" name="showservantitems" method="post" action="'.$this->getAbsoluteURL($this->id).'">';
		$isOdd = 0;

		$hideArray = array();

		// TURN OFF ALL NON-DISPLAY FIELDS
		//--------------------------------------------------------------------------
		if (is_array($this->config['display_oppfields']) && count($this->config['display_oppfields']) > 0) {
			$fieldArray = $this->db_showFields;
			for ($i = 0; $i < count($fieldArray); $i++)	{
				$fieldFound = false;
				foreach ($this->config['display_oppfields'] AS $display_field) {
					if (!strcasecmp($display_field,$fieldArray[$i])) {
						$fieldFound = true;
						break;
					}
				}
				// the field was not in display_list, so turn it off
				if (!$fieldFound) {
					$hideArray[strtoupper($fieldArray[$i])] = 1;
				}
			}
		}
		// display each minOpp in the array
		for ($i = 0; $i < count($minArray); $i++) {
			$thisItem = $minArray[$i];
			unset($thisMarkerArray);
			$changed = false;
			foreach ($this->db_showFields as $theField)	{
				if ($hideArray[strtoupper($theField)] == 1 || !$thisItem[$theField]) {
					$thisMarkerArray['###SHOW_'.strtoupper($theField).'###'] = 'style="display:none;"';
				}
				if ($thisItem[$theField]) {
					$thisMarkerArray['###ITEMLABEL_'.strtoupper($theField).'###'] = $this->pi_getLL('itemlabel_'.strtolower($theField));
					$thisMarkerArray['###'.strtoupper($theField).'###'] = $thisItem[$theField];
					$changed = true;
				}
			}
			// if we did add one, then process it
			if ($changed) {
				// alternate the background colors for each item
				if ($isOdd && $this->conf['displayItemBackColor2'])
					$thisMarkerArray['###ITEM_EVENODD_DISPLAY###'] = 'background-color:'.$this->conf['displayItemBackColor2'];
				else if ($this->conf['displayItemBackColor'])
					$thisMarkerArray['###ITEM_EVENODD_DISPLAY###'] = 'background-color:'.$this->conf['displayItemBackColor'];
				$isOdd = !$isOdd;

				$thisMarkerArray['###SHOW_CONTACT_STAFFLINK###'] = 'style="display:none;"';
				if (($staffDirPage = $this->conf['staffDirectoryPage']) && count($this->staffLink)) {
					if ($staffID = $this->staffLink[$thisItem['contact_uid']]) {
						$paramArray['tx_wecstaffdirectory_pi1']['curstaff'] = $thisItem['contact_uid'];
						$pageURL = $this->pi_getPageLink($staffDirPage,'',$paramArray);
						$thisMarkerArray['###CONTACT_STAFFLINK###'] = '<a href="'.$pageURL.'">'.$this->pi_getLL('contact_stafflink','Click to view').'</a>';
						$thisMarkerArray['###SHOW_CONTACT_STAFFLINK###'] = 'style="display:block;"';
						$thisMarkerArray['###ITEMLABEL_CONTACT_STAFFLINK###'] = $this->pi_getLL('itemlabel_contact_stafflink','Staff page:');
					}
				}

				if ($showButtons) {
					if ($this->config['can_save_for_later']) {
						// Determine if is Saved & then add saved option...
						$isSaved = "";
						for ($k = 0; $k < count($this->savedMinOpps); $k++)
							if ($this->savedMinOpps[$k] == $thisItem['uid']) { // found a match
								$isSaved = 'checked';
								break;
							}
						$thisMarkerArray['###SAVE_ITEM###'] = '<span><input type="checkbox" name="wecservant_save[]" value="'.$thisItem['uid'].'" '.$isSaved.'/> '.$this->pi_getLL('itemlabel_save_item',"Save For Later").'</span>';
					}

					if (!$this->postvars['single']) {
						$singleParams['tx_wecservant[single]'] = $thisItem['uid'];
						$theURL = $this->getAbsoluteURL($this->id,$singleParams);
						$thisMarkerArray['###VIEW_SINGLE###'] =  '<a class="button smallButton" href="'.$theURL.'"><span class="label viewIcon">'.$this->pi_getLL('view_single_link','View This').'</span></a>';
					}

					// show signup/commitment button(s)
					if (($this->config['require_login_to_signup'] == 0) || ($this->userID != 0)) {
						if ($this->showInterestForm) {
							$extraParams['tx_wecservant[showinterest]'] = $thisItem['uid'];
							$theURL = $this->getAbsoluteURL($this->id,$extraParams);
						   	$thisMarkerArray['###SIGNUP_BTN###'] = '<a class="button smallButton" href="' . $theURL . '"><span class="label subscribeIcon">' . $this->pi_getLL('interest_btn','I Am Interested') . '</span></a>';
						}
						if ($this->showCommitmentForm) {
							$extraParams2['tx_wecservant[makecommitment]'] = $thisItem['uid'];
							$theURL = $this->getAbsoluteURL($this->id,$extraParams2);
						   	$thisMarkerArray['###COMMITMENT_BTN###'] = '<a class="button smallButton" href="'.$theURL.'"><span class="label subscribeIcon">' . $this->pi_getLL('commitment_btn','Make Commitment') . '</span></a>';
						}
					}
					// add edit button for admins or main contact person
					if ($this->isAdministrator || ($this->userID && ($this->userID == $thisItem['contact_uid']))) {
						$extraParams3['edit'] = $thisItem['uid'];
						$theURL = $this->getAbsoluteURL($this->id,$extraParams3);
					   	$thisMarkerArray['###EDIT_BTN###'] = '<a class="button smallButton" href="'.$theURL.'"><span class="label editIcon">' . $this->pi_getLL('edit_btn','Edit') . '</span></a>';
					}
					// if want to show just partial and allow to "display more..." for details
					if (!$this->config['show_all_entry'] && !$showAll) {
						$thisMarkerArray['###TOGGLE_ITEM###'] .= '<li class="row"><span class="label labelNoPad toggleItem">';
						$thisMarkerArray['###TOGGLE_ITEM###'] .= '<div id="hideitem'.$thisItem['uid'].'" class="showBlock noprint">';
						$thisMarkerArray['###TOGGLE_ITEM###'] .= '<a href="#" onclick="toggleSection(\'showitem'.$thisItem['uid'].'\');toggleSection(\'hideitem'.$thisItem['uid'].'\');return false;">'.$this->pi_getLL('display_more_details','Display more details...').'</a>';
						$thisMarkerArray['###TOGGLE_ITEM###'] .= '</div>';
						$thisMarkerArray['###TOGGLE_ITEM###'] .= '</span></li>';
						$thisMarkerArray['###TOGGLE_ITEM###'] .= '<div id="showitem'.$thisItem['uid'].'" class="hidden yesprint">';
						$thisMarkerArray['###TOGGLE_ITEM_END###'] = '</div>';
					}
				}

				$content .= $this->cObj->substituteMarkerArrayCached($itemTemplate,$thisMarkerArray,array(),array());
			}
		}

		if ($showButtons) {
			$content .= '<script type="text/javascript">
						//<![CDATA[
						function SetAllServant(typename,val)
 						{
							theForm = document.showservantitems;
							len = theForm.elements.length;
							for (var i = 0; i < len; i++)
 							{
								if (theForm.elements[i].name == typename)
 								{
									theForm.elements[i].checked=val;
								}
							}
						}
						//]]>
						</script>
			';
			$content .= '<div class="bottomRow noprint">';
			if ($this->config['can_save_for_later']) {
				$content .= '<input type="submit" name="save_selected" value="'.$this->pi_getLL('save_button','Save Selected').'"/>';
				if (t3lib_div::_GP('showsaved')) // if we are showing saved, let know so don't overwrite
					$content .= '<input name="showing_saved" type="hidden" value="1"/>';
			}
			if ($this->config['can_save_for_later']) {
				// add clear all button
				$content .= '<input type="button" name="clear_all" onclick="SetAllServant(\'wecservant_save[]\',0);" value="'.$this->pi_getLL('clearall_button','clear all').'"/>';
				// add select all button
				$content .= '<input type="button" name="select_all" onclick="SetAllServant(\'wecservant_save[]\',1);" value="'.$this->pi_getLL('selectall_button','select all').'"/>';
			}

			// end div for bottom row of buttons
			$content .= '</div>';
		}
		if ($this->postvars['single']) { // add back button
			$getArray = t3lib_div::_GET();
			unset($getArray['tx_wecservant']['single']);
			unset($getArray['showplain']);
			$theURL = $this->getAbsoluteURL($this->id,$getArray);
			$content .= '<div class="bottomRow noprint">';
			$content .= '<input type="button" name="back_btn" onclick="location.href=\''.$theURL.'\';" value="'.$this->pi_getLL('back_button','Back').'"/>';
			$content .= '</div>';
		}

		$content .= '</form>';
		return $content;
	}

	/**
	 *	FIND_BY_MINISTRY: Show interface for user to find by ministry
	 *
	 *	@return	string	content of interface to find
	 */
	function find_by_ministry() {
		$selMinistryStr = '<form name="findministry" method="post" action="'.$this->getAbsoluteURL($this->id).'"  class="selectForm">';
		$selMinistryStr .= '<select name="findbyministry" size="1" onchange="submit();">';
		$selMinistryStr .= '<option value="0">'.$this->pi_getLL('select_ministry','Select...').'</option>';
		if (($allMinText = $this->pi_getLL('select_all_ministries')) && strlen($allMinText)) $selMinistryStr .= '<option value="-1">'.$allMinText.'</option>';
		foreach ($this->ministryList as $min)
		 	$selMinistryStr .= '<option value="'.$min["uid"].'">'.$min["title"].'</option>';
		$selMinistryStr .= '</select>';
		$selMinistryStr .= '</form>';

		return $selMinistryStr;
	}

	/**
	 *	FIND_BY_MINISTRIES: Show interface for user to find by multiple ministries
	 *
	 *	@return	string	content of interface to find
	 */
	function find_by_ministries() {
		$selMinistryStr = '<form name="findministries" method="post" action="'.$this->getAbsoluteURL($this->id).'"  class="selectForm">';
		$selMinistryStr .= '<div class="multiList"><ul>';
		foreach ($this->ministryList as $min)
		 	$selMinistryStr .= '<li><input type="checkbox" name="findbyministries[]" value="'.$min['uid'].'" '.$isSelectedStr.'/> '.$min['title'].'</li>';
	 	$selMinistryStr .= '</ul></div>';
		$selMinistryStr .= '<div class="bottomRow">';
		$selMinistryStr .= '<input type="submit" name="findall" value="'.$this->pi_getLL('find_all_ministries_btn','Find By Ministries Selected').'" class="horizSection"/>';
		$selMinistryStr .= '</div>';
		$selMinistryStr .= '</form>';

		return $selMinistryStr;
	}

	/**
	 *	FIND_BY_NEEDS: Show interface for user to find by needs
	 *
	 *	@return	string	content of interface to find
	 */
	function find_by_needs() {
		$selNeedsStr = '<form name="findneeds" method="post" action="'.$this->getAbsoluteURL($this->id).'" class="selectForm">';
		$selNeedsStr .= '
				<select name="findbyneeds" size="1"  onchange="submit();">
					<option value="0">'.$this->pi_getLL('priorityLevel0','Select...').'</option>';
		// can have up to 9 priority levels...whatever is defined in locallang.php
		for ($i = 1; $i < 10; $i++) {
			if ($str = $this->pi_getLL("priorityLevel".$i)) {
				$selNeedsStr .= '<option value="'.$i.'" '.(($thisPri==$i) ? "selected" : "").'>'.$str.'</option>';
			}
		}
		$selNeedsStr .= '</select>';
		$selNeedsStr .= '</form>';

		return $selNeedsStr;
	}

	/**
	 *	FIND BY LASTADDED: Show interface for user to find by last added
	 *
	 *	@return	string	content of interface to find
	 */
	function find_by_lastadded() {
		$selLastAddedStr = '<form name="findlastadded" method="post" action="'.$this->getAbsoluteURL($this->id).'" class="selectForm" >';
		$selLastAddedStr .= '
				<select name="findbylastadded" size="1"  onchange="if (this.value) submit();">
					<option value="0">'.$this->pi_getLL('last_added_option0','Select...').'</option>
					<option value="1">'.$this->pi_getLL('last_added_option1','Last Week').'</option>
					<option value="2">'.$this->pi_getLL('last_added_option2','Last Month').'</option>
					<option value="3">'.$this->pi_getLL('last_added_option3','Last 3 Months').'</option>
					<option value="4">'.$this->pi_getLL('last_added_option4','Last 6 Months').'</option>
				</select>';
		$selLastAddedStr .= '</form>';

		return $selLastAddedStr;
	}

	/**
	 *	FIND_BY_SKILLS: Show interface for user to find by skills
	 *
	 *  @param	array	$existingSkills	skills that were already selected
	 *	@return	string	content of interface to find
	 */
	function find_by_skills($existingSkills) {
		$selSkillsStr = '<form name="findskills" method="post" action="'.$this->getAbsoluteURL($this->id).'" class="selectForm">';
		$prevGroup = 0;
		$selSkillsStr .= '<div class="multiList"><ul>';
		foreach ($this->skillsList as $sk) {
			$isSelectedStr = "";
			$grp = $sk['group_by'];
			$grp_required = $sk['required_group'];
			if (strcmp($prevGroup,$grp)) {
				$prevGroup = $grp;
				$showPrevGroup = $prevGroup;
				if ($grp_required)
					$showPrevGroup .= $this->pi_getLL('required_group_marker',' (required to select one)');
				$selSkillsStr .= ' </ul><div class="header">'.$showPrevGroup.'</div><ul>';
			}
			// check if should be selected (because passed in or set as default)
			if ($existingSkills) {
				foreach ($existingSkills as $existing_sk) {
					if ($existing_sk == $sk['uid']) {
						$isSelectedStr = ' checked';
						break;
					}
				}
			}
			else if ($grp_required == 2)
				$isSelectedStr = ' checked';

			// now add the skill as a checkbox to the output
		 	$selSkillsStr .= '<li><input type="checkbox" name="findbyskills[]" value="'.$sk['uid'].'" '.$isSelectedStr.'/> '.$sk['name'].'</li>';
		}
		$selSkillsStr .= '</ul></div>';
		$selSkillsStr .= '<div class="bottomRow">';
		$selSkillsStr .= '<input type="submit" name="findany" value="'.$this->pi_getLL('find_any_skills_btn','Match Any Skills Selected').'" class="horizSection"/>';
		$selSkillsStr .= '<input type="submit" name="findall" value="'.$this->pi_getLL('find_all_skills_btn','Match ALL Skills Selected').'" class="horizSection"/>';
		$selSkillsStr .= '<script type="text/javascript">
							//<![CDATA[
							function selectAll(thisForm,bSelect) {
								var el,elID=0;
								while (el = thisForm[elID++])
									if (el.type=="checkbox") el.checked = bSelect;
							}
							//]]>
							</script>';
		$selSkillsStr .= '<input type="button" name="reset" onclick="selectAll(this.form,false); return false;" value="'.$this->pi_getLL('reset_skills_btn',"Clear Skills Selected").'"/>';
		$selSkillsStr .= '</div>';
		$selSkillsStr .= '</form>';

		return $selSkillsStr;
	}

	/**
	 *	GET ROW DATA: Fill in array with all row data from mySQL query
	 *
	 *	@param	array	$res			the mysql data
	 *	@return	array	$minOppArray	list of all ministry opportunities that match criteria
	 */
	function get_row_data($res) {
		// grab all the records and store them in $minOppArray
		$minOppArray = array();
		$minOppUIDList = "";
 		while ($rowData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			if ($this->versioningEnabled) {
				// get workspaces Overlay
				$GLOBALS['TSFE']->sys_page->versionOL($this->servantContentTable,$rowData);
			}
			if (!is_array($rowData)) {
				continue;
			}

 			$newOpp = array();
 			foreach ($this->db_fields as $theField) {
 				if (isset($rowData[$theField])) {
 					$newOpp[$theField] = html_entity_decode(stripslashes($rowData[$theField]),ENT_QUOTES,$GLOBALS['TSFE']->renderCharset);

					// convert priority # to text name string
					if (!strcmp($theField,'priority')) {
						if (($priLevel = $rowData[$theField]) != 0) {
							$lStr = (string) $this->pi_getLL('priorityLevel'.$rowData[$theField]);
							$newOpp[$theField] = $lStr;
						}
					}
				}
			}
			// fix description, misc_description, and qualifications field so newlines show break
 	   		$newOpp['description']= str_replace("\r\n","<br />",$newOpp['description']);
 	   		$newOpp['misc_description']= str_replace("\r\n","<br />",$newOpp['misc_description']);
 	   		$newOpp['qualifications']= str_replace("\r\n","<br />",$newOpp['qualifications']);

			// for ministry, show the title
			foreach ($this->ministryList as $min)
				if ($newOpp['ministry_uid'] == $min['uid']) {
					$newOpp['ministry_info'] = $min['title'];
					break;
				}
			// for the contact, show the name
			foreach ($this->contactList as $con)
				if ($newOpp['contact_uid'] == $con['uid']) {
					$newOpp['contact_info'] = $con['name'];
					$newOpp['contact_phone'] = $con['telephone'];
					$newOpp['contact_emailVal'] = $con['emailVal'];
					$newOpp['contact_email'] = $con['email'];
					break;
				}
			$newOpp['skills'] = "";

			// if contact info has emails in it, then wrap those appropriately
			if ($newOpp['contact_info']) {
				if (is_array($this->conf['mail_stdWrap.'])) {
					$newOpp['contact_info'] = $this->cObj->stdWrap($newOpp['contact_info'], $this->conf['mail_stdWrap.']);
				}
			}

			// add the new opportunity to the list
			array_push($minOppArray,$newOpp);
			$minOppUIDList .= (strlen($minOppUIDList) > 0) ? ",".$newOpp['uid'] : $newOpp['uid'];
			$minOppUIDArray[$newOpp['uid']] = $newOpp;
 		}

		// go grab all the skills for each given minOpp...
		//
		if (count($minOppArray)) {
			$where = 'uid_local IN ('.$minOppUIDList.')';
			$orderBy = 'uid_local,sorting';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->servantSkillsMMTable,$where,"",$orderBy,"");
	 		while ($rowData = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
	 			$whichUID = $rowData['uid_local'];
	 			$whichSkillNum = $rowData['uid_foreign'];

	 			// lookup the given skill and assign to minOpp array
	 			$curMinOpp = 0;
	 			for ($i = 0; $i < count($minOppArray); $i++) {
	 				// first, find the minOpp
	 				if ($minOppArray[$i]['uid'] == $whichUID) {
						// then find the skill
		 				for  ($j = 0; $j < count($this->skillsList); $j++) {
		 					$skl = $this->skillsList[$j];
		 					// add it to the skill list
		 					if ($skl['uid'] == $whichSkillNum) {
		 						if (strlen($minOppArray[$i]['skills']) > 0) $minOppArray[$i]['skills'] .= ", ";
			 					$minOppArray[$i]['skills'] .= $skl['name'];
			 					$minOppArray[$i]['skill_list'] .= $skl['uid']."|";
								break;
			 				}
			 			}
			 			break;
				 	}
	 			}
	 		}
		}
		return ($minOppArray);
	}

	/**
	 *	GET BY UID: Return one ministry opportunity with a given UID
	 *
	 *	@param	integer	$ministryUID	which ministry UID to match to. These are fe_group UIDs.
	 *	@return	array	$minOppArray	list of all ministry opportunities that match criteria
	 */
	function get_by_uid($minOppUID) {
		if (!$minOppUID)
			return 0;
		$order_by = 'tstamp DESC';
		$where .= ' pid IN('.$this->pid_list.')';
		if (!is_array($minOppUID))
			$where .= ' AND uid='.intval($minOppUID);
		else {
			$minUIDStr = "";
			foreach ($minOppUID as $minUID) {
				$minUIDStr .= (strlen($minUIDStr) ? ',' : "") . $minUID;
			}
			$where .= ' AND uid IN ('.$minUIDStr.')';
		}
		// handle languages
		$lang = ($l = $GLOBALS['TSFE']->sys_language_uid) ? $l : '0,-1';
		$where .= ' AND sys_language_uid IN ('.$lang.') ';

		$where .= $this->cObj->enableFields($this->servantContentTable);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->servantContentTable,$where,"",$order_by);
		if (mysql_error())	t3lib_div::debug(array(mysql_error(),"SELECT * FROM ".$this->servantContentTable." WHERE ".$where." ORDER BY ".$order_by." LIMIT ".$limit));

		$minOppArray  = $this->get_row_data($res);

		if (!is_array($minOppUID))
	 		return $minOppArray[0]; // return only one (there can be only one)
	 	else
	 		return $minOppArray;
	}

	/**
	 *	GET BY MINISTRY: Return all ministry opportunities that are under a given ministry UID
	 *
	 *	@param	integer	$ministryUID	which ministry UID(s) to match to. These are fe_group UIDs.
	 *									=-1 to show all
	 *	@return	array	$minOppArray	list of all ministry opportunities that match criteria
	 */
	function get_by_ministry($ministryUID) {
		$order_by = 'name ASC';
		$where = ' pid IN('.$this->pid_list.')';
		if (is_array($ministryUID)) {
			$ministryUID = implode(',',$ministryUID);
			$where .= ' AND ministry_uid IN ('.$ministryUID.')';
		}
		else if ($ministryUID != -1)
			$where .= ' AND ministry_uid ='.intval($ministryUID);

		// handle languages
		$lang = ($l = $GLOBALS['TSFE']->sys_language_uid) ? $l : '0,-1';
		$where .= ' AND sys_language_uid IN ('.$lang.') ';

		$where .= $this->cObj->enableFields($this->servantContentTable);
		if ($this->conf['maxMatchesToShow'] && ($ministryUID != -1))
			$limit = $this->conf['maxMatchesToShow'];
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->servantContentTable,$where,"",$order_by,$limit);

		if (mysql_error())	t3lib_div::debug(array(mysql_error(),"SELECT * FROM ".$this->servantContentTable." WHERE ".$where." ORDER BY ".$order_by." LIMIT ".$limit));

 		return $this->get_row_data($res);
	}

	/**
	 *	GET BY NEEDS: Return all ministry opportunities with a given need/priority level
	 *
	 *	@param	integer	$needLevel		which need level to match to.
	 *	@return	array	$minOppArray	list of all ministry opportunities that match criteria
	 */
	function get_by_needs($needLevel) {
		$order_by = 'name ASC';
		$where .= ' pid IN('.$this->pid_list.')';
		$where .= ' AND priority='.intval($needLevel);
		// handle languages
		$lang = ($l = $GLOBALS['TSFE']->sys_language_uid) ? $l : '0,-1';
		$where .= ' AND sys_language_uid IN ('.$lang.') ';
		$where .= $this->cObj->enableFields($this->servantContentTable);

		if ($this->conf['maxMatchesToShow']) $limit = $this->conf['maxMatchesToShow'];
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->servantContentTable,$where,"",$order_by,$limit);

		if (mysql_error())	t3lib_div::debug(array(mysql_error(),"SELECT * FROM ".$this->servantContentTable." WHERE ".$where." ORDER BY ".$order_by." LIMIT ".$limit));

		return $this->get_row_data($res);
	}

	/**
	 *	GET BY CONTACT: Return all ministry opportunities that a given contact person is in charge of
	 *
	 *	@param	integer	$ministryUID	which contact user UID to match to. These are fe_user UIDs.
	 *	@return	array	$minOppArray	list of all ministry opportunities that match criteria
	 */
	function get_by_contact($contactUID) {
		if (!$contactUID)
			return 0;

		$order_by = 'name ASC';
		$where .= ' pid IN('.$this->pid_list.')';
		$where .= ' AND contact_uid='.intval($contactUID);
		// handle languages
		$lang = ($l = $GLOBALS['TSFE']->sys_language_uid) ? $l : '0,-1';
		$where .= ' AND sys_language_uid IN ('.$lang.') ';
		$where .= $this->cObj->enableFields($this->servantContentTable);

		if ($this->conf['maxMatchesToShow']) $limit = $this->conf['maxMatchesToShow'];
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->servantContentTable,$where,"",$order_by,$limit);

		if (mysql_error())	t3lib_div::debug(array(mysql_error(),"SELECT * FROM ".$this->servantContentTable." WHERE ".$where." ORDER BY ".$order_by." LIMIT ".$limit));

 		return $this->get_row_data($res);
 	}

	/**
	 *	GET BY LASTUPDATED: Return #N ministry opportunities that were last updated
	 *
	 *	@param	integer	$lastNum		previous number of ministry opps to find
	 *	@return	array	$minOppArray	list of all ministry opportunities that match criteria
	 */
	function get_by_lastupdated($lastNum) {
		if (!$lastNum)
			return 0;

		$limit = $lastNum;
		$order_by = 'tstamp DESC';
		$where = ' pid IN ('.$this->pid_list.')';
		// handle languages
		$lang = ($l = $GLOBALS['TSFE']->sys_language_uid) ? $l : '0,-1';
		$where .= ' AND sys_language_uid IN ('.$lang.') ';
		$where .= $this->cObj->enableFields($this->servantContentTable);

		if ($this->conf['maxMatchesToShow']) $limit = $this->conf['maxMatchesToShow'];

		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->servantContentTable,$where,"",$order_by,$limit);

		if (mysql_error())	t3lib_div::debug(array(mysql_error(),"SELECT * FROM ".$this->servantContentTable." WHERE ".$where." ORDER BY ".$order_by." LIMIT ".$limit));

 		return $this->get_row_data($res);
	}

	/**
	 *	GET BY LASTADDED: Return #N ministry opportunities that were last added
	 *
	 *	@param	integer	$whatTime		how far back to go
	 *	@param	integer	$maxMatchesToShow		how many to show
	 *	@return	array	$minOppArray	list of all ministry opportunities that match criteria
	 */
	function get_by_lastadded($whatTime, $maxMatchesToShow = 20) {
		if (!$whatTime)
			return 0;

		$limit	= $maxMatchesToShow;
		$order_by = 'tstamp DESC';
		$where = ' pid IN('.$this->pid_list.')';
		$where .= ' AND tstamp >= '.$whatTime;
		// handle languages
		$lang = ($l = $GLOBALS['TSFE']->sys_language_uid) ? $l : '0,-1';
		$where .= ' AND sys_language_uid IN ('.$lang.') ';
		$where .= $this->cObj->enableFields($this->servantContentTable);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->servantContentTable,$where,"",$order_by, $limit);

		if (mysql_error())	t3lib_div::debug(array(mysql_error(),"SELECT * FROM ".$this->servantContentTable." WHERE ".$where." ORDER BY ".$order_by." LIMIT ".$limit));

 		return $this->get_row_data($res);
	}

	/**
	 *	GET BY SKILLS: Return all ministry opportunities with given skills
	 *
	 *	@param	array	$matchSkills	array of skills to match to
	 *  @param	integer	$matchAll		if should match all or match any
	 *	@return	array	$minOppArray	list of all ministry opportunities that match criteria
	 */
	function get_by_skills($matchSkills, $matchAll) {
		// First check any required groups and if one is not set in a required group, then give error message
		// This also builds up these required skills in an array and removes them from the db query because
		// if it is a required group then MANY hits are going to be made.
		$reqGroup = array();
		$reqSkills = array();
		foreach ($this->skillsList as $sk) {
			$grp = $sk['group_by'];
			$grp_required = $sk['required_group'];
			if ($grp && $grp_required) {
				if (!isset($reqGroup[$grp])) // set it so can keep track (even if empty)
					$reqGroup[$grp]=0;
				// now see if it matches with a chosen skill
				for ($i = count($matchSkills) - 1; $i >= 0; $i--) {
					if ($matchSkills[$i] == $sk['uid']) {
						$reqGroup[$grp]++;
						unset($matchSkills[$i]); // clear out the skill because don't want to match on till later
						array_push($reqSkills,$sk['uid']); // save the skill so can match by hand
						break;
					}
				}
			}
		}
		if (count($matchSkills) == 0) { // none left
			// show error
			$this->responseMsg = $this->pi_getLL('required_group_none','You need to select other skills beyond those in the required group(s)');
			return -1; // special marker that there is an error
		}

		// check to see if at least one in reqGroup has been set
		foreach ($reqGroup as $grpKey => $grpVal) {
			if ($grpVal == 0) {	// show error
				$this->responseMsg = $this->pi_getLL('required_group_error','One option needs to be set for group ') . "'". $grpKey . "'";
				return -1; // special marker that there is an error
			}
		}

		// Then find matches for skills -- this OR finds them all that match with ANY
		$what  = 'DISTINCT A.*';
		$fromDB  = $this->servantContentTable.' A, '.$this->servantSkillsMMTable.' B ';
		$where = 'A.uid = B.uid_local ';
		$where .= ' AND pid IN('.$this->pid_list.') ';
		$where .= ' AND B.uid_foreign IN (';
		$skillsQueryStr = '';
		foreach ($matchSkills as $thisSkill) {
			$skillsQueryStr .= (strlen($skillsQueryStr)==0) ? $thisSkill : ','.$thisSkill;
		}
		$where .= $skillsQueryStr;
		$where .= ') ';
		$where .= ' AND A.deleted=0 AND A.hidden=0';
		$where .= ' AND A.starttime<='.mktime().' AND (A.endtime=0 OR A.endtime>'.mktime().')';
		// handle languages
		$lang = ($l = $GLOBALS['TSFE']->sys_language_uid) ? $l : '0,-1';
		$where .= ' AND A.sys_language_uid IN ('.$lang.') ';
		$orderBy = 'name DESC';
		if ($this->conf['maxMatchesToShow'])
			$limit = $this->conf['maxMatchesToShow'];
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($what,$fromDB,$where,"",$orderBy,$limit);

		if (mysql_error())	t3lib_div::debug(array(mysql_error(),$where));

		// go grab the actual data for database query
 		$minOppArray = $this->get_row_data($res);

		// now handle if matchAll or matchAny
 		if ($matchAll) { // go through and get rid of any that do not intersect
 			$newMinOppArray = array();
 			for ($i = 0; $i < count($minOppArray); $i++) {
				$skl_list = explode("|",$minOppArray[$i]['skill_list']);
				$same_skills  = array_intersect($skl_list, $matchSkills);
				if (count($same_skills) == count($matchSkills))
					array_push($newMinOppArray,$minOppArray[$i]);
 			}
 		}
 		else {	// match with any
			if (count($reqSkills)) { // Filter out those not found in reqSkills (if set)
				$newMinOppArray = array();
				for ($i = count($minOppArray)-1; $i>=0; $i--) {
					$minOpp = $minOppArray[$i];
					$isMatched = false;
					$skl_list = explode('|',$minOpp['skill_list']);
					// if found in skill list, then add to new array (A MATCH!)
					if (count(array_intersect($skl_list,$reqSkills)))
						array_push($newMinOppArray,$minOpp);
				}
			}
			else { // keep array the same and just copy it
				$newMinOppArray = $minOppArray;
			}
 		}

 		return $newMinOppArray;
	}

	/**
	 *	LOAD SAVED MINOPP: Load interested in minOpps
	 *
	 *	@return	array	$interestedInList		string of minOpp UIDs that were saved as being "interested in"
	 */
	function load_saved_minopp() {
		// load cookie
		$interestedInList = "";
		if ($cookieData = $this->getWECcookie($GLOBALS['TSFE']->id)) {
			$interestedInList = $cookieData[1];
		}
		if (strlen($interestedInList)) {
			$savedList = strtr($interestedInList,"-",",");
			$this->savedMinOpps = explode(',',$savedList);
			$this->savedMinOpps = array_unique($this->savedMinOpps);
		}

		return $interestedInList;
	}

	/**
	 *	SAVE MINOPP: Saved interested in minOpps to cookies so will persist
	 *
	 *	@param	array	$saveSelected		the array of minOpp items to save
	 *	@return	void
	 */
	function save_minopp($saveSelected) {
		$savedOpps = "";

		// first add all existing saved ones
		if (!t3lib_div::_GP('showing_saved') && $this->savedMinOpps) { // if not showing saved, then add onto existing list
			foreach ($this->savedMinOpps as $curSaveItem)
				$savedOpps .= (!strlen($savedOpps)) ? $curSaveItem : '-'.$curSaveItem;
		}

		// build a string with all saved minopps
		if (is_array($saveSelected)) {
			foreach ($saveSelected as $thisItemUID)
				$savedOpps .= (!strlen($savedOpps)) ? $thisItemUID : '-'.$thisItemUID;
		}

		$saveUserData[0] = $savedOpps;
		$this->savedMinOpps = $savedOpps;
		$this->setWECcookie($GLOBALS['TSFE']->id, $saveUserData);
	}

	/**
	 *	GET SAVED MINOPP: Load from cookies & then get from db all interested in minOpps
	 *
	 *	@return	array	$interestedInArray		array of minOpps that were saved as being "interested in"
	 */
	function get_saved_minopp() {
		// load from cookies
		$savedList = $this->load_saved_minopp();
		if (!strlen($savedList)) return array();
		$savedList = strtr($savedList,"-",",");
		$this->savedMinOpps = explode(",",$savedList);
		$this->savedMinOpps = array_unique($this->savedMinOpps);
		$minOppArray = $this->get_by_uid($this->savedMinOpps);

		return $minOppArray;
	}

	/**
	 *	DISPLAY THE SIGN-UP FORM
	 *
	 *	Handle signing up and then processing the form (send by email and/or save in DB)
	 *
	 *	@return string		content that contains the display of messages
	 *=====================================================================================
	 */
	function display_signup_form() {
		$isErrors = ($this->formErrorText) ? true : false;
		$isCommitment = htmlspecialchars($this->postvars['makecommitment']);
		$isInterest  = htmlspecialchars($this->postvars['showinterest']);
		$minSignedUp = 0;
		if ($isCommitment > 0) 	$minSignedUp = $isCommitment;
		if ($isInterest > 0)	$minSignedUp = $isInterest;
		$subpartArray = array();

		// load the post/reply form
		$templateFormContent = $this->cObj->getSubpart($this->templateCode, '###TEMPLATE_SIGNUPFORM###');

		// fill in error text if have errors
		if ($isErrors) {
			$markerArray['###FORM_ERROR###'] = $this->formErrorText;
		}
		else {
			$subpartArray['###SHOW_ERRORS###'] = '';
		}
		$markerArray['###SUBMIT_BTN_TEXT###'] = $this->pi_getLL('form_submit_btn','Send Email');
		if ($isCommitment) {
			$markerArray['###FORM_HEADER###'] = $this->pi_getLL('form_header_commitment','Make A Commitment');
			$markerArray['###SIGNUP_TEXT###'] = $this->pi_getLL('form_commitment_text',	'You are committed to doing the following:');
			$markerArray['###HIDDEN_VARS###'] = '<input type="hidden" name="tx_wecservant[signupForm]" value="2"/>
												 <input type="hidden" name="tx_wecservant[makecommitment]" value="'.$isCommitment.'"/>';
		}
		else {
			$markerArray['###FORM_HEADER###'] = $this->pi_getLL('form_header_interest','Contact About Your Interest');
			$markerArray['###SIGNUP_TEXT###'] = $this->pi_getLL('form_interest_text','You are interested in doing the following:');
			$markerArray['###HIDDEN_VARS###'] = '<input type="hidden" name="tx_wecservant[signupForm]" value="1"/>
												 <input type="hidden" name="tx_wecservant[showinterest]" value="'.$isInterest.'"/>';
		}

		// build list of all ministries opps that signed up for...
		$minUID = "";
		$minListText = "";
		$minListHTML = "";
		if ($minSignedUp) { // signed up for one
			if ($minOpp = $this->get_by_uid($minSignedUp)) {
				$minUID = $minOpp['uid'];
				$minListText = $minOpp['name'];
				$minListHTML = '<div class="tx-wecservant-bold">'.$minListText.'</div>';
			}
		}
		else if  (($isCommitment == -2) || ($isInterest == -2)) { // custom servant opp
			$minListText = $this->pi_getLL('custom_ministry','Custom Ministry');
			$minListHTML = '<div class="tx-wecservant-bold">'.$minListText.'</div>';

			$minUID = -2;
		}
		else {	// add ALL minOpp(s) that signed up for
			$minArray = $this->get_saved_minopp();
			if (count($minArray)) {
				for ($i = 0; $i < count($minArray); $i++) {
					$minListText .= $minArray[$i]['name'];
					$minListHTML .= '<div class="tx-wecservant-bold">'.$minArray[$i]['name'].'</div>';
					if ((count($minArray) > 1) && ($i != (count($minArray) -1))) $minListText .= $this->pi_getLL('minlist_spacer',', ');
				}
			}
		}
		$markerArray['###SIGNUP_TEXT###'] .= $minListHTML;
		$markerArray['###CHOSEN_MINOPPS###'] = strip_tags($minListText);

		// add instructions if available...
		$markerArray['###SIGNUP_INSTRUCTIONS###'] = $this->config['signup_instructions'] ? $this->config['signup_instructions'] : $this->pi_getLL('signup_instructions','Please fill in the following and someone will contact you soon.');

		// fill in all form fields
		foreach ($this->db_showFormFields AS $req_field) {
			$markerArray['###FORM_'.strtoupper($req_field).'###'] = $this->pi_getLL('form_'.strtolower($req_field),$req_field);
		}

		$markerArray['###CANCEL_BTN###'] = '<input name="Cancel" type="button" onclick="javascript:history.go(-1)" value="'.$this->pi_getLL('cancel_btn',"Cancel").'"/>';

		// fill in hidden vars field
		$markerArray['###HIDDEN_VARS###'] .=
			'<input type="hidden" name="tx_wecservant[useruid]" value="'.$this->userID.'"/>
		    <input type="hidden" name="tx_wecservant[interestedUID]" value="'.$minUID.'"/>
		    <input type="hidden" name="tx_wecservant[chosen_minopps]" value="'.htmlspecialchars(strip_tags($minListText)).'"/>
		    <input type="hidden" name="no_cache" value="1"/>
		    ';

		// Pre-fill in data if logged in FE user and not errors
        if (!$this->postvars['name'] && $GLOBALS['TSFE']->loginUser) {
			$surname_pos = strpos($GLOBALS['TSFE']->fe_user->user['name'], ' ');
            $markerArray['###VALUE_NAME###'] = $GLOBALS['TSFE']->fe_user->user['name'];
	        $markerArray['###VALUE_EMAIL###'] = $GLOBALS['TSFE']->fe_user->user['email'];
	        $markerArray['###VALUE_ADDRESS###'] = $GLOBALS['TSFE']->fe_user->user['address'];
	        $markerArray['###VALUE_CITY###'] = $GLOBALS['TSFE']->fe_user->user['city'];
	        $markerArray['###VALUE_STATE###'] = $GLOBALS['TSFE']->fe_user->user['zone'];
	        $markerArray['###VALUE_ZIP###'] = $GLOBALS['TSFE']->fe_user->user['zip'];
	        $markerArray['###VALUE_PHONE###'] = $GLOBALS['TSFE']->fe_user->user['telephone'];
    	}
    	// if errors, then fill in any known values
    	if ($this->postvars && $this->formErrorText) {
			foreach ($this->postvars as $fieldName => $fieldValue) {
				if ($fieldName[strlen($fieldName)-1] == '+')
					$fieldName = substr($fieldName,0,strlen($fieldName)-1);
				$markerArray['###VALUE_'.strtoupper($fieldName).'###'] = $fieldValue;

				// for radio buttons...
				// note this fills in all values though because cannot determin if radio button or no
				$fVal = str_replace(' ','_',trim($fieldValue));
				if ($fVal) {
					$fVal = str_replace('/','-',$fVal); // because regex in substituteArrayMarker needs this fixed
					$markerArray['###VALUE_'.strtoupper($fieldName).'_'.strtoupper($fVal).'###'] = 'checked';
				}
			}
    	}

		$getvars = t3lib_div::_GET();
		unset($getvars['id']);
		unset($getvars['tx_wecservant[showinterest]']);
		unset($getvars['tx_wecservant[makecommitment]']);
		$markerArray['###ACTION_URL###'] = $this->getAbsoluteURL($this->id, $getvars);

		//
		// MARK ALL REQUIRED FIELDS
		// put a space for ALL fields (this is default)
		//--------------------------------------------------------------------------
		foreach ($this->db_showFormFields AS $req_field) {
			$markerArray['###FORM_'.strtoupper($req_field).'_REQUIRED###'] = '&nbsp;';
		}

		// then mark all the required fields
		if (is_array($this->config['required_formfields']) && count($this->config['required_formfields']) > 0) {
			foreach ($this->config['required_formfields'] AS $req_field) {
				$markerArray['###FORM_'.strtoupper($req_field).'_REQUIRED###'] = $this->pi_getLL('required_text_marker','*');
			}
			$markerArray['###SHOW_REQUIRED_TEXT###'] = $this->pi_getLL('show_required_text', '* = required field');
		}

		//
		// TURN OFF ALL NON-DISPLAY FIELDS
		//--------------------------------------------------------------------------
		if (is_array($this->config['display_formfields']) && count($this->config['display_formfields'])>0) {
			$fieldArray = $this->db_showFormFields;
			for ($i = 0; $i < count($fieldArray); $i++)	{
				$fieldFound = false;
				foreach ($this->config['display_formfields'] AS $display_field) {
					if (!strcasecmp($display_field,$fieldArray[$i])) {
						$fieldFound = true;
						break;
					}
				}
				// the field was not in display_list, so turn it off
				if (!$fieldFound) {
					$dMarker = '###SHOW_'.strtoupper($fieldArray[$i]).'###';
					$templateFormContent = $this->cObj->substituteSubpart($templateFormContent,$dMarker,'');
				}
			}
		}
		else {
			// turn off attachment (because extra field...only add it if added)
			$templateFormContent = $this->cObj->substituteSubpart($templateFormContent,'###SHOW_ATTACHMENT###','');
		}

		// then do the substitution with the template
		$formContent = $this->cObj->substituteMarkerArrayCached($templateFormContent, $markerArray, $subpartArray, array());

		// clear out any empty template fields
		$formContent = preg_replace('/###.*?###/', '', $formContent);

		return $formContent;
	}

	/**
	 *==================================================================================
	 *   CHECK FOR VALID FIELDS
	 *
	 *	 Check the request form to make sure all fields filled out & if any errors in certain fields
	 *
	 *	@return string		return either 0 or a string containing the error messages
	 *==================================================================================
	 */
	function check_for_valid_fields() {
		$error = "";
		if (is_array($this->config['required_formfields']) && count($this->config['required_formfields']) > 0) {
			$whichOne = 0;
			foreach ($this->config['required_formfields'] AS $req_field) {
				if (empty($this->postvars[$req_field])) {
					$error .= '<li> "'.ucfirst($this->pi_getLL('form_'.$req_field.'_label')).'" '.$this->pi_getLL('form_required_blank') . '</li>';
				}
			}
		}
		// check if optional one is required
		foreach ($this->postvars as $fieldName => $fieldValue) {
			$lastCh = substr($fieldName,strlen($fieldName)-1,1);
			if ($lastCh == '+') { // required marker
				$shortFieldName = substr($fieldName,0,strlen($fieldName)-1);
				$altFieldValue = 0;
				if ($this->postvars[$shortFieldName]) {
					$altFieldValue = $this->postvars[$shortFieldName];
				}
				if (!$altFieldValue && (!strlen($fieldValue) || empty($fieldValue))) {
					$fieldName = substr($fieldName,0,strlen($fieldName)-1);
					$fieldName = strtr($fieldName,'_',' ');
					$error .= '<li> "'.ucfirst($fieldName).'" '.$this->pi_getLL('form_required_blank') .'</li>';
				}
			}
		}

		if (!empty($this->postvars['email'])) {
			if (t3lib_div::validEmail($this->postvars['email']) == false) {
				$error .= '<li> '.$this->pi_getLL('form_email_label')." ".$this->pi_getLL('form_invalid_field')."</li>";
			}
		}

		if (!strlen($error))
			return 0;
		else
			return '<ul>'.$error.'</ul>';
	}

	/**
	 *	SEND OUT THE SIGN-UP FORM INFORMATION
	 *
	 *	Send the form to the contact person(s) for each ministry and/or any administrative contact(s)
	 *
	 *	@param	array	$formvars	properties/values from the submitted form
	 *	@return integer		the number of e-mails that were sent out
	 *=====================================================================================
	 */
	function send_signup_email($formvars)	{
		$sentMail = 0;

		// check to make sure all the fields are valid
		$this->formErrorText = 0;
		if ($errStr = $this->check_for_valid_fields()) {
			$this->formErrorText = $errStr;
			return 0;
		}

		$isCommitment = ($formvars['signupForm'] == 2);

		// Grab all the ministry opps interested in (and sort by contact so we can group together)
		//----------------------------------------------------------------------------------------
		$ministryInfo = array();
		$minID = $formvars['interestedUID'];

		if ($minID > 0 && (strpos($minID,',') == false)) {	// add one selected
			array_push($ministryInfo, $this->get_by_uid($minID));
		}
		else if ($minID == -2) { // custom...
			;
		}
		else	// add multiple ones saved
			$ministryInfo = $this->get_saved_minopp();

		if (($minID != -2) && (count($ministryInfo) < 1)) {
			$this->formErrorText = $this->pi_getLL('email_none_to_send','You did not select any to send.');
			return 0;
		}
		// Now sort all the ministryOpps by contacts
		//----------------------------------------------
		if (count($ministryInfo) > 1) {
			foreach ($ministryInfo as $key => $row) {
				$name_val[$key] = $row['name'];
				$contact_val[$key] = $row['contact_uid'];
			}
			array_multisort($contact_val, SORT_ASC, $ministryInfo);
		}

		// now grab all the contacts
		//----------------------------------------------
		$contactEmailList = array();
		foreach ($ministryInfo as $thisMin) {
			foreach ($this->contactList as $thisContact) {
				if ($thisMin['contact_uid'] == $thisContact['uid']) {
					array_push($contactEmailList, $thisContact);
					break;
				}
			}
			// we want to track each opportunity they sign up for...
			$this->save_tracking($formvars['chosen_minopps'],$formvars['name'],$thisContact['uid'],$formvars);
		}
		// make contact's unique
		$contactEmailList = array_unique($contactEmailList);

		// if custom ministry, there is no contacts, so let's fake contact with either admin_email locallang or notify_email constant
		if (($minID == -2) || !count($contactEmailList)) {
			$adminEmail = strlen($this->pi_getLL('admin_email')) ? $this->pi_getLL('admin_email') : $this->config['notify_email'];

			// we want to track if they sign up for a custom ministry
			$this->save_tracking($formvars['chosen_minopps'],$formvars['name'],0,$formvars);
			if ($adminEmail)
				array_push($contactEmailList, array('emailVal' => $adminEmail));
		}

		// Set the "from" field for the email
		// == it should be the contact name set in Flexform (for spam filter whitelisting)
		//    but if that is not set, it will be the person who signed up
		//    if they do not give an email then it will be the default blank emailFrom
		//--------------------------------------------------------------------------------
		$emailFrom = "From: ";
		if ($this->config['contact_name'])	// contact person from FlexForm
			$emailFrom .= $this->config['contact_name']." <".$this->config['contact_email'].">";
		else if ($this->config['contact_email']) // or the email
			$emailFrom .= "<".$this->config['contact_email'].">";
		else if (!$formvars['email'])	// if no
			$emailFrom .= "<".$this->pi_getLL('blankEmailFrom','<nobody@nowwhere.com>').">";
			// will be set to the servant email (if available)
		else if ($formvars['name'])
			$emailFrom .= $formvars['name']." <".$formvars['email'].">";
		else
			$emailFrom = "<".$formvars['email'].">";

		// Then grab each contact info and send an email...
		//---------------------------------------------------------
		//
		foreach ($contactEmailList as $contact) {
			$emailBody = "";
//$contact['name'] = 0;
			// if no email for  contact,then figure out who to send to
			if (!$contact['emailVal']) {
				// send to the admin (if available)...
				$adminEmail = strlen($this->pi_getLL('admin_email')) ? $this->pi_getLL('admin_email') : $this->config['notify_email'];

				// if there is no admin email and no contact email, we cannot send this...
				if (!strlen($adminEmail)) {
					$this->formErrorText = $this->pi_getLL('email_failure2','We could not send your email [code=no_contacts]');
					return 0;
				}
				// send to admin email
				else {
					$contact['emailVal'] = $adminEmail;
					$contact['name'] = 0; // so admin email can have multiple emails
					$emailBody = $this->pi_getLL('no_valid_contact_email','No valid contact email -- forwarding to admin')."\n\n";
				}
			}

			// EMAIL 1: compose the header info
			//---------------------------------------------------
			$thisDescription = $this->config['title'] ? $this->config['title'] : $this->pi_getLL('title_description','Servant Commitment');
			$emailSubject = $this->pi_getLL('email_subject','Servant Signup Notification');

			// EMAIL 2: create the email message content
			//----------------------------------------------------
			$emailBody .= $emailSubject.$this->pi_getLL('form_email_at',' at: ').$this->getAbsoluteURL($this->id)."\n\n";

			if ($this->config['signup_emailHeader'])
				$emailBody .= $this->config['signup_emailHeader'] . "\n";

			$emailBody .= $this->pi_getLL('form_name_label','Name: ').': '.stripslashes($formvars['name'])."\n";
			if ($formvars['email'])   $emailBody .= $this->pi_getLL('form_email_label','Email: ').': '.stripslashes($formvars['email'])."\n";
			if ($formvars['phone'])   $emailBody .= $this->pi_getLL('form_phone_label','Phone: ').': '.stripslashes($formvars['phone'])."\n";
			if ($formvars['address']) $emailBody .= $this->pi_getLL('form_address_label','Address: ').': '.stripslashes($formvars['address'])."\n";
			if ($formvars['city']) 	  $emailBody .= $this->pi_getLL('form_city_label','City: ').': '.stripslashes($formvars['city'])."\n";
			if ($formvars['state'])   $emailBody .= $this->pi_getLL('form_state_label','State: ').': '.stripslashes($formvars['state'])."\n";
			if ($formvars['zip']) 	  $emailBody .= $this->pi_getLL('form_zip_label','Zip: ').': '.stripslashes($formvars['zip'])."\n\n";
			if ($formvars['message']) $emailBody .= $this->pi_getLL('form_message_label','Message: ').': '.stripslashes($formvars['message'])."\n";
			if ($formvars['misc_description']) $emailBody .= $this->pi_getLL('form_misc_description_label','Misc: ').': '.stripslashes($formvars['misc_description'])."\n";

			if ($formvars['chosen_minopps']) {
				$emailBody .= $isCommitment ? $this->pi_getLL('email_makeCommitment','Is Committed To: ') : $this->pi_getLL('email_interestedIn','Is Interested In: ');
				$emailBody .= html_entity_decode($formvars['chosen_minopps'],ENT_COMPAT,$GLOBALS['TSFE']->renderCharset);
				unset($formvars['chosen_minopps']); // so only prints once
			}

			$emailBody .= "\n";

			// now add any extra formvars that are not in list
			$extraFound = 0;
			$skipFields = $this->db_showFormFields;
			array_push($skipFields,"signupForm","useruid","interestedUID");  // add internal ones not in showForm...want to skip these
			foreach ($formvars as $fvKey => $fvValue) {
				$found = false;
				foreach ($skipFields as $formF) {
					if ($fvKey == $formF) {
						$found = true; break;
					}
				}
				// not on list...so add...
				if (!$found) {
					$newKey = $fvKey;
					if ($fvKey[strlen($fvKey)-1] == '+')
						$newKey = substr($fvKey,0,strlen($fvKey)-1);

					// gets rid of duplicate (and hidden) radio buttons that have no value
					if (!$fvValue && ($newKey != $fvKey))
						continue;

					// put a header in the email for all additional/extra fields
					if ($extraFound == 0)
						$emailBody .= "\n".$this->pi_getLL('email_etc_label','=== Other Info ===')."\n";

					// add the field name and the value. replace all ' ' with '_'.
					$emailBody .= ucfirst(str_replace("_"," ",$newKey)).': '.stripslashes($fvValue)."\n";
					$extraFound++;
				}
			}

			$emailBody .= "\n\n";
			if ($this->config['signup_emailFooter']) {
				$emailBody .= $this->config['signup_emailFooter'];
			}

			// EMAIL 3: Now set the email contact info
			//-------------------------------------------------------------------------

			// Add any attachments if they are here
			$tmp_name = $_FILES['wecservant_attachment']['tmp_name'];
			if ($tmp_name && file_exists($tmp_name)) {
				$file_type = $_FILES['wecservant_attachment']['type'];
			   	$file_name = $_FILES['wecservant_attachment']['name'];
   			   	$file_size = $_FILES['wecservant_attachment']['size'];
   			   	if (is_uploaded_file($tmp_name)) {
   			   		$file = fopen($tmp_name,'rb');
   			   		$file_data = fread($file,filesize($tmp_name));
  			   		fclose($file);
  			   		$file_data = chunk_split(base64_encode($file_data));
   			   	}
  			  	$mime_boundary="==Multipart_Boundary_x".md5(mt_rand())."x";
		      	$headers = "From: ".$emailFrom."\r\n" .
			         "MIME-Version: 1.0\r\n" .
			         "Content-Type: multipart/mixed;\r\n" .
			         " boundary=\"{$mime_boundary}\"";
		      	$emailBody = "This is a multi-part message in MIME format.\n\n" .
			         "--{$mime_boundary}\n" .
			         "Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
			         "Content-Transfer-Encoding: 7bit\n\n" .
		        $emailBody . "\n\n";

		      	// now we'll insert a boundary to indicate we're starting the attachment
		      	// we have to specify the content type, file name, and disposition as
		      	// an attachment, then add the file content and set another boundary to
		      	// indicate that the end of the file has been reached
		      	$emailBody .= "--{$mime_boundary}\n" .
			         "Content-Type: {$file_type};\n" .
			         " name=\"{$file_name}\"\n" .
			         "Content-Transfer-Encoding: base64\n\n" .
			         $file_data . "\n\n" .
			         "--{$mime_boundary}--\n";
   			}
   			else {
				$headers = $emailFrom;
			}

			// EMAIL 4: Send out the email to the contact person
			//-------------------------------------------------------------------------
			if ($contact['name'])
				$toName = $contact['name']." <".$contact['emailVal'].">";
			else
				$toName = $contact['emailVal'];
			$sendEmailBody = $emailBody;

			// if mail successfully sent, then count it.
			$toName = trim($toName);
			if ((strlen($toName) > 0) && mail($toName, $emailSubject, $sendEmailBody, $headers))
				$sentMail++;

			// EMAIL 5: If notify_email set, then send email there too
			//-------------------------------------------------------------------------
			if (($toNotifyName = trim($this->config['notify_email'])) && strcmp($toNotifyName,$toName)) { // toName could be multiple emails separated by commas
				// add contact info for notify...as long as not custom
				if ($minID != -2) {
					$contactInfoText = $this->pi_getLL('email_contact_label','=== Ministry Contact Person ===');
					$contactInfoText .= "\n";
					if ($contact['name']) $contactInfoText .= $this->pi_getLL('form_name_label','Name:'). ': '.$contact['name']."\n";
					$contactInfoText .= $this->pi_getLL('form_email_label','Email:'). ': '.$contact['emailVal']."\n";
					$sendEmailBody .= $contactInfoText;
				}
				// add header on subject
				$emailSubject = $this->pi_getLL('email_subject_notification','NOTIFICATION: ').$emailSubject;
				// then send it out
				mail($toNotifyName, $emailSubject, $sendEmailBody, $headers);
				// if no mail sent, but this was sent, then count this
				if (!strlen($toName) && !$sentMail)
					$sentMail++;
			}
		}

		if ($sentMail)
			$this->responseMsg = $this->pi_getLL('email_success','Your email has been sent. You should receive a response in a few days.');
		else {
			$this->formErrorText = $this->pi_getLL('email_failure','Your email could not be sent -- there appears to be a system error. Please try again later.');
		}

		return $sentMail;
	}

	/**
	 * DISPLAY SIGNUP CONFIRM
	 *
	 *		displays signup confirmation with what signed up for
	 * @param	array	$formvars	form variables passed in
	 * @return	string	content generated for the preview
	 */
	function display_signup_confirm($formvars) {
		$signupText = $this->pi_getLL('signup_thanks','<h4>Thank you for signing up. You will soon be contacted by the ministry area.</h4>');
		$printBtnText = '<a href="'.$this->getAbsoluteURL($this->id).'">'.$this->pi_getLL('print_button_text2','Click here to exit this page').'</a>';
		$signupText .= $this->add_print_button($printBtnText);
		$signupText .= $this->pi_getLL('signup_header','<h1>#NAME# Servant Signup Information</h1>');
		$signupText = str_replace('#NAME#', stripslashes($formvars['name']), $signupText);

		// show name & contact info
		$signupText .= $this->pi_getLL('contact_header','<h2>Your Contact Info</h2>');
		$signupText .= $this->pi_getLL('form_name_label','Name: ').': '.stripslashes($formvars['name'])."<br>";
		if ($formvars['email'])   $signupText .= $this->pi_getLL('form_email_label','Email: ').': '.stripslashes($formvars['email'])."<br>";
		if ($formvars['phone'])   $signupText .= $this->pi_getLL('form_phone_label','Phone: ').': '.stripslashes($formvars['phone'])."<br>";
		if ($formvars['address']) $signupText .= $this->pi_getLL('form_address_label','Address: ').': '.stripslashes($formvars['address'])."<br>";
		if ($formvars['city']) 	  $signupText .= $this->pi_getLL('form_city_label','City: ').': '.stripslashes($formvars['city']).",&nbsp;&nbsp;";
		if ($formvars['state'])   $signupText .= $this->pi_getLL('form_state_label','State: ').': '.stripslashes($formvars['state']).",&nbsp;&nbsp;";
		if ($formvars['zip']) 	  $signupText .= $this->pi_getLL('form_zip_label','Zip: ').': '.stripslashes($formvars['zip'])."<br><br>";

		// show what signed up for
		$signupText .= $this->pi_getLL('servantopp_header','<h2>Your Servant Opportunity</h2>');

		if (($v = $formvars['interestedUID']) && ($v > 0)) {
			$savedShowAll = $this->config['show_all_entry'];
			$savedPrintSave = t3lib_div::_GP('printsaved');
			t3lib_div::_GETset(true,'printsaved');
			$this->config['show_all_entry'] = true;

			// grab the ministry opportunity info
			$oppvars = $this->get_by_uid($v);
			$minArray[0] = $oppvars;
			$signupText  .= $this->show_items($minArray);

			$this->config['show_all_entry'] = $savedShowAll;
			t3lib_div::_GETset($savedPrintSave, 'printsaved');
		}
		else if ($v == -2) { // custom ministry...
			$signupText .= $this->pi_getLL('custom_ministry','Custom Ministry');
			if ($v2 = $formvars['serving_opportunity_to_investigate'])
				$signupText .= "<br>".$v2."<br>";
			if ($v3 = $formvars['other_comments']) {
				$signupText .= $this->pi_getLL('other_comments','Other Comments');
				$signupText .= "<br>".$v3."<br>";
			}
		}
		else { // multiple ministry signup
			$signupText .= "<br>".$formvars['chosen_minopps']."<br>";
			if ($v3 = $formvars['other_comments']) {
				$signupText .= $this->pi_getLL('other_comments','Other Comments');
				$signupText .= "<br>".$v3."<br>";
			}
		}
		$subpartMarkerArray = array();
		$subpartMarkerArray['###SHOW_HEADER###'] = '';
		$subpartMarkerArray['###SHOW_FIND_MINISTRIES###'] = '';
		$subpartMarkerArray['###SHOW_FIND_SKILLS###'] = '';
		$markerArray['###DISPLAY_RESULTS###'] = $signupText;

		// now read in the part of the template file with the PAGE subtemplatename
		$template = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_PAGE###');

		// then substitute all the markers in the template into appropriate places
		$content = $this->cObj->substituteMarkerArrayCached($template,$markerArray,$subpartMarkerArray,array());

		// clear out any empty template fields
		$content = preg_replace('/###.*?###/', '', $content);

		return $content;

	}

	/**
	 * DISPLAY PREVIEW
	 *
	 *		Will show preview of a given Servant Matcher page. Set on a "per ministry" basis.
	 *
	 * @return	string		$content:	content generated for the preview
	 */
	function display_preview() {
		$previewContent = "";
		$thisMinUID = $this->config['preview_ministry'];

		// grab the items for the given ministry
		$minOppArray = $this->get_by_ministry($thisMinUID);

		// now format the top #N in preview template
		$preview_template = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_PREVIEW###');
		$itemContent = "";
		for ($i = 0; $i < count($minOppArray) && $i < $this->config['preview_howmany']; $i++) {
			if ($minOppArray[$i]['name']) {
				$preview_item = $this->cObj->getSubpart($this->templateCode,'###PREVIEW_ITEM###');
				$markerArray['###NAME###'] = $minOppArray[$i]['name'];
				// add description, but only #n length
				$descrStr = substr($minOppArray[$i]['description'],0,$this->config['preview_description_length']);
				$k = strrpos($descrStr," ");
				$descrStr = substr($descrStr,0,$k) . $this->pi_getLL('preview_description_etc','...');
				$markerArray['###DESCRIPTION###'] = $descrStr;

				$itemContent .= $this->cObj->substituteMarkerArrayCached($preview_item,$markerArray,array(), array());
			}
		}
		// fill in all the marker fields
		$pMarkerArray['###PREVIEW_TITLE###'] = $this->config['preview_title'] ? $this->config['preview_title'] : $this->pi_getLL('preview_title','What Opportunities Are Available?');
		$servantDataID = $this->config['previewdata_PID'] ? $this->config['previewdata_PID'] : $this->pid_list;
		$servantLinkID = $this->config['previewlink_PID'];
		$btnStr = $this->config['preview_button'] ? $this->config['preview_button'] : $this->pi_getLL('preview_btn','Find out more');
		if ($servantLinkID)
			$pMarkerArray['###GO_BTN###'] = '<a class="button" href="' . $this->getAbsoluteURL($servantLinkID,"") . '"><span class="label viewIcon">' . $btnStr . '</span></a>';
		$pMarkerArray['###PREVIEW_ITEM_LIST###'] = $itemContent;

		// generate the preview content
		$previewContent = $this->cObj->substituteMarkerArrayCached($preview_template,$pMarkerArray,array(), array());

		// clear out any empty template marker fields
		$previewContent = preg_replace('/###.*?###/', '', $previewContent);

		return $previewContent;
	}


	/**
	 * Save Tracking Information
	 *
	 *		Will save to database tracking information of signups
	 *
	 * @param   integer		$minOppUID:	ministry opp uid signed up for
	 * @param	string		$userName:	user who signed up
	 * @param   integer		$contactUID: contact person uid
	 * @param	array		$inputArray: input form fields [name / data ]
	 * @return	integer					uid of record inserted (0 = failed)
	 */
	function save_tracking($minOpp, $userName, $contactUID, $inputArray) {
		$newRec['crdate'] = mktime();
		$newRec['name'] = $userName;
		$newRec['minopp'] = $minOpp;
		$newRec['contact_uid'] = $contactUID;

		// build input from inputArray
		$inputText = "";
		unset($inputArray['signupForm']);
		unset($inputArray['interestedUID']);
		foreach ($inputArray as $key => $val) {
			$key = str_replace('+','',$key);
			$key = str_replace('_',' ',$key);
			$inputText .= $key . ": ".$val."\n";
		}
		$newRec['input'] = $inputText;
		$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_wecservant_tracking', $newRec);

		$insertWorked = mysql_insert_id();
		if (mysql_error())	t3lib_div::debug(array(mysql_error(),$newRec));

		return $insertWorked;
	}


	/**
	 * Add a print button. Just a function to re-use code
	 *
	 * @param	string	$printStr	the print button text
	 * @return	string	print button code
	 */
	function add_print_button($printStr = '') {
		if (!$printStr)
			$printStr = $this->pi_getLL('print_button_text','Click your browser back button after print');

		return '
				<form>
				<div id="printform" class="centered">
					<input name="Print" value="Print" type="button" onclick="printItems();document.getElementById(\'printform\').style.display=\'none\';window.print();return false;"/>
					<br>'.$printStr.'
				</div>
				</form>
				';
	}

	/**
	* Getting the full URL (ie. http://www.host.com/... to the given ID with all needed params
	* This function handles cross-site (on same server) links
	*
	* @param integer  $id: Page ID
	* @param string   $urlParameters: array of parameters to include in the url (i.e., "$urlParameters['action'] = 4" would append "&action=4")
	* @param boolean  $forceFullURL: if should create a full URL or just a relative one (http://www.site.com/test/... vs. /test/)
	* @return string  $url: URL
	*/
	function getAbsoluteURL($id, $extraParameters = '', $forceFullURL = FALSE) {
		// get the page url from TYPO system (realURL or simulated or not)
		$pageURL = $this->pi_getPageLink($id, '', $extraParameters);

		// if did not cross page boundaries, then generate url from info
		if ((strpos($pageURL,"http") === FALSE) || $forceFullURL) {
			// use the baseURL if given
			if ($GLOBALS["TSFE"]->config['config']['baseURL']) {
				$hostURL = $GLOBALS["TSFE"]->config['config']['baseURL'];
			}
			// otherwise generate URL from PHP var
			else {
				$hostURL = (t3lib_div::getIndpEnv('TYPO3_SSL') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/';
			}
			$absURL =  $hostURL . $pageURL;
		}
		// crosses boundaries (likely different url on same server)
		else {
			$absURL = $pageURL;
		}

		//
		$absURL = str_replace('&','&amp;', $absURL);
		return $absURL;
	}

	/**
	 * getWECcookie -- Get the page/extension data from the WEC Cookie
	 *
	 * @param	integer		$pid: Page ID
	 * @return	array		$data: data found in the cookie for the pid
	 */
	function getWECcookie($pid) {
		$data = 0;
		if (isset($_COOKIE["WEC"]))	{
			$cData = explode('|', $_COOKIE["WEC"]);
			for ($i = 0; $i < count($cData); $i++) {
				$thisCookie = explode('_', $cData[$i]);
				if ((count($thisCookie) > 1) && ($thisCookie[0] == $pid)) {
					$data = $thisCookie;
					break;
				}
			}
		}
		return $data;
	}

	/**
	 * setWECcookie -- Store data for the page/extension in the WEC Cookie
	 *
	 * @param	integer		$pid: Page ID
	 * @param	array		$data: array of data to store for this page
	 * @return	void
	 */
	function setWECcookie($pid,$data) {
		// get current cookie, then append the data
		$cookieFound = 0;
		$cookieCount = 0;
		$newCookie = "";
		if (isset($_COOKIE["WEC"]))	{
			// parse out all the cookies
			$cData = explode('|',$_COOKIE["WEC"]);
			$cookieCount = count($cData);
			for ($i = 0; $i < $cookieCount; $i++) {
				$curCookies[$i] = explode('_', $cData[$i]);
				if ((count($curCookies[$i]) > 1) && ($curCookies[$i][0] == $pid)) {
					// fill in new data
					for ($j = 0; $j < count($data); $j++)
						$curCookies[$i][$j+1] = $data[$j];
					$cookieFound = 1;
					break;
				}
			}
		}
		if (!$cookieFound) {
			$curCookies[$cookieCount][0] = $pid;
			for ($i = 0; $i < count($data); $i++)
				$curCookies[$cookieCount][$i+1] = $data[$i];
		}

		// build the new cookie
		for ($i = 0; $i < count($curCookies); $i++) {
			for ($j = 0; $j < count($curCookies[$i]); $j++)	{
				$newCookie .= ($j != (count($curCookies[$i]) -1)) ? $curCookies[$i][$j]."_" : $curCookies[$i][$j];
			}
			if ($i < (count($curCookies)-1)) $newCookie .= "|";
		}
		setcookie("WEC", $newCookie, time()+(60*60*24*365), '/');
	}

	/**
	 * Get the Groups by Type
	 *
	 * @param	integer 	$groupTypeID group type
	 * @param	string		$sortOrder	field(s) to sort by
	 * @return	array		all found groups in an array
	 */
	function get_groups_by_type($groupTypeID, $sortOrder="") {
//		if (strlen($sortOrder) > 0) $sortOrder = ' ORDER BY '.$sortOrder;

		// grab all the groups that match groupTypeStr. Only get groups that offer opportunities
		//
		$whatFields = 'DISTINCT ' .  $this->servantGroupTable . '.*';
		$fromDB  = $this->servantGroupTable.',tx_wecservant_minopp';
		$where   = $this->servantGroupTable . '.wecgroup_type=' . $groupTypeID . ' AND ' .
		    $this->servantGroupTable . '.hidden=0 AND ' .  $this->servantGroupTable . '.deleted=0 AND
			tx_wecservant_minopp.ministry_uid=' .  $this->servantGroupTable . '.uid AND tx_wecservant_minopp.pid IN (' . $this->pid_list . ') ';
		$where  .= $this->cObj->enableFields('tx_wecservant_minopp');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($whatFields,$fromDB,$where,"",$sortOrder,"");
		if (mysql_error())	t3lib_div::debug(array(mysql_error(),$query));
		if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))
			return 0;

		$grpArray = array();
 		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
 			$this->wecgroup_type = $row['wecgroup_type']; // save for admin only
			array_push($grpArray, $row);
 		}

		return $grpArray;
	}


	/**
	 *	Administrator Menu
	 *
	 *	Create or edit an opportunity including adding or updating a ministry or contact as needed
	 *
	 *	@return	string	$content	content of menu
	 */
	function admin_menu() {
		// grab the admin menu
		$admin_template = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_ADMINMENU###');
		// setup vars
		$subpartMarker = array();
		$thisPri = ($this->postvars) ? $this->postvars['priority'] : 1;
		$thisUID = 0;

		$goBackURL = $this->getAbsoluteURL($this->id);
		$markerArray['###ADMIN_BACK_BTN###'] = '<a class="button" href="' . $goBackURL . '"><span class="label prevIcon">' . $this->pi_getLL('admin_back_btn','back') . '</span></a>';

		// if stats, then just jump to stats menu...
		if ($v = t3lib_div::_GP('adminstats')) {
			return $this->admin_stats_menu($v);
		}
		// build ministry dropdown
		$selMinistryStr = '<select name="tx_wecservant[ministry_uid]" size="1">';
		foreach ($this->ministryList as $min) {
			if ($this->adminMinistries) {
				// go through and only add if part of ministry list
				$found = false;
				foreach ($this->adminMinistries as $thisMin) {
					if ($min['uid'] == $thisMin)
						$found = true;
				}
				if (!$found)
					continue;
			}
			$isSelectedStr = ($this->postvars['ministry_uid'] == $min["uid"]) ? " selected" : "";
		 	$selMinistryOptStr .= '<option value="'.$min["uid"].'" '.$isSelectedStr.'>'.$min["title"].'</option>';
		}
		$selMinistryStr .= $selMinistryOptStr;
		$selMinistryStr .= '</select>';

		// build contact dropdown
		$selContactStr = '<select name="tx_wecservant[contact_uid]" size="1">';
		foreach ($this->contactList as $con) {
			if ($con["name"]) {
				$isSelectedStr = ($this->postvars['contact_uid'] == $con["uid"]) ? " selected" : "";
			 	$selContactOptStr .= '<option value="'.$con["uid"].'" '.$isSelectedStr.'>'.$con["name"].'</option>';
			}
		}
		$selContactStr .= $selContactOptStr;
		$selContactStr .= '</select>';

		$adminvars['admin'] = 1;
		$markerArray['###ACTION_URL###'] = $this->getAbsoluteURL($this->id,$adminvars);
		$markerArray['###MINISTRY_LIST_OPTIONS###'] = $selMinistryOptStr;
		$markerArray['###CONTACT_LIST_OPTIONS###'] = $selContactOptStr;
		$markerArray['###HIDDEN_VARS###'] = "";
		if ($this->responseMsg) {
			$markerArray['###ADMIN_RESPONSE_MESSAGE###'] = $this->responseMsg;
		}
		else {
			$subpartMarker['###SHOW_RESPONSE_MSG###'] = '';
		}
		// add special javascript code for whole page...
		$markerArray['###JAVASCRIPT_CODE###'] = '
			<script language="JavaScript" type="text/javascript">
				//<![CDATA[
				function toggleSection(whatToToggle)
				{
					toggleWhat = document.getElementById(whatToToggle);
					if (!toggleWhat) return false;
					if (toggleWhat.style.display == "none")
						toggleWhat.style.display = "block";
					else
						toggleWhat.style.display = "none";
				}
				//]]>
			</script>';
		if ($this->isAdministrator) {
			if (!$this->adminMinistries)
				$adminFindMenu = $GLOBALS['TSFE']->cObj->getSubpart($admin_template, '###ADMIN_FIND_MENU###'); // select ministries, contacts, last X
			else
				$adminFindMenu = $GLOBALS['TSFE']->cObj->getSubpart($admin_template, '###ADMIN_FIND_MENU2###'); // just select ministries
			$adminNewMenu  = $GLOBALS['TSFE']->cObj->getSubpart($admin_template, '###ADMIN_NEW_MENU###');
			$adminVar['adminstats'] = 1;
			$markerArray['###ADMIN_STATS###'] = '<a class="button" href="'.$this->getAbsoluteURL($this->id,$adminVar).'"><span class="label">Admin Stats</span></a>';
			$adminContactForm  = $GLOBALS['TSFE']->cObj->getSubpart($admin_template, '###ADMIN_CONTACT_FORM###');
			if (!$this->adminMinistries)
				$adminMinistryForm  = $GLOBALS['TSFE']->cObj->getSubpart($admin_template, '###ADMIN_MINISTRY_FORM###');
		}

		if (!$this->isAdministrator || $this->adminMinistries) {
			$listMine['adminMyList'] = $this->userID;
			$findURL = $this->getAbsoluteURL($this->id,$listMine);
			$adminNewMenu = '<div style="clear:left;width:100%; height:24px;"><a href="'.$findURL.'">'.$this->pi_getLL('admin_my_opps','Show My Servant Opportunities').'</a></div>';
			if (!$this->isAdministrator)
				$selContactStr = $GLOBALS['TSFE']->fe_user->user['name'].'<input type=hidden name="tx_wecservant[contact_uid]" value="'.$this->userID.'"/>';
		}
		$adminOppForm  = $GLOBALS['TSFE']->cObj->getSubpart($admin_template, '###ADMIN_OPP_FORM###');
		$adminItem 	   = $GLOBALS['TSFE']->cObj->getSubpart($admin_template, '###ADMIN_OPP_ITEM###');
		$adminHeader   = $GLOBALS['TSFE']->cObj->getSubpart($admin_template, '###ADMIN_HEADER###');
		$adminFooter   = $GLOBALS['TSFE']->cObj->getSubpart($admin_template, '###ADMIN_FOOTER###');

		$markerArray['###ADMIN_MENU_TITLE###'] = $this->pi_getLL('admin_menu_title','Servant Matcher Admin Menu');

		// fill in the contact form sub-section
		if ($adminContactForm) {
			$adminContactFields = $GLOBALS['TSFE']->cObj->getSubpart($adminContactForm,'###CONTACT_FORM_FIELDS###');
			$contactFormFields = array('contact_name','address','city','zone','zip','telephone','email');
			foreach ($contactFormFields as $theField)
				$cMarkerArray['###FORM_'.strtoupper($theField).'###'] = $this->pi_getLL('adminform_'.strtolower($theField));
			$adminContactFields =  $this->cObj->substituteMarkerArrayCached($adminContactFields,$cMarkerArray,$subpartMarker, array());
		}

		// fill in the ministry form sub-section
		if ($adminMinistryForm) {
			$adminMinistryFields = $GLOBALS['TSFE']->cObj->getSubpart($adminMinistryForm,'###MINISTRY_FORM_FIELDS###');
			$minFormFields = array('ministry_name','ministry_description','group_type','primary_contact');
			foreach ($minFormFields as $theField)
				$mMarkerArray['###FORM_'.strtoupper($theField).'###'] = $this->pi_getLL('adminform_'.strtolower($theField));
			$adminMinistryFields =  $this->cObj->substituteMarkerArrayCached($adminMinistryFields,$mMarkerArray,$subpartMarker, array());
		}

		// start the content with the header
		$content = $adminHeader;

		// add find menu & new menu
		$content .= $adminFindMenu . $adminNewMenu;

		if ($this->isAdministrator) {
		 	if (!$this->adminMinistries) {
				$markerArray['###TOGGLE_NEW_MINISTRY###'] 	= '<div id="createnewministry" class="hidden">'.$adminMinistryFields.'</div>';
				$markerArray['###ADD_NEW_MINISTRY###'] 		= '<a class="button smallButton" href="#" onclick="toggleSection(\'createnewministry\');return false;"><span class="label" style="width:auto;">Create new ministry</span></a>';
			}
			$markerArray['###TOGGLE_NEW_CONTACT###']  	= '<div id="createnewcontact" class="hidden">'.$adminContactFields.'</div>';
			$markerArray['###ADD_NEW_CONTACT###'] 		= '<a class="button smallButton" href="#" onclick="toggleSection(\'createnewcontact\');return false;"><span class="label" style="width:auto;">Create new contact</span></a>';
		}

		$formContent = ""; // save in separate variable so can add AFTER results
		// MINISTRY OPP FORM
		$formFields = array('name','ministry','contact','description','location','times_needed','priority','skills_needed','qualifications','misc_description');

		foreach ($formFields as $theField) {
			$markerArray['###FORM_'.strtoupper($theField).'###'] = $this->pi_getLL("adminform_".strtolower($theField));
			$llfield = 'adminform_'.strtolower($theField);
		}

		$thisMarkerArray['###SELECT_MINISTRY###'] = $selMinistryStr;
		$thisMarkerArray['###SELECT_CONTACT###'] = $selContactStr;

		if ($this->postvars) { // passed in an item for editting
			$markerArray['###FORM_TITLE###'] = 'Edit Ministry Opportunity';
			$markerArray['###ADDEDIT_BUTTON###'] = '<input name="adminUpd" type="submit" value="Update"/>';
			$markerArray['###DELETE_BUTTON###']	 = '<input name="adminDel" type="submit" id="Delete" value="Delete" onclick="return confirm(\'Are you sure you want to delete?\')"/>';
			if (t3lib_div::_GP('edit')) { // if came from viewing list in FE, then allow to go back
				$markerArray['###BACK_BUTTON###'] = '<input name="back" type="button" onclick="history.go(-1)" value="Go Back"/>';
			}

			// fill in values for form...
			foreach ($this->db_showFields as $theField)	{
				if ($this->postvars[$theField]) {
					$thisMarkerArray['###VALUE_'.strtoupper($theField).'###'] = htmlspecialchars($this->postvars[$theField]);
				}
			}
			$thisMarkerArray['###HIDDEN_VARS###'] .= '<input name="tx_wecservant[uid]" type="hidden" value="'.$this->postvars['uid'].'"/>';
			$thisMarkerArray['###HIDDEN_VARS###'] .= '<input name="tx_wecservant[original_contact_uid]" type="hidden" value="'.$this->postvars['contact_uid'].'"/>'.
													 '<input name="tx_wecservant[original_ministry_uid]" type="hidden" value="'.$this->postvars['ministry_uid'].'"/>'.
													 '<input name="tx_wecservant[original_skill_list]" type="hidden" value="'.$this->postvars['skill_list'].'"/>';

			$adminOppForm = $this->cObj->substituteMarkerArrayCached($adminOppForm,$thisMarkerArray,array(),array());
			$formContent .= $adminOppForm;
		}
		else {  // new item...
			$markerArray['###FORM_TITLE###'] = 'Add Ministry Opportunity';
			$markerArray['###ADDEDIT_BUTTON###'] = '<input name="adminAdd" type="submit" value="Add"/>';
			$formContent .= $adminOppForm;
		}

		// show all the search for minOpps
		$v = 0;
		if ($v = htmlspecialchars(t3lib_div::_GP('selMinList'))) {
			$minOppArray = $this->get_by_ministry($v);
			$content .= '<span class="tx-wecservant-header">Find By Ministry... ('.count($minOppArray).' found)</span>';
		}
		else if ($v = htmlspecialchars(t3lib_div::_GP('selContactList'))) {
			$minOppArray = $this->get_by_contact($v);
			$content .= '<span class="tx-wecservant-header">Find By Contact... ('.count($minOppArray).' found)</span>';
		}
		else if ($v = htmlspecialchars(t3lib_div::_GP('adminMyList'))) {
			$minOppArray = $this->get_by_contact($v);
			$content .= '<span class="tx-wecservant-header">Find My Opportunities... ('.count($minOppArray).' found)</span>';
		}
		else if ($v = htmlspecialchars(t3lib_div::_GP('selLastNumber'))) {
			$minOppArray = $this->get_by_lastupdated($v);
			$content .= '<span class="tx-wecservant-header">Find Last Updated... ('.count($minOppArray).' found)</span>';
		}
		if ($v) {
			// go through all items and display...
			for ($i = 0; $i < count($minOppArray); $i++) {
				$minOppItem = $minOppArray[$i];
			 	unset($thisMarkerArray);
				$thisAdminItem = $adminItem;
				$changed = false;
				foreach ($this->db_showFields as $theField)	{
					if ($minOppItem[$theField]) {
						$thisMarkerArray['###'.strtoupper($theField).'###'] = $minOppItem[$theField];
						$changed = true;
					}
				}
				// add new item to list if has values
				if ($changed) {
					$editIt['adminEdit'] = $minOppItem['uid'];
					$editURL = $this->getAbsoluteURL($this->id,$editIt);
					$thisMarkerArray['###EDIT_BTN###'] = '<a class="button smallButton" href="' . $editURL . '"><span class="label editIcon">Edit</span></a>';
					$delIt['adminDel'] = $minOppItem['uid'];
					$delURL  = $this->getAbsoluteURL($this->id,$delIt);
					$thisMarkerArray['###DELETE_BTN###'] = '<a class="button smallButton" href="' . $delURL . '" onclick="return confirm(\'Are you sure you want to delete?\')"><span class="label deleteIcon">Delete</span></a>';
					$content .= $this->cObj->substituteMarkerArrayCached($thisAdminItem,$thisMarkerArray,array(), array());
				}
			}
			if ($minOppArray == 0 || !count($minOppArray)) {
				$content .= '<div style="clear:both;">'.$this->pi_getLL('admin_no_entries','No entries found.').'</div>';
			}
		}

		// set the rest of the markers...
		if ($thisPri == 0) $thisPri = 1; // default to normal
		$selPri = '<select name="tx_wecservant[priority]" size="1">';
		for ($i = 1; $i < 10; $i++) {
			if ($pri_title = $this->pi_getLL("priorityLevel".$i)) {
				$selPri .= '<option value="'.$i.'" '.(($thisPri==$i) ? "selected" : "").'>'.$pri_title.'</option>';
			}
		}
		$selPri .= '</select>';
		$markerArray['###SELECT_PRIORITY###'] = $selPri;
		$markerArray['###SELECT_MINISTRY###'] = $selMinistryStr;
		$markerArray['###SELECT_CONTACT###'] = $selContactStr;

		// fill in the skills available selected for an item
		$skillArray = explode("|",$this->postvars['skill_list']);
		for ($i = 1; $i <= $this->maxSkillsPerItem; $i++) {
			$selSkillsStr = '<select name="tx_wecservant[skills'.$i.']" size="1">';
			$selSkillsStr .= '<option value="0">Select one...</option>';

			$curSkillUID = ($i <= count($skillArray)) ? $skillArray[$i-1] : 0;
			foreach ($this->skillsList as $sk) {
			 	$selSkillsStr .= '<option value="'.$sk["uid"].'" '.(($sk['uid'] == $curSkillUID) ? 'selected' : '').'>'.$sk["name"].'</option>';
			}
			$selSkillsStr .= '</select>';
			$markerArray['###SELECT_SKILLS'.$i.'###'] = $selSkillsStr;
		}

		// now add the form...
		$content .= $formContent;

		// then finish by adding the footer
		$content .= $adminFooter;

		// then substitute all the markers in the template into appropriate places
		$content = $this->cObj->substituteMarkerArrayCached($content,$markerArray,$subpartMarker, array());

		// clear out any empty template fields
		$content = preg_replace('/###.*?###/', '', $content);

		return $content;
	}

	/**
	 *	ADMIN_ADDUPDMINOPP: Add Or Update A New Ministry Opportunity
	 *
	 *	Create an opportunity including adding a ministry area or contact as needed
	 *
	 *
	 *	@param	array	$minOppData		array of data for the ministry opportunity
	 *  @param 	integer	$isEdit		if is an edit or new
	 *	@return void
	 */
	function admin_addUpdMinOpp($passedInVars,$isEdit) {
		// grab vars for update/new record for minOpp
		$newRec = array();
		foreach ($this->db_fields AS $field)
			if (isset($passedInVars[$field]))
				$newRec[$field] = $passedInVars[$field];

		// see if new contact
		if (strlen($passedInVars['contact_name'])) {
			$contactRec['name'] = $passedInVars['contact_name'];
			$contactRec['telephone'] = $passedInVars['telephone'];
			$contactRec['email'] = $passedInVars['email'];
			$contactRec['address'] = $passedInVars['address'];
			$contactRec['city'] = $passedInVars['city'];
			$contactRec['zone'] = $passedInVars['zone'];
			$contactRec['zip']	 = $passedInVars['zip'];
			$newRec['contact_uid'] = $this->admin_addUpdContact($contactRec,-1);
			$passedInVars['contact_uid'] = $newRec['contact_uid']; // save this for later
		}
		// see if should add/update contact
		else if ($passedInVars['contact_uid'] && ($passedInVars['contact_uid'] != $passedInVars['original_contact_uid'])) {
			$newRec['contact_uid'] = $passedInVars['contact_uid'];
		}
		// if new ministry added, then add that
		if ($passedInVars['ministry_name']) {
			$minRec['name'] = $passedInVars['ministry_name'];
			$minRec['description'] = $passedInVars['ministry_description'];
			$newRec['ministry_uid'] = $this->admin_addUpdMinistry($minRec,-1);
			$passedInVars['ministry_uid'] = $newRec['ministry_uid']; // save this for later
		}
		// see if should add/update ministry
		else if ($passedInVars['ministry_uid'] && ($passedInVars['ministry_uid'] != $passedInVars['original_ministry_uid'])) {
			$newRec['ministry_uid'] = $passedInVars['ministry_uid'];
		}

		// update tstamp for both new and updated (tstamp = last editted field)
		$newRec['tstamp'] = mktime();
		// set pid to the current page
		$newRec['pid'] = $this->pid_list;

		// ADD OR UPDATE THE MINOPP RECORD
		//------------------------------------------------------
		if (!$isEdit) {	// ADD A NEW RECORD
			$newRec['crdate'] = mktime();
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->servantContentTable, $newRec);
			$passedInVars['uid'] = mysql_insert_id();
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),$newRec));
			$this->responseMsg = $this->pi_getLL('admin_add_opp','Added New Ministry Opportunity');
		}
		else {	 // IF EDIT, JUST DO AN UPDATE
			$where = "uid=".$isEdit;
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->servantContentTable, $where, $newRec);
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),$newRec));
			$this->responseMsg = $this->pi_getLL('admin_upd_opp','Updated Existing Ministry Opportunity');
		}

		// set skills...
		$skillsStr = "";
		for ($i = 0; $i < $this->maxSkillsPerItem; $i++)	{
			if ($v = $this->postvars['skills'.$i]) {
				$skillsStr .= (strlen($skillsStr) > 0) ? "|".$v : $v;
			}
		}

		// see if should update skills
		$setSkills = false;
		for ($i = 0; $i < $this->maxSkillsPerItem; $i++)
			if ($passedInVars['skills'.$i]) { $setSkills = true; break; }
		// yes, will find differences of new skills added and skills deleted...
		if ($setSkills) {
			$newSkillsArray = explode("|",$skillsStr);
			$origSkillsArray = explode("|",$passedInVars['original_skill_list']);
			$this->postvars['skill_list'] = $skillsStr; // set this for post-processing
			$arrayAddDiff = array_diff($newSkillsArray,$origSkillsArray);
			$arrayDelDiff = array_diff($origSkillsArray,$newSkillsArray);
			if (count($arrayAddDiff)) { // add the skills in arrayDiff ...
				$skills_to_add = "";
				foreach ($arrayAddDiff as $sklUID) {
					if ($sklUID && !in_array($sklUID, $origSkillsArray)) { // if uid is valid & not already exists
						if (strlen($skills_to_add) > 0) $skills_to_add .= ", ";
						$skills_to_add .= "(".$passedInVars['uid'].",".$sklUID.",0)";
						$newSkillsRec = array('uid_local' => $passedInVars['uid'], 'uid_foreign' => $sklUID, 'sorting' => 0);
						$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_wecservant_skills_mm',$newSkillsRec);
					}
				}
			}
			if (count($arrayDelDiff)) {	// remove the skills that were deleted
				$skills_to_del = "";
				foreach ($arrayDelDiff as $sklUID) {
					if ($sklUID && in_array($sklUID,$origSkillsArray)) { // if uid is valid and exists
						if (strlen($skills_to_del) > 0) $skills_to_del .= ", ";
						$skills_to_del .= $sklUID;
					}
				}
				if (strlen($skills_to_del)) {
					$where = 'uid_local=' . $passedInVars['uid'] . ' AND uid_foreign IN (' . $skills_to_del . ');';
					$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_wecservant_skills_mm',$where);
					if (mysql_error())	t3lib_div::debug(array(mysql_error(),'DELETE WHERE ' . $where));
				}
			}
		}
	}

	/**
	 *	ADMIN_DELMINOPP: Delete An Existing Ministry Opportunity
	 *
	 *	@param	integer	$whichUID		id of minOpp to delete
	 *	@return	void
	*/
	function admin_delMinOpp($whichUID) {
		if ($whichUID > 0) {
			$where = 'uid='.$whichUID.' AND pid IN ('.$this->pid_list.')';
			$res = $GLOBALS['TYPO3_DB']->exec_DELETEquery($this->servantContentTable, $where);
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),$where));
			else $this->responseMsg = $this->pi_getLL('admin_del_opp','Deleted Ministry Opportunity');
			$this->postvars = 0; // clear out postvars since this is now gone
		}
	}

	/**
	 *	ADMIN_ADDUPDCONTACT: Add Or Update A Contact
	 *
	 *	@param	array 	$contactData	array of data for the contact
	 *  @param  integer $isEdit			> 0  update the record with given UID
	 *									= 0  create new record
	 *									= -1	 search for record and update, but if not found, then add
	 *
	 *	@return	integer					= 0 if failed
	 *									> 0 is uid of new object
	*/
	function admin_addUpdContact($passedInVars,$isEdit) {
		$retVal = $isEdit;

		// grab vars for update/new record
		$newRec = array();
		foreach ($this->db_contactFields AS $field)
			if (isset($passedInVars[$field]))
				$newRec[$field] = addslashes($passedInVars[$field]);

		if ($isEdit == -1) { // check if exists, if so, find UID
			$where = 'name="'.$GLOBALS['TYPO3_DB']->quoteStr($passedInVars['name'],$this->servantContactTable).'" AND tx_wecservant_is_contact=1';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->servantContactTable,$where,"","","");
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),"SELECT * FROM ".$this->servantContactTable." WHERE ".$where." ORDER BY ".$order_by." LIMIT ".$limit));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
				$isEdit = $row['uid'];
			else
				$isEdit = 0;
			$retVal = $isEdit;
	 	}

		$newRec['tstamp'] = mktime(); // update tstamp for both new and updated (tstamp = last editted field)
		$newRec['pid'] = $this->pid_list; // set pid to the current page
		$newRec['tx_wecservant_is_contact'] = 1;

		if (!$isEdit) {	// ADD A NEW RECORD
			$newRec['crdate'] = mktime();
			// assign name/username/etc.
			$theName = explode(" ",trim($passedInVars['name']));
			$newRec['username'] = (count($theName)>1) ? strtolower(substr($theName[0],0,1) . $theName[1]) : $theName;
			if (count($theName) > 1) {
				$newRec['first_name'] = $theName[0];
				$newRec['last_name']  = $theName[1];
			}
			// now save it to database
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->servantContactTable, $newRec);
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),$newRec));
			$retVal = mysql_insert_id();	// find and return uid of new record
			$this->responseMsg = $this->pi_getLL('admin_add_contact','Added New Contact');
		}
		else {	 // IF EDIT, JUST DO AN UPDATE
			$where = "uid=".$isEdit;
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->servantContactTable, $where, $newRec);
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),$newRec));
			$this->responseMsg = $this->pi_getLL('admin_upd_contact','Updated Existing Contact');
		}

		return $retVal;
	}

	/**
	 *	ADMIN_ADDUPDMINISTRY: Add Or Update A Ministry
	 *
	 *	@param	array 	$minData	array of data for the ministry
	 *  @param  integer $isEdit		> 0  update the record with given UID
	 *								= 0  create new record
	 *								= -1	 search for record and update, but if not found, then add
	 *
	 *	@return	integer				= 0 if failed
	 *								> 0 is uid of new object
	 */
	function admin_addUpdMinistry($passedInVars,$isEdit) {
		$retVal = $isEdit;

		// grab vars for update/new record
		$newRec = array();
		foreach ($this->db_minFields AS $field)
			if (isset($passedInVars[$field]))
				$newRec[$field] = $passedInVars[$field];
		if ($isEdit == -1) {
			$where = 'title="'.$GLOBALS['TYPO3_DB']->quoteStr($passedInVars['name'],$this->servantGroupTable).'"';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*',$this->servantGroupTable,$where,"","","");
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),"SELECT * FROM ".$this->servantGroupTable." WHERE ".$where." ORDER BY ".$order_by." LIMIT ".$limit));
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))
				$isEdit = $row['uid'];
			else
				$isEdit = 0;
			$retVal = $isEdit;
	 	}

		// update tstamp for both new and updated (tstamp = last editted field)
		$newRec['tstamp'] = mktime();
		// set pid to the current page
		$newRec['pid'] = $this->pid_list;

		$newRec['title'] = $newRec['name'];
		unset($newRec['name']);
		if (!$isEdit) {	// ADD A NEW RECORD
			$newRec['wecgroup_type'] = $this->wecgroup_type;
			$res = $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->servantGroupTable, $newRec);
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),$newRec));
			$retVal = mysql_insert_id();	// find and return uid of new record
			$this->responseMsg = $this->pi_getLL('admin_add_ministry','Added New Ministry');
		}
		else {	 // IF EDIT, JUST DO AN UPDATE
			$where = "uid=".$isEdit;
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->servantGroupTable, $where, $newRec);
			if (mysql_error())	t3lib_div::debug(array(mysql_error(),$newRec));
			$this->responseMsg = $this->pi_getLL('admin_upd_ministry','Updated Existing Ministry');
		}

		return $retVal;
	}


	/**
	 * Admin Stats Menu
	 *
	 *		Show & Handle Stats for Admin
	 *
	 * @param	integer	$action	which stats menu action to do
	 * @return	string	content to display admin menu
	 */
	function admin_stats_menu($action) {
		$content = "";
		switch ($action) {
			case 2: // = show monthly count
				$content .= '<div class="adminBox">';
				$content .= '<div class="adminHeader">'.$this->pi_getLL('admin_menu_monthly_stats','SERVANT MONTHLY SIGNUP STATS').'</div>';

				$stats = $this->get_stats();
				// sort monthly
				for ($i = 0; $i < count($stats); $i++) {
					$started = $stats[$i]['crdate'];
					$startMonth = date("M",$started);
					$startYear = date("Y",$started);
					$monthStats[$startMonth."-".$startYear]++;
				}

				$totalCount = 0;
				foreach ($monthStats as $whichMonth => $monthCount) {
					$content .= '<span class="adminItem">';
					$content .= $this->pi_getLL('admin_stats_month','Month of ').$whichMonth.' = '.$monthCount;
					$content .= "</span><br>";
					$totalCount += $monthCount;
				}
				$content .= '<div class="adminResults">'.$this->pi_getLL('admin_total_signups','Total Servant Signups').' = '.$totalCount.'</div>';
				$content .= '</div>';
			break;

			case 3: // = show weekly count
				$content .= '<div class="adminBox">';
				$content .= "<div class='adminHeader'>".$this->pi_getLL('admin_menu_weekly_stats','SERVANT WEEKLY SIGNUP STATS')."</div>";

				$stats = $this->get_stats();
				for ($i =  0; $i <  count($stats); $i++)   {
					$started   =   $stats[$i]['crdate'];
					$startDay   	= mktime(0,0,0,date("m",$started),date("d",$started),date("y",$started));
					$dayOfWeek   	= date("w",$startDay);
					$startWeekDay  	= mktime(0,0,0,date("m",$started),date("d",$started)-$dayOfWeek,date("y",$started));
					$weeklyStats[$startWeekDay]++;
				}

				$totalCount = 0;
				foreach ($weeklyStats as $whichWeek => $weekCount) {
					$content .= '<span class="adminItem">';
					$content .= $this->pi_getLL('admin_stats_week','Week of ').date("m/d/Y",$whichWeek) ." = ".$weekCount;
					$content .= "</span><br>";
					$totalCount += $weekCount;
				}
				$content .= '<div class="adminResults">'.$this->pi_getLL('admin_stats_total','Total Servant Signups').' = '.$totalCount.'</div>';
				$content .= '</div>';

			break;

			case 4: // = show active past year
			case 5: // = show active past 3 months
			case 6: // = show active past month
			case 7: // = show active
				$content .= '<div class="adminBox">';
				$content .= '<div class="adminHeader">'.$this->pi_getLL('admin_stats_active_header','ACTIVE SERVANT OPPORTUNITY STATS').'</div>';

				$timePast = mktime();
				switch ($action) {
					case 4: $content .= '<div class="adminSubHeader">'.$this->pi_getLL('admin_stats_pastyear','FOR PAST YEAR').'</div>';
						$timePast = mktime(0,0,0,date("m",$timePast)-12,date("d",$timePast),date("y",$timePast));
						break;
					case 5: $content .= '<div class="adminSubHeader">'.$this->pi_getLL('admin_stats_past3months','FOR PAST 3 MONTHS').'</div>';
						$timePast = mktime(0,0,0,date("m",$timePast)-3,date("d",$timePast),date("y",$timePast));
						break;
					case 6: $content .=	'<div class="adminSubHeader">'.$this->pi_getLL('admin_stats_pastmonth','FOR PAST MONTH').'</div>';
						$timePast = mktime(0,0,0,date("m",$timePast)-1,date("d",$timePast),date("y",$timePast));
						break;
					case 7: $content .=	'<div class="adminSubHeader">'.$this->pi_getLL('admin_stats_pastall','FOR ALL IN PAST').'</div>';
						$timePast = mktime(0,0,0,date("m",$timePast)-120,date("d",$timePast),date("y",$timePast));
						break;
				}
				$stats = $this->get_stats();
				$activeOpps = array();
				for ($i = 0; $i < count($stats); $i++) {
					$started = $stats[$i]['crdate'];
					$startDay = mktime(0,0,0,date("m",$started),date("d",$started),date("y",$started));
					if ($startDay < $timePast)
						break;

					$minOpp = $stats[$i]['minopp'];
					$activeOpps[$minOpp]++;
				}

				$totalCount = 0;
				foreach ($activeOpps as $minOpp => $minOppCount) {
					$content .= '<div class="adminItem">';
					$content .= $minOpp ." = ". $minOppCount;
					$content .= "</div>";
					$totalCount += $minOppCount;
				}
				$content .= '<div class="adminHeader">'.$this->pi_getLL('admin_stats_total','Total Servant Signups').' = '.$totalCount.'</div>';
				$content .= '</div>';

			break;

			case 12: // = show inactive past 6 months
			case 11: // = show inactive past year
			case 13: // = show inactive past all
				$content .= '<div class="adminBox">';
				$content .= '<div class="adminHeader">'.$this->pi_getLL('admin_stats_inactive_header','INACTIVE SERVANT OPPORTUNITY STATS').'</div>';

				$timePast = mktime();
				switch ($action) {
					case 12: $content .= '<div class="adminSubHeader">'.$this->pi_getLL('admin_stats_past6months','FOR PAST 6 MONTHS').'</div>';
						$timePast = mktime(0,0,0,date("m",$timePast)-6,date("d",$timePast),date("y",$timePast));
						break;
					case 11: $content .= '<div class="adminSubHeader">'.$this->pi_getLL('admin_stats_pastyear','FOR PAST YEAR').'</div>';
						$timePast = mktime(0,0,0,date("m",$timePast)-12,date("d",$timePast),date("y",$timePast));
						break;
					case 13: $content .= '<div class="adminSubHeader">'.$this->pi_getLL('admin_stats_pastall','FOR PAST ALL').'</div>';
						$timePast = mktime(0,0,0,date("m",$timePast)-120,date("d",$timePast),date("y",$timePast));
						break;
				}
				$stats = $this->get_stats(); // all active minopps
				$curMinOpps = $this->get_by_ministry(-1); // all minopps
				$totalActive = 0;
				for ($i = 0; $i < count($stats); $i++) {
					$started = $stats[$i]['crdate'];
					$startDay = mktime(0,0,0,date("m",$started),date("d",$started),date("y",$started));
					if ($startDay < $timePast)
						break;
					$minOpp = html_entity_decode($stats[$i]['minopp'],ENT_COMPAT,$GLOBALS['TSFE']->renderCharset);
					// if active in curMinOpps, delete it -- keep only inactive
					for ($k = 0; $k < count($curMinOpps); $k++) {
						if (!strcmp($curMinOpps[$k]['name'], $minOpp)) {
							if (isset($curMinOpps[$k])) $totalActive++;
							unset($curMinOpps[$k]);
						}
					}
				}
				$totalCount = 0;
				foreach ($curMinOpps as $minOpp) {
					$content .= '<div  class="adminItem">';
					$content .= $minOpp['name'];
					$content .= "</div>";
					$totalCount++;;
				}
				$content .= '<div class="adminResults">'.$this->pi_getLL('admin_stats_total_active','Total Active Ministry Opportunities').' = '.$totalActive.'</div>';
				$content .= '<div class="adminResults">'.$this->pi_getLL('admin_stats_total_inactive','Total Inactive Ministry Opportunities').' = '.$totalCount.'</div>';
				$content .= '</div>';

			break;

			case 9: // = exit stats
				$getvars['admin'] = 1;
				$gotourl = $this->pi_getPageLink($GLOBALS['TSFE']->id, '', $getvars);
				header('Location: '.t3lib_div::locationHeaderURL($gotourl));
			break;
		}

		// SHOW MENU...

		// grab from template
		$admin_template = $this->cObj->getSubpart($this->templateCode,'###TEMPLATE_ADMINMENU###');
		$adminStats_template = $this->cObj->getSubpart($admin_template,'###ADMIN_STATS_MENU###');
		$thisMarkerArray['###ACTION_URL###'] = $this->getAbsoluteURL($this->id, $getvars);
		$content .= $this->cObj->substituteMarkerArrayCached($adminStats_template,$thisMarkerArray,array(),array());

		return $content;
	}

	/**
	 * Get Stats
	 *
	 *		retrieve the stats info from the tracking database table
	 *
	 * @param	string	$orderBy	special order by
	 * @return	array	rows of data returned from stats table
	 */
	function get_stats($orderBy = "crdate DESC") {
		$where = "1";
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tx_wecservant_tracking',$where,"",$orderBy,"");
		$statsArray = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			array_push($statsArray,$row);
		}

		return $statsArray;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_servant/pi1/class.tx_wecservant_pi1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/wec_servant/pi1/class.tx_wecservant_pi1.php']);
}

?>