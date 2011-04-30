/*    Catroid: An on-device graphical programming language for Android devices
 *    Copyright (C) 2010-2011 The Catroid Team
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


var NewestProjects = Class.$extend( {
  __init__ : function(parent, basePath, maxLoadProjects, maxVisibleProjects, pageNr) {
	this.parent = parent;
    this.basePath = basePath;
    this.maxLoadProjects = parseInt(maxLoadProjects);
    this.maxVisibleProjects = parseInt(maxVisibleProjects);
    this.pageNr = { prev : parseInt(pageNr)-1, current : parseInt(pageNr), next : parseInt(pageNr)+1 };
   
    this.initialized = false;
    this.ajaxRequestMutex = false;
    this.pageLabels = new Array();
    this.pageContent = { prev : null, current : null, next : null };
  },
  
  initialize : function(object) {
    if(!object.initialized) {
      if(window.history.state != null && window.history.state.pageContent.current != null && window.history.state.newestProjects) {
        // FF 4.0 does not fire onPopState.event, webkit does
        object.restoreHistoryState(window.history.state);
        return;
      }
      
      object.createSkeleton();
      $("#fewerProjects").click($.proxy(object.prevPage, object));
      $("#moreProjects").click($.proxy(object.nextPage, object));
      
      object.loadAndCachePage();
      object.initialized = true;
    }
  },
  
  setDocumentTitle : function(title) {
    document.title = "Catroid Website - " + title;  // TODO
  },  

  setActive : function() {
    if(!this.initialized) {
      this.initialize(this);
      this.fillSkeletonWithContent();
    }
  },
  
  setInactive : function() {
    if(this.initialized) {
      $("#projectContainer").children().remove();
      this.initialized = false;
    }
  },
  
  saveHistoryState : function() {
    if(history.pushState) {
      var stateObject = { pageNr: {}, pageContent: {}, pageLabels: new Array()};
      stateObject.pageNr = this.pageNr;
      stateObject.pageLabels = this.pageLabels;
      stateObject.pageContent = this.pageContent;
      stateObject.newestProjects = true;
	      
      history.pushState(stateObject, "Page " + this.pageNr.current, this.basePath+"catroid/index/" + this.pageNr.current);
      this.setDocumentTitle("newest projects page " + this.pageNr.current);
    }
    this.saveStateToSession(this.pageNr.current);
  },

  restoreHistoryState : function(state) {
    if(state != null) {
      this.createSkeleton();
      $("#fewerProjects").click($.proxy(this.prevPage, this));
      $("#moreProjects").click($.proxy(this.nextPage, this));

      if(state.newestProjects) {
        this.pageNr = state.pageNr;
        this.pageLabels = state.pageLabels;
        this.pageContent = state.pageContent;
      }      
      $("#normalHeaderButtons").toggle(true);
      $("#cancelHeaderButton").toggle(false);
      $("#headerSearchBox").toggle(false);
      this.setDocumentTitle("newest projects page " + this.pageNr.current);
      this.fillSkeletonWithContent();
      this.initialized = true;
    }
  },
  
  saveStateToSession : function(pageNumber) {
    var self = this;
    $.ajax({
      type: "POST",
      url: self.basePath+"catroid/saveDataToSession/save.json",
      cache: false,      
      data: {
          content: {
            pageNr: pageNumber,
            task : "newestProjects"
          }
      }
    });
  },
  
  blockAjaxRequest : function() {
    if(!this.ajaxRequestMutex) {
      this.ajaxRequestMutex = true;
      $("#projectContainer").fadeTo(100, 0.20);
      return true;
    }
    return false;
  },
	  
  unblockAjaxRequest : function() {
    $("#projectContainer").fadeTo(10, 1.0);
    this.ajaxRequestMutex = false;
  },

  showStartPage : function() {
    if(this.blockAjaxRequest()) {
      this.pageContent.prev = "NIL";
      this.pageContent.current = null;
      this.pageContent.next = null;

      this.pageNr.prev = 0;
      this.pageNr.current = 1;
      this.pageNr.next = 2;
	      
      this.loadAndCachePage();
	      
      $("#normalHeaderButtons").toggle(true);
      $("#cancelHeaderButton").toggle(false);
      $("#headerSearchBox").toggle(false);
      $("#searchQuery").val("");     
    }
  },

  nextPage : function() {
    if(this.blockAjaxRequest()) {            
      this.pageContent.current = this.pageContent.current.concat(this.pageContent.next);
      this.pageNr.current++;
      
      this.pageContent.next = null;
      this.pageNr.next++;
      
      if(this.pageContent.current.length > this.maxVisibleProjects) {
        this.pageContent.current = this.pageContent.current.slice(this.maxLoadProjects);
        this.pageContent.prev = null; 
        this.pageNr.prev++;        
      }
      
      this.loadAndCachePage();
    }
  },

  prevPage : function() {
    if(this.blockAjaxRequest()) {
      this.pageContent.current = this.pageContent.prev.concat(this.pageContent.current);
      this.pageNr.current--;

      this.pageContent.prev = null;
      this.pageNr.prev--;

      if(this.pageContent.current.length > this.maxVisibleProjects) {
        this.pageContent.current = this.pageContent.current.slice(0, this.maxVisibleProjects);
        this.pageContent.next = null;
        this.pageNr.next--;
      }

      this.loadAndCachePage();
	  }
  },

  loadAndCachePage : function() {    
    if(this.pageContent.next == null) {
      this.requestPage(this.pageNr.next);
    }
    if(this.pageContent.current == null) {
      this.requestPage(this.pageNr.current);
    }
    if(this.pageContent.prev == null) {
      this.requestPage(this.pageNr.prev);
    }
  },

  requestPage : function(pageNr) {
    var self = this;
    $.ajax({
      url: self.basePath+"catroid/loadNewestProjects/"+pageNr+".json",
      cache: false,
      timeout: (5000),
    
      success: function(result){
        if(result != "") {
          for(var i = 0; i < result.content.length; i++) {
            result.content[i].pageNr = pageNr;
          }         
          
          self.pageLabels = result.labels;
          
          if(self.pageNr.current == pageNr) {
            if(self.pageContent.current == null) {
              self.pageContent.current = result.content;
            }
          }
          else {
            if(self.pageNr.prev == pageNr) {
              self.pageContent.prev = result.content;
            }
            if(self.pageNr.next == pageNr) {
              self.pageContent.next = result.content;
            }
          }
          
          if(self.pageContent.prev != null && self.pageContent.current != null && self.pageContent.next != null) {
            self.fillSkeletonWithContent();
            self.saveHistoryState();
            self.unblockAjaxRequest();            
          }
        }
      },
      error: function(result, errCode) {
        if(errCode == "timeout") {
          window.location.reload(false);          
        }        
      }
    });
  },

  createSkeleton : function() {
    if(!this.initialized) {
      var containerContent = $("<div />").addClass("projectListRow");

      var whiteBox = null;
      var projectListElementRow = null;
      for(var i = 0; i < this.maxVisibleProjects; i++) {
        if(whiteBox != null) {
          whiteBox.append(projectListElementRow);
          whiteBox.append("<div />").css("clear", "both");
          containerContent.append(whiteBox);
          var projectListSpacer = $("<div />").addClass("projectListSpacer").attr("id", "projectListSpacer"+i).css("display","none");
          containerContent.append(projectListSpacer);
        }

        whiteBox = $("<div />").addClass("whiteBoxMain").attr("id", "whiteBox"+i).css("display","none");
        projectListElementRow = $("<div />").addClass("projectListElementRow");

        var projectListElement = $("<div />").addClass("projectListElement").attr("id", "projectListElement"+i);
  	     
        var projectListThumbnail = $("<div />").addClass("projectListThumbnail").attr("id", "projectListThumbnail"+i);
        var projectListDetailsLinkThumb = $("<a />").addClass("projectListDetailsLink").attr("id", "projectListDetailsLinkThumb"+i);
        var projectListPreview = $("<img />").addClass("projectListPreview").attr("id", "projectListPreview"+i);
  	     
        var projectListTitle = $("<div />").addClass("projectDetailLine").attr("id", "projectListTitle"+i);
        var projectListDescription = $("<div />").addClass("projectDetailLine").attr("id", "projectListDescription"+i);
        var projectListDetails = $("<div />").addClass("projectListDetails");

        projectListThumbnail.append(projectListDetailsLinkThumb.append(projectListPreview).wrap("<div />"));
        projectListDetails.append(projectListTitle);
        projectListDetails.append(projectListDescription);

        projectListElement.append(projectListThumbnail);
        projectListElement.append(projectListDetails);

        projectListElementRow.append(projectListElement);
      }

      whiteBox.append(projectListElementRow);
      whiteBox.append("<div />").css("clear", "both");
      containerContent.append(whiteBox);
      containerContent.append($("<div />").addClass("projectListSpacer").attr("id", "projectListSpacer"+i).css("display","none"));

      var navigationButtonPrev = $("<button />").addClass("navigationButtons").addClass("button").addClass("white").addClass("medium").attr("type", "button");
      var navigationButtonNext = $("<button />").addClass("navigationButtons").addClass("button").addClass("white").addClass("medium").attr("type", "button");

      $("#projectContainer").append($("<div />").addClass("webMainNavigationButtons").append(navigationButtonPrev.attr("id", "fewerProjects").append($("<span />").addClass("navigationButtons"))));
      $("#projectContainer").append($("<div />").addClass("projectListSpacer"));
      $("#projectContainer").append(containerContent);
      $("#projectContainer").append($("<div />").addClass("projectListSpacer"));
      $("#projectContainer").append($("<div />").addClass("webMainNavigationButtons").append(navigationButtonNext.attr("id", "moreProjects").append($("<span />").addClass("navigationButtons"))));
    }
  },
  
  fillSkeletonWithContent : function() {
    var self = this;
    $("#projectListTitle").text(this.pageLabels['title']);
    $("#fewerProjects").children("span").html(this.pageLabels['prevButton']);
    $("#moreProjects").children("span").html(this.pageLabels['nextButton']);
    
    if(this.pageContent.prev == "NIL") {
      $("#fewerProjects").toggle(false);
    } else {
      $("#fewerProjects").toggle(true);
    }

    if(this.pageContent.next == "NIL") {
      $("#moreProjects").toggle(false);
    } else {
      $("#moreProjects").toggle(true);
    }

    var content = this.pageContent.current;
    for(var i=0; i<this.maxVisibleProjects; i++) {
      if(content != null && content[i]) {
        if($("#projectListElement"+i).length > 0) {
          $("#whiteBox"+i).css("display", "block");
          $("#projectListSpacer"+i).css("display", "block");
          $("#projectListThumbnail"+i).attr("title", content[i]['title']);
          $("#projectListDetailsLinkThumb"+i).attr("href", this.basePath+"catroid/details/"+content[i]['id']);
          $("#projectListDetailsLinkThumb"+i).unbind('click');
          $("#projectListDetailsLinkThumb"+i).bind("click", { pageNr: content[i]['pageNr'] }, function(event) { self.saveStateToSession(event.data.pageNr); });
          $("#projectListPreview"+i).attr("src", content[i]['thumbnail']).attr("alt", content[i]['title']);
          
          $("#projectListTitle"+i).html("<div class='projectDetailLineMaxWidth'><a class='projectListDetailsLinkBold' href='"+this.basePath+"catroid/details/"+content[i]['id']+"'>"+content[i]['title']+"</a></div>");
          $("#projectListTitle"+i).unbind('click');
          $("#projectListTitle"+i).bind("click", { pageNr: content[i]['pageNr'] }, function(event) { self.saveStateToSession(event.data.pageNr); });
          // + author $("#projectListDescription"+i).html("by <a class='projectListDetailsLink' href='#'>unknown</a><br />uploaded "+content[i]['upload_time']+" ago");
          $("#projectListDescription"+i).html("uploaded "+content[i]['upload_time']+" ago");          
        }
      }
      else {
        $("#whiteBox"+i).css("display", "none");
        $("#projectListSpacer"+i).css("display", "none");
      }
    }
  }
});
