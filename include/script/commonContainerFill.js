var CommonContainerFill = Class
    .$extend({
      __include__ : [__baseClassVars],
      __init__ : function(params) {
        this.params = params;
        this.initialized = false;
        this.fillSkeleton = null;

        this.createSkeletonHandler(this.params.view);
      },
      
      updateParams : function(params) {
        if(this.params.view != params.view) {
          this.createSkeletonHandler(this.params.view);         
        }
        console.log("commonContainer update" , this.params.task);
        this.params = params;
      },

      createSkeletonHandler : function(view) {
        switch(view) {
        case 'projectsByRow':
          this.createSkeletonRow();
          this.fillSkeleton = this.fillSkeletonRow;
          break;
        default:
          this.createSkeletonWall();
          this.fillSkeleton = this.fillSkeletonWall;
        }
      },

      createSkeletonRow : function() {
        if(!this.initialized){
          var containerContent = $("<div />").addClass("projectListRow");

          var whiteBox = null;
          var projectListElementRow = null;
          for( var i = 0; i < this.params.numProjectsPerPage; i++){
            if(whiteBox != null){
              whiteBox.append(projectListElementRow);
              whiteBox.append("<div />").css("clear", "both");
              containerContent.append(whiteBox);
              var projectListSpacer = $("<div />").addClass("projectListSpacer").attr("id", "projectListSpacer" + i)
                  .css("display", "none");
              containerContent.append(projectListSpacer);
            }

            whiteBox = $("<div />").addClass("whiteBoxMain").attr("id", "whiteBox" + i).css("display", "none");
            projectListElementRow = $("<div />").addClass("projectListElementRow");

            var projectListElement = $("<div />").addClass("projectListElement").attr("id", "projectListElement" + i);

            var projectListThumbnail = $("<div />").addClass("projectListThumbnail").attr("id",
                "projectListThumbnail" + i);
            var projectListDetailsLinkThumb = $("<a />").addClass("projectListDetailsLink").attr("id",
                "projectListDetailsLinkThumb" + i);
            var projectListPreview = $("<img />").addClass("projectListPreview").attr("id", "projectListPreview" + i);

            var projectListTitle = $("<div />").addClass("projectDetailLine").attr("id", "projectListTitle" + i);
            var projectListDescription = $("<div />").addClass("projectDetailLine").attr("id",
                "projectListDescription" + i);
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
          containerContent.append($("<div />").addClass("projectListSpacer").attr("id", "projectListSpacer" + i).css(
              "display", "none"));

          var navigationButtonPrev = $("<button />").addClass("navigationButtons").addClass("button").addClass("white")
              .addClass("medium").attr("type", "button");
          var navigationButtonNext = $("<button />").addClass("navigationButtons").addClass("button").addClass("white")
              .addClass("medium").attr("type", "button");

          $("#projectContainer")
              .append(
                  $("<div />").addClass("webMainNavigationButtons").append(
                      navigationButtonPrev.attr("id", "fewerProjects").append(
                          $("<span />").addClass("navigationButtons"))));
          $("#projectContainer").append($("<div />").addClass("projectListSpacer"));
          $("#projectContainer").append(containerContent);
          $("#projectContainer").append($("<div />").addClass("projectListSpacer"));
          $("#projectContainer").append(
              $("<div />").addClass("webMainNavigationButtons").append(
                  navigationButtonNext.attr("id", "moreProjects").append($("<span />").addClass("navigationButtons"))));
        }

      },

      fillSkeletonRow : function(projects, buttons) {
        var self = this;
        $("#projectListTitle").text(this.params.pageLabels['title'] + " (" + this.params.pageLabels['pageNr'] + ")");
        $("#fewerProjects").children("span").html(this.params.pageLabels['prevButton']);
        $("#moreProjects").children("span").html(this.params.pageLabels['nextButton']);

        if(projects === "NIL"){
          var msg = ("ERROR: fillSkeletonRow: no projects! ");
          console.log(msg);
          $(this.params.container).html("<b>" + msg + "</b>");
          return;
        }

        for( var i = 0; i < this.params.numProjectsPerPage; i++){
          if(projects != null && projects[i]){
            if($("#projectListElement" + i).length > 0){
              $("#whiteBox" + i).css("display", "block");
              $("#projectListSpacer" + i).css("display", "block");
              $("#projectListThumbnail" + i).attr("title", projects[i]['title']);
              $("#projectListDetailsLinkThumb" + i)
                  .attr("href", this.basePath + "catroid/details/" + projects[i]['id']);
              $("#projectListDetailsLinkThumb" + i).unbind('click');
              $("#projectListDetailsLinkThumb" + i).bind("click", {
                pageNr : projects[i]['pageNr']
              }, function(event) {
                // TODO: session handling
                // self.saveStateToSession(event.data.pageNr);
              });
              $("#projectListPreview" + i).attr("src", projects[i]['thumbnail']).attr("alt", projects[i]['title']);

              $("#projectListTitle" + i).html(
                  "<div class='projectDetailLineMaxWidth'><a class='projectListDetailsLinkBold' href='" + this.basePath
                      + "catroid/details/" + projects[i]['id'] + "'>" + projects[i]['title'] + "</a></div>");
              $("#projectListTitle" + i).unbind('click');
              $("#projectListTitle" + i).bind("click", {
                pageNr : projects[i]['pageNr']
              }, function(event) {
                // TODO: session handling
                // self.saveStateToSession(event.data.pageNr);
              });
              $("#projectListDescription" + i).html(
                  projects[i]['upload_time'] + " " + projects[i]['uploaded_by_string']);
            }
          } else{
            $("#whiteBox" + i).css("display", "none");
            $("#projectListSpacer" + i).css("display", "none");
          }
        }
        $("#fewerProjects").toggle(buttons.prevButton);
        $("#moreProjects").toggle(buttons.nextButton);
      },

      fill : function(result) {
        if(!this.params.container || this.params.container === 'undefined'){
          console.log("ERROR: no container ");
        }
        if(result.error){
          // TODO: error handling
          // self.showErrorPage(result.error['type'], result.error['code'],
          // result.error['extra']);
          console.log("request page " + result.projects.pageNr + ": failed!");
        } else{        
          this.params.pageLabels = result.pageLabels;
          this.fillSkeleton.call(this, result.content, result.buttons);         
        }

      }
    });