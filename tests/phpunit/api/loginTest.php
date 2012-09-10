<?php
/*    Catroid: An on-device graphical programming language for Android devices
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

require_once('testsBootstrap.php');

class loginTest extends PHPUnit_Framework_TestCase
{
  protected $obj;

  protected function setUp() {
    require_once CORE_BASE_PATH.'modules/api/login.php';
    $this->obj = new login();
  }

  /**
   * @dataProvider validLogin
   */
  public function testCatroidLogin($postData) {
    $_POST = $postData;
    $this->obj->loginRequest();

    $this->assertGreaterThan(0, intval($this->obj->session->userLogin_userId));
    $this->assertEquals($postData['loginUsername'], $this->obj->session->userLogin_userNickname);

    $this->obj->logoutRequest();
    $this->assertEquals(0, intval($this->obj->session->userLogin_userId));
    $this->assertEquals('', $this->obj->session->userLogin_userNickname);
  }
  
  /**
   * @dataProvider invalidLogin
   */
  public function testInvalidCatroidLogin($postData) {
    $_POST = $postData;
    $this->obj->loginRequest();

    $this->assertEquals(0, intval($this->obj->session->userLogin_userId));
    $this->assertEquals('', $this->obj->session->userLogin_userNickname);
  }
  
  /* *** DATA PROVIDERS *** */
  public function validLogin() {
    $dataArray = array(
    array(array("loginUsername" => "catroweb", "loginPassword" => "cat.roid.web", "loginSubmit" => "login"))
    );
    return $dataArray;
  }
  public function invalidLogin() {
    $dataArray = array(
    array(array("loginUsername" => "invalidUser", "loginPassword" => "invalidPass", "loginSubmit" => "login")),
    array(array("loginUsername" => "invalidUser", "loginPassword" => "cat.roid.web", "loginSubmit" => "login")),
    array(array("loginUsername" => "catroweb", "loginPassword" => "invalidPass", "loginSubmit" => "login")),
    array(array("loginUsername" => "", "loginPassword" => "", "loginSubmit" => "login"))
    );
    return $dataArray;
  }
}
?>
