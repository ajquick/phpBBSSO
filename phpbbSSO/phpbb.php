<?php
 
class phpbbSSO{
 
 
    //////////////////////////////////////////////////////////////////
    //CONFIGURE
    //////////////////////////////////////////////////////////////////
    const REQUIRE_GROUP = false; //must the user be part of a specific group?
    const WIKI_GROUP = 'Wiki'; //What group?
 
    public $userId;
    public $userType;
    public $userName;
    public $email;
    public $username_clean;
    public $session_id;
    public $messages;
 
    function __construct($forumDirectory){
    //////////////////////////////////////////////////////////////////
    // Connect to phpbb and setup/get session
    //////////////////////////////////////////////////////////////////
        global $db, $cache, $config, $user, $auth, $template, $phpbb_root_path, $phpEx, $request, $symfony_request, $phpbb_filesystem, $phpbb_container, $phpbb_dispatcher;
 
 	define('PHPBB_ROOT_PATH', $forumDirectory);
        define('IN_PHPBB', true);
        define('FROM_MEDIAWIKI', true);
 
	$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : './../phpBB3/';
	$phpEx = substr(strrchr(__FILE__, '.'), 1);
	include($phpbb_root_path . 'common.' . $phpEx);
 
	// Start session management
	$user->session_begin();
	$auth->acl($user->data);
	$user->setup();     
 
	$request->enable_super_globals();
 
        $this->userId = $user->data['user_id'];
        $this->userType = $user->data['user_type'];
        $this->userName = $user->data['username'];
        $this->email = $user->data['user_email'];
        $this->username_clean = $user->data['username_clean'];
        $this->session_id = $user->data['session_id'];
        $this->messages = $user->data['user_new_privmsg'];
 
        unset($user);
        unset($db);
        unset($cache);
        unset($config);
        unset($user);
        unset($phpbb_root_path);
        unset($phpEx);
 
    }
 
 
 
    private function checkIfUserInGroup($group = 'Wiki'){
    //////////////////////////////////////////////////////////////////
    // Check if the user is part of a specific group
    //////////////////////////////////////////////////////////////////
        global $db; //phpbb database object
 
        $sql = '
        SELECT COUNT(*) as count FROM '.USER_GROUP_TABLE.', '.GROUPS_TABLE.'
        WHERE '.USER_GROUP_TABLE.'.group_id = '.GROUPS_TABLE.'.group_id
        AND '.USER_GROUP_TABLE.'.user_pending = 0
        AND '.GROUPS_TABLE.'.group_name = "'. $db->sql_escape($group) .'"
        AND '.USER_GROUP_TABLE.'.user_id = "'. $db->sql_escape($this->userId) .'"'; 
 
        $result = $db->sql_query($sql);
        $row = $db->sql_fetchrow($result);
 
 
        if($row['count'] > 0){
            return true;
        }else{
            return false;
        }
 
    }
 
 
    public function isAnonymous(){
    //////////////////////////////////////////////////////////////////
    // find if user is anonymous or a bot, or nor part of the group
    //////////////////////////////////////////////////////////////////
 
        if(self::REQUIRE_GROUP){
            //if a group is required, and that user is not in that group, say anonymous
            if(! $this->checkIfUserInGroup(self::WIKI_GROUP)) return true;
        }
 
 
        if($this->userId == 1 || $this->userType == 1 || $this->userType == 2) return true;
 
        return false;
 
 
 
    }
}
?>