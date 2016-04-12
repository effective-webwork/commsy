// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bl�ssl, Matthias Finck, Dirk Fust, Franz Gr�nig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.


/*********************************************************************/
//       These functions are originally written by
//	www.dhtmlgoodies.com
//	Alf Magne Kalleland
//	They are adopted for use with CommSy
/*********************************************************************/

   var xpPanel_slideActive = true;	// Slide down/up active?
   var xpPanel_slideSpeed = 30;	// Speed of slide
   var xpPanel_onlyOneExpandedPane = false;	// Only one pane expanded at a time ?
   var commsy_pane;
   var commsy_panel_index;
   var savedActivePane = new Array();
   var savedActiveSub = new Array();
   var xpPanel_currentDirection = new Array();
   var cookieNames = new Array();
   var speedArray = new Array();
   var currentlyExpandedPane = false;


   /*
   These cookie functions are downloaded from
   http://www.mach5.com/support/analyzer/manual/html/General/CookiesJavaScript.htm
   */
   function Get_Cookie(name) {
      var start = document.cookie.indexOf(name+"=");
      var len = start+name.length+1;
      if ((!start) && (name != document.cookie.substring(0,name.length))){
         return null;
      }
      if (start == -1){
         return null;
      }
      var end = document.cookie.indexOf(";",len);
      if (end == -1){
         end = document.cookie.length;
      }
      return unescape(document.cookie.substring(len,end));
   }

   // This function has been slightly modified
   function Set_Cookie(name,value,expires,path,domain,secure) {
      expires = expires * 60*60*24*1000;
      var today = new Date();
      var expires_date = new Date( today.getTime() + (expires) );
      var cookieString = name + "=" +escape(value) +
          ( (expires) ? ";expires=" + expires_date.toGMTString() : "") +
          ( (path) ? ";path=" + path : "") +
          ( (domain) ? ";domain=" + domain : "") +
          ( (secure) ? ";secure" : "");
       document.cookie = cookieString;
   }

   function cancelXpWidgetEvent(){
      return false;
   }

   function showHidePaneContent(e,inputObj){
      if(!inputObj)inputObj = this;

      var img = inputObj;
      var numericId = img.id.replace(/[^0-9]/g,'');
      var obj = document.getElementById('paneContent' + numericId);
      xpPanel_slideSpeed = speedArray[numericId];

      if(img.src.toLowerCase().indexOf('up')>=0){
         currentlyExpandedPane = false;
         img.src = img.src.replace('up','down');
         if(xpPanel_slideActive && xpPanel_slideSpeed<200){
            obj.style.display='block';
            xpPanel_currentDirection[obj.id] = (xpPanel_slideSpeed*-1);
            slidePane((xpPanel_slideSpeed*-1), obj.id);
         }else{
            obj.style.display='none';
         }
         if(cookieNames[numericId]){
            Set_Cookie(cookieNames[numericId],'0',100000);
         }
      }else{
         if(this){
            if(currentlyExpandedPane && xpPanel_onlyOneExpandedPane){
               showHidePaneContent(xpPanel_slideSpeed,false,currentlyExpandedPane);
            }
            currentlyExpandedPane = this;
         }else{
            currentlyExpandedPane = false;
         }
         img.src = img.src.replace('down','up');
         if(xpPanel_slideActive && xpPanel_slideSpeed<200){
            if(document.all){
               obj.style.display='block';
               //obj.style.height = '1px';
            }
            xpPanel_currentDirection[obj.id] = xpPanel_slideSpeed;
            slidePane(xpPanel_slideSpeed,obj.id);
         }else{
            obj.style.display='block';
            subDiv = obj.getElementsByTagName('DIV')[0];
            obj.style.height = subDiv.offsetHeight + 'px';
         }
         if(cookieNames[numericId]){
            Set_Cookie(cookieNames[numericId],'1',100000);
         }
      }
      return true;
   }



   function slidePane(slideValue,id,name){
      if(slideValue!=xpPanel_currentDirection[id]){
         return false;
      }
      var activePane = document.getElementById(id);
      if(activePane==savedActivePane){
         var subDiv = savedActiveSub;
      }else{
         var subDiv = activePane.getElementsByTagName('DIV')[0];
      }
      savedActivePane = activePane;
      savedActiveSub = subDiv;

      var height = activePane.offsetHeight;
      var innerHeight = subDiv.offsetHeight;
      height+=slideValue;
      if(height<0){
         height=0;
      }
      if(height>innerHeight){
         height = innerHeight;
      }
      if(document.all){
         activePane.style.filter = 'alpha(opacity=' + Math.round((height / subDiv.offsetHeight)*100) + ')';
      }else{
         var opacity = (height / subDiv.offsetHeight);
   if(opacity==0){
            opacity=0.01;
         }
   if(opacity==1){
            opacity = 0.99;
         }
   activePane.style.opacity = opacity;
      }

      if(slideValue<0){
         activePane.style.height = height + 'px';
   subDiv.style.top = height - subDiv.offsetHeight + 'px';
   if(height>0){
      setTimeout('slidePane(' + slideValue + ',"' + id + '")',10);
   }else{
      if(document.all){
               activePane.style.display='none';
            }
   }
      }else{
         subDiv.style.top = height - subDiv.offsetHeight + 'px';
   activePane.style.height = height + 'px';
   if(height<innerHeight){
      setTimeout('slidePane(' + slideValue + ',"' + id + '")',10);
   }
      }
   }

   function initCommSyPanels(panelTitles,panelDesc,panelDisplayed,cookieArray,sizeArray){
      commsy_pane = document.getElementById('commsy_panels');
      var divs = commsy_pane.getElementsByTagName('DIV');
      commsy_panel_index=0;
      cookieNames = cookieArray;
      for(var no=0;no<sizeArray.length;no++){
         if (sizeArray[no] < 31) {
            speedArray[no]=xpPanel_slideSpeed;
         } else if (sizeArray[no] < 61) {
            speedArray[no]=xpPanel_slideSpeed*2;
         } else {
            speedArray[no]=sizeArray[no];
         }
      }
      for(var no=0;no<divs.length;no++){
         if(divs[no].className == 'commsy_panel'){
            var outerContentDiv = document.createElement('DIV');
            var contentDiv = divs[no].getElementsByTagName('DIV')[0];
            outerContentDiv.appendChild(contentDiv);

            outerContentDiv.id = 'paneContent' + commsy_panel_index;
            outerContentDiv.className = 'panelContent';
            var topBar = document.createElement('DIV');
            topBar.onselectstart = cancelXpWidgetEvent;
            var span = document.createElement('SPAN');
            span.innerHTML = panelTitles[commsy_panel_index].replace(/&COMMSYDHTMLTAG&/g,'</');
            topBar.appendChild(span);

            var span2 = document.createElement('SPAN');
            span2.innerHTML = panelDesc[commsy_panel_index];
            span2.className = 'small';
            topBar.appendChild(span2);

            topBar.style.position = 'relative';

            var img = document.createElement('IMG');
            img.id = 'showHideButton' + commsy_panel_index;
            img.src = 'images/arrow_up.gif';
            topBar.appendChild(img);

            img.onclick = showHidePaneContent;
            if(document.all){
               img.ondblclick = showHidePaneContent;
            }
            img.onmouseover = mouseoverTopbar;
            img.onmouseout = mouseoutTopbar;

            if(cookieArray[commsy_panel_index]){
               cookieValue = Get_Cookie(cookieArray[commsy_panel_index]);
               if(cookieValue ==1){
                  panelDisplayed[commsy_panel_index] = true;
               }else{
                  panelDisplayed[commsy_panel_index] = false;
               }
            }

            if(!panelDisplayed[commsy_panel_index]){
               outerContentDiv.style.height = '0px';
               contentDiv.style.top = 0 - contentDiv.offsetHeight + 'px';
               if(document.all){
                  outerContentDiv.style.display='none';
               }
               img.src = 'images/arrow_down.gif';
            }

            topBar.className='topBar';
            divs[no].appendChild(topBar);
            divs[no].appendChild(outerContentDiv);
            commsy_panel_index++;
         }
      }
   }