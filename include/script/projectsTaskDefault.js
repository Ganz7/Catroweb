var ProjectsTaskDefault = Class.$extend( {
  __include__ : [__baseClassVars],
  __init__ : function(cbParams) {
    var self = this;
//    console.debug(sender);
    this.name = "ProjectsTaskDefault";
    
    console.log("= = = ", "ProjectsTaskDefault");
    this.params = cbParams.call(this);
    console.log(this.params);
    
    this.loadProjects = new LoadProjects(cbParams);
    setTimeout(function() { self.initialize(self); }, 50);
  },
  
  initialize : function(object) {
    console.log("ProjectsTaskDefault: init");    
    console.log(object.params.task.toLowerCase());
    console.log(this.name.toLowerCase());
    if(object.params.task.toLowerCase() == this.name.toLowerCase()) {
      this.loadProjects.initialize(object);
    }
    
  },
  
  getName : function() {
    return this.name;
  },
  
  isActive : function() {
    return  (this.params.task.toLowerCase() === this.name.toLowerCase());
  },
  
  changePage : function(pageNr) {
    if(this.isActive()) {
      this.params.pageNr = pageNr;
      this.loadProjects.loadPage(this.params);
    }
  }

//  
//  restoreHistoryState : function() {
//    console.debug("--> ääääääääääääääääääääääääääää", "projectsMain", "restoreHistoryState");
//  },
//  
//  restore : function(state, commonContainerFill) {
//    this.commonContainerFill = this.loadProjects.getInstance().commonContainerFill;
//    console.log("????????????????????????????????????????????? projectsMain - restore");          
//    // restore view specific buttons
//    $("#normalHeaderButtons").toggle(true);
//    $("#cancelHeaderButton").toggle(false);
//    $("#headerSearchBox").toggle(false);
//    
//    if(state != null){
//      console.log('restoring history state!', state);
//      var isLanguageChanged = (state.language != $("#switchLanguage").val());
//
//      if(state.params.task == this.name){
//        if(isLanguageChanged){
//          this.ajaxResult = null;
//          this.loadProjects.requestPage(this.state.params.pageNr);
//        } else{
//          this.params = state.params;
//          this.ajaxResult = state.ajaxResult;
//        }
//        console.log("_______________> " ,state.ajaxResult);
//
//        if(state.params.task.toLowerCase() == this.name.toLowerCase){
//          $("#normalHeaderButtons").toggle(true);
//          $("#cancelHeaderButton").toggle(false);
//          $("#headerSearchBox").toggle(false);
//        } else{
//          console.log("????????????????????????????????????????????? projectsMain - restore");          
//        }
//
//        if(!isLanguageChanged){
//          //this.setDocumentTitle();
//          this.commonContainerFill.fill(state.ajaxResult);
//        }
//      }
//      this.initialized = true;
//    }
//  }  
});
