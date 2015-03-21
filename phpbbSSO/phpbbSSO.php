<?php

	$wgExtensionCredits['other'][] = array(
		'name' => 'phpBB SSO',
		'version' => '0.11',
		'author' => array( 'Adam Meyer', 'Otheus Shelling', 'Rusty Burchfield', 'James Kinsman', 'Daniel Thomas', 'Ian Ward Comfort', 'AJ Quick' ),
		'url' => 'http://www.mediawiki.org/wiki/Extension:Phpbb_Single_Sign-On',
		'description' => 'Automatically logs users in/out using their PHPbb session. Based on the Auth_remote_user extension',
	);
 
 
	// This requires a user be logged into the wiki to make changes and no anony edits
	$GLOBALS['wgGroupPermissions']['*']['edit'] = false;
	$GLOBALS['wgGroupPermissions']['*']['createaccount'] = false;
	$wgMinimalPasswordLength = 1;
 
 
	require('phpbb.php');
	$wgPhpbbSSO = new phpbbSSO($wgPhpbbSSO_Forum_Location);
 
 
 
	$wgExtensionFunctions[] = 'phpbbSSO_hook';

	$wgAutoloadClasses['Auth_remoteuser'] = __DIR__ . '/phpbbSSO.body.php';