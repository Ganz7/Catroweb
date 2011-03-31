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

/* Set TESTS_BASE_PATH to your catroid www-root */
require_once 'testsBootstrap.php';

class AdminUploadTest extends PHPUnit_Framework_TestCase
{
  private $selenium;

  public function setUp() {
    $path= 'http://'.ADMIN_AREA_USER.':'.DB_PASS.'@'.str_replace('http://', '', TESTS_BASE_PATH).'admin/';
    $this->selenium = new Testing_Selenium("*firefox", $path);
    if(TESTS_SLOW_MODE) {
      $this->selenium->setSpeed(TESTS_SLOW_MODE_SPEED);
    } else {
      $this->selenium->setSpeed(1);
    }
    $this->selenium->start();
  }

  public function tearDown()
  {
    $this->selenium->stop();
  }
  
  public function testUploadTest()
  {
    $adminpath= 'http://'.ADMIN_AREA_USER.':'.DB_PASS.'@'.str_replace('http://', '', TESTS_BASE_PATH).'admin';
    $uploadpath= TESTS_BASE_PATH.'catroid/upload/';
    
    // upload project
    $this->selenium->open($uploadpath);
    $this->selenium->waitForPageToLoad("10000");
    $uploadpath = dirname(__FILE__);
    if(strpos($uploadpath, '\\') >= 0) {
      $uploadpath .= "\testdata\test.zip";
    } else {
      $uploadpath .= "/testdata/test.zip";
    }
    
    $projectTitle = "testproject".rand(1,9999);

    $this->selenium->type("upload",$uploadpath);
    $this->selenium->type("projectTitle",$projectTitle);
    $this->selenium->click("submit_upload");
    $this->selenium->waitForPageToload("10000");
    
    // verify creation & click download
    $this->selenium->open(TESTS_BASE_PATH);
    $this->selenium->waitForPageToload("2000");
    $this->selenium->waitForCondition("", 2000); // necessary for loading delay (loading...)
    $this->assertTrue($this->selenium->isTextPresent($projectTitle));
    
     
    // delete project
    $this->selenium->open($adminpath);
    $this->selenium->click("aAdministrationTools");
    $this->selenium->waitForPageToLoad(10000);
    $this->selenium->click("aAdminToolsEditProjects");
    $this->selenium->waitForPageToLoad(10000);
    $this->assertTrue($this->selenium->isTextPresent($projectTitle));
    $this->selenium->chooseOkOnNextConfirmation();
    $this->selenium->click("xpath=//input[@name='deleteButton']");
    $this->selenium->waitForPageToLoad(10000);
    $this->assertFalse($this->selenium->isTextPresent($projectTitle));
    
    // verify deletion
    $this->selenium->open(TESTS_BASE_PATH);
    $this->selenium->waitForPageToload("10000");
    $this->assertFalse($this->selenium->isTextPresent($projectTitle));
  }

}
?>

