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

package at.tugraz.ist.catroweb.catroid;

import static com.thoughtworks.selenium.grid.tools.ThreadSafeSeleniumSessionStorage.session;

import java.sql.Connection;
import java.sql.DriverManager;
import java.sql.SQLException;
import java.sql.Statement;
import java.util.HashMap;

import org.testng.annotations.DataProvider;
import org.testng.annotations.Test;
import static org.testng.AssertJUnit.*;

import at.tugraz.ist.catroweb.BaseTest;
import at.tugraz.ist.catroweb.common.*;

public class ProfileTests extends BaseTest {
  @Test(dataProvider = "loginData", groups = { "catroid" }, description = "check profile page")
  public void profilePage(HashMap<String, String> dataset) throws Throwable {
    session().open(Config.TESTS_BASE_PATH + "catroid/registration/");
    waitForPageToLoad();

    session().type("xpath=//input[@name='registrationUsername']", dataset.get("registrationUsername"));
    session().type("xpath=//input[@name='registrationPassword']", dataset.get("registrationPassword"));
    session().type("xpath=//input[@name='registrationEmail']", dataset.get("registrationEmail"));
    session().type("xpath=//select[@name='registrationGender']", dataset.get("registrationGender"));
    session().type("xpath=//select[@name='registrationMonth']", dataset.get("registrationMonth"));
    session().type("xpath=//select[@name='registrationYear']", dataset.get("registrationYear"));
    session().type("xpath=//select[@name='registrationCountry']", dataset.get("registrationCountry"));
    session().type("xpath=//input[@name='registrationCity']", dataset.get("registrationCity"));

    session().click("xpath=//input[@name='registrationSubmit']");
    ajaxWait();
    assertTrue(session().isTextPresent("CATROID registration successfull!"));
    assertTrue(session().isTextPresent("BOARD registration successfull!"));
    assertTrue(session().isTextPresent("WIKI registration successfull!"));

    session().open(Config.TESTS_BASE_PATH);
    waitForPageToLoad();
    ajaxWait();

    session().click("headerProfileButton");
    session().type("loginUsername", dataset.get("registrationUsername"));
    session().type("loginPassword", dataset.get("registrationPassword"));
    session().click("loginSubmitButton");
    waitForPageToLoad();
    ajaxWait();
    assertTrue(session().isVisible("headerProfileButton"));
    session().click("headerProfileButton");
    assertTrue(session().isVisible("logoutSubmitButton"));
    session().click("headerCancelButton");

    session().click("xpath=//button[@id='headerMenuButton']");
    waitForPageToLoad();
    ajaxWait();

    assertTrue(session().isVisible("xpath=//button[@id='menuProfileButton']"));
    assertFalse(session().isVisible("xpath=//button[@id='menuLoginButton']"));
    assertTrue(session().isVisible("xpath=//button[@id='menuLogoutButton']"));

    assertTrue(session().isTextPresent("Profile"));
    session().click("xpath=//button[@id='menuProfileButton']");
    waitForPageToLoad();

    assertTrue(session().isTextPresent(dataset.get("registrationUsername") + "\'s Profile"));
    assertTrue(session().isTextPresent("change my password"));
    assertTrue(session().isTextPresent(dataset.get("registrationEmail")));
    assertTrue(session().isTextPresent("from "));

    session().click("xpath=//a[@id='profileChangePassword']");
    ajaxWait();

    assertTrue(session().isVisible("xpath=//input[@id='profileOldPassword']"));
    assertTrue(session().isVisible("xpath=//input[@id='profileNewPassword']"));
    assertTrue(session().isVisible("xpath=//input[@id='profilePasswordSubmit']"));

    session().type("xpath=//input[@id='profileOldPassword']", dataset.get("registrationPassword"));
    session().type("xpath=//input[@id='profileNewPassword']", dataset.get("changedPassword"));
    session().click("xpath=//input[@id='profilePasswordSubmit']");
    ajaxWait();

    assertTrue(session().isTextPresent("You updated your password successfully."));

    session().click("xpath=//a[@id='profileChangePassword']");
    ajaxWait();

    session().type("xpath=//input[@id='profileOldPassword']", dataset.get("registrationPassword"));
    session().type("xpath=//input[@id='profileNewPassword']", dataset.get("shortPassword"));
    session().click("xpath=//input[@id='profilePasswordSubmit']");
    ajaxWait();

    assertTrue(session().isTextPresent("The old password was incorrect."));
    assertTrue(session().isTextPresent("The new password must have at least 6 characters."));

    session().type("xpath=//input[@id='profileOldPassword']", dataset.get("changedPassword"));
    session().type("xpath=//input[@id='profileNewPassword']", dataset.get("emptyPassword"));
    session().click("xpath=//input[@id='profilePasswordSubmit']");
    ajaxWait();

    assertTrue(session().isTextPresent("The new password is missing."));

    session().type("xpath=//input[@id='profileOldPassword']", dataset.get("emptyPassword"));
    session().type("xpath=//input[@id='profileNewPassword']", dataset.get("shortPassword"));
    session().click("xpath=//input[@id='profilePasswordSubmit']");
    ajaxWait();

    assertTrue(session().isTextPresent("The old password is missing."));
    assertTrue(session().isTextPresent("The new password must have at least 6 characters."));

    session().type("xpath=//input[@id='profileOldPassword']", dataset.get("changedPassword"));
    session().type("xpath=//input[@id='profileNewPassword']", dataset.get("registrationPassword"));
    session().click("xpath=//input[@id='profilePasswordSubmit']");
    ajaxWait();

    assertTrue(session().isTextPresent("You updated your password successfully."));

    session().click("xpath=//a[@id='profileChangePassword']");
    ajaxWait();

    session().type("xpath=//input[@id='profileOldPassword']", dataset.get("registrationPassword"));
    session().type("xpath=//input[@id='profileNewPassword']", dataset.get("registrationPassword"));
    session().click("xpath=//input[@id='profilePasswordSubmit']");
    ajaxWait();

    assertTrue(session().isTextPresent("You updated your password successfully."));

    session().click("xpath=//a[@id='profileChangeEmailText']");
    ajaxWait();

    session().type("xpath=//input[@id='profileEmail']", dataset.get("changedEmail"));
    session().click("xpath=//input[@id='profileEmailSubmit']");
    waitForPageToLoad();
    ajaxWait();

    session().getAlert();
    assertTrue(session().isTextPresent(dataset.get("changedEmail")));

    session().click("xpath=//a[@id='profileChangeEmailText']");
    ajaxWait();

    session().type("xpath=//input[@id='profileEmail']", dataset.get("registrationEmail"));
    session().click("xpath=//input[@id='profileEmailSubmit']");
    waitForPageToLoad();
    ajaxWait();

    session().getAlert();
    assertTrue(session().isTextPresent(dataset.get("registrationEmail")));

    deleteUserFromDatabase(dataset.get("registrationUsername"));
  }

  private void deleteUserFromDatabase(String username) {
    try {
      Connection connection = DriverManager.getConnection(Config.DB_HOST + Config.DB_NAME, Config.DB_USER, Config.DB_PASS);
      Statement statement = connection.createStatement();
      statement.executeUpdate("DELETE FROM cusers WHERE username='" + username + "';");
      statement.close();
      connection.close();
    } catch(SQLException e) {
      System.out.println("ProjectUploader: deleteProject: SQL Exception couldn't execute sql query!");
    }
  }

  @DataProvider(name = "loginData")
  public Object[][] loginData() {
    final String randomString = CommonData.getRandomShortString();

    Object[][] dataArray = new Object[][] { { new HashMap<String, String>() {
      {
        put("registrationUsername", "myUnitTest" + randomString);
        put("registrationPassword", "myPassword123");
        put("changedPassword", "anotherPassword123");
        put("shortPassword", "short");
        put("emptyPassword", "");
        put("registrationEmail", "test" + randomString + "@selenium.at");
        put("changedEmail", "other" + randomString + "@selenium.at");
        put("registrationGender", "male");
        put("registrationMonth", "2");
        put("registrationYear", "1980");
        put("registrationCountry", "AT");
        put("registrationCity", "Graz");
      }
    } } };
    return dataArray;
  }
}