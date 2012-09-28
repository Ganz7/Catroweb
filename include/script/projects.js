var Projects = Class.$extend( {
  __include__ : [__baseClassVars],
  __init__ : function(params) {
    var self = this;
    this.params = jQuery.parseJSON(params);      
    
    if(this.params == null) {
      console.log("ERROR: params are NULL!");
      return;
    }
    
    window.onpopstate = function(event) {
      console.log("restoring history state!");
      console.log(event);
      if(event.state && ((event.state.params.task == "newestProjects") || (event.state.params.task == "searchProjects"))) {
        self.loadProjects.restoreHistoryState(event.state);        
      }
    }
    
    this.loadProjects = new LoadProjects(this.params);
    setTimeout(function() { self.initialize(self); }, 50);
  },
  
  initialize : function(object) {
    if(window.history.state != null && window.history.state.pageContent != null) {
      console.log("restoring history!");
      object.loadProjects.restoreHistoryState(window.history.state);
    }
    if((object.params.task == "newestProjects") || (object.params.task == "searchProjects")) {
      this.loadProjects.initialize(object);
    }
    
    $("#aIndexWebLogoLeft").click($.proxy(this.startPage, this));
    $("#aIndexWebLogoMiddle").click($.proxy(this.startPage, this));
    // TODO:
//    $("#headerMenuButton").click(function() { self.newestProjects.saveStateToSession(self.newestProjects.pageNr.current); });
    
    $("#fewerProjects").click($.proxy(object.prevPage, object));
    $("#moreProjects").click($.proxy(object.nextPage, object));
    $("#searchForm").submit($.proxy(this.search, this));
    $("#headerCancelButton").click($.proxy(this.cancelSearch, this));
    
  },
  
  search : function() {
    if (this.params.state == "searchProjects") {
      this.loadProjects.triggerSearch(true);
    }
    else if($.trim($("#searchQuery").val()) != "") {
      this.params.task = "searchProjects";
      this.loadProjects.updateParams(this.params);
      this.loadProjects.triggerSearch(true);
    }
    return false;
  },
  
  cancelSearch : function() {
    console.log("cancelSearch!");
    this.params.task = "newestProjects";
    this.params.searchQuery = "";
    this.params.pageNr = 1;
    this.loadProjects.updateParams(this.params);
    this.loadProjects.prevPage(this.params);
    $("#searchQuery").val("");
    
  },
  
  prevPage : function() {
    this.params.pageNr = (this.params.pageNr == 0)? 0 : this.params.pageNr - 1; 
    this.loadProjects.prevPage(this.params);
  },
  
  nextPage : function() {
    this.params.pageNr = (this.params.pageNr == this.params.pageNrMax)? this.params.pageNrMax : this.params.pageNr + 1;
    this.loadProjects.nextPage(this.params);
  },
  
  startPage : function() {
    window.location = this.basePath + "catroid/index";
  }
  
  
});