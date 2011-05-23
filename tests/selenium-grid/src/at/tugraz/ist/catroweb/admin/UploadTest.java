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

package at.tugraz.ist.catroweb.admin;

import static com.thoughtworks.selenium.grid.tools.ThreadSafeSeleniumSessionStorage.session;

import org.testng.annotations.Test;
import static org.testng.AssertJUnit.*;

import at.tugraz.ist.catroweb.BaseTest;
import at.tugraz.ist.catroweb.common.*;

public class UploadTest extends BaseTest {
  @Test(groups = { "admin" }, description = "upload and delete a project")
  public void uploadTest() throws Throwable {
    String projectTitle = "testproject" + CommonData.getRandomLongString();
    projectUploader.upload(CommonData.getUploadPayload(projectTitle, "", "", "", "", "", "", ""));

    // verify creation & click download
    session().open(Config.TESTS_BASE_PATH);
    waitForPageToLoad();
    ajaxWait();
    assertTrue(session().isTextPresent(projectTitle));

    // delete project
    session().open(CommonFunctions.getAdminPath(this.webSite));
    session().click("aAdministrationTools");
    waitForPageToLoad();
    session().click("aAdminToolsEditProjects");
    waitForPageToLoad();
    assertTrue(session().isTextPresent(projectTitle));
    session().click("xpath=//input[@name='deleteButton']");
    session().getConfirmation();
    waitForPageToLoad();
    assertFalse(session().isTextPresent(projectTitle));

    // verify deletion
    session().open(Config.TESTS_BASE_PATH);
    waitForPageToLoad();
    ajaxWait();
    assertFalse(session().isTextPresent(projectTitle));
  }
}
