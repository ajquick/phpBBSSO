<?php

function phpbbSSO_hook() {
		global $wgUser, $wgRequest, $wgPhpbbSSO, $wgAuth;
 
		$user = User::newFromSession();
 
 
 
		// For a few special pages, don't do anything.
		$title = $wgRequest->getVal( 'title' );
		if ( ( $title == Title::makeName( NS_SPECIAL, 'UserLogout' ) ) ||
			( $title == Title::makeName( NS_SPECIAL, 'UserLogin' ) ) ) {
			return;
		}
 
		if($wgPhpbbSSO->isAnonymous()){
			$user->doLogout();
			return;
		}
 
 
		if ( !$user->isAnon() ) {
 
			if ( $user->getName() == $wgAuth->getCanonicalName($wgPhpbbSSO->username_clean) ) {
				return; // Correct user is already logged in.
			} else {
				$user->doLogout(); // Logout mismatched user.
			}
		}
 
 
		// Copied from includes/SpecialUserlogin.php
		if ( !isset( $wgCommandLineMode ) && !isset( $_COOKIE[session_name()] ) ) {
			wfSetupSession();
		}
 
		// If the login form returns NEED_TOKEN try once more with the right token
		$trycount = 0;
		$token = '';
		$errormessage = '';
		do {
			$tryagain = false;
			// Submit a fake login form to authenticate the user.
			$params = new FauxRequest( array(
				'wpName' => $wgAuth->getCanonicalName($wgPhpbbSSO->username_clean),
				'wpPassword' => ' ',
				'wpDomain' => '',
				'wpLoginToken' => $token,
				'wpRemember' => ''
				) );
 
 
			// Authenticate user data will automatically create new users.
			$loginForm = new LoginForm( $params );
			$result = $loginForm->authenticateUserData();
 
 
			switch ( $result ) {
				case LoginForm :: SUCCESS :
					$wgUser->setOption( 'rememberpassword', 1 );
					$wgUser->setCookies();
					break;
				case LoginForm :: NEED_TOKEN:
					$token = $loginForm->getLoginToken();
					$tryagain = ( $trycount == 0 );
					break;
				case LoginForm :: WRONG_TOKEN:
					$errormessage = 'WrongToken';
					break;
				case LoginForm :: NO_NAME :
					$errormessage = 'NoName';
					break;
				case LoginForm :: ILLEGAL :
					$errormessage = 'Illegal';
					break;
				case LoginForm :: WRONG_PLUGIN_PASS :
					$errormessage = 'WrongPluginPass';
					break;
				case LoginForm :: NOT_EXISTS :
					$errormessage = 'NotExists';
					break;
				case LoginForm :: WRONG_PASS :
					$errormessage = 'WrongPass';
					break;
				case LoginForm :: EMPTY_PASS :
					$errormessage = 'EmptyPass';
					break;
				default:
					$errormessage = 'Unknown';
					break;
			}
 
			if ( $result != LoginForm::SUCCESS && $result != LoginForm::NEED_TOKEN ) {
				echo $errormessage;
				error_log( 'Unexpected REMOTE_USER authentication failure. Login Error was:' . $errormessage );
			}
			$trycount++;
		} while ( $tryagain );
 
		return;
	}
 
	class Auth_remoteuser extends AuthPlugin {
		/**
		 * Disallow password change.
		 *
		 * @return bool
		 */
		function allowPasswordChange() {
			return false;
		}
 
		/**
		 * This should not be called because we do not allow password change.  Always
		 * fail by returning false.
		 *
		 * @param $user User object.
		 * @param $password String: password.
		 * @return bool
		 * @public
		 */
		function setPassword( $user, $password ) {
			return false;
		}
 
		/**
		 * We don't support this but we have to return true for preferences to save.
		 *
		 * @param $user User object.
		 * @return bool
		 * @public
		 */
		function updateExternalDB( $user ) {
			return true;
		}
 
		/**
		 * We can't create external accounts so return false.
		 *
		 * @return bool
		 * @public
		 */
		function canCreateAccounts() {
			return false;
		}
 
		/**
		 * We don't support adding users to whatever service provides REMOTE_USER, so
		 * fail by always returning false.
		 *
		 * @param User $user
		 * @param string $password
		 * @return bool
		 * @public
		 */
		public function addUser( $user, $password, $email='', $realname='' )
    	{
    		return false;
    	}
 
		/**
		 * Pretend all users exist.  This is checked by authenticateUserData to
		 * determine if a user exists in our 'db'.  By returning true we tell it that
		 * it can create a local wiki user automatically.
		 *
		 * @param $username String: username.
		 * @return bool
		 * @public
		 */
		function userExists( $username ) {
			return true;
		}
 
		/**
		 * Check whether the given name matches REMOTE_USER.
		 * The name will be normalized to MediaWiki's requirements, so
		 * lower it and the REMOTE_USER before checking.
		 *
		 * @param $username String: username.
		 * @param $password String: user password.
		 * @return bool
		 * @public
		 */
		function authenticate( $username, $password ) {
			return true;
		}
 
		/**
		 * Check to see if the specific domain is a valid domain.
		 *
		 * @param $domain String: authentication domain.
		 * @return bool
		 * @public
		 */
		function validDomain( $domain ) {
			return true;
		}
 
		/**
		 * When a user logs in, optionally fill in preferences and such.
		 * For instance, you might pull the email address or real name from the
		 * external user database.
		 *
		 * The User object is passed by reference so it can be modified; don't
		 * forget the & on your function declaration.
		 *
		 * @param User $user
		 * @public
		 */
		function updateUser( &$user ) {
			// We only set this stuff when accounts are created.
			return true;
		}
 
		/**
		 * Return true because the wiki should create a new local account
		 * automatically when asked to login a user who doesn't exist locally but
		 * does in the external auth database.
		 *
		 * @return bool
		 * @public
		 */
		function autoCreate() {
			return true;
		}
 
		/**
		 * Return true to prevent logins that don't authenticate here from being
		 * checked against the local database's password fields.
		 *
		 * @return bool
		 * @public
		 */
		function strict() {
			return true;
		}
 
		/**
		 * When creating a user account, optionally fill in preferences and such.
		 * For instance, you might pull the email address or real name from the
		 * external user database.
		 *
		 * @param $user User object.
		 * @public
		 */
		function initUser( &$user, $autocreate=true ){
			global $c, $wgPhpbbSSO, $wgAuth;
 
			$username = $wgAuth->getCanonicalName($wgPhpbbSSO->username_clean);
			$user->setRealName( '' );
			$user->setEmail( $wgPhpbbSSO->email );
			$user->mPassword = ' ';
 
			//The update user function does everything else we need done.
			$this->updateUser($user);
 
 
			$user->mEmailAuthenticated = wfTimestampNow();
			$user->setToken();
 
			$user->setOption( 'enotifwatchlistpages', 1 );
			$user->setOption( 'enotifusertalkpages', 1 );
			$user->setOption( 'enotifminoredits', 1 );
			$user->setOption( 'enotifrevealaddr', 1 );
 
			$user->saveSettings();
		}
 
		/**
		 * Modify options in the login template.  This shouldn't be very important
		 * because no one should really be bothering with the login page.
		 *
		 * @param $template UserLoginTemplate object.
		 * @public
		 */
		function modifyUITemplate( &$template ) {
			// disable the mail new password box
			$template->set( 'useemail', false );
			// disable 'remember me' box
			$template->set( 'remember', false );
			$template->set( 'create', false );
			$template->set( 'domain', false );
			$template->set( 'usedomain', false );
		}
 
		/**
		 * Normalize user names to the MediaWiki standard to prevent duplicate
		 * accounts.
		 *
		 * @param $username String: username.
		 * @return string
		 * @public
		 */
		function getCanonicalName( $username ) {
			// lowercase the username
			$username = strtolower( $username );
			// uppercase first letter to make MediaWiki happy
			return ucfirst( $username );
		}
	}
