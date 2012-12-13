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

class loadProjects extends CoreAuthenticationNone {
  protected $pageNr = 0;
  protected $ajax = false;

  public function __construct() {
    parent::__construct();

  }


  public function __default() {

    // catroid/loadProjects/1.json
    // data:
    // - task: projects, search
    // - sort: newest, views, downloads, random
    // - pageNr:
     
    if(isset($_REQUEST)) {
      $this->ajax = true;
      
      $this->request = $_REQUEST;
      if(isset($_REQUEST['query'])) {
        $this->searchQuery = $_REQUEST['query'];
      }

      if(isset($_REQUEST['method'])) {
        if($_REQUEST['method'] == 'random'){
          $_REQUEST['sort'] = 'random';
        }
        else {
          $this->pageNr = intval($_REQUEST['method']) - 1;
        }
      }

      if(!isset($_REQUEST['sort'])) {
        $_REQUEST['sort'] = '';
      }

      $limit = intval((isset($_REQUEST['numProjectsPerPage']))? $_REQUEST['numProjectsPerPage'] : PROJECT_PAGE_NUM_PROJECTS_PER_PAGE);
      $offset = 0;

      if(isset($_REQUEST['page'])) {
        $this->pageNr = intval($_REQUEST['page']) - 1;
        $offset = max(0,($this->pageNr)*$limit);
      }

      $this->session->pageNr = $this->pageNr;
      $pageLabels = array();
      if($_REQUEST['task'] == "searchProjects") {
        $this->content = $this->retrieveSearchResultsFromDatabase($this->searchQuery, $this->pageNr, $_REQUEST['sort']);
        $pageLabels['title'] = $this->languageHandler->getString('search_title');
      }
      
      else if($_REQUEST['task'] == "projectsTaskDefault") {
        $pageLabels['title'] = $this->languageHandler->getString('title');
        $this->content = $this->getProjects($_REQUEST['sort'], $limit, $offset);
      }
      
      $this->buttons = array("prevButton" => ($this->pageNr == 0)? false : true, "nextButton" => (count($this->content) == PROJECT_PAGE_NUM_PROJECTS_PER_PAGE)? true : false);
    
      $pageLabels['websitetitle'] = SITE_DEFAULT_TITLE;          
      $pageLabels['pageNr'] = $this->languageHandler->getString('page_number',  intVal($this->session->pageNr + 1 ));
      $pageLabels['prevButton'] = $this->languageHandler->getString('prev_button', '&laquo;');
      $pageLabels['nextButton'] = $this->languageHandler->getString('next_button', '&raquo;');
      $pageLabels['loadingButton'] = $this->languageHandler->getString('loading_button');
      $this->pageLabels = $pageLabels;
    }
  }
  
  public function retrieveSearchResultsFromDatabase($keywords, $pageNr, $sort) {
    if($pageNr < 0) {
      return "NIL";
    }
  
    if(!isset($sort) || $sort == "") {
      //$this->pageNr = intval($_REQUEST['method'])-1;
      $sort = 'projectsTaskDefault';
    }
  
    $searchTerms = explode(" ", $keywords);
    $keywordsCount = 3;
    $searchQuery = "";
    $searchRequest = array();
  
    foreach($searchTerms as $term) {
      if ($term != "") {
        $searchQuery .= (($searchQuery=="")?"":" OR " )."title ILIKE \$".$keywordsCount;
        $searchQuery .= " OR description ILIKE \$".$keywordsCount;
        $searchTerm = pg_escape_string(preg_replace("/\\\/", "\\\\\\", checkUserInput($term)));
        $searchTerm = preg_replace(array("/\%/", "/\_/"), array("\\\%", "\\\_"), $searchTerm);
        array_push($searchRequest, "%".$searchTerm."%");
        $keywordsCount++;
      }
    }
  
    $orderBy = 'upload_time';
    switch($sort) {
      case 'downloads':
        $orderBy = "download_count";
        break;
      case 'views':
        $orderBy = "view_count";
        break;
      default:
        $orderBy = "upload_time";
    }
  
  
    pg_prepare($this->dbConnection, "get_search_results", "SELECT projects.id, projects.title, projects.upload_time, projects.view_count, projects.download_count, cusers.username AS uploaded_by FROM projects, cusers WHERE ($searchQuery) AND visible = 't' AND cusers.id=projects.user_id ORDER BY ($orderBy) DESC  LIMIT \$1 OFFSET \$2") or
    $this->errorHandler->showErrorPage('db', 'query_failed', pg_last_error());
    $result = pg_execute($this->dbConnection, "get_search_results", array_merge(array(PROJECT_PAGE_NUM_PROJECTS_PER_PAGE, PROJECT_PAGE_NUM_PROJECTS_PER_PAGE * $pageNr), $searchRequest)) or
    $this->errorHandler->showErrorPage('db', 'query_failed', pg_last_error());
    $projects = pg_fetch_all($result);
    pg_query($this->dbConnection, 'DEALLOCATE get_search_results');
    pg_free_result($result);
    if($projects[0]['id']) {
      $i=0;
      foreach($projects as $project) {
        $projects[$i]['title'] = $projects[$i]['title'];
        $projects[$i]['title_short'] = makeShortString($project['title'], PROJECT_TITLE_MAX_DISPLAY_LENGTH);
        $projects[$i]['upload_time'] =  $this->languageHandler->getString('uploaded', getTimeInWords(strtotime($project['upload_time']), $this->languageHandler, time()));
        $projects[$i]['thumbnail'] = getProjectThumbnailUrl($project['id']);
        $projects[$i]['download_count'] = isset($projects[$i]['download_count'])? $projects[$i]['download_count'] : '';
        $projects[$i]['view_count'] = isset($projects[$i]['view_count'])? $projects[$i]['view_count'] : '';
        $projects[$i]['uploaded_by_string'] = $this->languageHandler->getString('uploaded_by', $projects[$i]['uploaded_by']);
        $i++;
      }
      return($projects);
    } elseif($pageNr == 0) {
      $projects[0]['id'] = 0;
      $projects[0]['title'] = $this->languageHandler->getString('no_results');
      $projects[0]['title_short'] = $this->languageHandler->getString('no_results');
      $projects[0]['upload_time'] =  "";
      $projects[0]['thumbnail'] = BASE_PATH."images/symbols/thumbnail_gray.jpg";
      return($projects);
    } else {
      return "NIL";
    }
  }

  public function getProjects($sort, $limit = 0, $offset = 0) {
    $projects = null;
    if(!isset($sort) || $sort == "") {
      $sort = 'newest';
    }

    if(($this->pageNr < 0) && ($this->ajax)) {
      return "NIL";
    }

    switch($sort) {
      case 'newest':
        $projects = $this->retrieveProjectsFromDatabase("get_visible_projects_ordered_by_uploadtime_limited_and_offset", $limit, $offset);
        break;
      case 'downloads':
        $projects = $this->retrieveProjectsFromDatabase("get_visible_projects_order_by_download_count_limited_and_offset", $limit, $offset);
        break;
      case 'views':
        $projects = $this->retrieveProjectsFromDatabase("get_visible_projects_ordered_by_view_count_limited_and_offset", $limit, $offset);
        break;
      case 'random':
        $projects = $this->retrieveProjectsFromDatabase("get_visible_projects_ordered_by_random_limited_and_offset", $limit, $offset);
        break;
      default:
        $projects = $this->retrieveProjectsFromDatabase("get_visible_projects_ordered_by_uploadtime_limited_and_offset", $limit, $offset);
        break;
    }

    return $projects;
  }

  public function retrieveProjectsFromDatabase($sql, $limit, $offset = 0) {
    $result = pg_execute($this->dbConnection, $sql,
        array($limit, $offset)) or
        $this->errorHandler->showErrorPage('db', 'query_failed', pg_last_error());

    $projects = pg_fetch_all($result);
    pg_free_result($result);
    if($projects[0]['id']) {
      $i=0;
      foreach($projects as $project) {
        $projects[$i]['title'] = $projects[$i]['title'];
        $projects[$i]['title_short'] = makeShortString($project['title'], PROJECT_TITLE_MAX_DISPLAY_LENGTH);
        $projects[$i]['upload_time'] =  $this->languageHandler->getString('uploaded', getTimeInWords(strtotime($project['upload_time']), $this->languageHandler, time()));
        $projects[$i]['thumbnail'] = getProjectThumbnailUrl($project['id']);
        $projects[$i]['download_count'] = isset($projects[$i]['download_count'])? $projects[$i]['download_count'] : '';
        $projects[$i]['view_count'] = isset($projects[$i]['view_count'])? $projects[$i]['view_count'] : '';
        $projects[$i]['uploaded_by_string'] = $this->languageHandler->getString('uploaded_by', $projects[$i]['uploaded_by']);
        $i++;
      }
      return($projects);
    } else {
      return "NIL";
    }
  }

  public function __destruct() {
    parent::__destruct();
  }
}
?>
