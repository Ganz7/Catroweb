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
  }

  public function __default() {
    $this->numberOfPages = ceil($this->getNumberOfVisibleProjects() / PROJECT_PAGE_NUM_PROJECTS_PER_PAGE);
   
    if(!$this->session->pageNr) {
      $this->session->pageNr = 1;
      $this->session->task = "newestProjects";
    }
    
    if(isset($_REQUEST['method']) || isset($_REQUEST['p'])) {
      if(isset($_REQUEST['method'])) {
        $this->session->pageNr = intval($_REQUEST['method']);
      }
      if(isset($_REQUEST['p'])) {
        $this->session->pageNr = intval($_REQUEST['p']);
      }
      if($this->session->pageNr < 1) {
        $this->session->pageNr = 1;
      }
      if($this->session->pageNr > $this->numberOfPages) {
        $this->session->pageNr = $this->numberOfPages;
      }
    }
    
    if(isset($_SERVER['HTTP_REFERER']) && !$this->session->referer) {
      $this->session->referer = $_SERVER['HTTP_REFERER'];
    }
    if(isset($_SERVER['HTTP_REFERER']) && $this->session->referer != $_SERVER['HTTP_REFERER']) {
      $this->session->referer = $_SERVER['HTTP_REFERER'];
      $this->session->task = "newestProjects";
    }

    if(isset($_REQUEST['q'])) {
      $this->session->searchQuery = $_REQUEST['q'];
      $this->session->task = "searchProjects";
    }
    
    if(!$this->session->sort) {
      $this->session->sort = "newest";
    }
    
    if(isset($_REQUEST['sort'])) {
      switch($_REQUEST['sort']) {
        case 'downloads':
        case 'views':
        case 'newest':
          $this->session->sort = $_REQUEST['sort'];
          break;
        default:
          $this->session->sort = 'newest';
      }
    }    

    if(!$this->session->task) {
      $this->session->task = "newestProjects";
    }

    $this->task = $this->session->task;
    $this->pageNr = $this->session->pageNr;
    $this->searchQuery = "";
    if($this->session->searchQuery != "") {
      $this->searchQuery = $this->session->searchQuery;
    }
    
    $params = array();
    $params['numProjectsPerPage'] = PROJECT_PAGE_NUM_PROJECTS_PER_PAGE;
    $params['pageNr'] = intVal($this->session->pageNr);
    $params['pageNrMax'] = $this->numberOfPages;
    $params['searchQuery'] = $this->session->searchQuery;
    $params['task'] = 'newestProjects';
    $params['view'] = 'projectsByRow';
    $params['container'] = '#projectContainer';
    $this->projectParams = "'".json_encode($params)."'";
    
    $this->task = $this->session->task;
    $this->pageNr = $this->session->pageNr;
    $this->searchQuery = "";
    if($this->session->searchQuery != "") {
      $this->searchQuery = $this->session->searchQuery;
    }
    

  }
  
  public function getNumberOfVisibleProjects() {
    $result = pg_execute($this->dbConnection, "get_number_of_visible_projects", array()) or
    $this->errorHandler->showErrorPage('db', 'query_failed', pg_last_error());
    $number = pg_fetch_all($result);
    pg_free_result($result);
  
    if($number[0]['count']) {
      return $number[0]['count'];
    }
    return 0;
  }

  public function __destruct() {
    parent::__destruct();
  }
}