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
?>
    <script type="text/javascript">
      $(document).ready(function() {
        new HeaderMenu();
        new Login();
        new Menu(<?php echo "'".$this->module->session->userLogin_userId."'"; ?>);
      });
    </script>
    <div class="webMainTop">
      <div class="blueBoxMain">
        <div class="webMainHead">
          <div class="webHeadLogo">
            <a id="aIndexWebLogoLeft" href="<?php echo BASE_PATH?>catroid/index/1">
              <img class="catroidLogo" src="<?php echo BASE_PATH?>images/logo/logo_head.png" alt="head logo" />
            </a>
          </div>        
          <div class="webHeadTitle">
            <div class="webHeadTitleName">
                <a class="noLink" id="aIndexWebLogoMiddle" href="catroid/index">
                  <img class="catroidLettering" src="<?php echo BASE_PATH?>images/logo/logo_lettering.png" alt="catroid [beta]" />
                </a>			      			
            </div>
          </div>
          <div id="normalHeaderButtons" class="webHeadButtons">
            <button type="button" class="webHeadButtons button orange medium" id="headerMenuButton"><img class="webHeadSymbolOnButton" src="<?php echo BASE_PATH?>images/symbols/wall.png" alt="<?php echo $this->languageHandler->getString('template_header_menu')?>" /></button>
            <button type="button" class="webHeadButtons button orange medium" id="headerSearchButton"><img class="webHeadSymbolOnButton" src="<?php echo BASE_PATH?>images/symbols/search.png" alt="<?php echo $this->languageHandler->getString('template_header_search')?>" /></button>
          </div>
          <div id="cancelHeaderButton" class="webHeadButtons">
            <button type="button" class="webHeadCancelButton button orange medium" id="headerCancelButton">
              <span class="webHeadCancelButtonText"><?php echo $this->languageHandler->getString('template_header_cancel')?></span>
            </button>
          </div>
          <div style="clear:both;"></div>
        </div>
      </div>
      <div id="headerSearchBox" class="headerSearchBox" style="display:none;">
        <div class="webHeadBoxSpacer"></div>
        <div class="blueBoxMain">
          <div class="webMainHead">
            <form id="searchForm">
              <div class="headerSearchBoxRight" >
              <?php if($this->module->clientDetection->isMobile()) {?>
                <input id="searchQuery" type="text" class="webHeadSearchBox" placeholder="<?php echo $this->languageHandler->getString('template_header_search_for_projects')?>" autofocus  /><br>             
                <input type="submit" class="webHeadSearchSubmit button orange" id="webHeadSearchSubmit" value="<?php echo $this->languageHandler->getString('template_header_search')?>" />
              <?php } else {?>
                <input id="searchQuery" type="text" class="webHeadSearchBox" placeholder="<?php echo $this->languageHandler->getString('template_header_search_for_projects')?>" autofocus  />             
                <input type="submit" class="webHeadSearchSubmit button orange" id="webHeadSearchSubmit" value="<?php echo $this->languageHandler->getString('template_header_search')?>" />
              <?php }?>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div> <!--  WEBMAINTOP -->
