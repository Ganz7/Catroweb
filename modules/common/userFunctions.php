<?php
/**
 *    Catroid: An on-device graphical programming language for Android devices
 *    Copyright (C) 2010-2012 The Catroid Team
 *    (<http://code.google.com/p/catroid/wiki/Credits>)
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU Affero General Public License as
 *    published by the Free Software Foundation, either version 3 of the
 *    License, or (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU Affero General Public License for more details.
 *
 *    You should have received a copy of the GNU Affero General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

define('IN_PHPBB', true);
global $phpbb_root_path, $phpEx, $user, $db, $config, $cache, $template, $auth;
$phpbb_root_path = (defined('PHPBB_ROOT_PATH')) ? PHPBB_ROOT_PATH : CORE_BASE_PATH . 'addons/board/';
$phpEx = substr(strrchr(__FILE__, '.'), 1);
require_once($phpbb_root_path . 'common.' . $phpEx);
require_once($phpbb_root_path . 'includes/functions_user.' . $phpEx);

class userFunctions extends CoreAuthenticationNone {
  protected $registerCatroidId;
  protected $registerBoardId;
  protected $registerWikiId;

  public function __construct() {
    parent::__construct();
    $this->registerCatroidId = 0;
    $this->registerBoardId = 0;
    $this->registerWikiId = 0;
  }

  public function __default() {
  }

  public function isLoggedIn() {
    if($this->session->userLogin_userId == 0 || $this->session->userLogin_userNickname == "") {
      return false;
    }
    return true;
  }

  public function isRecoveryHashValid($hash) {
    $hash = trim(strval($hash));
    $result = pg_execute($this->dbConnection, "get_user_password_hash_time", array($hash));
     
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
     
    $numRows = pg_num_rows($result);
    $row = pg_fetch_assoc($result);
    pg_free_result($result);
     
    if($numRows != 1) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'hash_not_found', pg_last_error($this->dbConnection)),
          STATUS_CODE_USER_RECOVERY_EXPIRED);
    }
     
    if((intval($row['recovery_time']) + 24*60*60) < time()) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'expired_url', pg_last_error($this->dbConnection)),
          STATUS_CODE_USER_RECOVERY_EXPIRED);
    }
  }

  public function isValidationHashValid($hash) {
    $hash = trim(strval($hash));
    $result = pg_execute($this->dbConnection, "get_email_hash", array($hash));
     
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
     
    $numRows = pg_num_rows($result);
    $row = pg_fetch_assoc($result);
    pg_free_result($result);
     
    if($numRows != 1) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'hash_not_found', pg_last_error($this->dbConnection)),
          STATUS_CODE_USER_RECOVERY_EXPIRED);
    }
  }

  public function checkUserExists($username) {
    $username = trim($username);
    $result = pg_execute($this->dbConnection, "get_user_row_by_username", array($username));
     
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
    $userExists = (pg_num_rows($result) == 1);
    pg_free_result($result);
     
    return $userExists;
  }

  public function checkUsername($username) {
    $username = trim(strval($username));
    if($username == '') {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_missing'),
          STATUS_CODE_USER_USERNAME_MISSING);
    }

    // # < > [ ] | { }
    if(preg_match('/_|^_$/', $username)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_invalid_underscore'),
          STATUS_CODE_USER_USERNAME_INVALID_CHARACTER);
    }
    if(preg_match('/#|^#$/', $username)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_invalid_hash'),
          STATUS_CODE_USER_USERNAME_INVALID_CHARACTER);
    }
    if(preg_match('/\||^\|$/', $username)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_invalid_verticalbar'),
          STATUS_CODE_USER_USERNAME_INVALID_CHARACTER);
    }
    if(preg_match('/\{|^\{$/', $username) || preg_match('/\}|^\}$/', $username)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_invalid_curlybrace'),
          STATUS_CODE_USER_USERNAME_INVALID_CHARACTER);
    }
    if(preg_match('/\<|^\<$/', $username) || preg_match('/\>|^\>$/', $username)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_invalid_lessgreater'),
          STATUS_CODE_USER_USERNAME_INVALID_CHARACTER);
    }
    if(preg_match('/\[|^\[$/', $username) || preg_match('/\]|^\]$/', $username)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_invalid_squarebracket'),
          STATUS_CODE_USER_USERNAME_INVALID_CHARACTER);
    }
    if(preg_match("/\\s/", $username)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_invalid_spaces'),
          STATUS_CODE_USER_USERNAME_INVALID_CHARACTER);
    }

    if($this->badWordsFilter->areThereInsultingWords($username)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_invalid_insulting_words'),
          STATUS_CODE_INSULTING_WORDS);
    }

    //username must not look like an IP-address
    $oktettA = '([1-9][0-9]?)|(1[0-9][0-9])|(2[0-4][0-9])|(25[0-4])';
    $oktettB = '(0)|([1-9][0-9]?)|(1[0-9][0-9])|(2[0-4][0-9])|(25[0-4])';
    $ip = '('.$oktettA.')(\.('.$oktettB.')){2}\.('.$oktettA.')';
    $regEx = '/^'.$ip.'$/';
    if(preg_match($regEx, $username)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_invalid'),
          STATUS_CODE_USER_USERNAME_INVALID);
    }

    $usernameClean = $this->cleanUsername($username);
    if(empty($usernameClean)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_invalid'),
          STATUS_CODE_USER_USERNAME_INVALID);
    }

    if(in_array($username, getUsernameBlacklistArray()) || in_array($usernameClean, getUsernameBlacklistArray())) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_blacklisted'),
          STATUS_CODE_USER_USERNAME_INVALID);
    }

    foreach(getPublicServerBlacklistArray() as $value) {
      if(preg_match("/".$value."/i", $username)) {
        throw new Exception($this->errorHandler->getError('userFunctions', 'username_blacklisted'),
            STATUS_CODE_USER_USERNAME_INVALID);
      }
    }
     
    if($this->checkUserExists($username)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_already_exists'),
          STATUS_CODE_USER_USERNAME_INVALID);
    }
  }

  public function checkPassword($username, $password) {
    $password = trim(strval($password));
    if($password == '') {
      throw new Exception($this->errorHandler->getError('userFunctions', 'password_missing'),
          STATUS_CODE_USER_PASSWORD_MISSING);
    }

    if(strcasecmp($username, $password) == 0) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'username_password_equal'),
          STATUS_CODE_USER_USERNAME_PASSWORD_EQUAL);
    }
     
    if(strlen($password) < USER_MIN_PASSWORD_LENGTH) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'password_new_too_short', '', USER_MIN_PASSWORD_LENGTH),
          STATUS_CODE_USER_PASSWORD_TOO_SHORT);
    }
     
    if(strlen($password) > USER_MAX_PASSWORD_LENGTH) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'password_new_too_long', '', USER_MAX_PASSWORD_LENGTH),
          STATUS_CODE_USER_PASSWORD_TOO_LONG);
    }
  }

  public function checkLoginData($username, $md5Password) {
    $result = pg_execute($this->dbConnection, "get_user_login", array($this->cleanUsername($username), $md5Password));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
     
    $loginSuccess = (pg_num_rows($result) == 1);
    pg_free_result($result);

    return $loginSuccess;
  }

  public function checkEmail($email) {
    $email = trim(strval($email));
    if($email == '') {
      throw new Exception($this->errorHandler->getError('userFunctions', 'email_missing'),
          STATUS_CODE_USER_EMAIL_INVALID);
    }

    $name = '[a-zA-Z0-9]((\.|\-|_)?[a-zA-Z0-9])*';
    $domain = '[a-zA-Z]((\.|\-)?[a-zA-Z0-9])*';
    $tld = '[a-zA-Z]{2,8}';
    $regEx = '/^('.$name.')@('.$domain.')\.('.$tld.')$/';
    if(!preg_match($regEx, $email)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'email_invalid'),
          STATUS_CODE_USER_EMAIL_INVALID);
    }
    $result = pg_execute($this->dbConnection, "get_user_row_by_valid_email", array($email));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
    if(pg_num_rows($result) > 0) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'email_already_exists'),
          STATUS_CODE_USER_EMAIL_INVALID);
    }
  }

  public function checkCountry($country) {
    $country = strtoupper($country);
    if(!preg_match("/^[A-Z][A-Z]$/i", $country)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'country_missing'),
          STATUS_CODE_USER_COUNTRY_INVALID);
    }
  }

  public function tokenAuthentication() {
    if(intval($this->session->userLogin_userId) > 0) {
      return true;
    }
    
    if(isset($_REQUEST['token']) && strlen(strval($_REQUEST['token'])) > 0) {
      $authToken = strtolower(strval($_REQUEST['token']));
      $result = pg_execute($this->dbConnection, "get_user_device_login", array($authToken));
       
      if($result && pg_num_rows($result) > 0) {
        $data = pg_fetch_assoc($result);
        pg_free_result($result);
         
        try {
          // we dont't get the password in plaintext, so we can't do a board and wiki login.
          $this->loginCatroid($data['username'], $data['password']);
          return true;
        } catch(Exception $e) {
          return false;
        }
      }
    }
    return false;
  }

  public function login($username, $password) {
    if($this->requestFromBlockedIp()) {
      throw new Exception($this->errorHandler->getError('viewer', 'ip_is_blocked'),
          STATUS_CODE_AUTHENTICATION_FAILED);
    }
    if($this->requestFromTemporarilyBlockedIp()) {
      throw new Exception($this->errorHandler->getError('viewer', 'ip_is_blocked_temporary'),
          STATUS_CODE_AUTHENTICATION_FAILED);
    }
    if($this->requestFromBlockedUser()) {
      throw new Exception($this->errorHandler->getError('viewer', 'user_is_blocked'),
          STATUS_CODE_AUTHENTICATION_FAILED);
    }

    $this->loginCatroid($username, md5($password));
    $this->loginBoard($username, $password);
    $this->loginWiki($username, $password);
    $this->setUserLanguage($this->session->userLogin_userId);
  }

  private function loginCatroid($username, $md5Password) {
    $user = $this->cleanUsername($username);
    $result = pg_execute($this->dbConnection, "get_user_login", array($user, $md5Password));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }

    $row = pg_fetch_assoc($result);
    pg_free_result($result);

    $ip = '';
    if(isset($_SERVER["REMOTE_ADDR"])) {
      $ip = $_SERVER["REMOTE_ADDR"];
    }

    if(is_array($row)) {
      $this->session->userLogin_userId = $row['id'];
      $this->session->userLogin_userNickname = $row['username'];

      $result = pg_execute($this->dbConnection, "reset_failed_attempts", array($ip));
      if(!$result) {
        throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
            STATUS_CODE_SQL_QUERY_FAILED);
      }
      pg_free_result($result);
    } else {
      $result = pg_execute($this->dbConnection, "save_failed_attempts", array($ip));
      if(!$result) {
        throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
            STATUS_CODE_SQL_QUERY_FAILED);
      }
      pg_free_result($result);
      throw new Exception($this->errorHandler->getError('userFunctions', 'password_or_username_wrong'),
          STATUS_CODE_AUTHENTICATION_FAILED);
    }
  }

  private function loginBoard($username, $password) {
    if(!$this->checkLoginData($username, md5($password))) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'password_or_username_wrong'),
          STATUS_CODE_AUTHENTICATION_FAILED);
    }
    
    global $auth, $db, $user;
    $db->sql_connect(DB_HOST_BOARD, DB_USER_BOARD, DB_PASS_BOARD, DB_NAME_BOARD, '', false, defined('PHPBB_DB_NEW_LINK') ? PHPBB_DB_NEW_LINK : false);
 	  $user->session_begin();
 	  $auth->acl($user->data);
 	  $user->setup();
 	  
 	  $auth->login($username, $password, false, 1);
 	  if(intval($user->data['user_id']) <= 0) {
 	    throw new Exception($this->errorHandler->getError('userFunctions', 'board_authentication_failed'),
 	        STATUS_CODE_AUTHENTICATION_FAILED);
 	  }
  }

  private function loginWiki($username, $password) {
    if(!$this->checkLoginData($username, md5($password))) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'password_or_username_wrong'),
          STATUS_CODE_AUTHENTICATION_FAILED);
    }

    require_once(CORE_BASE_PATH . 'include/lib/Snoopy.php');
    $snoopy = new Snoopy();
    $snoopy->curl_path = false;
    $wikiroot = BASE_PATH . 'addons/mediawiki';
    $apiUrl = $wikiroot . "/api.php";

    $loginVars['action'] = "login";
    //wiki login needs first letter capitalized
    $loginVars['lgname'] = mb_convert_case($this->cleanUsername($username), MB_CASE_TITLE, "UTF-8");
    $loginVars['lgpassword'] = $password;
    $loginVars['format'] = "php";

    $snoopy->submit($apiUrl, $loginVars);
    $response = unserialize($snoopy->results);
    if(!isset($response['login']['result']) || !isset($response['login']['token']) ||
        !isset($response['login']['cookieprefix']) || !isset($response['login']['sessionid'])) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'wiki_api_response_incorrect', $snoopy->results),
          STATUS_CODE_AUTHENTICATION_FAILED);
    }

    $loginVars['lgtoken'] = $response['login']['token'];
    $cookiePrefix = $response['login']['cookieprefix'];
    $snoopy->cookies[$cookiePrefix . "_session"] = $response['login']['sessionid'];

    $snoopy->submit($apiUrl, $loginVars);
    $response = unserialize($snoopy->results);

    if(!isset($response['login']['result']) || !isset($response['login']['lgtoken']) ||
        !isset($response['login']['cookieprefix']) || !isset($response['login']['lgusername']) ||
        !isset($response['login']['lgtoken']) || !isset($response['login']['sessionid'])) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'wiki_api_response_incorrect', $snoopy->results),
          STATUS_CODE_AUTHENTICATION_FAILED);
    }

    $cookieExpire = 0;//time() + (60*60*24*2);
    $cookieDomain = '';

    setcookie($cookiePrefix . 'UserName', $response['login']['lgusername'], $cookieExpire, "/", $cookieDomain, false, true);
    setcookie($cookiePrefix . 'UserID', $response['login']['lguserid'], $cookieExpire, "/", $cookieDomain, false, true);
    setcookie($cookiePrefix . 'Token', $response['login']['lgtoken'], $cookieExpire, "/", $cookieDomain, false, true);
    setcookie($cookiePrefix . '_session', $response['login']['sessionid'], 0, "/", $cookieDomain, false, true);
  }

  private function setUserLanguage($userId) {
    $result = pg_execute($this->dbConnection, "get_user_language", array($userId));

    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }

    $row = pg_fetch_assoc($result);
    pg_free_result($result);

    if(strlen($row['language']) > 1) {
      $this->languageHandler->setLanguageCookie($row['language']);
    }
  }

  public function logout() {
    $this->logoutCatroid();
    $this->logoutBoard();
    $this->logoutWiki();
  }

  private function logoutCatroid() {
    $this->session->userLogin_userId = 0;
    $this->session->userLogin_userNickname = '';
  }

  private function logoutBoard() {
    global $auth, $db, $user;
    $db->sql_connect(DB_HOST_BOARD, DB_USER_BOARD, DB_PASS_BOARD, DB_NAME_BOARD, '', false, defined('PHPBB_DB_NEW_LINK') ? PHPBB_DB_NEW_LINK : false);
    $user->session_begin();
    $auth->acl($user->data);
    $user->setup();
    $user->session_kill();
  }

  private function logoutWiki() {
    require_once(CORE_BASE_PATH . 'include/lib/Snoopy.php');
    $snoopy = new Snoopy();
    $snoopy->curl_path = false;
    $wikiroot = BASE_PATH.'addons/mediawiki';
    $apiUrl = $wikiroot . "/api.php?action=logout";

    $logoutVars['action'] = "logout";
    $snoopy->submit($apiUrl, $logoutVars);
    $response = $snoopy->results;

    $now = date('YmdHis', time());
    $cookieExpires = time() + (60*60*24*2);
    $cookieExpired = time() - (60*60*24*2);
    $cookieDomain = '';

    setcookie('catrowikiLoggedOut', $now, $cookieExpires, "/", $cookieDomain, false, true);
    setcookie('catrowiki_session', '', $cookieExpired, "/", $cookieDomain, false, true);
    setcookie('catrowikiUserID', '', $cookieExpired, "/", $cookieDomain, false, true);
    setcookie('catrowikiUserName', '', $cookieExpired, "/", $cookieDomain, false, true);
    setcookie('catrowikiToken', '', $cookieExpired, "/", $cookieDomain, false, true);
  }

  public function register($postData) {
    try {
      $this->checkUsername($postData['registrationUsername']);
      $this->checkPassword($postData['registrationUsername'], $postData['registrationPassword']);
      $this->checkEmail($postData['registrationEmail']);
      $this->checkCountry($postData['registrationCountry']);
       
      $this->registerCatroidId = $this->registerCatroid($postData);
      $this->registerBoardId = $this->registerBoard($postData);
      $this->registerWikiId = $this->registerWiki($postData);
      	
      $this->sendRegistrationEmail($postData);
    } catch(Exception $e) {
      $this->undoRegister();
      throw new Exception($e->getMessage(), $e->getCode());
    }
  }

  private function registerCatroid($postData) {
    $username = checkUserInput($postData['registrationUsername']);
    $usernameClean = $this->cleanUsername($username);
    $md5password = md5($postData['registrationPassword']);
    $authToken = $this->generateAuthenticationToken($username, $postData['registrationPassword']);

    $email = checkUserInput($postData['registrationEmail']);
    $ipRegistered = $_SERVER['REMOTE_ADDR'];
    $country = checkUserInput($postData['registrationCountry']);
    $status = USER_STATUS_STRING_ACTIVE;
     
    $dateOfBirth = NULL;
    $year = checkUserInput($postData['registrationYear']);
    $month = checkUserInput($postData['registrationMonth']);
     
    if($month != 0 && $year != 0) {
      $dateOfBirth = $year . '-' . sprintf("%02d", $month) . '-01 00:00:01';
    }

    $gender = checkUserInput($postData['registrationGender']);
    $city = checkUserInput($postData['registrationCity']);
    $language = $this->languageHandler->getLanguage();

    $result = pg_execute($this->dbConnection, "user_registration", array($username, $usernameClean, $md5password,
        $email, $dateOfBirth, $gender, $country, $city, $ipRegistered, $status, $authToken, $language));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }

    $row = pg_fetch_assoc($result);
    pg_free_result($result);

    return $row['id'];
  }

  private function registerBoard($postData) {
    global $auth, $db, $phpbb_root_path, $user;
    $db->sql_connect(DB_HOST_BOARD, DB_USER_BOARD, DB_PASS_BOARD, DB_NAME_BOARD, '', false, defined('PHPBB_DB_NEW_LINK') ? PHPBB_DB_NEW_LINK : false);
    $user->session_begin();
    $auth->acl($user->data);
    $user->setup();

    $username = checkUserInput($postData['registrationUsername']);
    $password = md5($postData['registrationPassword']);
    $email = checkUserInput($postData['registrationEmail']);

    $user_row = array(
        'username' => $username,
        'user_password' => $password,
        'user_email' => '', //$email,
        'group_id' => '2',
        'user_timezone' => '0',
        'user_dst' => '0',
        'user_lang' => 'en',
        'user_type' => '0',
        'user_actkey' => '',
        'user_dateformat' => 'D M d, Y g:i a',
        'user_style' => '1',
        'user_regdate' => time()
    );

    if($phpbb_user_id = user_add($user_row)) {
      return $phpbb_user_id;
    } else {
      throw new Exception($this->errorHandler->getError('userFunctions', 'board_registration_failed'),
          STATUS_CODE_USER_REGISTRATION_FAILED);
    }
  }

  private function registerWiki($postData) {
    $wikiDbConnection = pg_connect("host=" . DB_HOST_WIKI . " dbname=" . DB_NAME_WIKI . " user=" . DB_USER_WIKI .
        " password=" . DB_PASS_WIKI);
    if(!$wikiDbConnection) {
      throw new Exception($this->errorHandler->getError('db', 'connection_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }

    $username = checkUserInput($postData['registrationUsername']);
    $username = $this->cleanUsername($username);
    $username = mb_convert_case($username, MB_CASE_TITLE, "UTF-8");
    $userToken = md5($username);
    $hexSalt = sprintf("%08x", mt_rand(0, 0x7fffffff));
    $hash = md5($hexSalt . '-' . md5($postData['registrationPassword']));
    $password = ":B:$hexSalt:$hash";

    pg_prepare($wikiDbConnection, "add_wiki_user", "INSERT INTO mwuser (user_name, user_token, user_password, user_registration) VALUES (\$1, \$2, \$3, now()) RETURNING user_id");
    $result = pg_execute($wikiDbConnection, "add_wiki_user", array($username, $userToken, $password));
    pg_query($wikiDbConnection, 'DEALLOCATE add_wiki_user');
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }

    $row = pg_fetch_assoc($result);
    pg_free_result($result);
    pg_close($wikiDbConnection);

    return $row['user_id'];
  }
  
  public function generateAuthenticationToken($username, $password) {
    $md5user = md5(strtolower($username));
    $md5password = md5($password);
    return md5($md5user . ':' . $md5password);
  }

  public function undoRegister() {
    $this->undoRegisterCatroid();
    $this->undoRegisterBoard();
    $this->undoRegisterWiki();
  }

  private function undoRegisterCatroid() {
    if($this->registerCatroidId != 0) {
      $result = pg_execute($this->dbConnection, "delete_user_by_id", array($this->registerCatroidId));
      if($result) {
        pg_free_result($result);
      }
      $this->registerCatroidId = 0;
    }
  }

  private function undoRegisterBoard() {
    if($this->registerBoardId != 0) {
      global $auth, $db, $phpbb_root_path, $user;
      $db->sql_connect(DB_HOST_BOARD, DB_USER_BOARD, DB_PASS_BOARD, DB_NAME_BOARD, '', false, defined('PHPBB_DB_NEW_LINK') ? PHPBB_DB_NEW_LINK : false);
      $user->session_begin();
      $auth->acl($user->data);
      $user->setup();
       
      user_delete('remove', $this->registerBoardId);
      $this->registerBoardId = 0;
    }
  }

  private function undoRegisterWiki() {
    if($this->registerWikiId != 0) {
      $wikiDbConnection = pg_connect("host=" . DB_HOST_WIKI . " dbname=" . DB_NAME_WIKI . " user=" . DB_USER_WIKI .
          " password=" . DB_PASS_WIKI);
       
      pg_prepare($wikiDbConnection, "delete_wiki_user", "DELETE FROM mwuser WHERE user_id=$1");
      $result = pg_execute($wikiDbConnection, "delete_wiki_user", array($this->registerWikiId));
      if($result) {
        pg_free_result($result);
      }
      pg_close($wikiDbConnection);
      $this->registerWikiId = 0;
    }
  }

  public function recover($userData) {
    $userData = trim(strval($userData));
     
    if($userData == '') {
      throw new Exception($this->errorHandler->getError('userFunctions', 'userdata_missing'),
          STATUS_CODE_USER_POST_DATA_MISSING);
    }
     
    $data = $this->getUserDataForRecovery($userData);
    $hash = $this->createUserHash($data);
    $this->sendPasswordRecoveryEmail($hash, $data['id'], $data['username'], $data['email']);
  }

  public function validateEmail($hash) {
    $hash = trim(strval($hash));
    
    $this->isValidationHashValid($hash);
    $result = pg_execute($this->dbConnection, "validate_email_by_hash", array($hash));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
    pg_free_result($result);
  }
  
  public function updateAvatar() {
    $maxAvatarSize = 128;
    if(intval($this->session->userLogin_userId) > 0 && isset($_FILES['file'])) {
      $data = "";
      
      $avatarSource = 0;
      switch($_FILES['file']['type']) {
        case "image/jpeg":
          $avatarSource = imagecreatefromjpeg($_FILES['file']['tmp_name']);
          break;
        case "image/png":
          $avatarSource = imagecreatefrompng($_FILES['file']['tmp_name']);
          break;
        case "image/gif":
          $avatarSource = imagecreatefromgif($_FILES['file']['tmp_name']);
          break;
        default:
          throw new Exception($this->errorHandler->getError('userFunctions', 'unsupported_image'),
              STATUS_CODE_UPLOAD_UNSUPPORTED_MIME_TYPE);
      }
      
      if($avatarSource) {
        $desiredWidth = $width = imagesx($avatarSource);
        $desiredHeight = $height = imagesy($avatarSource);
        
        if($width == 0 || $height == 0) {
          throw new Exception($this->errorHandler->getError('userFunctions', 'unsupported_image'),
              STATUS_CODE_UPLOAD_UNSUPPORTED_FILE_TYPE);
        }
        
        if(max($width, $height) > $maxAvatarSize) {
          if($width > $height) {
            $desiredHeight = round(($maxAvatarSize / $width) * $height);
            $desiredWidth = $maxAvatarSize;
          } else {
            $desiredWidth = round(($maxAvatarSize / $height) * $width);
            $desiredHeight = $maxAvatarSize;
          }
        }
        
        $avatar = imagecreatetruecolor($maxAvatarSize, $maxAvatarSize);
        if(!$avatar) {
          throw new Exception($this->errorHandler->getError('userFunctions', 'avatar_creation_failed'),
              STATUS_CODE_USER_AVATER_CREATION_FAILED);
        }
        imagesavealpha($avatar, true);
        imagefill($avatar, 0, 0, imagecolorallocatealpha($avatar, 0, 0, 0, 127));
        
        if(!imagecopyresampled($avatar, $avatarSource, floor(($maxAvatarSize - $desiredWidth) / 2.0),
            floor(($maxAvatarSize - $desiredHeight) / 2.0), 0, 0, $desiredWidth, $desiredHeight, $width, $height)) {
          imagedestroy($avatar);
          throw new Exception($this->errorHandler->getError('userFunctions', 'avatar_creation_failed'),
              STATUS_CODE_USER_AVATER_CREATION_FAILED);
        }

        $temp = tempnam("/tmp", "avatar");
        if(!imagepng($avatar, $temp, 7)) {
          imagedestroy($avatar);
          throw new Exception($this->errorHandler->getError('userFunctions', 'avatar_creation_failed'),
              STATUS_CODE_USER_AVATER_CREATION_FAILED);
        }
        imagedestroy($avatar);
        
        $data = file_get_contents($temp);
      }
      
      $outputImage = "data:image/png;base64," . base64_encode($data);
      $result = pg_execute($this->dbConnection, "update_avatar_by_id", array($outputImage, $this->session->userLogin_userId));
      if(!$result) {
        throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
            STATUS_CODE_SQL_QUERY_FAILED);
      }
      pg_free_result($result);
      return $outputImage;
    }
    throw new Exception($this->errorHandler->getError('upload', 'missing_file_data'),
              STATUS_CODE_UPLOAD_MISSING_DATA);
  } 

  public function updatePassword($username, $newPassword) {
    $username = $this->cleanUsername($username);

    $this->updateCatroidPassword($username, $newPassword);
    $this->updateBoardPassword($username, $newPassword);
    $this->updateWikiPassword($username, $newPassword);
  }

  private function updateCatroidPassword($username, $password) {
    $password = md5($password);
    $result = pg_execute($this->dbConnection, "update_password_by_username", array($password, $username));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
    pg_free_result($result);
  }

  private function updateBoardPassword($username, $password) {
    global $db;
    $db->sql_connect(DB_HOST_BOARD, DB_USER_BOARD, DB_PASS_BOARD, DB_NAME_BOARD, '', false, defined('PHPBB_DB_NEW_LINK') ? PHPBB_DB_NEW_LINK : false);
    $password = phpbb_hash($password);

    $sql = "UPDATE phpbb_users SET user_password='" . $password . "',
    user_pass_convert = 0 WHERE username_clean='" . $username . "'";

    if(!$db->sql_query($sql)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'password_new_board_update_failed'),
          STATUS_CODE_USER_NEW_PASSWORD_BOARD_UPDATE_FAILED);
    }
  }

  private function updateWikiPassword($username, $password) {
    $wikiDbConnection = pg_connect("host=" . DB_HOST_WIKI . " dbname=" . DB_NAME_WIKI . " user=" . DB_USER_WIKI .
        " password=" . DB_PASS_WIKI);
    if(!$wikiDbConnection) {
      throw new Exception($this->errorHandler->getError('db', 'connection_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_CONNECTION_FAILED);
    }

    $username = mb_convert_case($username, MB_CASE_TITLE, "UTF-8");
    $hexSalt = sprintf("%08x", mt_rand(0, 0x7fffffff));
    $hash = md5($hexSalt.'-'.md5($password));
    $password = ":B:$hexSalt:$hash";

    pg_prepare($wikiDbConnection, "update_wiki_user_password", "UPDATE mwuser SET user_password=$1 WHERE user_name=$2");
    $result = pg_execute($wikiDbConnection, "update_wiki_user_password", array($password, $username));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
    pg_free_result($result);
    pg_close($wikiDbConnection);
  }

  public function updateCity($city) {
    if($this->session->userLogin_userId > 0) {
      $result = pg_execute($this->dbConnection, "update_user_city", array(checkUserInput($city),
          $this->session->userLogin_userId));
       
      if(!$result) {
        throw new Exception($this->errorHandler->getError('userFunctions', 'city_update_failed', pg_last_error($this->dbConnection)),
            STATUS_CODE_USER_UPDATE_CITY_FAILED);
      }
      pg_free_result($result);
    } else {
      throw new Exception($this->errorHandler->getError('userFunctions', 'city_update_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_USER_UPDATE_CITY_FAILED);
    }
  }

  public function updateCountry($country) {
    if($this->session->userLogin_userId > 0) {
      $this->checkCountry($country);
      $result = pg_execute($this->dbConnection, "update_user_country", array($country, $this->session->userLogin_userId));

      if(!$result) {
        throw new Exception($this->errorHandler->getError('userFunctions', 'country_update_failed', pg_last_error($this->dbConnection)),
            STATUS_CODE_USER_UPDATE_COUNTRY_FAILED);
      }
      pg_free_result($result);
    } else {
      throw new Exception($this->errorHandler->getError('userFunctions', 'country_update_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_USER_UPDATE_COUNTRY_FAILED);
    }
  }

  public function updateGender($gender) {
    if($this->session->userLogin_userId > 0) {
      $result = pg_execute($this->dbConnection, "update_user_gender", array($gender, $this->session->userLogin_userId));
      if(!$result) {
        throw new Exception($this->errorHandler->getError('userFunctions', 'gender_update_failed', pg_last_error($this->dbConnection)),
            STATUS_CODE_USER_UPDATE_GENDER_FAILED);
      }
      pg_free_result($result);
    } else {
      throw new Exception($this->errorHandler->getError('userFunctions', 'gender_update_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_USER_UPDATE_GENDER_FAILED);
    }
  }

  public function updateBirthday($birthdayMonth, $birthdayYear) {
    if($this->session->userLogin_userId > 0) {
      if($birthdayMonth == 0 && $birthdayYear == 0) {
        $result = pg_execute($this->dbConnection, "delete_user_birth", array($this->session->userLogin_userId));
        if(!$result) {
          throw new Exception($this->errorHandler->getError('userFunctions', 'birth_update_failed', pg_last_error($this->dbConnection)),
              STATUS_CODE_USER_UPDATE_BIRTHDAY_FAILED);
        }
        pg_free_result($result);
      } else if($birthdayMonth > 0 && $birthdayYear > 1) {
        $birthday = sprintf("%04d", $birthdayYear) . '-' . sprintf("%02d", $birthdayMonth) . '-01 00:00:01';
        $result = pg_execute($this->dbConnection, "update_user_birth", array($birthday, $this->session->userLogin_userId));
        if(!$result) {
          throw new Exception($this->errorHandler->getError('userFunctions', 'birth_update_failed', pg_last_error($this->dbConnection)),
              STATUS_CODE_USER_UPDATE_BIRTHDAY_FAILED);
        }
        pg_free_result($result);
      }
    } else {
      throw new Exception($this->errorHandler->getError('userFunctions', 'birth_update_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_USER_UPDATE_BIRTHDAY_FAILED);
    }
  }

  public function updateLanguage($language) {
    if(intval($this->session->userLogin_userId) > 0) {
      if($language == '') {
        throw new Exception($this->errorHandler->getError('userFunctions', 'language_update_failed', pg_last_error($this->dbConnection)),
            STATUS_CODE_USER_UPDATE_LANGUAGE_FAILED);
      }

      $result = pg_execute($this->dbConnection, "update_user_language_by_id", array($language, $this->session->userLogin_userId));
      if(!$result) {
        throw new Exception($this->errorHandler->getError('userFunctions', 'language_update_failed', pg_last_error($this->dbConnection)),
            STATUS_CODE_USER_UPDATE_LANGUAGE_FAILED);
      }
      pg_free_result($result);
    }
  }

  public function getUserData($username) {
    $username = trim(strval($username));
    $result = pg_execute($this->dbConnection, "get_user_row_by_username", array($username));
     
    if(!$result) {
      return array();
    }
     
    $user = array();
    if(pg_num_rows($result) > 0) {
      $user = pg_fetch_assoc($result);
    }
    
    if($user['avatar'] == NULL) {
      $user['avatar'] = BASE_PATH . "images/symbols/avatar_boys.png";
    }
     
    pg_free_result($result);
    return $user;
  }

  public function getUserDataByRecoveryHash($hash) {
    $hash = trim(strval($hash));
    $result = pg_execute($this->dbConnection, "get_user_row_by_recovery_hash", array($hash));
     
    if(!$result) {
      return array();
    }
     
    $userData = array();
    if(pg_num_rows($result) > 0) {
      $userData = pg_fetch_assoc($result);
    }
     
    pg_free_result($result);
    return $userData;
  }

  public function getUserDataForRecovery($userData) {
    $userData = trim(strval($userData));
    $result = pg_execute($this->dbConnection, "get_user_row_by_username_or_username_clean", array($userData, $this->cleanUsername($userData)));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }

    $userRow = array();
    if(pg_num_rows($result) == 1) {
      $userRow = pg_fetch_assoc($result);
    }

    pg_free_result($result);
    if(!empty($userRow)) {
      return $userRow;
    }
    
    $result = pg_execute($this->dbConnection, "get_user_row_by_email", array($userData));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
    
    if(pg_num_rows($result) == 1) {
      $userRow = pg_fetch_assoc($result);
    }
    
    pg_free_result($result);
    if(!empty($userRow)) {
      return $userRow;
    }

    throw new Exception($this->errorHandler->getError('userFunctions', 'userdata_not_exists'),
        STATUS_CODE_USER_RECOVERY_NOT_FOUND);
  }

  public function getEmailAddresses($userId) {
    $result = pg_execute($this->dbConnection, "get_user_emails_by_id", array(intval($userId)));
    if(!$result) {
      return array();
    }
     
    $emails = array();
    while($email = pg_fetch_assoc($result)) {
      array_push($emails, array('address' => $email['email'], 'valid' => intval($email['validated'] == 't')));
    }
    pg_free_result($result);
    
    return $emails;
  }

  public function addEmailAddress($userId, $email) {
    $this->checkEmail($email);

    $userEmails = $this->getEmailAddresses($userId);
    foreach($userEmails as $current) {
      if($current['address'] === $email) {
        throw new Exception($this->errorHandler->getError('userFunctions', 'email_address_exists'),
            STATUS_CODE_USER_ADD_EMAIL_EXISTS);
      }
    }

    $result = pg_execute($this->dbConnection, "add_user_email", array($userId, $email));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
    pg_free_result($result);
    
    $data = $this->getUserDataForRecovery($email);
    $hash = $this->createUserHash($data);
    try {
      while(true) {
        $this->isValidationHashValid($hash);
        $hash = $this->createUserHash($data);
      }
    } catch(Exception $e) {
      if($e->getCode() != STATUS_CODE_USER_RECOVERY_EXPIRED) {
        throw $e;
      }
    }
    
    $this->sendEmailAddressValidatingEmail($hash, $data['id'], $data['username'], $email);
  }

  public function deleteEmailAddress($email) {
    $userId = intval($this->session->userLogin_userId);

    $numberOfValidEmailAddresses = 0;
    foreach($this->getEmailAddresses($userId) as $emails) {
      if($emails['address'] == $email && !$emails['valid']) {
        $numberOfValidEmailAddresses++;
      }
      if($emails['valid']) {
        $numberOfValidEmailAddresses++;
      }
    }
    
    if($userId == 1 && $numberOfValidEmailAddresses < 3) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'email_update_of_catroweb_failed'),
          STATUS_CODE_USER_DELETE_EMAIL_FAILED);
    } elseif($numberOfValidEmailAddresses < 2) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'email_delete_failed'),
          STATUS_CODE_USER_DELETE_EMAIL_FAILED);
    }

    $result = pg_execute($this->dbConnection, "get_user_email_by_email", array($email));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }

    $getEmailFromAdditionalEmailsList = (pg_num_rows($result) > 0);
    pg_free_result($result);

    if($getEmailFromAdditionalEmailsList) {
      $result = pg_execute($this->dbConnection, "update_user_email_from_additional_email_by_user_email", array($userId));
      if(!$result) {
        throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
            STATUS_CODE_SQL_QUERY_FAILED);
      }
      pg_free_result($result);

      $result = pg_execute($this->dbConnection, "delete_user_email_from_additional_email_by_user_email", array($userId));
      if(!$result) {
        throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
            STATUS_CODE_SQL_QUERY_FAILED);
      }
      pg_free_result($result);
    } else {
      $result = pg_execute($this->dbConnection, "delete_user_additional_email_by_email", array($email));
      if(!$result) {
        throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
            STATUS_CODE_SQL_QUERY_FAILED);
      }
      pg_free_result($result);
    }
  }

  public function createUserHash($userData) {
    if(is_array($userData)) {
      $data = str_shuffle($userData['username'] . ':' . $userData['email']);
      $salt = hash("md5", str_shuffle($userData['password']) . rand());
      $hash = hash("md5", $data . ':' . $salt);
      return $hash;
    }
    throw new Exception($this->errorHandler->getError('userFunctions', 'create_hash_failed'),
        STATUS_CODE_USER_RECOVERY_HASH_CREATION_FAILED);
  }

  public function sendRegistrationEmail($postData) {
    $catroidProfileUrl = BASE_PATH . 'catroid/profile';
    $catroidLoginUrl = BASE_PATH . 'catroid/login';
    $catroidRecoveryUrl = BASE_PATH . 'catroid/passwordrecovery';

    if(SEND_NOTIFICATION_USER_EMAIL) {
      $username = $postData['registrationUsername'];
      $password = $postData['registrationPassword'];
      $userMailAddress = $postData['registrationEmail'];
      $mailSubject = $this->languageHandler->getString('registration_mail_subject');
      $mailText =    $this->languageHandler->getString('registration_mail_text_row1') . "\r\n\r\n";
      $mailText .=   $this->languageHandler->getString('registration_mail_text_row2') . "\r\n";
      $mailText .=   $this->languageHandler->getString('registration_mail_text_row3', $username) . "\r\n";
      $mailText .=   $this->languageHandler->getString('registration_mail_text_row5', $password) . "\r\n\r\n";
      $mailText .=   $this->languageHandler->getString('registration_mail_text_row6') . "\r\n\r\n";
      $mailText .=   $this->languageHandler->getString('registration_mail_text_row7') . "\r\n";
      $mailText .=   $catroidLoginUrl."\n\n";
      $mailText .=   $this->languageHandler->getString('registration_mail_text_row8') . "\r\n";
      $mailText .=   $catroidProfileUrl."\n\n";
      $mailText .=   $this->languageHandler->getString('registration_mail_text_row9') . "\r\n";
      $mailText .=   $catroidRecoveryUrl."\n\n";
      $mailText .=   $this->languageHandler->getString('registration_mail_text_row10') . "\r\n";
      $mailText .=   $this->languageHandler->getString('registration_mail_text_row11');

      if(!$this->mailHandler->sendUserMail($mailSubject, $mailText, $userMailAddress)) {
        throw new Exception($this->errorHandler->getError('userFunctions', 'sendmail_failed', '', CONTACT_EMAIL),
            STATUS_CODE_SEND_MAIL_FAILED);
      }
    }
  }

  public function sendPasswordRecoveryEmail($userHash, $userId, $userName, $userEmail) {
    $catroidPasswordResetUrl = BASE_PATH . 'catroid/passwordrecovery?c=' . $userHash;
    $catroidProfileUrl = BASE_PATH . 'catroid/profile';
    $catroidLoginUrl = BASE_PATH . 'catroid/login';

    $result = pg_execute($this->dbConnection, "update_recovery_hash_recovery_time_by_id", array($userHash, time(), $userId));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
    pg_free_result($result);
     
    if(DEVELOPMENT_MODE) {
      throw new Exception($catroidPasswordResetUrl, STATUS_CODE_OK);
    }

    if(SEND_NOTIFICATION_USER_EMAIL) {
      $mailSubject = $this->languageHandler->getString('recovery_mail_subject');
      $mailText =    $this->languageHandler->getString('recovery_mail_text_row1', $userName) . "\r\n\r\n";
      $mailText .=   $this->languageHandler->getString('recovery_mail_text_row2') . "\r\n\r\n";
      $mailText .=   $this->languageHandler->getString('recovery_mail_text_row3') . "\r\n";
      $mailText .=   $catroidPasswordResetUrl . "\r\n\r\n";
      $mailText .=   $this->languageHandler->getString('recovery_mail_text_row5') . "\r\n\r\n";
      $mailText .=   $this->languageHandler->getString('recovery_mail_text_row6') . "\r\n";
      $mailText .=   $catroidLoginUrl . "\r\n\r\n";
      $mailText .=   $this->languageHandler->getString('recovery_mail_text_row7') . "\r\n";
      $mailText .=   $catroidProfileUrl . "\r\n\r\n\r\n";
      $mailText .=   $this->languageHandler->getString('recovery_mail_text_row8') . "\r\n";
      $mailText .=   $this->languageHandler->getString('recovery_mail_text_row9') . "\r\n";
      
      if(!$this->mailHandler->sendUserMail($mailSubject, $mailText, $userEmail)) {
        throw new Exception($this->errorHandler->getError('userFunctions', 'sendmail_failed', '', CONTACT_EMAIL),
            STATUS_CODE_SEND_MAIL_FAILED);
      }
    }
  }

  public function sendEmailAddressValidatingEmail($userHash, $userId, $userName, $userEmail) {
    $catroidValidationUrl = BASE_PATH . 'catroid/emailvalidation?c=' . $userHash;
    
    $result = pg_execute($this->dbConnection, "update_email_validation_hash_by_email_and_id", array($userHash, $userEmail, $userId));
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)),
          STATUS_CODE_SQL_QUERY_FAILED);
    }
    pg_free_result($result);

    if(DEVELOPMENT_MODE) {
      throw new Exception($catroidValidationUrl, STATUS_CODE_OK);
    }
    
    $mailSubject = $this->languageHandler->getString('email_validation_subject');
    $mailText =    $this->languageHandler->getString('email_validation_text_row1', $userName) . "\r\n\r\n";
    $mailText .=   "{unwrap}" . $catroidValidationUrl . "{/unwrap}\r\n";
    $mailText .=   $this->languageHandler->getString('email_validation_text_row2') . "\r\n";
    $mailText .=   $this->languageHandler->getString('email_validation_text_row3');
    

    if(!$this->mailHandler->sendUserMail($mailSubject, $mailText, $userEmail)) {
      throw new Exception($this->errorHandler->getError('userFunctions', 'sendmail_failed', '', CONTACT_EMAIL),
          STATUS_CODE_SEND_MAIL_FAILED);
    }
  }
  
  private function cleanUsername($username) {
    $username_clean = utf8_clean_string(trim($username));
    return $username_clean;
  }

  public function __destruct() {
    parent::__destruct();
  }
}

?>