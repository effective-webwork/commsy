<?PHP
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

$this->includeClass(MATERIAL_INDEX_VIEW);
include_once('classes/cs_reader_manager.php');
include_once('functions/text_functions.php');
include_once('classes/cs_link.php');

/**
 *  class for CommSy list-view: material
 */
class cs_material_admin_index_view extends cs_material_index_view {

   /** array of ids in clipboard*/
   var $_selected_label = NULL;
   var $_available_labels = NULL;
   var $_selected_buzzword = NULL;
   var $_selected_status = NULL;
   var $_available_status = NULL;
   var $_available_buzzwords = NULL;

   /** constructor
    * the only available constructor, initial values for internal variables
    *
    * @param array params parameters in an array of this class
    */
   function cs_material_admin_index_view ($params) {
      $environment = $params['environment'];
      $with_modifying_actions = true;
      if ( isset($params['with_modifying_actions']) ) {
         $with_modifying_actions = $params['with_modifying_actions'];
      }
      $this->cs_material_index_view($params);
      $this->setTitle($this->_translator->getMessage('MATERIAL_INDEX'));
   }
   function setSelectedStatus ($status) {
      $this->_selected_status = (int)$status;
   }

   function getSelectedStatus () {
      return $this->_selected_status;
   }

   function setSelectedLabel ($label_id) {
      $this->_selected_label = (int)$label_id;
   }

   function getSelectedLabel () {
      return $this->_selected_label;
   }

   function setAvailableLabels ($label_list) {
      $this->_available_labels = $label_list;
   }

   function getAvailableLabels () {
      return $this->_available_labels;
   }

   function setSelectedBuzzword ($buzzword_id) {
      $this->_selected_buzzword = (int)$buzzword_id;
   }

   function getSelectedBuzzword () {
      return $this->_selected_buzzword;
   }

   function setAvailableBuzzwords ($buzzword_list) {
      $this->_available_buzzwords = $buzzword_list;
   }

   function getAvailableBuzzwords () {
      return $this->_available_buzzwords;
   }

   function _getGetParamsAsArray() {
      $params = parent::_getGetParamsAsArray();
      $params['sellabel'] = $this->getSelectedLabel();
      $params['selbuzzword'] = $this->getSelectedBuzzword();
      $params['selstatus'] = $this->getSelectedStatus();
      return $params;
   }

   function _getAdditionalFormFieldsAsHTML () {
      $html ='';
      $current_context = $this->_environment->getCurrentContextItem();
      $session = $this->_environment->getSession();
      $left_menue_status = $session->getValue('left_menue_status');
      $width = '235px';
      $selstatus = $this->getSelectedStatus();
      $html .= '<div class="infocolor">&nbsp;'.$this->_translator->getMessage('COMMON_STATUS').BRLF;
      $html .= '   <select name="selstatus" size="1" style="width: '.$width.'; font-size:10pt; margin-bottom:5px;" onChange="javascript:document.indexform.submit()">'.LF;
      $html .= '      <option value="6"';
      if ( !isset($selstatus) || $selstatus == 6 ) {
         $html .= ' selected="selected"';
      }
      $html .= '>*'.$this->_translator->getMessage('ALL').'</option>'.LF;

      $html .= '      <option value="2"';
      if ( isset($selstatus) and $selstatus == 2 ) {
         $html .= ' selected="selected"';
      }
      $text = $this->_translator->getMessage('MATERIAL_PUBLISHED');
      $html .= '>'.$text.'</option>'.LF;

      $html .= '      <option value="1"';
      if ( isset($selstatus) and $selstatus == 1 ) {
         $html .= ' selected="selected"';
      }
      $text = $this->_translator->getMessage('MATERIAL_REQUEST_PUBLISHED');
      $html .= '>'.$text.'</option>'.LF;
      $html .= '   </select>'.LF;

      $html .='</div>';
      $html .='</div>';

      return $html;
   }

   function _getListActionsAsHTML () {
      $current_context = $this->_environment->getCurrentContextItem();
      $current_user = $this->_environment->getCurrentUserItem();
      $html  = '';
      $html .= '<div style="clear:both; padding-bottom:0px;">';
      $current_user = $this->_environment->getCurrentUserItem();
      $params = $this->_environment->getCurrentParameterArray();
      $params['mode']='print';
      $image = '<img src="images/commsyicons/22x22/print.png" style="vertical-align:bottom;" alt="'.$this->_translator->getMessage('COMMON_LIST_PRINTVIEW').'"/>';
      $html .= ahref_curl($this->_environment->getCurrentContextID(),
                         $this->_environment->getCurrentModule(),
                         'index',
                         $params,
                         $image,
                         $this->_translator->getMessage('COMMON_LIST_PRINTVIEW')).LF;
      $html .= '</div>'.LF;
      return $html;
   }


   /** get View-Actions of this index view
    * this method returns the index actions as html
    *
    * @return string index actions
    */
   function _getViewActionsAsHTML () {
      $html  = '';
      $html .= '<select name="index_view_action" size="1" style="width:160px; font-size:8pt;">'.LF;
      $html .= '   <option selected="selected" value="-1">*'.$this->_translator->getMessage('COMMON_LIST_ACTION_NO').'</option>'.LF;
      $html .= '   <option class="disabled" disabled="disabled">------------------------------</option>'.LF;
      $html .= '   <option value="1">'.$this->_translator->getMessage('COMMON_MATERIAL_PUBLISH').'</option>'.LF;
      $html .= '   <option value="2">'.$this->_translator->getMessage('COMMON_MATERIAL_NOT_PUBLISH').'</option>'.LF;
      $html .= '</select>'.LF;
      $html .= '<input type="submit" style="width:70px; font-size:8pt;" name="option"';
      $html .= ' value="'.$this->_translator->getMessage('COMMON_LIST_ACTION_BUTTON_GO').'"';
      $html .= '/>'.LF;

      return $html;
   }

   /** get the title of the item
    * this method returns the item title in the right formatted style
    *
    * @return string title
    */
   function _getItemTitle($item){
      $title_text = $item->getTitle();
      $title_text = $this->_compareWithSearchText($title_text);
      $user = $this->_environment->getCurrentUser();
      if (!$this->_environment->inProjectRoom() and !$item->isPublished() and !$user->isUser() ){
         $title = '<span class="disabled">'.$title_text.'</span>'.LF;
      } else {
         $params = array();
         $params['iid'] = $item->getItemID();
         $title = ahref_curl( $this->_environment->getCurrentContextID(),
                              'material_admin',
                              'detail',
                              $params,
                              $this->_text_as_html_short($title_text));
         unset($params);
         if ( $this->_environment->inProjectRoom() ) {
            $title .= $this->_getItemChangeStatus($item);
            $title .= $this->_getItemAnnotationChangeStatus($item);
         }
      }
      return $title;
   }


   function asHTML () {
      $html  = LF.'<!-- BEGIN OF LIST VIEW -->'.LF;

       $html .= $this->_getIndexPageHeaderAsHTML();

      if(!$this->_clipboard_mode and !(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='<div id="right_boxes_area" style="float:right; width:27%; padding-top:5px; vertical-align:top; text-align:left;">'.LF;
         $html .='<div style="width:250px;">'.LF;
         $html .= '<form style="padding:0px; margin:0px;" action="'.curl($this->_environment->getCurrentContextID(), $this->_module, $this->_function,'').'" method="get" name="indexform">'.LF;
         $current_context = $this->_environment->getCurrentContextItem();
         $list_box_conf = $current_context->getListBoxConf();
         $first_box = true;
         $title_string ='';
         $desc_string ='';
         $config_text ='';
         $size_string = '';
         $html .= $this->_getHiddenFieldsAsHTML();
         $html .='<div id="commsy_panels">'.LF;
         $html .= '<div class="commsy_no_panel" style="margin-bottom:1px;">'.LF;
         $tempMessage = '';
         switch ( strtoupper($this->_environment->getCurrentModule()) ) {
            case 'ANNOUNCEMENT':
               $tempMessage = getMessage('ANNOUNCEMENT_INDEX');
               break;
            case 'DATE':
               $tempMessage = getMessage('DATE_INDEX');
               break;
            case 'DISCUSSION':
               $tempMessage = getMessage('DISCUSSION_INDEX');
               break;
            case 'INSTITUTION':
               $tempMessage = getMessage('INSTITUTION_INDEX');
               break;
            case 'GROUP':
               $tempMessage = getMessage('GROUP_INDEX');
               break;
            case 'MATERIAL':
               $tempMessage = getMessage('MATERIAL_INDEX');
               break;
            case 'MYROOM':
               $tempMessage = getMessage('MYROOM_INDEX');
               break;
            case 'PROJECT':
               $tempMessage = getMessage('PROJECT_INDEX');
               break;
            case 'TODO':
               $tempMessage = getMessage('TODO_INDEX');
               break;
            case 'TOPIC':
               $tempMessage = getMessage('TOPIC_INDEX');
               break;
            case 'USER':
               $tempMessage = getMessage('USER_INDEX');
               break;
            default:
               $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR'.' cs_index_view(1455) ');
               break;
         }
         $html .= $this->_getListInfosAsHTML($tempMessage);
         $context_item = $this->_environment->getCurrentContextItem();

         $html .= $this->_getExpertSearchAsHTML();

         $html .= $this->_getConfigurationOverviewAsHTML();

         /*****************Usage Information*************/
         $user = $this->_environment->getCurrentUserItem();
         $room = $this->_environment->getCurrentContextItem();
         $act_rubric = $this->_environment->getCurrentModule();
         $rubric_info_array = $room->getUsageInfoArray();
         if (!is_array($rubric_info_array)) {
            $rubric_info_array = array();
         }
         if ( !strstr($list_box_conf,'usage_nodisplay') ){
            if ( $first_box ){
               $first_box = false;
               $additional_text ='';
            }else{
               $additional_text =',';
            }
            $html .= '<div style="margin-bottom:1px;">'.LF;
            $html .= '<div style="position:relative; top:12px;">'.LF;
            $html .= '<img src="images/commsyicons/usage_info_3.png"/>';
            $html .= '</div>'.LF;
            $html .= '<div class="right_box_title" style="font-weight:bold;">'.$room->getUsageInfoHeaderForRubric($act_rubric).'</div>';
            $html .= '<div class="usage_info">'.LF;
            $info_text = $room->getUsageInfoTextForRubric($act_rubric);
            $html .= $this->_text_as_html_long($info_text).BRLF;
            $html .= '</div>'.LF;
            $html .='</div>'.LF;
         }

         $html .= '</form>'.LF;

         $html .='</div>'.LF;
         $html .= '<script type="text/javascript">'.LF;
         $html .= 'initCommSyPanels(Array('.$title_string.'),Array('.$desc_string.'),Array('.$config_text.'), Array(),Array('.$size_string.'));'.LF;
         $html .= '</script>'.LF;
      }
      elseif(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='<div style="float:right; width:27%; padding-top:5px; padding-left:5px; vertical-align:top; text-align:left;">'.LF;
         $html .='<div style="width:250px;">'.LF;
         $html .='<div style="margin-bottom:1px;">'.LF;
         $html .= $this->_getRubricClipboardInfoAsHTML($this->_environment->getCurrentModule());
         $html .='</div>'.LF;
         $html .='</div>'.LF;
      }

      $current_browser = strtolower($this->_environment->getCurrentBrowser());
      $current_browser_version = $this->_environment->getCurrentBrowserVersion();
      if ( $current_browser == 'msie' and (strstr($current_browser_version,'5.') or (strstr($current_browser_version,'6.'))) ){
         $width= ' width:100%; padding-right:10px;';
      }else{
         $width= '';
      }

      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .='</div>'.LF;
         $html .='<div class="index_content_display_width" style="'.$width.'padding-top:5px; vertical-align:bottom;">'.LF;
      }else{
         $html .='</div>'.LF;
         $html .='<div style="width:100%; padding-top:5px; vertical-align:bottom;">'.LF;
      }
      $params = $this->_environment->getCurrentParameterArray();
      $html .= '<form style="padding:0px; margin:0px;" action="';
      $html .= curl($this->_environment->getCurrentContextID(),
                    $this->_environment->getCurrentModule(),
                    $this->_environment->getCurrentFunction(),
                    $params
                   ).'" method="post">'.LF;
      if ( $this->hasCheckboxes() and $this->_has_checkboxes != 'list_actions' ) {
         $html .= '   <input type="hidden" name="ref_iid" value="'.$this->_text_as_form($this->getRefIID()).'"/>'.LF;
      }
      $html .= '<table class="list" style="width: 100%; border-collapse: collapse;" summary="Layout">'.LF;
      $html .= $this->_getTableheadAsHTML();
      if (!$this->_clipboard_mode){
         $html .= $this->_getContentAsHTML();
      }else{
         $html .= $this->_getClipboardContentAsHTML();
      }
      if(!(isset($_GET['mode']) and $_GET['mode']=='print')){
         $html .= $this->_getTablefootAsHTML();
      }
      $html .= '</table>'.LF;
      $html .= '</form>'.LF;
      $html .='</div>'.LF;
      $html .='<div style="clear:both;">'.LF;
      $html .='</div>'.LF;
      $html .='</div>'.LF;
      $html .= '<!-- END OF PLAIN LIST VIEW -->'.LF.LF;
      return $html;
   }



  function _getExpertSearchAsHTML(){
     $html  = '';
     $context_item = $this->_environment->getCurrentContextItem();
     $module = $this->_environment->getCurrentModule();
     if ($context_item->withActivatingContent()
          or $module == CS_DATE_TYPE
          or $module == CS_USER_TYPE
          or $module == CS_MATERIAL_TYPE
          or $module == 'material_admin'
          or $module == CS_TODO_TYPE
          or $module == 'campus_search'
      ){
         $width = '235';
         $html .= '<div class="commsy_no_panel" style="margin-bottom:1px;">'.LF;
         $html .= '<div class="right_box">'.LF;
         $html .= '<div class="right_box_title">'.$this->_translator->getMessage('COMMON_RESTRICTIONS').'</div>';
         $html .= '<div class="right_box_main" style="padding-top:5px;">'.LF;
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
         $html .= $this->_getAdditionalRestrictionBoxAsHTML('14.5').LF;
         $html .= $this->_getAdditionalFormFieldsAsHTML().LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
         $html .= '</div>'.LF;
      }
      return $html;
  }

     function _getConfigurationOverviewAsHTML(){
        $html='';
        $room = $this->_environment->getCurrentContextItem();
        $html .='<div class="commsy_no_panel" style="margin-bottom:1px; padding:0px;">'.LF;
        $html .= '<div class="right_box">'.LF;
        $array = $this->_environment->getCurrentParameterArray();
        $html .= '<div class="right_box_title">'.getMessage('COMMON_COMMSY_CONFIGURE_LINKS').'</div>';
        $html .= '<div class="right_box_main" style="font-size:8pt;">'.LF;
        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_COMMSY_CONFIGURE').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        $image = '<img src="images/commsyicons/22x22/config.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_COMMSY_CONFIGURE').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'index',
                                       '',
                                       $image,
                                       getMessage('COMMON_COMMSY_CONFIGURE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;
        $html .='<div class="listinfoborder">'.LF;
        $html .='</div>'.LF;

        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        $image = '<img src="images/commsyicons/22x22/config/room_options.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'room_options',
                                       '',
                                       $image,
                                       getMessage('COMMON_CONFIGURATION_ROOM_OPTIONS')).LF;
        $image = '<img src="images/commsyicons/22x22/config/rubric_options.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'rubric_options',
                                       '',
                                       $image,
                                       getMessage('COMMON_CONFIGURATION_RUBRIC_OPTIONS')).LF;
        $image = '<img src="images/commsyicons/22x22/config/structure_options.png" style="vertical-align:bottom;" alt="'.getMessage('CONFIGURATION_STRUCTURE_OPTIONS_TITLE').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'structure_options',
                                       '',
                                       $image,
                                       getMessage('CONFIGURATION_STRUCTURE_OPTIONS_TITLE')).LF;
        $image = '<img src="images/commsyicons/22x22/config/account_options.png" style="vertical-align:bottom;" alt="'.getMessage('CONFIGURATION_ACCOUNT_OPTIONS_TITLE').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'account_options',
                                       '',
                                       $image,
                                       getMessage('CONFIGURATION_ACCOUNT_OPTIONS_TITLE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;

        $html .='<div class="listinfoborder">'.LF;
        $html .='</div>'.LF;

        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_CONFIGURATION_ADMIN_OPTIONS').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        $image = '<img src="images/commsyicons/22x22/config/account.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_PAGETITLE_ACCOUNT').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'account',
                                       'index',
                                       '',
                                       $image,
                                       getMessage('COMMON_PAGETITLE_ACCOUNT')).LF;
        $context_item = $this->_environment->getCurrentContextItem();
        if ( $context_item->isCommunityRoom()
           and $context_item->isOpenForGuests()
           and $context_item->withRubric(CS_MATERIAL_TYPE)
        ) {
           $image = '<img src="images/commsyicons/22x22/config/material_admin.png" style="vertical-align:bottom;" alt="'.getMessage('MATERIAL_ADMIN_TINY_HEADER_CONFIGURATION').'"/>';
           $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'material_admin',
                                       'index',
                                       '',
                                       $image,
                                       getMessage('MATERIAL_ADMIN_TINY_HEADER_CONFIGURATION')).LF;
        }
        $image = '<img src="images/commsyicons/22x22/config/informationbox.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_INFORMATION_BOX').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'informationbox',
                                       '',
                                       $image,
                                       getMessage('COMMON_INFORMATION_BOX')).LF;
        $image = '<img src="images/commsyicons/22x22/config/usage_info_options.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_CONFIGURATION_USAGEINFO_FORM_TITLE').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'usageinfo',
                                       '',
                                       $image,
                                       getMessage('COMMON_CONFIGURATION_USAGEINFO_FORM_TITLE')).LF;
        $image = '<img src="images/commsyicons/22x22/config/mail_options.png" style="vertical-align:bottom;" alt="'.getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'mail',
                                       '',
                                       $image,
                                       getMessage('COMMON_CONFIGURATION_MAIL_FORM_TITLE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;

        $html .='<div class="listinfoborder">'.LF;
        $html .='</div>'.LF;

        $html .= '         <table style="width:100%; border-collapse:collapse;" summary="Layout" >'.LF;
        $html .= '         <tr>'.LF;
        $html .= '         <td style="font-size:10pt; white-space:nowrap;" class="infocolor">'.LF;
        $html .= $this->_translator->getMessage('COMMON_CONFIGURATION_ADDON_OPTIONS').': ';
        $html .= '         </td>'.LF;
        $html .= '         <td style="text-align:right; font-size:10pt;" class="right_box_main">'.LF;
        global $c_html_textarea;
        if ( $c_html_textarea ) {
           $image = '<img src="images/commsyicons/22x22/config/htmltextarea.png" style="vertical-align:bottom;" alt="'.getMessage('CONFIGURATION_TEXTAREA_TITLE').'"/>';
           $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'htmltextarea',
                                       '',
                                       $image,
                                       getMessage('CONFIGURATION_TEXTAREA_TITLE')).LF;
        }
        $context_item = $this->_environment->getCurrentContextItem();
        if ( $context_item->withWikiFunctions() and !$context_item->isServer() ) {
           $image = '<img src="images/commsyicons/22x22/config/pmwiki.png" style="vertical-align:bottom;" alt="'.getMessage('WIKI_CONFIGURATION_LINK').'"/>';
           $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'wiki',
                                       '',
                                       $image,
                                       getMessage('WIKI_CONFIGURATION_LINK')).LF;
        }
        if ( $context_item->withChatLink() and !$context_item->isPortal() ) {
        $image = '<img src="images/commsyicons/22x22/config/etchat.png" style="vertical-align:bottom;" alt="'.getMessage('CHAT_CONFIGURATION_LINK').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'chat',
                                       '',
                                       $image,
                                       getMessage('CHAT_CONFIGURATION_LINK')).LF;
        }
        $image = '<img src="images/commsyicons/22x22/config/template_options.png" style="vertical-align:bottom;" alt="'.getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'template_options',
                                       '',
                                       $image,
                                       getMessage('CONFIGURATION_TEMPLATE_FORM_ELEMENT_TITLE')).LF;
        $image = '<img src="images/commsyicons/22x22/config/rubric_extras.png" style="vertical-align:bottom;" alt="'.getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE').'"/>';
        $html .= ahref_curl($this->_environment->getCurrentContextID(),
                                       'configuration',
                                       'rubric_extras',
                                       '',
                                       $image,
                                       getMessage('CONFIGURATION_RUBRIC_EXTRAS_TITLE')).LF;
        $html .= '         </td>'.LF;
        $html .= '         </tr>'.LF;
        $html .= '         </table>'.LF;


        $html .= '</div>'.LF;
        $html .='</div>'.LF;
        $html .= '</div>'.LF;
        return $html;
     }


   function getRoomConfigurationList () {
      $room_link_list = '';
      include_once('include/inc_configuration_room_links.php');
      return $room_link_list;
   }

   function getAdminConfigurationList () {
      $admin_link_list = '';
      include_once('include/inc_configuration_admin_links.php');
      return $admin_link_list;
   }

   function getRubricConfigurationList () {
      $rubric_link_list = '';
      include_once('include/inc_configuration_rubric_links.php');
      return $rubric_link_list;
   }

   function getAddOnConfigurationList () {
        $addon_link_list = '';
      // addon configuration options
      include_once('include/inc_configuration_links_addon.php');
      return $addon_link_list;
   }

}
?>