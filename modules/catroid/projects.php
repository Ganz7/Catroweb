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

class projects extends CoreAuthenticationNone {

  public function __construct() {
    parent::__construct();
    if($this->clientDetection->isBrowser(CoreClientDetection::BROWSER_FIREFOX) ||
        $this->clientDetection->isBrowser(CoreClientDetection::BROWSER_FIREFOX_MOBILE) ||
        $this->clientDetection->isBrowser(CoreClientDetection::BROWSER_SAFARI) ||
        $this->clientDetection->isBrowser(CoreClientDetection::BROWSER_CHROME) ||
        $this->clientDetection->isBrowser(CoreClientDetection::BROWSER_ANDROID)) {
      $this->addCss('projectList.css');
    } else {
      $this->addCss('projectList_nohtml5.css');
    }
    $this->addCss('index.css');
    $this->addJs('loadProjects.js');
    $this->addJs('commonContainerFill.js');
    $this->addJs('projects.js');
    $this->htmlHeaderFile = 'htmlProjectsHeaderTemplate.php';

    $params = array();
    $params['numProjectsPerPage'] = PROJECT_PAGE_NUM_PROJECTS_PER_PAGE;
    $params['pageNr'] = intVal($this->session->pageNr);    
    $params['searchQuery'] = $this->session->searchQuery;
    $params['task'] = 'newestProjects';
    $params['view'] = 'projectsByRow';
    $params['container'] = '#projectContainer';
    $this->projectParams = "'".json_encode($params)."'";
  }
  
  public function __default() {
    
  }
  
  public function __destruct() {
    parent::__destruct();
  }
}