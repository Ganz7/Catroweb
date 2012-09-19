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

package at.tugraz.ist.catroweb.catroid;


import org.openqa.selenium.By;
import org.testng.annotations.Test;

import static org.testng.AssertJUnit.*;

import at.tugraz.ist.catroweb.BaseTest;
import at.tugraz.ist.catroweb.common.*;

@Test(groups = {"catroid", "HistoryTests"})
public class HistoryTests extends BaseTest {

  @Test(groups = {"visibility"}, description = "history search tests")
  public void search() throws Throwable {
    try {
      String projectTitle = "test";
      openLocation();
      ajaxWait();
      driver().findElement(By.id("headerSearchButton")).click();
      driver().findElement(By.id("searchQuery")).sendKeys(projectTitle);
      driver().findElement(By.id("webHeadSearchSubmit")).click();
      ajaxWait();
      assertRegExp(".*/catroid/search/[?]q=" + projectTitle + "&p=1", driver().getCurrentUrl());

      String locationBefore = driver().getCurrentUrl();
      driver().navigate().back();
      ajaxWait();
      assertNotEquals(locationBefore, driver().getCurrentUrl());
      driver().navigate().forward();
      ajaxWait();
      assertEquals(locationBefore, driver().getCurrentUrl());
    } catch(AssertionError e) {
      captureScreen("HistoryTests.location");
      throw e;
    } catch(Exception e) {
      captureScreen("HistoryTests.location");
      throw e;
    }
  }

  @Test(groups = {"functionality", "upload"}, description = "history privacy tests")
  public void pageNavigation() throws Throwable {
    try {
      openLocation();
      ajaxWait();
      
      String locationBefore = driver().getCurrentUrl();
      int pageNr = 1;
      while(driver().findElement(By.id("moreProjects")).isDisplayed()) {
        driver().findElement(By.id("moreProjects")).click();
        ajaxWait();
        assertRegExp(".*/catroid/index/" + (pageNr + 1), driver().getCurrentUrl());
        driver().navigate().back();
        assertEquals(locationBefore, driver().getCurrentUrl());
        driver().navigate().forward();
        assertNotEquals(locationBefore, driver().getCurrentUrl());
        
        locationBefore =  driver().getCurrentUrl();
        driver().findElement(By.xpath("//a[@class='projectListDetailsLinkBold']")).click();
        ajaxWait();
        String detailsURL = driver().getCurrentUrl();
        assertRegExp(".*catroid/details/.*", detailsURL);
        driver().navigate().back();
        ajaxWait();
        assertEquals(locationBefore, driver().getCurrentUrl());
        driver().navigate().forward();
        ajaxWait();
        assertEquals(detailsURL, driver().getCurrentUrl());
        driver().navigate().back();
        pageNr++;
      }
      
      while(pageNr-- > 1) {
        driver().navigate().back();
        assertRegExp(".*/catroid/index/" + (pageNr), driver().getCurrentUrl());
      }
 
    } catch(AssertionError e) {
      captureScreen("HistoryTests.privacy");
      throw e;
    } catch(Exception e) {
      captureScreen("HistoryTests.privacy");
      throw e;
    }
  }
}
