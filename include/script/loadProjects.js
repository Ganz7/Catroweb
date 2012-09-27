var LoadProjects = Class.$extend({
  __include__ : [__baseClassVars],
  __init__ : function(params) {
    this.params = params;
    this.commonContainerFill = new CommonContainerFill(this.params);
    this.initialized = false;
    this.ajaxRequestMutex = false;
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

  tryAcquireAjaxMutex : function() {
    if(!this.ajaxRequestMutex){
      this.ajaxRequestMutex = true;
      $("#projectContainer").fadeTo(100, 0.60);
      return true;
    }
    return false;
  },

  releaseAjaxMutex : function() {
    $("#projectContainer").fadeTo(10, 1.0);
    this.ajaxRequestMutex = false;
  },

  prevPage : function(params) {
    if(this.tryAcquireAjaxMutex()){
      this.params = params;
      this.requestPage(this.params.pageNr);
    }
  },

  nextPage : function(params) {
    if(this.tryAcquireAjaxMutex()){
      this.params = params;
      this.requestPage(this.params.pageNr);
    }
  },

  requestPage : function(pageNr) {
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
          self.ajaxResult = result;
          self.saveHistoryState();
          self.setDocumentTitle();
          self.releaseAjaxMutex();
        }
      },
      error : function(result, errCode) {
        if(errCode == "timeout"){
          window.location.reload(false);
        }
      }
    });
  },

  saveHistoryState : function() {
    if(history.pushState){
      var stateObject = {
        params : new Array(),
        ajaxResult : new Array(),
        language : {}
      };
      stateObject.params = this.params;
      stateObject.ajaxResult = this.ajaxResult;
      stateObject.language = $("#switchLanguage").val();

      history.pushState(stateObject, this.params.pageLabels['websitetitle'] + " - " + this.params.pageLabels['title']
          + " - " + this.params.pageNr, this.basePath + "catroid/projects/" + this.params.pageNr);
      console.log("pushing history state");
      console.log(stateObject);
    }
  },

  restoreHistoryState : function(state) {
    if(state != null){
      console.log('restoring history state!');
      var isLanguageChanged = (state.language != $("#switchLanguage").val());

      if((state.params.task == "newestProjects") || (state.params.task == "searchProjects")){
        if(isLanguageChanged){
          this.ajaxResult = null;
          this.requestPage(this.params.pageNr);
        } else{
          this.params = state.params;
          this.ajaxResult = state.ajaxResult;
        }

        if(state.params.task == "newestProjects"){
          $("#normalHeaderButtons").toggle(true);
          $("#cancelHeaderButton").toggle(false);
          $("#headerSearchBox").toggle(false);
        } else{
          // TODO: restore search buttons
        }

        if(!isLanguageChanged){
          this.setDocumentTitle();
          this.commonContainerFill = new CommonContainerFill(this.params);
          this.commonContainerFill.fill(state.ajaxResult);
        }
      }
      this.initialized = true;
    }
  },

  setDocumentTitle : function() {
    document.title = this.params.pageLabels['websitetitle'] + " - " + this.params.pageLabels['title'] + " - "
        + (this.params.pageNr + 1);
  },

});
