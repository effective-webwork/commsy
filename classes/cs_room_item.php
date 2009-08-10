<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez
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

/** upper class of the project item
 */
include_once('classes/cs_context_item.php');
include_once('functions/text_functions.php');

/** father class for a rooms (project or community)
 * this class implements an abstract room item
 */

class cs_room_item extends cs_context_item {

   var $_old_status = NULL;

   /** constructor
   *
   * @param object environment environment of the commsy project
   */
   function cs_room_item ($environment) {
      $this->cs_context_item($environment);
   }

   ######################################################
   # methods for linking times (clock pulses) and rooms #
   ######################################################

   function _getContinuousStatus () {
      $retour = '';
      $retour = $this->_getValue('continuous');
      if (empty($retour)) {
         $retour = -1;
      }
      return $retour;
   }

   function isContinuous () {
      $retour = false;
      $value = $this->_getContinuousStatus();
      if ($value == 1) {
         $retour = true;
      }
      return $retour;
   }

   function setContinuous () {
      $this->_setValue('continuous',1,true);
      $this->_setLinksToTimeLabels();
   }

   function setNotContinuous () {
      $this->_setValue('continuous',-1,true);
   }

   function _setLinksToTimeLabels () {
      $portal_item = $this->getContextItem();
      $start_date = $this->_getDateFromDateTime($this->getCreationDate());
      if ($this->isClosed()) {
         $end_date = $this->_getDateFromDateTime($this->getClosureDate());
      }

      $current_date = getCurrentDate();

      $get_time_item_ids = false;
      $first = true;

      if ( $portal_item->showTime() ) {
         $time_item_id_array = array();
         $time_list = $portal_item->getTimeList();
         if ($time_list) {
            $time_item = $time_list->getFirst();
            while ($time_item) {
               if (!$time_item->isDeleted()) {
                  $date_label_start = $this->_getBeginDateFromTimeLabel($time_item->getTitle());
                  $date_label_end = $this->_getEndDateFromTimeLabel($time_item->getTitle());
                  if ($date_label_end < $date_label_start) {
                     $date_label_end = $date_label_end + 10000;
                  }
                  if ( $date_label_start <= $start_date
                       and $start_date <= $date_label_end) {
                     $get_time_item_ids = true;
                  }
                  if ($first) {
                     if ($date_label_start > $start_date) {
                        $get_time_item_ids = true;
                     }
                     $first = false;
                  }
                  if ($current_date < $date_label_start) {
                     $get_time_item_ids = false;
                  }
                  if ( isset($end_date)
                       and $date_label_start <= $end_date
                       and $end_date <= $date_label_end) {
                     $get_time_item_ids = false;
                  }
                  if ($get_time_item_ids) {
                     $time_item_id_array[] = $time_item->getItemID();
                  }
               }
               $time_item = $time_list->getNext();
            }
         }
         $this->setTimeListByID($time_item_id_array);
      }
   }

   function _getDateFromDateTime ($datetime) {
      $retour = '';
      $retour = $datetime[0].$datetime[1].$datetime[2].$datetime[3].$datetime[5].$datetime[6].$datetime[8].$datetime[9];
      return $retour;
   }

   function _getBeginDateFromTimeLabel ($title) {
      $retour = '';
      $title_array = explode('_',$title);
      $day_month = $this->_getBeginDayMonthFromTimeLabel($title);
      $retour = $title_array[0].$day_month[3].$day_month[4].$day_month[0].$day_month[1];
      return $retour;
   }

   function _getEndDateFromTimeLabel ($title) {
      $retour = '';
      $title_array = explode('_',$title);
      $day_month = $this->_getEndDayMonthFromTimeLabel($title);
      $retour = $title_array[0].$day_month[3].$day_month[4].$day_month[0].$day_month[1];
      return $retour;
   }


   function _getDayMonthFromTimeLabel ($title, $key) {
      $retour = '';
      $portal_item = $this->getContextItem();
                 if ( !$portal_item->isPortal() ) {
                    $portal_item = $this->_environment->getCurrentPortalItem();
                 }
      $time_text_array = $portal_item->getTimeTextArray();
      $title_array = explode('_',$title);
      $retour = $time_text_array[$title_array[1]][$key];
      return $retour;

   }

   function _getBeginDayMonthFromTimeLabel ($title) {
      return $this->_getDayMonthFromTimeLabel($title,'BEGIN');
   }

   function _getEndDayMonthFromTimeLabel ($title) {
      return $this->_getDayMonthFromTimeLabel($title,'END');
   }

   function setClosureDate ($value) {
      $this->_addExtra('CLOSURE_DATE',$value);
   }

   function getClosureDate () {
      $retour = '';
      if ($this->_issetExtra('CLOSURE_DATE')) {
         $retour = $this->_getExtra('CLOSURE_DATE');
      }
      return $retour;
   }

   function setContactPerson ($fullname) {
      if ( !empty($fullname) ) {
         $value = '';
         if ($this->_issetExtra('CONTACT_PERSONS')) {
            $value = $this->_getExtra('CONTACT_PERSONS');
         }
         if(!mb_stristr($value,$fullname)){
            $value .= $fullname.', ';
            $this->_setExtra('CONTACT_PERSONS',$value);
         }
      }
   }

   function getContactPersonString () {
      $return = '';
      if ($this->_issetExtra('CONTACT_PERSONS')) {
         $return = $this->_getExtra('CONTACT_PERSONS');
      }
      if(mb_strlen($return)>2 and mb_strstr($return,', ')){
         $return = mb_substr($return,0,(mb_strlen($return)-2));
      }
      return $return;
   }


   function unsetContactPerson ($fullname) {
      if ( !empty($fullname) ) {
         $value = '';
         if ($this->_issetExtra('CONTACT_PERSONS')) {
            $value = $this->_getExtra('CONTACT_PERSONS');
         }
         if(mb_stristr($value,$fullname.', ')){
            $value = str_replace($fullname.', ','',$value);
         }
         $this->_setExtra('CONTACT_PERSONS',$value);
      }
   }

   public function emptyContactPersonString () {
      $this->_unsetExtra('CONTACT_PERSONS');
   }

   /** close a room
   * this method sets the status of the room to closed
   */
   function close () {
      include_once('functions/date_functions.php');
      $this->setClosureDate(getCurrentDateTimeInMySQL());
      parent::close();
   }

   /** get time of a room
   * this method returns a list of clock pulses which are linked to the room
   *
   * @return object cs_list a list of clock pulses (cs_label_item)
   */
   function getTimeList() {
      $time_list = $this->_getLinkedItems($this->_environment->getTimeManager(), 'in_time');
      $time_list->sortBy('sorting');
      return $time_list;
   }

   /** set clock pulses of a room item by id
   * this method sets a list of clock pulses item_ids which are linked to the room
   *
   * @param array of time ids
   */
   function setTimeListByID ($value) {
      $time_array = array();
      foreach ( $value as $iid ) {
         $tmp_data = array();
         $tmp_data['iid'] = $iid;
         $time_array[] = $tmp_data;
      }
      $this->_setValue('in_time', $time_array, FALSE);
   }

   /** set clock pulses of a room
   * this method sets a list of clock pulses which are linked to the room
   *
   * @param object cs_list value list of clock pulses (cs_label_item)
   */
   function setTimeList($value) {
      $this->_setObject('in_time', $value, FALSE);
   }

    ######################################################
   # methods for template technique                     #
   ######################################################

   function _getTemplateStatus () {
      $retour = '-1';
      $value = $this->_getValue('template');
      if (!empty($value) and $value == 1) {
         $retour = 1;
      }
      return $retour;
   }

   function isTemplate () {
      $retour = false;
      $value = $this->_getTemplateStatus();
      if ($value == 1) {
         $retour = true;
      }
      return $retour;
   }

   function setTemplate () {
      $this->_setValue('template',1,true);
#      $this->close();
   }

   function setNotTemplate () {
      $this->_setValue('template',-1,true);
   }

   /** get topics of a project
    * this method returns a list of topics which are linked to the project
    *
    * @return object cs_list a list of topics (cs_label_item)
    */
   function getTopicList() {
      $topic_list = $this->_getLinkedItems($this->_environment->getLabelManager(), CS_TOPIC_TYPE);
      $topic_list->sortBy('name');
      return $topic_list;
   }

  /** set topics of a project item by id
   * this method sets a list of topic item_ids which are linked to the project
   *
   * @param array of topic ids
   *
   * @author CommSy Development Group
   */
   function setTopicListByID ($value) {
      $topic_array = array();
      foreach ( $value as $iid ) {
         $tmp_data = array();
         $tmp_data['iid'] = $iid;
         $topic_array[] = $tmp_data;
      }
      $this->_setValue(CS_TOPIC_TYPE, $topic_array, FALSE);
   }

   /** set topics of a project
    * this method sets a list of topics which are linked to the project
    *
    * @param object cs_list value list of topics (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function setTopicList($value) {
      $this->_setObject(CS_TOPIC_TYPE, $value, FALSE);
   }

   /** get institutions of a project
    * this method returns a list of institutions which are linked to the project
    *
    * @return object cs_list a list of institutions (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function getInstitutionList() {
      return $this->getLinkedItemList(CS_INSTITUTION_TYPE);
   }

  /** set institutions of a project item by id
   * this method sets a list of institution item_ids which are linked to the project
   *
   * @param array of institution ids
   *
   * @author CommSy Development Group
   */
   function setInstitutionListByID ($value) {
      $institution_array = array();
      foreach ( $value as $iid ) {
         $tmp_data = array();
         $tmp_data['iid'] = $iid;
         $institution_array[] = $tmp_data;
      }
      $this->_setValue(CS_INSTITUTION_TYPE, $institution_array, FALSE);
   }

   /** set institutions of a project
    * this method sets a list of institutions which are linked to the project
    *
    * @param object cs_list value list of institutions (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function setInstitutionList($value) {
      $this->_setObject(CS_INSTITUTION_TYPE, $value, FALSE);
   }

   /** get materials of a project
    * this method returns a list of materials which are linked to the project
    *
    * @return object cs_list a list of materials (cs_material_item)
    *
    * @author CommSy Development Group
    */
   function getMaterialList () {
      return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
   }

  /** set materials of a project item by item id and version id
   * this method sets a list of material item_ids and version_ids which are linked to the project
   *
   * @param array of material ids, index of id must be 'iid', index of version must be 'vid'
   * Example:
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   *
   * @author CommSy Development Group
   */
   function setMaterialListByID ($value) {
      $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);
   }

   /** set materials of a project
    * this method sets a list of materials which are linked to the project
    *
    * @param string value title of the project
    *
    * @author CommSy Development Group
    */
   function setMaterialList ($value) {
      $this->_setObject(CS_MATERIAL_TYPE, $value, FALSE);
   }

   public function getDataAsXML () {
      $retour  = '<room_item>';
      $retour .= '<title><![CDATA['.$this->getTitle().']]></title>';
      $retour .= '<item_id><![CDATA['.$this->getItemID().']]></item_id>';
      $retour .= '<context_id><![CDATA['.$this->getContextID().']]></context_id>';
      $retour .= '</room_item>';
      return $retour;
   }

   public function getXMLData () {
      return $this->getDataAsXML();
   }

   /** Sets the data of the item.
    *
    * @param $data_array Is the prepared array from "_buildItem($db_array)"
    * @return boolean TRUE if data is valid FALSE otherwise
    */
   function _setItemData($data_array) {
      $this->_data = $data_array;
      $retour = $this->isValid();
      if ($retour) {
         $this->_old_status = $this->getStatus();
      }
      return $retour;
   }

   ################################################################
   # mail to moderation, if the room status changed
   # - delete
   # - undelete
   # - open
   # - archive
   # - template (not implemented yet because flagged function)
   # - untemplate (not implemented yet because flagged function)
   # - reopen
   # - link to and unlink from community room
   ################################################################

   function _sendMailRoomDeleteToProjectModeration () {
      $this->_sendMailToModeration('project','delete');
   }

   function _sendMailRoomDeleteToCommunityModeration () {
      $this->_sendMailToModeration('community','delete');
   }

   function _sendMailRoomDeleteToPortalModeration () {
      $this->_sendMailToModeration('portal','delete');
   }

   function _sendMailRoomUnDeleteToProjectModeration () {
      $this->_sendMailToModeration('project','undelete');
   }

   function _sendMailRoomUnDeleteToCommunityModeration () {
      $this->_sendMailToModeration('community','undelete');
   }

   function _sendMailRoomUnDeleteToPortalModeration () {
      $this->_sendMailToModeration('portal','undelete');
   }

   function _sendMailRoomOpenToProjectModeration () {
      $this->_sendMailToModeration('project','open');
   }

   function _sendMailRoomOpenToCommunityModeration () {
      $this->_sendMailToModeration('community','open');
   }

   function _sendMailRoomOpenToPortalModeration () {
      $this->_sendMailToModeration('portal','open');
   }

   function _sendMailRoomArchiveToProjectModeration () {
      $this->_sendMailToModeration('project','archive');
   }

   function _sendMailRoomArchiveToCommunityModeration () {
      $this->_sendMailToModeration('community','archive');
   }

   function _sendMailRoomArchiveToPortalModeration () {
      $this->_sendMailToModeration('portal','archive');
   }

   function _sendMailRoomReOpenToProjectModeration () {
      $this->_sendMailToModeration('project','reopen');
   }

   function _sendMailRoomReOpenToCommunityModeration () {
      $this->_sendMailToModeration('community','reopen');
   }

   function _sendMailRoomReOpenToPortalModeration () {
      $this->_sendMailToModeration('portal','reopen');
   }

   function _sendMailRoomLinkToProjectModeration () {
      $this->_sendMailToModeration('project','link');
   }

   function _sendMailRoomLinkToCommunityModeration () {
      $this->_sendMailToModeration('community','link');
   }

   function _sendMailRoomLinkToPortalModeration () {
      $this->_sendMailToModeration('portal','link');
   }

   function _sendMailRoomLockToProjectModeration () {
      $this->_sendMailToModeration('project','lock');
   }

   function _sendMailRoomLockToCommunityModeration () {
      $this->_sendMailToModeration('community','lock');
   }

   function _sendMailRoomLockToPortalModeration () {
      $this->_sendMailToModeration('portal','lock');
   }

   function _sendMailRoomUnlockToProjectModeration () {
      $this->_sendMailToModeration('project','unlock');
   }

   function _sendMailRoomUnlockToCommunityModeration () {
      $this->_sendMailToModeration('community','unlock');
   }

   function _sendMailRoomUnlockToPortalModeration () {
      $this->_sendMailToModeration('portal','unlock');
   }

   /** get UsageInfos
   *   this method returns the usage infos
   *
   *   @return array
   */
   function getUsageInfoArray () {
      $retour = NULL;

        if(( $this->_getExtra('USAGE_INFO_GLOBAL') == 'false')  or (!$this->_issetExtra('USAGE_INFO_GLOBAL')))
        {
        if ($this->_issetExtra('USAGE_INFO')) {
            $retour = $this->_getExtra('USAGE_INFO');
            if ( empty($retour) ) {
               $retour = array();
            } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
            }
        } else {
          $retour = array();
        }
        } else {
           $retour = array();
           $array = $this->_default_rubrics_array;
           foreach($array as $current)
           {
            $retour[] = $current.'_no';
           }
           $retour[] = 'home_no';
        }

      return $retour;
   }

   /** set UsageInfos
   *   this method sets the usage infos
   *
   *   @param array
   */
   function setUsageInfoArray ($value_array) {
     if (is_array($value_array)){
       $this->_addExtra('USAGE_INFO',$value_array);
     }
   }
   function setUsageInfoGlobal ($value) {
      $this->_addExtra('USAGE_INFO_GLOBAL',$value);
   }


   /** get UsageInfos
   *   this method returns the usage infos
   *
   *   @return array
   */
   function getUsageInfoFormArray () {
      $retour = NULL;
      if(( $this->_getExtra('USAGE_INFO_GLOBAL') == 'false')  or (!$this->_issetExtra('USAGE_INFO_GLOBAL'))) {

        if ($this->_issetExtra('USAGE_INFO_FORM')) {
          $retour = $this->_getExtra('USAGE_INFO_FORM');
          if ( empty($retour) ) {
            $retour = array();
          } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
          }
        } else {
            $retour = array();
        }

      } else {
        $retour = array();
        $array = $this->_default_rubrics_array;
        foreach($array as $current)
        {
          $retour[] = $current.'_no';
        }
           $retour[] = 'home_no';
        }
      return $retour;
   }
   /** set UsageInfos
    *  this method sets the usage infos
    *
    * @param array
    */
   function setUsageInfoFormArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM',$value_array);
      }
   }
   function getUsageInfoHeaderArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }

   function setUsageInfoHeaderArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_HEADER',$value_array);
      }
   }



  function getUsageInfoFormHeaderArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_HEADER');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }

   function setUsageInfoFormHeaderArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM_HEADER',$value_array);
      }
   }


   function getUsageInfoTextArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }

   function setUsageInfoTextArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_TEXT',$value_array);
      }
   }

   function getUsageInfoFormTextArray () {
      $retour = NULL;
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      return $retour;
   }

   function setUsageInfoFormTextArray ($value_array) {
      if (is_array($value_array)){
         $this->_addExtra('USAGE_INFO_FORM_TEXT',$value_array);
      }
   }

   function getUsageInfoHeaderForRubric($rubric){
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
         if ( empty($retour) ) {
            $retour = array();
         } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
         }
      } else {
         $retour = array();
      }
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = getMessage('USAGE_INFO_HEADER');
      }
      return $retour;
   }

   function setUsageInfoHeaderForRubric($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $value_array = $this->_getExtra('USAGE_INFO_HEADER');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
      $this->_addExtra('USAGE_INFO_HEADER',$value_array);
   }

   function getUsageInfoHeaderForRubricForm($rubric){
      if ($this->_issetExtra('USAGE_INFO_HEADER')) {
         $retour = $this->_getExtra('USAGE_INFO_HEADER');
       if ( empty($retour) ) {
         $retour = array();
       } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
       }
      } else {
         $retour = array();
      }
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = getMessage('USAGE_INFO_HEADER');
      }
      return $retour;
   }

   function setUsageInfoHeaderForRubricForm($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_FORM_HEADER')) {
         $value_array = $this->_getExtra('USAGE_INFO_FORM_HEADER');
       if ( empty($value_array) ) {
         $value_array = array();
       } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
       }
      } else {
         $value_array = array();
      }
      $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
      $this->_addExtra('USAGE_INFO_FORM_HEADER',$value_array);
   }

   function _cronDaily () {
      // you can link daily cron jobs here like this
      // $cron_array[] = $this->_sendEmailNewsLetter();
      $cron_array = array();
      $cron_array[] = $this->_cronUnlinkFiles();
      $cron_array[] = $this->_cronControlLinkItems();
      $cron_array[] = $this->_cronControlLinks();
      global $c_virus_scan_cron;
      if ( isset($c_virus_scan_cron)
           and !empty($c_virus_scan_cron)
           and $c_virus_scan_cron
         ) {
         $cron_array[] = $this->_cronScanFiles();
      }
      global $c_indexing_cron;
      if ( isset($c_indexing_cron)
           and !empty($c_indexing_cron)
           and $c_indexing_cron
         ) {
         $cron_array[] = $this->_cronIndexFiles();
      }
      return $cron_array;
   }

   function _cronScanFiles () {
      $retour = array();
      $retour['title'] = 'scan files';
      $retour['description'] = 'scan files for virus';
      $retour['success'] = false;
      $retour['success_text'] = 'cron failed';

      $file_manager = $this->_environment->getFileManager();
      $file_manager->setNotScanLimit();
      $file_manager->setContextLimit($this->getItemID());
      $file_manager->select();
      $file_list = $file_manager->get();
      unset($file_manager);
      if ( $file_list->isNotEmpty() ) {
         $virus_found = 0;
         $file_item = $file_list->getFirst();
         while ($file_item) {
            $file_item->setCacheOff();
            if ( $file_item->hasVirus() ) {
               $virus = trim($file_item->getVirusName());
               $virus_found++;
               $file_item->deleteReally();

               // send mail to creator
               $translator = $this->_environment->getTranslationObject();
               $creator = $file_item->getCreatorItem();
               $context = $file_item->getContextItem();
               $context_title = $context->getTitle();
               $context_title_from = $context_title;
               if ( $context_title == 'PRIVATE_ROOM' ) {
                  $context_title = $translator->getMessage('COMMON_PRIVATE_ROOM');
                  if ( !$this->_cache_on ) {
                     $context->setCacheOff();
                  }
                  $portal_item = $context->getContextItem();
                  $context_title_from = $portal_item->getTitle();
                  unset($portal_item);
               }
               $user_manager = $this->_environment->getUserManager();
               $root_user = $user_manager->getRootUser();
               unset($user_manager);

               $subject = $translator->getMessage('VIRUS_VIRUS_FOUND_MAIL_SUBJECT',$context_title);
               $body    = $translator->getMessage('VIRUS_VIRUS_FOUND_MAIL_BODY',$virus,$file_item->getTitle(),$context_title);

               include_once('classes/cs_mail.php');
               $mail = new cs_mail();
               $mail->set_to($creator->getEMail());
               $mail->set_from_name($context_title_from);
               $server_item = $this->_environment->getServerItem();
               $default_sender_address = $server_item->getDefaultSenderAddress();
               if ( !empty($default_sender_address) ) {
                  $mail->set_from_email($default_sender_address);
               } else {
                  $mail->set_from_email('@');
               }
               $mail->set_reply_to_name($root_user->getFullname());
               $root_mail = $root_user->getEmail();
               if ( !empty($root_mail) ) {
                  $mail->set_reply_to_email($root_user->getEmail());
               } elseif ( !empty($default_sender_address) ) {
                  $mail->set_reply_to_email($default_sender_address);
               }
               $mail->set_subject($subject);
               $mail->set_message($body);
               $mail->send();
               unset($mail);
               unset($creator);
               unset($translator);
               unset($context);
               unset($server_item);
               unset($root_user);
            } else {
               $file_item->updateScanned();
            }
            unset($file_item);
            $file_item = $file_list->getNext();
         }
         $count = $file_list->getCount();
         unset($file_list);
         unset($file_item);
         $retour['success'] = true;
         $retour['success_text'] = $count.' files scanned: '.$virus_found.' virus found';
      } else {
         $retour['success'] = true;
         $retour['success_text'] = 'no files to scan';
      }

      return $retour;
   }

   function _cronIndexFiles () {
      $retour = array();
      $retour['title'] = 'indexing files';
      $retour['description'] = 'indexing of uploaded files for search';
      $retour['success'] = false;
      $retour['success_text'] = 'cron failed';

      global $c_indexing;
      if ( isset($c_indexing)
           and !empty($c_indexing)
           and $c_indexing
         ) {
         $file_manager = $this->_environment->getFileManager();
         $file_manager->resetLimits();
         $file_manager->setContextLimit($this->getItemID());
         $file_manager->setNewerLimit(getCurrentDateTimeMinusHoursInMySQL(24));
         $file_manager->select();
         $file_list = $file_manager->get();
         if ( $file_list->isNotEmpty() ) {
            $indexing_manager = $this->_environment->getFTSearchManager();
            $indexing_manager->setRoomID($this->getItemID());
            $indexing_manager->setPortalID($this->getContextID());
            $indexing_manager->setIncremental();
            $indexing_manager->buildFTIndex();
            unset($indexing_manager);
            $retour['success'] = true;
            $retour['success_text'] = $file_list->getCount().' files add to index';
         } else {
            $retour['success'] = true;
            $retour['success_text'] = 'nothing to do';
         }
         unset($file_list);
         unset($file_manager);
      } else {
         $retour['success_text'] = 'indexing is not enabled';
      }
      return $retour;
   }

   public function _cronControlLinkItems () {
      $retour = array();
      $retour['title'] = 'control link-items';
      $retour['description'] = 'delete link items, if first or second item doen\'t exists';
      $retour['success'] = false;
      $retour['success_text'] = 'cron failed';

      $link_item_manager = $this->_environment->getLinkItemManager();
      $count = $link_item_manager->deleteUnneededLinkItems($this->getItemID());
      unset($link_item_manager);
      if ( !isset($count) ) {
         $retour['success_text'] = 'cron failed';
      } elseif ( !empty($count) ) {
         $retour['success'] = true;
         if ( $count == 1 ) {
            $retour['success_text'] = 'delete '.$count.' link item';
         } else {
            $retour['success_text'] = 'delete '.$count.' link items';
         }
      } else {
         $retour['success'] = true;
         $retour['success_text'] = 'nothing to do';
      }

      return $retour;
   }

   public function _cronControlLinks () {
      $retour = array();
      $retour['title'] = 'control links';
      $retour['description'] = 'delete links, if from or to item doen\'t exists';
      $retour['success'] = false;
      $retour['success_text'] = 'cron failed';

      $link_item_manager = $this->_environment->getLinkManager();
      $count = $link_item_manager->deleteUnneededLinks($this->getItemID());
      unset($link_item_manager);
      if ( !isset($count) ) {
         $retour['success_text'] = 'cron failed';
      } elseif ( !empty($count) ) {
         $retour['success'] = true;
         if ( $count == 1 ) {
            $retour['success_text'] = 'delete '.$count.' link';
         } else {
            $retour['success_text'] = 'delete '.$count.' links';
         }
      } else {
         $retour['success'] = true;
         $retour['success_text'] = 'nothing to do';
      }

      return $retour;
   }

   ###################
   # RSS HASH
   ###################

   public function getUserByRSSHash ($rss_hash) {
      $retour = NULL;
      $hash_manager = $this->_environment->getHashManager();
      $retour = $hash_manager->getUserByRSSHash($rss_hash);
      unset($hash_manager);
      return $retour;
   }

   public function initTagRootItem () {
      $tag_manager = $this->_environment->getTagManager();
      $tag_root_item = $tag_manager->getRootTagItemFor($this->getItemID());
      if ( isset($tag_root_item) ) {
         $tag_root_item_id = $tag_root_item->getItemID();
      }
      if ( !isset($tag_root_item)
           or empty($tag_root_item_id)
         ) {
         $tag_manager->createRootTagItemFor($this->getItemID());
      }
      unset($tag_root_item);
      unset($tag_manager);
   }
}
?>