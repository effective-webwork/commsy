<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
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

$this->includeClass(ROOM_INDEX_VIEW);

/**
 *  class for CommSy list view: discussion
 */
class cs_discussion_index_view extends cs_room_index_view {

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_discussion_index_view ($params) {
      $this->cs_room_index_view($params);
      $this->setTitle($this->_translator->getMessage('DISCUSSION_HEADER'));
      $this->setActionTitle($this->_translator->getMessage('COMMON_DISCUSSION'));
      $this->setColspan(5);
   }

   /** set the content of the list view
    * this method sets the whole entries of the list view
    *
    * @param list  $this->_list          content of the list view
    *
    * @author CommSy Development Group
    */
    function setList ($list) {
       $this->_list = $list;
       if (!empty($this->_list)){
          $id_array = array();
          $item = $list->getFirst();
          while($item){
             $id = $item->getModificatorID();
             if (!in_array($id, $id_array)){
                $id_array[] = $id;
             }
             $item = $list->getNext();
          }
          $user_manager = $this->_environment->getUserManager();
          $user_manager->getRoomUserByIDsForCache($this->_environment->getCurrentContextID(),$id_array);
       }
    }

   function _getAdditionalRestrictionBoxAsHTML($field_length=14.5){
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      $selected_value = $this->_attribute_limit;
      if ($left_menue_status !='disapear'){
         $width = '190';
      }else{
         $width = '220';
      }
      $context_item = $this->_environment->getCurrentContextItem();
      $html = '';
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withActivatingContent()){
          $html .= '<div class="infocolor" style="text-align:left; font-size: 10pt;">'.$this->_translator->getMessage('COMMON_SHOW_ACTIVATING_ENTRIES').'<br />'.LF;
          $html .= '   <select style="width: '.$width.'px; font-size:10pt; margin-bottom:5px;" name="selactivatingstatus" size="1" onChange="javascript:document.indexform.submit()">'.LF;
          $html .= '      <option value="1"';
          if ( isset($this->_activation_limit) and $this->_activation_limit == 1 ) {
             $html .= ' selected="selected"';
          }
          $html .= '>*'.$this->_translator->getMessage('COMMON_ALL_ENTRIES').'</option>'.LF;
          $html .= '   <option class="disabled" disabled="disabled" value="-2">------------------------------</option>'.LF;
          $html .= '      <option value="2"';
          if ( !isset($this->_activation_limit) || $this->_activation_limit == 2 ) {
              $html .= ' selected="selected"';
          }
          $html .= '>'.$this->_translator->getMessage('COMMON_SHOW_ONLY_ACTIVATED_ENTRIES').'</option>'.LF;
          $html .= '   </select>'.LF;
          $html .='</div>';
      }
      return $html;
   }


   function getAdditionalRestrictionTextAsHTML(){
/***Activating Code***/
      $html = '';
      $params = $this->_environment->getCurrentParameterArray();
      if ( !isset($params['selactivatingstatus']) or (isset($params['selactivatingstatus']) and $params['selactivatingstatus'] == 2 ) ){
         $this->_additional_selects = true;
         $html_text ='<div class="restriction">';
         $html_text .= '<span class="infocolor">'.getMessage('COMMON_ACTIVATION_RESTRICTION').':</span> ';
         $html_text .= '<span>'.getMessage('COMMON_SHOW_ONLY_ACTIVATED_ENTRIES').'</span>';
         $picture = '<img src="images/delete_restriction.gif" alt="x" border="0"/>';
         $new_params = $params;
         $new_params['selactivatingstatus'] = 1;
         $html_text .= '&nbsp;'.ahref_curl($this->_environment->getCurrentContextID(),$this->_environment->getCurrentModule(),'index',$new_params,$picture,$this->_translator->getMessage('COMMON_DELETE_RESTRICTIONS')).LF;
         $html_text .='</div>';
         $html .= $html_text;
      }
      $context_item = $this->_environment->getCurrentContextItem();
      if ($context_item->withActivatingContent()){
         return $html;
      }else{
         return '';
      }
/*********************/
   }


   function _getListActionsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div class="right_box">'.LF;
      $html .= '         <noscript>';
      $html .= '<div class="right_box_title">'.getMessage('COMMON_ACTIONS').'</div>';
      $html .= '         </noscript>';
      $html .= '<div class="right_box_main" >'.LF;
      $current_user = $this->_environment->getCurrentUserItem();
      if ($current_user->isUser() and $this->_with_modifying_actions ) {
        $params = array();
        $params['iid'] = 'NEW';
        $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),CS_DISCUSSION_TYPE,'edit',$params,$this->_translator->getMessage('COMMON_NEW_ITEM')).BRLF;
        unset($params);
     } else {
        $html .= '> <span class="disabled">'.$this->_translator->getMessage('COMMON_NEW_ITEM').'</span>'.BRLF;
     }
     $params = $this->_environment->getCurrentParameterArray();
     $params['mode']='print';
     $html .= '> '.ahref_curl($this->_environment->getCurrentContextID(),CS_DISCUSSION_TYPE,'index',$params,$this->_translator->getMessage('COMMON_LIST_PRINTVIEW')).BRLF;
     $html .= '</div>'.LF;
     $html .= '</div>'.LF;

     return $html;
   }


   function _getTableheadAsHTML () {
      $params = $this->_getGetParamsAsArray();
      $params['from'] = 1;
      $html = '   <tr class="head">'.LF;
      $html .= '      <td class="head" style="width:47%;" colspan="2">';
      if ( $this->getSortKey() == 'title' ) {
         $params['sort'] = 'title_rev';
         $picture = '&nbsp;<img src="images/sort_up.gif" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'title_rev' ) {
         $params['sort'] = 'title';
         $picture = '&nbsp;<img src="images/sort_down.gif" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'title';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('COMMON_TITLE'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td style="width:20%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'numposts' ) {
         $params['sort'] = 'numposts_rev';
         $picture = '&nbsp;<img src="images/sort_up.gif" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'numposts_rev' ) {
         $params['sort'] = 'numposts';
         $picture = '&nbsp;<img src="images/sort_down.gif" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'numposts';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('DISCUSSION_ARTICLES'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td style="width:20%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'creator' ) {
         $params['sort'] = 'creator_rev';
         $picture = '&nbsp;<img src="images/sort_up.gif" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'creator_rev' ) {
         $params['sort'] = 'creator';
         $picture = '&nbsp;<img src="images/sort_down.gif" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'creator';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('COMMON_EDIT_BY'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;

      $html .= '      <td style="width:13%; font-size:8pt;" class="head">';
      if ( $this->getSortKey() == 'latest' ) {
         $params['sort'] = 'latest_rev';
         $picture = '&nbsp;<img src="images/sort_up.gif" alt="&lt;" border="0"/>';
      } elseif ( $this->getSortKey() == 'latest_rev' ) {
         $params['sort'] = 'latest';
         $picture = '&nbsp;<img src="images/sort_down.gif" alt="&lt;" border="0"/>';
      } else {
         $params['sort'] = 'latest';
         $picture ='&nbsp;';
      }
      $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                             $params, $this->_translator->getMessage('COMMON_EDIT_AT'), '', '', $this->getFragment(),'','','','class="head"');
      $html .= $picture;
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }


   function _getTablefootAsHTML() {
      $html  = '   <tr class="list">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions') {
         $html .= '<td class="foot_left" colspan="3"><input style="font-size:8pt;" type="submit" name="option" value="'.$this->_translator->getMessage('COMMON_ATTACH_BUTTON').'" /> <input type="submit"  style="font-size:8pt;" name="option" value="'.$this->_translator->getMessage('COMMON_CANCEL_BUTTON').'"/>';
      }else{
         $html .= '<td class="foot_left" colspan="3" style="vertical-align:middle;">'.LF;
         $html .= '<span class="select_link">[</span>';
         $params = $this->_environment->getCurrentParameterArray();
         $params['select'] = 'all';
         $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,
                          $params, $this->_translator->getMessage('COMMON_ALL_ENTRIES'), '', '', $this->getFragment(),'','','','class="select_link"');
         $html .= '<span class="select_link">]</span>'.LF;

         $html .= $this->_getViewActionsAsHTML();
      }
      $html .= '</td>'.LF;
      $html .= '<td class="foot_right" colspan="2" style="vertical-align:middle; text-align:right; font-size:8pt;">'.LF;
      if ( $this->hasCheckboxes() ) {
         if (count($this->getCheckedIDs())=='1'){
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED_ONE',count($this->getCheckedIDs()));
         }else{
            $html .= ''.$this->_translator->getMessage('COMMON_SELECTED',count($this->getCheckedIDs()));
         }
      }
      $html .= '</td>'.LF;
      $html .= '   </tr>'.LF;
      return $html;

   }

   /** get the item of the list view as HTML
    * this method returns the single item in HTML-Code
    *
    * overwritten method form the upper class
    *
    * @return string item as HMTL
    *
    * @author CommSy Development Group
    */
   function _getItemAsHTML($item,$pos=0,$with_links=true) {
      $html = '';
      $shown_entry_number = $pos;
      $shown_entry_number = $pos + $this->_count_headlines;
      if ($shown_entry_number%2 == 0){
         $style='class="odd"';
      }else{
         $style='class="even"';
      }
      if ($this->_clipboard_mode){
         $sort_criteria = $item->getContextID();
         if ( $sort_criteria != $this->_last_sort_criteria ) {
            $this->_last_sort_criteria = $sort_criteria;
            $this->_count_headlines ++;
            $room_manager = $this->_environment->getProjectManager();
            $sort_room = $room_manager->getItem($sort_criteria);
            $html .= '                     <tr class="list"><td '.$style.' width="100%" style="font-weight:bold;" colspan="5">'."\n";
            if ( empty($sort_room) ) {
               $community_manager = $this->_environment->getCommunityManager();
               $sort_community = $community_manager->getItem($sort_criteria);
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM').'&nbsp;'.$this->_translator->getMessage('COMMON_COMMUNITY_ROOM_TITLE').'&nbsp;"'.$sort_community->getTitle().'"'."\n";
            } elseif( $sort_room->isPrivateRoom() ){
               $user = $this->_environment->getCurrentUserItem();
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PRIVATEROOM').'&nbsp;"'.$user->getFullname().'"'."\n";
            }elseif( $sort_room->isGroupRoom() ){
              $html .= '                        '.$this->_translator->getMessage('COPY_FROM_GROUPROOM').'&nbsp;"'.$sort_room->getTitle().'"'.LF;
            }else {
               $html .= '                        '.$this->_translator->getMessage('COPY_FROM_PROJECTROOM').'&nbsp;"'.$sort_room->getTitle().'"'."\n";
            }
            $html .= '                     </td></tr>'."\n";
            if ( $style=='class="odd"' ){
               $style='class="even"';
            }else{
               $style='class="odd"';
            }
         }
      }
      $html  .= '   <tr class="list">'.LF;
      $checked_ids = $this->getCheckedIDs();
      $dontedit_ids = $this->getDontEditIDs();
      $key = $item->getItemID();
      $fileicons = $this->_getItemFiles($item, $with_links);
      if ( !empty($fileicons) ) {
         $fileicons = ' '.$fileicons;
      }
      $user = $this->_environment->getCurrentUser();
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .= '      <td '.$style.' style="vertical-align:middle;" width="2%">'.LF;
         $html .= '         <input style="font-size:8pt; padding-left:0px; padding-right:0px; margin-left:0px; margin-right:0px;" type="checkbox" onClick="quark(this)" name="attach['.$key.']" value="1"';
         if($item->isNotActivated() and !($item->getCreatorID() == $user->getItemID() or $user->isModerator()) ){
            $html .= ' disabled="disabled"'.LF;
         }elseif ( isset($checked_ids)
              and !empty($checked_ids)
              and in_array($key, $checked_ids)
            ) {
            $html .= ' checked="checked"'.LF;
            if ( in_array($key, $dontedit_ids) ) {
               $html .= ' disabled="disabled"'.LF;
            }
         }
         $html .= '/>'.LF;
         $html .= '         <input type="hidden" name="shown['.$this->_text_as_form($key).']" value="1"/>'.LF;
         $html .= '      </td>'.LF;
         if ($item->isNotActivated()){
            $title = $item->getTitle();
            $title = $this->_compareWithSearchText($title);
            if($item->getCreatorID() == $user->getItemID() or $user->isModerator()){
               $params = array();
               $params['iid'] = $item->getItemID();
               $title = ahref_curl( $this->_environment->getCurrentContextID(),
                                  CS_DISCUSSION_TYPE,
                                  'detail',
                                  $params,
                                  $title,
                                  '','', '', '', '', '', '', '',
                                  CS_DISCUSSION_TYPE.$item->getItemID());
               unset($params);
               if ($this->_environment->inProjectRoom()) {
                  $title .= $this->_getItemChangeStatus($item);
                  $title .= $this->_getItemAnnotationChangeStatus($item);
               }
            }
            $activating_date = $item->getActivatingDate();
            if (strstr($activating_date,'9999-00-00')){
               $title .= BR.getMessage('COMMON_NOT_ACTIVATED');
            }else{
               $title .= BR.getMessage('COMMON_ACTIVATING_DATE').' '.getDateInLang($item->getActivatingDate());
            }
            $title = '<span class="disabled">'.$title.'</span>';
            $html .= '      <td '.$style.'>'.$title.LF;
         }else{
             if($with_links) {
                $html .= '      <td '.$style.'>'.$this->_getItemTitle($item).$fileicons.LF;
             } else {
                $title = $this->_text_as_html_short($item->getTitle());
                $html .= '      <td '.$style.'>'.$title.LF;
             }
         }
      } else {
         $html .= '      <td colspan="2" '.$style.' style="font-size:10pt;">'.$this->_getItemTitle($item).$fileicons.'</td>'.LF;
      }
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getFastItemArticleCount($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemModificator($item).'</td>'.LF;
      $html .= '      <td '.$style.' style="font-size:8pt;">'.$this->_getItemLastArticleDate($item).'</td>'.LF;
      $html .= '   </tr>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    *
    * @author CommSy Development Group
    */
   function _getItemTitle ($item) {
      $title = $item->getTitle();
      $title_text = $this->_compareWithSearchText($title);
      $params = array();
      $params['iid'] = $item->getItemID();
      $title = ahref_curl( $this->_environment->getCurrentContextID(),
                           CS_DISCUSSION_TYPE,
                           'detail',
                           $params,
                           $this->_text_as_html_short($title_text),
                           '','', '', '', '', '', '', '',
                           CS_DISCUSSION_TYPE.$item->getItemID());

      unset($params);
      if ($item->isClosed()) {
         $title .= ' <span class="closed">('.$this->_translator->getMessage('DISCUSSION_IS_CLOSED').')</span>';
      }
     if ($this->_environment->inProjectRoom()) {
         $title .= $this->_getItemChangeStatus($item);
     }
      return $title;
   }



   /** get article count of a discussion
    * Returns the total and unread number of articles
    * for a discussion-item in a formatted string.
    *
    * @return string article_count
    *
    * @author CommSy Development Group
    */
   function _getItemArticleCount ($item) {
     $all_articles = $item->getAllArticlesCount();
     $unread_articles = $item->getUnreadArticles();
     return $all_articles.' ('.$unread_articles.' <span class="desc">'.$this->_translator->getMessage('COMMON_UNREAD').'</span>)';
   }

   function _getFastItemArticleCount ($item) {
     $array = $item->getAllAndUnreadArticles();
     return $array['count'].' ('.$array['unread'].' <span class="desc">'.$this->_translator->getMessage('COMMON_UNREAD').'</span>)';
   }
   /** get the date of last added article
    * this method returns the number in the right formatted style
    *
    * @return date last_article_date
    *
    * @author CommSy Development Group
    */
   function _getItemLastArticleDate ($item) {
     $last_article_date = $item->getLatestArticleModificationDate();
     $last_article_date = getDateInLang($last_article_date);
     //$last_article_date = //'<span class="list_view_description">'.
                          //$this->_translator->getMessage('LAST_ARTICLE_DATE').
                          //'</span> '.
                          //$last_article_date;
     return $this->_text_as_html_short($last_article_date);
   }

   /** get the file list of the item
    * this method returns the item file list in the right formatted style
    *
    * @return string file list
    */
   function _getItemFiles($item, $with_links=true){
      $retour = '';
      $file_list='';
      $files = $item->getFileListWithFilesFromArticles();
      $files->sortby('filename');
      $file = $files->getFirst();
      $user = $this->_environment->getCurrentUser();
      while ($file) {
         $url = $file->getUrl();
         $displayname = $file->getDisplayName();
         $filesize = $file->getFileSize();
         $fileicon = $file->getFileIcon();
         if ($with_links and $this->_environment->inProjectRoom() || (!$this->_environment->inProjectRoom() and ($item->isPublished() || $user->isUser())) ) {
            if ( isset($_GET['mode']) and $_GET['mode']=='print' ) {
               $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
            } else {
              if ( stristr(strtolower($file->getFilename()),'png')
                    or stristr(strtolower($file->getFilename()),'jpg')
                    or stristr(strtolower($file->getFilename()),'jpeg')
                    or stristr(strtolower($file->getFilename()),'gif')
                  ) {
                      $this->_with_slimbox = true;
                      $file_list.='<a href="'.$url.'" rel="lightbox[gallery'.$item->getItemID().']" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" >'.$fileicon.'</a> ';
                  }else{
                     $file_list.='<a href="'.$url.'" title="'.$this->_text_as_html_short($displayname).' ('.$filesize.' kb)" target="blank" >'.$fileicon.'</a> ';
                  }
              }
         } else {
            $file_list .= '<span class="disabled">'.$fileicon.'</span>'."\n";
         }
         $file = $files->getNext();
      }
      return $retour.$file_list;
   }
}
?>