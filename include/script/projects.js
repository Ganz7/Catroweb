var Projects = Class.$extend( {
  __include__ : [__baseClassVars],
  __init__ : function(params) {
    var self = this;
    this.params = jQuery.parseJSON(params);
    this.cbChangePage = null;
    
    if(this.params == null) {
      console.log("ERROR: params are NULL!");
      return;
    }

    this.projectsTaskDefault = new ProjectsTaskDefault($.proxy(this.getParams, this));
    this.projectsTaskSearch = new ProjectsTaskSearch($.proxy(this.getParams, this));
//    
    window.onpopstate = function(event) {
      console.log("= = = ", "projects.js", "= =  = ", "onpopstate ", event);
//      if(event.state && event.state.callback) {
//        console.debug("heyoo wiicki MY CALLBACK");
//        eval(event.state.callback).call(this);
//      }
//      if(event.state && event.state.params.task == "newestProjects") {
//        self.projectsMain.restore(event.state);
//        //self.newestProjects.restoreHistoryState(event.state);
//        //self.state = "newestProjects";
//      }
//      if(event.state && event.state.searchProjects) {
//        self.projectsSearch.restore(event);
//        //self.searchProjects.restoreHistoryState(event.state);
//        //self.state = "searchProjects";
//      }        
    }

    setTimeout(function() { self.initialize(self); }, 50);
  },
  
  getParams : function() {
    console.log("= = = >", "getting params");
    return this.params;
  },
  
  initialize : function(object) {
    if(window.history.state != null && window.history.state.pageContent != null) {
//      console.log("----------> restoring history!");
//      object.loadProjects.restoreHistoryState(window.history.state);
      
      //RESTORE HISTORY STATE ?
      // with this.params.task
      // return;
    }
    
    $("#fewerProjects").click($.proxy(this.changePage, this, 'back'));
    $("#moreProjects").click($.proxy(this.changePage, object, 'forward'));
//    $("#searchForm").submit($.proxy(this.projectsTaskSearch.search, this.projectsTaskSearch));
//    $("#headerCancelButton").click($.proxy(this.cancelSearch, this));
    $("#searchForm").submit($.proxy(this.setCurrentTask, this, this.projectsTaskSearch.getName()));
    $("#headerCancelButton").click($.proxy(this.cancelSearch, this));
    $("#aIndexWebLogoLeft").click($.proxy(this.startPage, this));
    $("#aIndexWebLogoMiddle").click($.proxy(this.startPage, this));
    // TODO:
    // $("#headerMenuButton").click(function() { self.newestProjects.saveStateToSession(self.newestProjects.pageNr.current); });
    },
    
  setCurrentTask : function(task) {
    console.log("setting task..........");
    var currentTask = null;
    if(task == null || task == "" || task == "undefined") { // TODO
      task = this.params.task;
    }
    
    if(task.toLowerCase() === this.projectsTaskSearch.getName().toLowerCase()) {
      currentTask = this.projectsTaskSearch;      
    }
    else if (task.toLowerCase() === this.projectsTaskDefault.getName().toLowerCase()) {
      currentTask = this.projectsTaskDefaut;
    }
    else {
      currentTask = eval("this." + this.params.defaultTask);
      task = this.params.defaultTask;
    }
    
    this.currentTask = currentTask;
    this.params.task = task;    
  },
  
  changePage : function(direction) {
    console.log("= = = ", "projects", "changePage");
    console.log("direction", direction , "pageNr: ", this.params.pageNr);
    this.setCurrentTask();
    
    if(direction === 'back') {
      this.params.pageNr = (this.params.pageNr == 0)? 0 : this.params.pageNr - 1;
    }
    else if(direction === 'forward') {
      this.params.pageNr = (this.params.pageNr == this.params.pageNrMax)? this.params.pageNrMax : this.params.pageNr + 1;
    }
    console.log("pageNr: ", this.params.pageNr);
    
    this.currentTask.changePage(this.params.pageNr);
  },
  
  startPage : function() {
    window.location = this.basePath + "catroid/index";
  }
  
  
});
