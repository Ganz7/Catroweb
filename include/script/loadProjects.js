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
        query : self.params.searchQuery
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

  checkHistoryStatesEqual : function(state1, state2) {
    var equal = false;
    if((state1 != null) && (state2 != null)){
      equal = (state1.language === state2.language)
      // && (JSON.stringify(state1.ajaxResults.pageLabels) ===
      // JSON.stringify(state2.ajaxResults.pageLabels))
      && (state1.params.pageNr === state2.params.pageNr) && (state1.params.searchQuery === state2.searchQuery)
          && (state1.params.task === state2.params.task)
          && (JSON.stringify(state1.ajaxResults) === JSON.stringify(state2.ajaxResults));
    }
    return equal;
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

      if(!this.checkHistoryStatesEqual(stateObject, history.state)){
        console.log("pushing history state");
        console.log(stateObject);
        history.pushState(stateObject, this.params.pageLabels['websitetitle'] + " - " + this.params.pageLabels['title']
            + " - " + this.params.pageNr, this.basePath + "catroid/projects/" + this.params.pageNr);
      } else
        console.log("history states equal, skipping!");

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
          $("#normalHeaderButtons").toggle(false);
          $("#cancelHeaderButton").toggle(true);
          $("#headerSearchBox").toggle(true);
          $("#searchQuery").focus();
        }

        if(!isLanguageChanged){
          this.setDocumentTitle();
          this.commonContainerFill.fill(state.ajaxResult);
        }
      }
      this.initialized = true;
    }
  },

  updateParams : function(params) {
    this.params = params;
    console.log("loadProjects update", this.params.task);
    this.commonContainerFill.updateParams(this.params);
  },

  triggerSearch : function(loadAndCache) {
    console.log("triggering search! load=", loadAndCache);
    var search = $.trim($("#searchQuery").val());
    if(search != ""){
      if(this.tryAcquireAjaxMutex()){
        this.params.searchQuery = search;
        this.params.ajaxContent = null;
        this.params.pageNr = 1;
        if(loadAndCache){
          this.requestPage(this.params.pageNr);
        } else{
          this.releaseAjaxMutex();
        }
      }
    }
  },

  test : function() {
    console.log(this);
  },

  setDocumentTitle : function() {
    if(this.params.task == "newestProjects"){
      document.title = this.params.pageLabels['websitetitle'] + " - " + this.params.pageLabels['title'] + " - "
          + (this.params.pageNr);
    } else if(this.params.task == "searchProjects"){
      document.title = this.params.pageLabels['websitetitle'] + " - " + this.params.pageLabels['title'] + " - " + this.params.searchQuery
          + " - " + this.params.pageNr;
    }

  },

});
