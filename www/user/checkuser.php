<?php

/**
 * Copyright 2020-2021, SimTK DataShare Team
 *
 * This file is part of SimTK DataShare. Initial development
 * was funded under NIH grants R01GM107340 and U54EB020405
 * and the U.S. Army Medical Research & Material Command award
 * W81XWH-15-1-0232R01. Continued maintenance and enhancement
 * are funded by NIH grant R01GM124443.
 */

	$conf = file_get_contents('/usr/local/mobilizeds/conf/mobilizeds.conf');
	$conf = json_decode($conf);
	include 'server.php';

	// ======
	if (isset($_REQUEST['groupid']) && $_REQUEST['groupid']) {
		$groupid = $_REQUEST['groupid'];
	}
	else {
		$groupid = false;
	}
	if (isset($_REQUEST['section']) && $_REQUEST['section']) {
		$section = $_REQUEST['section'];
	}
	else {
		$section = false;
	}
	if (isset($_REQUEST['userid']) && $_REQUEST['userid']) {
		$userid = $_REQUEST['userid'];
	}
	else {
		$userid = false;
	}
	if (isset($_REQUEST['token']) && $_REQUEST['token']) {
		$token = $_REQUEST['token'];
	}
	else {
		$token = false;
	}
	$private = 0;
	if (isset($_REQUEST['private']) && $_REQUEST['private']) {
		$private = $_REQUEST['private'];
	}
	if (isset($_REQUEST['firstname']) && $_REQUEST['firstname']) {
		$firstname = $_REQUEST['firstname'];
	}
	else {
		$firstname = false;
	}
	if (isset($_REQUEST['lastname']) && $_REQUEST['lastname']) {
		$lastname = $_REQUEST['lastname'];
	}
	else {
		$lastname = false;
	}
	if (isset($_REQUEST['studyid']) && $_REQUEST['studyid']) {
		$studyid = $_REQUEST['studyid'];
	}
	else {
		$studyid = false;
	}
	if (isset($_REQUEST['isDOI']) && $_REQUEST['isDOI']) {
		$isDOI = $_REQUEST['isDOI'];
	}
	else {
		$isDOI = false;
	}
	if (isset($_REQUEST['doi_identifier']) && $_REQUEST['doi_identifier']) {
		$doi_identifier = $_REQUEST['doi_identifier'];
	}
	else {
		$doi_identifier = false;
	}
	if (isset($_REQUEST['subject_prefix']) && $_REQUEST['subject_prefix']) {
		$subject_prefix = $_REQUEST['subject_prefix'];
	}
	else {
		$subject_prefix = false;
	}

	if (isset($_REQUEST['member']) && $_REQUEST['member']) {
		$member = $_REQUEST['member'];
	}
	else {
		$member = false;
	}
	if (isset($_REQUEST['pathSelected']) && $_REQUEST['pathSelected']) {
		$pathSelected = $_REQUEST['pathSelected'];
	}
	else {
		$pathSelected = false;
	}

/*
	// Check study.
	$url = "https://$domain_name/plugins/api/index.php?key=$api_key" .
		"&userid=" . $userid .
		"&token=" . $token . 
		"&studyid=" . $studyid .
		"&groupid=" . $groupid .
		"&action=20" .
		"&tool=" . $section;
	$isStudyValid = true;
	$response_study_json = file_get_contents($url);
	$response_study = json_decode($response_study_json);
	if ($response_study != null && $response_study->study_valid) {
		$valid_group_id = $response_study->group_id;
		$valid_token = $response_study->token;
		$templateid = $response_study->template_id;
		$is_private = $response_study->is_private;  // 0 is public, 1 is registered user, 2 is private
		$active = $response_study->active;  // active field
		$group_name = $response_study->group_name;  // group name
		$group_id = $response_study->group_id;  // group id
		$study_name = $response_study->study_name;  // study name
		$subject_prefix = $response_study->subject_prefix;  // Subject prefix
		$is_group_public = $response_study->is_public;  // 1 is public, 0 is private
	}
	else {
		$isStudyValid = false;
		return;
	}
*/

	// Check user.
	$isStudyValid = true;
	$url = "https://$domain_name/plugins/api/index.php?key=$api_key" .
		"&userid=" . $userid .
		"&token=" . $token . 
		"&studyid=" . $studyid .
		"&groupid=" . $groupid .
		"&action=14" .
		"&tool=" . $section;
	$context = array(
		"ssl"=>array(
			"verify_peer"=>false,
			"verify_peer_name"=>false,
		),
	);
	$response_study_json = file_get_contents($url, false, stream_context_create($context));
	$response_study = json_decode($response_study_json);

	if ($response_study == null || !$response_study->study_valid) {
		// Invalid study.
		$isStudyValid = false;
		return;
	}

	// Set up data from study.
	$valid_group_id = $response_study->group_id;
	$valid_token = $response_study->token;
	$templateid = $response_study->template_id;
	$is_private = $response_study->is_private;  // 0 is public, 1 is registered user, 2 is private
	$active = $response_study->active;  // active field
	$group_name = $response_study->group_name;  // group name
	$group_id = $response_study->group_id;  // group id
	$study_name = $response_study->study_name;  // study name
	$subject_prefix = $response_study->subject_prefix;  // Subject prefix
	$is_group_public = $response_study->is_public;  // 1 is public, 0 is private

	if (!$userid) {
		$email = false;
		$member = false;
	}
	else {
		$email = $response_study->email;
		$member = $response_study->is_member;
	}

	if ($userid == 101) {
		// check for forge admin, if member then make perm val 3 and member 1
		$response_study->perm_val = 3;
		$member = 1;
	}


	// ===== TRANSLATE PERMISSION BITMASK
	// initialize permissions
	$permissions = [ 'none' => true, 'read' => false, 'download' => false, 'write' => false, 'admin' => false ];
	//if ( $row[ 'permissions' ] & 1 ) { $permissions[ 'read' ]  = true; $permissions[ 'none' ] = false; }
	//if ( $row[ 'permissions' ] & 2 ) { $permissions[ 'write' ] = true; $permissions[ 'none' ] = false; }
	//if ( $row[ 'permissions' ] & 4 ) { $permissions[ 'admin' ] = true; $permissions[ 'none' ] = false; }

	// if nothing below is satisfied, then json probably set to 0 - no access.
	$perm = 0;
	$download = 0;
	$login_required = 0;
	if (!$response_study->valid_user && $response_study->study_valid) {
		// User not logged in or invalid user.
		if ($response_study->is_private == 0 && $is_group_public) {
			// public study and public group - public dataset
			$permissions[ 'read' ] = true;
			$permissions[ 'download' ] = true;
			$permissions[ 'none' ] = false;
			$perm = 1;
			$download = 1;
		}
		else if ($response_study->is_private == 1 || 
			$response_study->is_private == 2 || 
			!$is_group_public) {

			// require login or private study or private group
			$login_required = 1;
		}
	}
	else if ($response_study->valid_user && $response_study->study_valid) {
		// user logged in and valid study
		if ($response_study->is_private == 0 && $is_group_public) {
			// public study and public group - public dataset
			$permissions[ 'read' ] = true;
			$permissions[ 'download' ] = true;
			$permissions[ 'none' ] = false;
			$perm = 1;
			$download = 1;
			if ($userid) {
				// user logged in.  Check to see if they can import
				if ($response_study->perm_val == 3) {
					$permissions[ 'download' ] = true;
					$permissions[ 'write' ] = true;
					$permissions[ 'admin' ] = true;	
					$permissions[ 'none' ] = false;
					$perm = 3;
					$download = 1;
				}
			} // if userid
		}
		else if ($response_study->is_private == 1 && $is_group_public) {
			// is_private = 1 is registered user dataset
			if ($userid) {
				// user logged in.
				if (!$member) {
					// logged in but not member
					// permissions should be same as member with perm_val 1
					$permissions[ 'read' ] = true;
					$permissions[ 'download' ] = true;
					$permissions[ 'none' ] = false;
					$perm = 1;
					$download = 1;
				}
				else {
					if ($response_study->perm_val == 0) {
						$permissions[ 'none' ] = true;
					}
					else if ($response_study->perm_val == 1) {
						$permissions[ 'read' ] = true;
						$permissions[ 'download' ] = true;
						$permissions[ 'none' ] = false;
						$perm = 1;
						$download = 1;
					}
					else if ($response_study->perm_val == 2) {
						$permissions[ 'read' ] = true;
						$permissions[ 'download' ] = true;
						$permissions[ 'none' ] = false;
						$perm = 2;
						$download = 1;
					}
					else if ($response_study->perm_val == "3") {
						$permissions[ 'read' ] = true;
						$permissions[ 'download' ] = true;
						$permissions[ 'write' ] = true;
						$permissions[ 'admin' ] = true;
						$permissions[ 'none' ] = false;
						$perm = 3;
						$download = 1;
					}
				} // else
			} // if userid
		}
		else if (($response_study->is_private == 0 || $response_study->is_private == 1) && 
			!$is_group_public) {
			// Private Project and Public Study or Registered User Required
			if ($userid) {
				// user logged in.
				// must be member
				if ($response_study->perm_val == 0) {
					$permissions[ 'none' ] = true;
				}
				else if ($response_study->perm_val == 1) {
					$permissions[ 'read' ] = true;
					$permissions[ 'download' ] = true;
					$permissions[ 'none' ] = false;
					$perm = 1;
					$download = 1;
				}
				else if ($response_study->perm_val == 2) {
					$permissions[ 'read' ] = true;
					$permissions[ 'download' ] = true;
					$permissions[ 'none' ] = false;
					$perm = 2;
					$download = 1;
				}
				else if ($response_study->perm_val == "3") {
					$permissions[ 'read' ] = true;
					$permissions[ 'download' ] = true;
					$permissions[ 'write' ] = true;
					$permissions[ 'admin' ] = true;
					$permissions[ 'none' ] = false;
					$perm = 3;
					$download = 1;
				}
			} // if userid
		}
		else if ($response_study->is_private == 2) {
			// private dataset - this path is group is private or public
			if ($userid) {
				// user logged in.
				// must be member
				if ($response_study->perm_val == 0) {
					$permissions[ 'none' ] = true;
				}
				else if ($response_study->perm_val == 1) {
					//$permissions[ 'read' ] = true;
					//$permissions[ 'download' ] = true;
					//$permissions[ 'none' ] = false;
					// need to confirm and test this  - th 4-11-19
					$permissions[ 'none' ] = true;
					$perm = 1;
					//$download = 1;
				}
				else if ($response_study->perm_val == 2) {
					$permissions[ 'read' ] = true;
					$permissions[ 'download' ] = true;
					$permissions[ 'none' ] = false;
					$perm = 2;
					$download = 1;
				}
				else if ($response_study->perm_val == "3") {
					$permissions[ 'read' ] = true;
					$permissions[ 'download' ] = true;
					$permissions[ 'write' ] = true;
					$permissions[ 'admin' ] = true;
					$permissions[ 'none' ] = false;
					$perm = 3;
					$download = 1;
				}
			} // if userid
		} // else if
	} // user logged in and valid study

	// ===== TRANSFER USER INFORMATION TO SESSION
	$_SESSION['is_auth'] = true;
	$_SESSION['email'] = $email;
	$_SESSION['firstname'] = $firstname;
	$_SESSION['lastname'] = $lastname;
	$_SESSION['userid'] = $userid;
	$_SESSION['section'] = $section;
	$_SESSION['token'] = $token;
	$_SESSION['templateid'] = $templateid;
	$_SESSION['group_name'] = $group_name;
	$_SESSION['group_id'] = $group_id;
	$_SESSION['study_id'] = $studyid;
	$_SESSION['pathSelected'] = $pathSelected;
	if (isset($isDOI)) {
		$_SESSION['isDOI'] = $isDOI;
	}
	else {
		$_SESSION['isDOI'] = false;
	}
	if (isset($doi_identifier)) {
		$_SESSION['doi_identifier'] = $doi_identifier;
	}
	else {
		$_SESSION['doi_identifier'] = false;
	}
	if (isset($subject_prefix)) {
		$_SESSION['subject_prefix'] = $subject_prefix;
	}
	else {
		$_SESSION['subject_prefix'] = false;
	}
	$_SESSION['study_name'] = $study_name;


	$_SESSION['private'] = $private;
	$_SESSION['member'] = $member;
?>



