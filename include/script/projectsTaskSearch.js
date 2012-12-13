var ProjectsTaskSearch = Class.$extend( {
  __include__ : [__baseClassVars],
  __init__ : function(params) {
    var self = this;
    
    this.name = "ProjectsTaskSearch";
//    this.params = params.cbParams.call(this);
    console.log("bye from searchProjects");
    return;
    this.params = params;
    this.loadProjects = new LoadProjects(this.params, this);
    setTimeout(function() { self.initialize(self); }, 50);
  },
  
  initialize : function(object) {
    console.log("ProjectsSearch: init");
  },
  
  getName : function() {
    return this.name;
  },
  
  search : function(object) {
    this.params.task = "searchProjects";
    console.log("ProjectsSearch: search");
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
  
  isActive : function() {
    return  (this.params.task === this.name);
  },
  
  changePage : function(pageNr) {
    console.debug("?????????? working?");
    retrun;
    if(this.isActive()) {
      this.params.pageNr = pageNr;
      this.loadProjects.loadPage(this.params);
    }
  },
  
  nextPage : function() {
//    this.params.pageNr = (this.params.pageNr == this.params.pageNrMax)? this.params.pageNrMax : this.params.pageNr + 1;
//    this.loadProjects.nextPage(this.params);
  },

  restore : function() {
    console.log("ÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄÄ", "projectsSearch", "restore");
    // restore view specific buttons
    $("#normalHeaderButtons").toggle(false);
    $("#cancelHeaderButton").toggle(true);
    $("#headerSearchBox").toggle(true);
    $("#searchQuery").focus();
  }
  
});
