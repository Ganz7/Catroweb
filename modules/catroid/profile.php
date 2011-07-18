<?php
/*    Catroid: An on-device graphical programming language for Android devices
 *    Copyright (C) 2010-2011 The Catroid Team
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

class profile extends CoreAuthenticationNone {

  public function __construct() {
    parent::__construct();
    $this->setupBoard();
    $this->addCss('profile.css');
    $this->addJs("profile.js");
  }

  public function __default() {
    if($_GET['method'] && $this->checkUserValid($_GET['method'])) {
      if( strcmp($_GET['method'], $this->session->userLogin_userNickname) == 0 ) {
        $this->ownProfile = true;
        $this->requestedUser = $this->session->userLogin_userNickname;
      }
      else {
        $this->ownProfile = false;
        $this->requestedUser = $_GET['method'];
      }
    }
    else {
      if($this->session->userLogin_userId > 0) {
        header("Location: ".BASE_PATH."catroid/profile/".$this->session->userLogin_userNickname);
        exit;
      }
      else {
        header("Location: ".BASE_PATH."catroid/login/".$this->session->userLogin_userNickname);
        exit;
      }
    }
    $this->initDynamicProfileData($this->requestedUser);
    $this->setWebsiteTitle($this->languageHandler->getString('title', $this->requestedUser));
  }
  
  public function profilePasswordRequestQuery() {
    $postData = $_POST;
    if($postData) {
      if($this->doChangePassword($this->session->userLogin_userNickname, $postData['profileOldPassword'], $postData['profileNewPassword'])) {
        $this->statusCode = 200;
        $this->answer_ok .= $this->languageHandler->getString('password_success');
        return true;
      } else {
        $this->statusCode = 500;
        return false;
      }
    }
  }
  
//  public function profileEmailRequestQuery() {
//    $postData = $_POST;
//    if($postData) {
//      if($this->doChangeEmailAddress($this->session->userLogin_userNickname, $postData['profileEmail'])) {
//        $this->statusCode = 200;
//        $this->answer_ok .= $this->languageHandler->getString('email_success');
//        return true;
//      } else {
//        $this->statusCode = 500;
//        return false;
//      }
//    }
//  }
  
  
  // all necessary data for the text field generatrion
  public function profileEmailRequestQuery() {
    $postData = $_POST;
    if($postData) {
      $requestType = $postData['requestType'];
      switch($requestType) {
        case 'change':
          if($this->doChangeEmailAddress($this->session->userLogin_userNickname, $postData['profileNewEmail'], $postData['profileOldEmail'])) {
            $this->statusCode = 200;
            $this->answer_ok .= $this->languageHandler->getString('email_change_successful');
            return true;
          }
          else {
            $this->statusCode = 500;
            return false;
          } 
          break;
        case 'add':
          if($this->doAddEmailAddress($this->session->userLogin_userNickname, $postData['profileNewEmail'])) {
            $this->statusCode = 200;
            $this->answer_ok .= $this->languageHandler->getString('email_add_successful');
            return true;
          }
          else {
            $this->statusCode = 500;
            return false;
          }
          break;
        case 'delete':
          if($this->doDeleteEmailAddress($this->session->userLogin_userNickname, $postData['profileEmail'])) {
            $this->statusCode = 200;
            $this->answer_ok .= $this->languageHandler->getString('email_delete_success');
            return true;
          }
          else {
            $this->statusCode = 500;
            return false;
          }
          break;
      }
//      if($postData['profileEmail'] == '' || strlen($postData['profileEmail']) == 0 || empty($postData['profileEmail']) || strcmp('', $postData['profileEmail']) != 0) {
//        if(($postData['profileEmailInputId']) == 0) {
//          if($this->doAddEmailAddress($this->session->userLogin_userNickname, $postData['profileEmail'])) {
//            
//          }
//        } 
//      } 
//      else {
//        if($this->doAddEmailAddress($this->session->userLogin_userNickname, $postData['profileEmail'])) {
//  //        if($this->getUserEmails($postData['username'])) {
//  //          $this->statusCode = 200;
//  //          $this->answer_ok .= $this->languageHandler->getString('email_success');
//  //          return true;
//  //        }
//  //      } else {
//  //        $this->statusCode = 500;
//  //        return false;
//        }
//      }

      
    }
  }
  
  
  public function profileGetEmailCountRequestQuery() {
      $this->emailCount = $this->getNumberOfUserEmails($this->session->userLogin_userId);
  }
  

  public function profileCountryRequestQuery() {
    $postData = $_POST;
    if($postData) {
      if($this->doChangeUserCountry($this->session->userLogin_userNickname, $postData['profileCountry'])) {
        $this->statusCode = 200;
        $this->answer_ok .= $this->languageHandler->getString('password_success');
        return true;
      } else {
        $this->statusCode = 500;
        return false;
      }
    }
  }
  
  private function doChangeEmailAddress($username, $new_email, $old_email) {
    $user_id = false;
    try {
      $user_id = $this->checkEmail($new_email, $old_email);
    } catch(Exception $e) {
      $this->answer .= $e->getMessage().'<br>';
    }
    
    if($user_id) {
      try {
        $query = "EXECUTE update_user_email_by_user_email('$new_email', '$old_email')";
        $result = @pg_query($this->dbConnection, $query);
        if(!$result || pg_num_rows($result) <= 0) {
          $query = "EXECUTE update_user_additional_email_by_user_email('$new_email', '$old_email')";
          $result = @pg_query($this->dbConnection, $query);
          if(!$result) {
            throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
          }
        }
      } catch(Exception $e) {
        $this->answer .= $this->errorHandler->getError('profile', 'email_update_failed', $e->getMessage()).'<br>';
        $user_id = false;
      }
    }
    return $user_id;
  }
  
  private function doAddEmailAddress($username, $email) {
    $user_id = 0;
    $email_valid = false;
    try {
      $this->checkEmail($email);
      $user_id = $this->session->userLogin_userId;
      $user_emails = $this->getUserEmailsArray($user_id);
      $x = 0;
      while($x<count($user_emails)) {
        if(strcmp($email, $user_emails[$x]) == 0) {
           throw new Exception($this->errorHandler->getError('profile', 'email_address_exists'));
        }
        else {
          $email_valid = true;
          break;
        }      
      }
    } catch(Exception $e) {
      $this->answer .= $e->getMessage().'<br>';
    }
    
    if($email_valid) {
      try {
        $query = "EXECUTE add_user_email('$user_id', '$email')";
        $result = @pg_query($this->dbConnection, $query);
        if(!$result) {
          throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
        }
      } catch(Exception $e) {
        $this->answer .= $this->errorHandler->getError('profile', 'email_update_failed', $e->getMessage()).'<br>';
        $email_valid = false;
      }
    }
    return $email_valid;
  } 
  
  private function doDeleteEmailAddress($username, $email) {
    $email_valid = true;
    $user_id = $this->session->userLogin_userId;
//    try {
//      //$user_id = $this->checkEmail($email);
//      
//      $user_emails = $this->getUserEmailsArray($user_id);
//      $x = 0;
//      while($x<count($user_emails)) {
//        if(strcmp($email, $user_emails[$x]) != 0) {
//           throw new Exception($this->errorHandler->getError('profile', 'email_address_string_changed'));
//        }
//        else {
//          $email_valid = true;
//          break;
//        }      
//      }
//    } catch(Exception $e) {
//      $this->answer .= $e->getMessage().'<br>';
//    }
    
    if($email_valid) {
      try {
        $this->answer .= 'get_user_email_by_email '.$email.'<br>';
        $query = "EXECUTE get_user_email_by_email('$email')";
        $result = @pg_query($this->dbConnection, $query);
        if(!$result) { 
          throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
        }
        
        if(pg_num_rows($result) > 0) {
          $query = "EXECUTE update_user_email_from_additional_email_by_user_email('$username', '$user_id')";
          $result = @pg_query($this->dbConnection, $query);
          if(!$result) {
            $email_valid = false;
            throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
          }
          
          $query = "EXECUTE delete_user_email_from_additional_email_by_user_email('$user_id')";
          $result = @pg_query($this->dbConnection, $query);
          if(!$result) {
            $email_valid = false;
            throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
          }
        }
        else {
          $this->answer .= 'delete_user_additional_email_by_email '.$email.'<br>';
          $query = "EXECUTE delete_user_additional_email_by_email('$email')";
          $result = @pg_query($this->dbConnection, $query);
          if(!$result) {
            $email_valid = false;
            throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
          }
        }
      } catch(Exception $e) {
        $this->answer .= $this->errorHandler->getError('profile', 'email_update_failed', $e->getMessage()).'<br>';
        $email_valid = false;
      }
    }
    //$this->$email_valid = $email_valid;
    return $email_valid;
  } 

  private function doChangePassword($username, $oldPassword, $newPassword) {
    $userPasswordValid = true;
    
    try {
      $this->checkOldPassword($username, $oldPassword);
    } catch(Exception $e) {
      $userPasswordValid = false;
      $answer .= $e->getMessage().'<br>';
    }
    try {
      $this->checkNewPassword($username, $newPassword);
    } catch(Exception $e) {
      $userPasswordValid = false;
      $answer .= $e->getMessage().'<br>';
    }
    
    if($userPasswordValid) {
      try {
        $catroidPasswordRecoverySuccess = $this->doUpdateCatroidPassword($username, $newPassword);

        if($catroidPasswordRecoverySuccess) {
          try {
            $boardPasswordRecoverySuccess = $this->doUpdateBoardPassword($username, $newPassword);
            if($boardPasswordRecoverySuccess) {
              try {
                $wikiPasswordRecoverySuccess = $this->doUpdateWikiPassword($username, $newPassword);
                if(!$wikiPasswordRecoverySuccess) {
                  $answer .= $this->errorHandler->getError('passwordrecovery', 'catroid_password_recovery_failed', $e->getMessage()).'<br>';
                  $userPasswordValid = false;
                }
              } catch(Exception $e) {
                $answer .= $this->errorHandler->getError('passwordrecovery', 'catroid_password_recovery_failed', $e->getMessage()).'<br>';
                $userPasswordValid = false;
              }                  
            }        
          } catch(Exception $e) {
            $answer .= $this->errorHandler->getError('passwordrecovery', 'catroid_password_recovery_failed', $e->getMessage()).'<br>';
            $userPasswordValid = false;
          }
        }
      } catch(Exception $e) {
        $answer .= $this->errorHandler->getError('passwordrecovery', 'catroid_password_recovery_failed', $e->getMessage()).'<br>';
        $userPasswordValid = false;
      }
    }
    $this->answer .= $answer;
    $this->answer_ok .= $answer_ok;
    return $userPasswordValid;
  }

  private function doChangeUserCountry($username, $countryCode) {
    $userCountryValid = false;
    try {
      $userCountryValid = $this->checkCountry($countryCode);
    } catch(Exception $e) {
      $this->answer .= $e->getMessage().'<br>';
    }
    if($userCountryValid) {
      try {
        $query = "EXECUTE update_user_country('$countryCode', '$username')";
        $result = @pg_query($this->dbConnection, $query);
        if(!$result) {
          throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
        }
      } catch(Exception $e) {
        $this->answer .= $this->errorHandler->getError('profile', 'email_update_failed', $e->getMessage()).'<br>';
        $userCountryValid = false;
      }
    }
    return $userCountryValid;
  }
  
  private function doUpdateCatroidPassword($username, $password) {
    $password = md5($password);
    $query = "EXECUTE update_password_by_username('$password', '$username')";
    $result = @pg_query($this->dbConnection, $query);
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
    }
    return true;
  }
    
  private function doUpdateBoardPassword($username, $password) {
    global $db, $phpbb_root_path;
    require_once($phpbb_root_path .'includes/functions.php');

    $username = utf8_clean_string($username); 
    $username = mb_convert_case($username, MB_CASE_TITLE, "UTF-8");
    $password = phpbb_hash($password);
    
  	$sql = 'UPDATE phpbb_users SET user_password = \'' . $password . '\',
  		user_pass_convert = 0 WHERE username_clean = \'' . $username . '\'';

    if($db->sql_query($sql)) {
      return true;
    } else {
      throw new Exception($this->errorHandler->getError('registration', 'board_registration_failed'));
    }
  }

  private function doUpdateWikiPassword($username, $password) {

    $wikiDbConnection = pg_connect("host=".DB_HOST_WIKI." dbname=".DB_NAME_WIKI." user=".DB_USER_WIKI." password=".DB_PASS_WIKI);
    if(!$wikiDbConnection) {
      throw new Exception($this->errorHandler->getError('db', 'connection_failed', pg_last_error($this->dbConnection)));
    }
    global $phpbb_root_path;
    require_once($phpbb_root_path .'includes/utf/utf_tools.php');

    $username = utf8_clean_string($username);
    $username = mb_convert_case($username, MB_CASE_TITLE, "UTF-8");
    $hexSalt = sprintf("%08x", mt_rand(0, 0x7fffffff));
    $hash = md5($hexSalt.'-'.md5($password));    
    $password = ":B:$hexSalt:$hash";

    $query = "UPDATE mwuser SET user_password = '".$password."' WHERE user_name = '".$username."'";
    
    $result = @pg_query($wikiDbConnection, $query);
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
    }
    pg_free_result($result);
    pg_close($wikiDbConnection);
    return true;
  }
  
  private function checkOldPassword($username, $oldPassword) {
    $username = trim($username);
    $oldPassword = trim($oldPassword);
    if((empty($oldPassword) && strcmp('0', $oldPassword) != 0) || $oldPassword == '' || mb_strlen($oldPassword) < 1) {
      throw new Exception($this->errorHandler->getError('profile', 'password_old_missing'));
    }

    global $phpbb_root_path;
    require_once($phpbb_root_path .'includes/utf/utf_tools.php');
    
    $user = $username; //$this->session->userLogin_userNickname;
    $user = utf8_clean_string($user);
    $pass = md5($oldPassword);
    $query = "EXECUTE get_user_login('$user', '$pass')";

    $result = @pg_query($this->dbConnection, $query);
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
    }

    if(!(pg_num_rows($result) > 0)) {
      throw new Exception($this->errorHandler->getError('profile', 'password_old_wrong'));
    }
    return true;
  }
  
  private function checkNewPassword($username, $newPassword) {
    $username = trim($username);
    $newPassword = trim($newPassword);
    if((empty($newPassword) && strcmp('0', $newPassword) != 0) || $newPassword == '' || mb_strlen($newPassword) < 1) {
      throw new Exception($this->errorHandler->getError('profile', 'password_new_missing'));
    }
 
    if(strcmp($username, $newPassword) != 0) {
      $text = '.{'.USER_MIN_PASSWORD_LENGTH.','.USER_MAX_PASSWORD_LENGTH.'}';
      $regEx = '/^'.$text.'$/';
      if(!preg_match($regEx, $newPassword)) {
        throw new Exception($this->errorHandler->getError('profile', 'password_new_length_invalid', '', USER_MIN_PASSWORD_LENGTH));
      }
    } else {
      throw new Exception($this->errorHandler->getError('profile', 'username_password_equal'));
    }
    return true;
  }

  private function checkUserValid($username) {
    $username = trim($username);
    $valid = false;
    $query = "EXECUTE get_user_row_by_username('".($username)."')";
    $result = pg_query($this->dbConnection, $query);
    if(!$result) {
      $valid = false;
    }
    if(pg_num_rows($result) > 0) {
      $this->answer .= $this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection));
      $valid = true;
    }
    return $valid;
  }
  
  private function checkEmail($email1, $email2 = 0) {
    $email = trim($email1);
    if(empty($email1) && strcmp('0', $email1) != 0) {
      throw new Exception($this->errorHandler->getError('registration', 'email_missing'));
    }
    
    $name = '[a-zA-Z0-9]((\.|\-|_)?[a-zA-Z0-9])*';
    $domain = '[a-zA-Z0-9]{2,}((\.|\-)?[a-zA-Z0-9])*';
    $tld = '[a-zA-Z]{2,8}';
    $regEx = '/^('.$name.')@('.$domain.')\.('.$tld.')$/';
    if(!preg_match($regEx, $email1)) {
      throw new Exception($this->errorHandler->getError('registration', 'email_invalid'));
    }
    
    $query = "EXECUTE get_user_row_by_email('$email1');";
    $result = pg_query($this->dbConnection, $query);

    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
    }
    
    if(pg_num_rows($result) > 0) {
      $email_valid = false;
      throw new Exception($this->errorHandler->getError('registration', 'email_already_exists'));
    }
//    
//    if(strcmp($email1, $email2) != 0 && $email2) {
//      if(pg_num_rows($result) > 0) {
//        $email_valid = false;
//        throw new Exception($this->errorHandler->getError('registration', 'email_already_exists'));
//      }
//    }
    
    $email_valid = true;
    
    if($email2) {
      $query = "EXECUTE get_user_row_by_email('$email2');";
      $result = pg_query($this->dbConnection, $query);
      if(!$result) {
        throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
      }
      $user = pg_fetch_assoc($result);
      $email_valid = $user['id'];
    }
    
    return $email_valid;
  }
  
  
  private function checkCountry($countryCode) {
    $countryCode = trim($countryCode);
    if($countryCode == "undef") {
      return true;
  	} 
    $query = "EXECUTE get_country_from_countries_by_countrycode('".($countryCode)."')";
    $result = @pg_query($this->dbConnection, $query);
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection))); 
    }
    if(pg_num_rows($result) > 0) {
      return true;
    } else {
      throw new Exception($this->errorHandler->getError('registration', 'country_codes_not_available'));
    }
  }
  
  private function initDynamicProfileData($requestedUser) {
    $answer .= '';
    try {
      $this->initCountryCodes();
    } catch(Exception $e) {
      $answer .= $e->getMessage().'<br>';
    }
    try {
      $this->fillDynamicProfileData($requestedUser);
    } catch(Exception $e) {
      $answer .= $e->getMessage().'<br>';
    }
    $this->answer .= $answer;
  }

  private function fillDynamicProfileData($userName) {
//    $query = "EXECUTE get_user_country_by_username('$userName')";
//    $result = @pg_query($this->dbConnection, $query);
//    if(!$result) {
//      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection))); 
//    }
    
    $userCountryCode = $this->getUserCountry($userName); 
    if($userCountryCode) {   
      $this->userCountryCode = $userCountryCode;
    }
    else {
      $this->userCountryCode = "undefined";
    }
    
    $this->userEmailsArray = $this->getUserEmailsArray($this->session->userLogin_userId);
    
//    $query = "EXECUTE get_user_email_by_username('$userName')";
//    $result = @pg_query($this->dbConnection, $query);
//    if(!$result) {
//      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection))); 
//    }
//    $userEmailResult = pg_fetch_assoc($result); 
//    $this->userEmail = $userEmailResult['email'];
  }
  
  private function initCountryCodes() {
    $query = "EXECUTE get_country_from_countries";
    $result = @pg_query($this->dbConnection, $query);

    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection))); 
    }
    if(pg_num_rows($result) > 0) {
      $countryCodeList = array();
      $countryNameList = array();
      $x = 1;
      while($country = pg_fetch_assoc($result)) {
        $countryCodeList[$x] = $country['code'];
        $countryNameList[$x] = $country['name'];
        $x++;
      }
      // if user country is not in list
      $countryCodeList[$x] = "undef";
      $countryNameList[$x] = "undefined";
      pg_free_result($result);      
    } else {
      throw new Exception($this->errorHandler->getError('registration', 'country_codes_not_available'));
    }

    $this->countryCodeList = $countryCodeList;
    $this->countryNameList = $countryNameList;
  }

  // get number of user's emails here! 
  private function getUserEmailsCount($user_id) {

//    $query = "EXECUTE get_user_emails_by_id_____($user_id)";
//    $result = @pg_query($this->dbConnection, $query);
//    if(!$result) {
//      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
//    }
//
//    $userEmails = pg_num_rows($result);
    return $userEmails;
  }
 

  // get number of user's emails here! 
  private function getUserEmailsArray($user_id) {

    $query = "EXECUTE get_user_emails_by_id($user_id)"; // _
    $result = @pg_query($this->dbConnection, $query);
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection)));
    }
    $userEmailsArray = array();
    $x=0;
    while($userEmails = pg_fetch_assoc($result)) {
      $userEmailsArray[$x] = $userEmails['email'];
      $x++;
    }
    return $userEmailsArray;
  }
  
  private function getUserCountry($userName) {
    $query = "EXECUTE get_user_country_by_username('$userName')";
    $result = @pg_query($this->dbConnection, $query);
    if(!$result) {
      throw new Exception($this->errorHandler->getError('db', 'query_failed', pg_last_error($this->dbConnection))); 
    }
    $userCountry = pg_fetch_assoc($result);

    $this->answer .= $userCountry['country'];
    return $userCountry['country'];
  }
  

  
  public function __destruct() {
    parent::__destruct();
  }
}
?>
