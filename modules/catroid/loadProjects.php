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

      if(isset($_REQUEST['method'])) {
        if($_REQUEST['method'] == 'random'){
          $_REQUEST['sort'] = 'random';
        }
        else {
          $this->pageNr = intval($_REQUEST['method'])-1;
        }
      }

      if(!isset($_REQUEST['sort'])) {
        $_REQUEST['sort'] = '';
      }

      $limit = intval((isset($_REQUEST['numProjectsPerPage']))? $_REQUEST['numProjectsPerPage'] : PROJECT_PAGE_NUM_PROJECTS_PER_PAGE);
      $offset = 0;

      if(isset($_REQUEST['page'])) {
        $this->pageNr = intval($_REQUEST['page']);
        $offset = ($this->pageNr)*$limit;
      }

      $this->session->pageNr = $this->pageNr;
      $this->content = $this->getProjects($_REQUEST['sort'], $limit, $offset);
      $this->buttons = array("prevButton" => ($this->pageNr == 0)? false : true, "nextButton" => (count($this->content) == PROJECT_PAGE_NUM_PROJECTS_PER_PAGE)? true : false);

      $pageLabels = array();
      $pageLabels['websitetitle'] = SITE_DEFAULT_TITLE;
      $pageLabels['title'] = $this->languageHandler->getString('title');
      $pageLabels['pageNr'] = $this->languageHandler->getString('page_number',  intVal($this->session->pageNr + 1 ));
      $pageLabels['prevButton'] = $this->languageHandler->getString('prev_button', '&laquo;');
      $pageLabels['nextButton'] = $this->languageHandler->getString('next_button', '&raquo;');
      $pageLabels['loadingButton'] = $this->languageHandler->getString('loading_button');
      $this->pageLabels = $pageLabels;
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
