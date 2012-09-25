var Projects = Class.$extend( {
  __include__ : [__baseClassVars],
  __init__ : function(params) {
    var self = this;
    this.params = jQuery.parseJSON(params);      
    
    if(this.params == null) {
      console.log("ERROR: params are NULL!");
      return;
    }
    
    this.loadProjects = new LoadProjects(this.params);
    setTimeout(function() { self.initialize(self); }, 50);
  },
  
  initialize : function(object) {
    this.loadProjects.initialize(object);
    $("#fewerProjects").click($.proxy(object.prevPage, object));
    $("#moreProjects").click($.proxy(object.nextPage, object));
  },
  
  prevPage : function() {
    this.params.pageNr = (this.params.pageNr == 0)? 0 : this.params.pageNr - 1; 
    this.loadProjects.prevPage(this.params);
  },
  
  nextPage : function() {
    this.params.pageNr = (this.params.pageNr == this.params.pageNrMax)? this.params.pageNrMax : this.params.pageNr + 1;
    this.loadProjects.nextPage(this.params);
  },
});