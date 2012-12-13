/*    Catroid: An on-device graphical programming language for Android devices
 *    Copyright (C) 2010-2012 The Catroid Team
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

function HistoryHandler() {
  var things = [];
  if (HistoryHandler.inst) {
    return HistoryHandler.inst;
  }
  // if not called with 'new', force it
  if (!this instanceof HistoryHandler) {
    return new HistoryHandler();
    }
  }

  // remember the first created instance
  HistoryHandler.inst = this;

  // add methods that can see our private `things` variable
  this.add = function(thing) {
    things.push(thing);
  }

  this.list = function() {
    console.log("listing things!");
    console.log(things.toString());
  }
  
//  this.checkHistoryStatesEqual = function(state1, state2) {
//    var equal = false;
//    if((state1 != null) && (state2 != null)){
//      equal = (state1.language === state2.language)
//      // && (JSON.stringify(state1.ajaxResults.pageLabels) ===
//      // JSON.stringify(state2.ajaxResults.pageLabels))
//      && (state1.params.pageNr === state2.params.pageNr) && (state1.params.searchQuery === state2.searchQuery)
//          && (state1.params.task === state2.params.task)
//          && (JSON.stringify(state1.ajaxResults) === JSON.stringify(state2.ajaxResults));
//    }
//    return equal;
//  }
//
//  this.pushHistoryState = function(sender) {  
//    console.log("------", "HistoryHandler", "pushHistoryState");
//    console.log("sender", sender);
//    if(history.pushState){
//      var stateObject = {
//        params : new Array(),
//        ajaxResult : new Array(),
//        stateCallback: {},
//        language : {}
//      };
//      stateObject.params = sender.params;
//      stateObject.ajaxResult = sender.ajaxResult;
//      stateObject.language = $("#switchLanguage").val();
//      console.log("++++++++ sender: ", sender.sender);
//      console.log("++++++++ sender.restore: ", sender.sender.restore);
//      console.log("================================================");      
//
//      stateObject.stateCallback = eval(sender.sender.restore);
//      console.debug("stateObject.object", stateObject);
//      stateObject.stateCallback.call();
//      
//      
//      if(!this.checkHistoryStatesEqual(stateObject, history.state)){
//        console.info("! pushing history state");
//        console.log(stateObject);
//        history.pushState(stateObject, sender.params.pageLabels['websitetitle'] + " - " + sender.params.pageLabels['title']
//            + " - " + sender.params.pageNr, sender.basePath + "catroid/projects/" + sender.params.pageNr);
//      } else
//        console.log("history states equal, skipping!");
//
//    }
//  }
//  
//  this.restoreHistoryState = function(event) {
////    self.loadProjects.restoreHistoryState(event.state);        
//    console.log("======> ", "HistoryHandler", "restoring history state!!");
//    console.log(state);
//    var state = event.state;
////    return;
//    if(state != null){
//      console.log('restoring history state!');
//      var isLanguageChanged = (state.language != $("#switchLanguage").val());
//
//      if((state.params.task == "newestProjects") || (state.params.task == "searchProjects")){
//        if(isLanguageChanged){
//          this.ajaxResult = null;
//          event.object.changePage(this.params.pageNr);
//        } else{
//          this.params = state.params;
//          this.ajaxResult = state.ajaxResult;
//        }        
//
//        if(state.params.task == "newestProjects"){
//          $("#normalHeaderButtons").toggle(true);
//          $("#cancelHeaderButton").toggle(false);
//          $("#headerSearchBox").toggle(false);
//        } else{
//          $("#normalHeaderButtons").toggle(false);
//          $("#cancelHeaderButton").toggle(true);
//          $("#headerSearchBox").toggle(true);
//          $("#searchQuery").fo===========ENDcus();
//        }
//
//        if(!isLanguageChanged){
//          event.object.setDocumentTitle();
//          event.object.commonContainerFill.fill(state.ajaxResult);
//        }
//      }
//      this.initialized = true;
//    }
//  }
//  
  var mpushState = history.pushState;
  
  history.pushState = function (event) {
      console.debug("**************************************************************** HI THERE");
      mpushState.apply(history, arguments);
//      console.debug(history.pushState, " event:", event);
      event.state.callback.call();
//      eval(event.object).call;  
      console.debug("**************************************************************** BYE THERE");
      //fireEvents('pushState', arguments);  // Some event-handling function
  }
  
  window.onpopstate = function(event) {
    console.debug("--------------------- onpopstate ", event);
    console.debug("event.state" , event.state);
    console.debug("event.state.task", event.state.params.task);
//    console.log("event.state.object", event.state.object);
    console.debug("VIENNA CALLING________ ", event.state.callback);
    event.state.callback.call();
    
    console.debug("-----------__>", event.state.object);
    event.state.object.restore();
    return;
    if(event.state && ((event.state.params.task == "newestProjects") || (event.state.params.task == "searchProjects"))) {
      console.log("HistoryHandler", "window.onpopstate");
      restoreHistoryState(event.state);
    }
  }
  
var historyHandler = new HistoryHandler();
