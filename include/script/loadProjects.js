var LoadProjects = Class.$extend({
  __include__ : [__baseClassVars],
  __init__ : function(params) {
    this.params = params;
    this.commonContainerFill = new CommonContainerFill(this.params);
    this.initialized = false;
    if(this.params == null){
      console.log("ERROR: params are NULL!");
      return;
    }
  },

  initialize : function(object) {
    if(!this.initialized){
      this.object = object;
      // TODO: set loading page
      this.requestPage(this.params.pageNr);
      this.initialized = true;
    }
  },
  
  prevPage : function(params) {
    this.params = params;
    this.requestPage(this.params.pageNr);
  },
  
  nextPage : function(params) {
    this.params = params;
    this.requestPage(this.params.pageNr);
  },

  requestPage : function(pageNr) {
    //TODO: ajax mutex
    var self = this;    
    $.ajax({
      url : self.basePath + "catroid/loadProjects/" + pageNr + ".json",
      type : "POST",
      data : {
        task : self.params.task,
        page : pageNr,
        numProjectsPerPage : self.params.numProjectsPerPage,
        query : self.searchQuery
      },
      timeout : (this.ajaxTimeout),
      success : function(result) {
        if(result != ""){
          console.log("request page " + pageNr + ": success!");
          self.commonContainerFill.fill(result);
          // TODO:
          // saveHistoryState();
          // self.setDocumentTitle();
          // unblockAjaxRequest();
        }
      },
      error : function(result, errCode) {
        if(errCode == "timeout"){
          window.location.reload(false);
        }
      }
    });
  },

});
