<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jose Manuel Gonzalez Vazquez
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

/** upper class of the news item
 */
include_once('classes/cs_item.php');

/** class for a user
 * this class implements a user item
 */
class cs_user_item extends cs_item {
   /**
   * array - is this needed??
   */
   var $_temp_picture_array = array();

   var $_picture_delete = false;

   var $_old_status = NULL;

   /** constructor: cs_user_item
    * the only available constructor, initial values for internal variables
    */
   function cs_user_item ($environment) {
      $this->cs_item($environment);
      $this->_type = CS_USER_TYPE;
      $this->_old_status = 'new';
   }

   /** Checks and sets the data of the item.
    *
    * @param $data_array Is the prepared array from "_buildItem($db_array)"
    */
   function _setItemData($data_array) {
      $this->_data = $data_array;
      if ( isset($data_array['status']) and !empty($data_array['status']) ) {
         $this->_old_status = $data_array['status'];
      }
   }

   /** get user id of the user
    * this method returns the user id (account or Benutzerkennung) of the user
    *
    * @return string user id of the user
    */
   function getUserID () {
      return $this->_getValue('user_id');
   }

   /** set user id of the user
    * this method sets the user id (account or Benutzerkennung) of the user
    *
    * @param string value user id of the user
    */
   function setUserID ($value) {
      $this->_setValue('user_id', $value);
   }

   function getAuthSource () {
      return $this->_getValue('auth_source');
   }

   function setAuthSource ($value) {
      $this->_setValue('auth_source', $value);
   }

   /** set groups of a news item by id
   * this method sets a list of group item_ids which are linked to the user
   *
   * @param array of group ids, index of id must be 'iid'<br />
   * Example:<br />
   * array(array('iid' => value1), array('iid' => value2))
   */
   function setGroupListByID ($value) {
      $this->setLinkedItemsByID(CS_GROUP_TYPE, $value);
   }

  /** set one group of a user item by id
   * this method sets one group item id which is linked to the user
   *
   * @param integer group id
   */
   function setGroupByID ($value) {
      $value_array = array();
      $value_array[] = $value;
      $this->setGroupListByID($value_array);
   }

  /** set one group of a user item
   * this method sets one group which is linked to the user
   *
   * @param object cs_label group
   */
   function setGroup ($value) {
      if ( isset($value)
           and $value->isA(CS_LABEL_TYPE)
           and $value->getLabelType() == CS_GROUP_TYPE
           and $value->getItemID() > 0
         ) {
         $this->setGroupByID($value->getItemID());
         unset($value);
      }
   }

   /** get topics of a user
   * this method returns a list of topics which are linked to the user
   *
   * @return object cs_list a list of topics (cs_label_item)
   */
   function getTopicList () {
      $topic_manager = $this->_environment->getLabelManager();
      $topic_manager->setTypeLimit(CS_TOPIC_TYPE);
      return $this->_getLinkedItems($topic_manager, CS_TOPIC_TYPE);
   }

   /** set topics of a user
   * this method sets a list of topics which are linked to the user
   *
   * @param cs_list list of topics (cs_label_item)
   */
   function setTopicList ($value) {
      $this->_setObject(CS_TOPIC_TYPE, $value, FALSE);
   }

   /** set topics of a news item by id
   * this method sets a list of topic item_ids which are linked to the user
   *
   * @param array of topic ids, index of id must be 'iid'<br />
   * Example:<br />
   * array(array('iid' => value1), array('iid' => value2))
   */
   function setTopicListByID ($value) {
      $this->setLinkedItemsByID(CS_TOPIC_TYPE, $value);
   }

  /** set one topic of a user item by id
   * this method sets one topic item id which is linked to the user
   *
   * @param integer topic id
   */
   function setTopicByID ($value) {
      $value_array = array();
      $value_array[] = $value;
      $this->setTopicListByID($value_array);
   }

  /** set one topic of a user item
   * this method sets one topic which is linked to the user
   *
   * @param object cs_label topic
   */
   function setTopic ($value) {
      $this->setTopicByID($value->getItemID());
   }

  /** get institutions of a user
   * this method returns a list of institutions which are linked to the user
   *
   * @return object cs_list a list of institutions (cs_label_item)
   */
   function getInstitutionList () {
      $institution_manager = $this->_environment->getLabelManager();
      $institution_manager->setTypeLimit(CS_INSTITUTION_TYPE);
      return $this->_getLinkedItems($institution_manager, CS_INSTITUTION_TYPE);
   }

   /** set institutions of a user
   * this method sets a list of institutions which are linked to the user
   *
   * @param cs_list list of institutions (cs_label_item)
   */
   function setInstitutionList ($value) {
      $this->_setObject(CS_INSTITUTION_TYPE, $value, FALSE);
   }

   /** set institutions of a news item by id
   * this method sets a list of institution item_ids which are linked to the user
   *
   * @param array of institution ids, index of id must be 'iid'<br />
   * Example:<br />
   * array(array('iid' => value1), array('iid' => value2))
   */
   function setInstitutionListByID ($value) {
      $this->setLinkedItemsByID(CS_INSTITUTION_TYPE, $value);
   }

  /** set one institution of a user item by id
   * this method sets one institution item id which is linked to the user
   *
   * @param integer institution id
   */
   function setInstitutionByID ($value) {
      $value_array = array();
      $value_array[] = $value;
      $this->setInstitutionListByID($value_array);
   }

  /** set one institution of a user item
   * this method sets one institution which is linked to the user
   *
   * @param object cs_label institution
   */
   function setInstitution ($value) {
      $this->setInstitutionByID($value->getItemID());
   }

   /** get firstname of the user
    * this method returns the firstname of the user
    *
    * @return string firstname of the user
    */
   function getFirstname () {
      return $this->_getValue("firstname");
   }

   /** set firstname of the user
    * this method sets the firstname of the user
    *
    * @param string value firstname of the user
    */
   function setFirstname ($value) {
      $this->_setValue("firstname", $value);
   }

   /** get lastname of the user
    * this method returns the lastname of the user
    *
    * @return string lastname of the user
    */
   function getLastname () {
      return $this->_getValue("lastname");
   }

   /** set lastname of the user
    * this method sets the lastname of the user
    *
    * @param string value lastname of the user
    */
   function setLastname ($value) {
      $this->_setValue("lastname", $value);
   }

   function makeContactPerson(){
      $this->_setValue("is_contact", '1');
   }

   function makeNoContactPerson(){
      $this->_setValue("is_contact", '0');
   }

   function getContactStatus(){
      $status = $this->_getValue("is_contact");
      return $status;
   }

   function isContact() {
      $retour = false;
      $status = $this->getContactStatus();
      if ($status == 1) {
         $retour = true;
      }
      return $retour;
   }

   /** get fullname of the user
    * this method returns the fullname (firstname + lastname) of the user
    *
    * @return string fullname of the user
    */
   function getFullName () {
        return ltrim($this->getFirstname().' '.$this->getLastname());
   }

   /** set title of the user
    * this method sets the title of the user
    *
    * @param string value title of the user
    */
   function setTitle ($value) {
      $this->_addExtra('USERTITLE',(string)$value);
   }

   /** get title of the user
    * this method returns the title of the user
    *
    * @return string title of the user
    */
   function getTitle () {
      $retour = '';
      if ($this->_issetExtra('USERTITLE')) {
         $retour = $this->_getExtra('USERTITLE');
      }
      return $retour;
   }

   /** set birthday of the user
    * this method sets the birthday of the user
    *
    * @param string value birthday of the user
    */
   function setBirthday ($value) {
      $this->_addExtra('USERBIRTHDAY',(string)$value);
   }

   /** get birthday of the user
    * this method returns the birthday of the user
    *
    * @return string birthday of the user
    */
   function getBirthday () {
      $retour = '';
      if ($this->_issetExtra('USERBIRTHDAY')) {
         $retour = $this->_getExtra('USERBIRTHDAY');
      }
      return $retour;
   }

   /** set birthday of the user
    * this method sets the birthday of the user
    *
    * @param string value birthday of the user
    */
   function setTelephone ($value) {
      $this->_addExtra('USERTELEPHONE',(string)$value);
   }

   /** get birthday of the user
    * this method returns the birthday of the user
    *
    * @return string birthday of the user
    */
   function getTelephone () {
      $retour = '';
      if ($this->_issetExtra('USERTELEPHONE')) {
         $retour = $this->_getExtra('USERTELEPHONE');
      }
      return $retour;
   }

   /** set celluarphonenumber of the user
    * this method sets the celluarphonenumber of the user
    *
    * @param string value celluarphonenumber of the user
    */
   function setCellularphone ($value) {
      $this->_addExtra('USERCELLULARPHONE',(string)$value);
   }

   /** get celluarphonenumber of the user
    * this method returns the celluarphonenumber of the user
    *
    * @return string celluarphonenumber of the user
    */
   function getCellularphone () {
      $retour = '';
      if ($this->_issetExtra('USERCELLULARPHONE')) {
         $retour = $this->_getExtra('USERCELLULARPHONE');
      }
      return $retour;
   }

   /** set homepage of the user
    * this method sets the homepage of the user
    *
    * @param string value homepage of the user
    */
   function setHomepage ($value) {
      if ( !empty($value) and $value != '-1' ) {
         if ( !mb_ereg("http://([a-z0-9_./?&=#:@]|-)*",$value) ) {
            $value = "http://".$value;
         }
      }
      $this->_addExtra('USERHOMEPAGE',(string)$value);
   }

   /** get homepage of the user
    * this method returns the homepage of the user
    *
    * @return string homepage of the user
    */
   function getHomepage () {
      $retour = '';
      if ($this->_issetExtra('USERHOMEPAGE')) {
         $retour = $this->_getExtra('USERHOMEPAGE');
      }
      return $retour;
   }


   /** set street of the user
    * this method sets the street of the user
    *
    * @param string value street of the user
    */
   function setStreet ($value) {
      $this->_addExtra('USERSTREET',(string)$value);
   }

   /** get street of the user
    * this method returns the street of the user
    *
    * @return string street of the user
    */
   function getStreet () {
      $retour = '';
      if ($this->_issetExtra('USERSTREET')) {
         $retour = $this->_getExtra('USERSTREET');
      }
      return $retour;
   }

   /** set zipcode of the user
    * this method sets the zipcode of the user
    *
    * @param string value zipcode of the user
    */
   function setZipcode ($value) {
      $this->_addExtra('USERZIPCODE',(string)$value);
   }

   /** get zipcode of the user
    * this method returns the zipcode of the user
    *
    * @return string zipcode of the user
    */
   function getZipcode () {
      $retour = '';
      if ($this->_issetExtra('USERZIPCODE')) {
         $retour = $this->_getExtra('USERZIPCODE');
      }
      return $retour;
   }

   /** set city of the user
    * this method sets the city of the user
    *
    * @param string value city of the user
    */
   function setCity ($value) {
      $this->_setValue('city', $value);
   }

   /** get city of the user
    * this method returns the city of the user
    *
    * @return string city of the user
    */
   function getCity () {
      return $this->_getValue('city');
   }

   /** set room of the user
    * this method sets the room of the user
    *
    * @param string value room of the user
    */
   function setRoom ($value) {
      $this->_addExtra('USERROOM',(string)$value);
   }

   /** get room of the user
    * this method returns the room of the user
    *
    * @return string room of the user
    */
   function getRoom () {
      $retour = '';
      if ($this->_issetExtra('USERROOM')) {
         $retour = $this->_getExtra('USERROOM');
      }
      return $retour;
   }

   /** set description of the user
    * this method sets the description of the user
    *
    * @param string value description of the user
    */
   function setDescription ($value) {
      $this->_addExtra('USERDESCRIPTION',(string)$value);
   }

   /** get description of the user
    * this method returns the description of the user
    *
    * @return string description of the user
    */
   function getDescription () {
      $retour = '';
      if ($this->_issetExtra('USERDESCRIPTION')) {
         $retour = $this->_getExtra('USERDESCRIPTION');
      }
      return $retour;
   }

   /** set picture filename of the user
    * this method sets the picture filename of the user
    *
    * @param string value picture filename of the user
    */
   function setPicture ($name) {
     // $this->_temp_picture_array = $value;
      $this->_addExtra('USERPICTURE',$name);
   }

   /** get description of the user
    * this method returns the description of the user
    *
    * @return string description of the user
    */
   function getPicture () {
      $retour = '';
      if ($this->_issetExtra('USERPICTURE')) {
         $retour = $this->_getExtra('USERPICTURE');
      }
      return $retour;
   }

   /** get email of the user
    * this method returns the email of the user
    *
    * @return string email of the user
    */
   function getEmail () {
      return $this->_getValue('email');
   }

   /** set email of the user
    * this method sets the email of the user
    *
    * @param string value email of the user
    */
   function setEmail ($value) {
      $this->_setValue('email', (string)$value);
   }

   /** get creator - do not use
    * this method is a warning for coders, because if you want an object cs_user_item here, you get into an endless loop
    */
   function getCreator () {
      echo('use getCreatorID()<br />');
   }

   /** set creator of the user - overwritting parent method - do not use
    *
    * @param object cs_user_item value creator of the user
    */
   function setCreator ($value) {
      echo('use setCreatorID( xxx )<br />');
   }

   /** get deleter - do not use
    * this method is a warning for coders, because if you want an object cs_user_item here, you get into an endless loop
    */
   function getDeleter () {
      echo('use getDeleterID()<br />');
   }

   /** set deleter of the user - overwritting parent method - do not use
    *
    * @param object cs_user_item value deleter of the user
    */
   function setDeleter ($value) {
      echo('use setDeleterID( xxx )<br />');
   }

   /** get user comment
    * this method returns the users comment: why he or she wants an account
    *
    * @return string user comment
    */
   function getUserComment () {
      $retour = '';
      if ($this->_issetExtra('USERCOMMENT')) {
         $retour = $this->_getExtra('USERCOMMENT');
      }
      return $retour;
   }

   /** set user comment
    * this method sets the users comment why he or she wants an account
    *
    * @param string value user comment
    */
   function setUserComment ($value) {
      $this->_addExtra('USERCOMMENT',(string)$value);
   }

   /** get comment of the moderators
    * this method returns the comment of the moderators
    *
    * @return string comment of the moderators
    */
   function getAdminComment () {
      $retour = '';
      if ($this->_issetExtra('ADMINCOMMENT')) {
         $retour = $this->_getExtra('ADMINCOMMENT');
      }
      return $retour;
   }

   /** set comment of the moderators
    * this method sets the comment of the moderators
    *
    * @param string value comment
    */
   function setAdminComment ($value) {
      $this->_addExtra('ADMINCOMMENT',$value);
   }

   /** get flag, if moderator wants a mail at new accounts
    * this method returns the getaccountwantmail flag
    *
    * @return integer value no, moderator doesn't want an e-mail
    *                       yes, moderator wants an e-mail
    */
   function getAccountWantMail () {
      $retour = 'yes';
      if ($this->_issetExtra('ACCOUNTWANTMAIL')) {
         $retour = $this->_getExtra('ACCOUNTWANTMAIL');
      }
      return $retour;
   }

   /** set flag if moderator wants a mail at new accounts
    * this method sets the comment of the moderator
    *
    * @param integer value no, moderator doesn't want an e-mail
    *                      yes, moderator wants an e-mail
    */
   function setAccountWantMail ($value) {
      $this->_addExtra('ACCOUNTWANTMAIL',(string)$value);
   }

   /** get flag, if moderator wants a mail at opening rooms
    * this method returns the getopenroomwantmail flag
    *
    * @return integer value no, moderator doesn't want an e-mail
    *                       yes, moderator wants an e-mail
    */
   function getOpenRoomWantMail () {
      $retour = 'yes';
      if ($this->_issetExtra('ROOMWANTMAIL')) {
         $retour = $this->_getExtra('ROOMWANTMAIL');
      }
      return $retour;
   }

   /** set flag if moderator wants a mail at opening rooms
    * this method sets the getopneroomwantmail flag
    *
    * @param integer value no, moderator doesn't want an e-mail
    *                      yes, moderator wants an e-mail
    */
   function setOpenRoomWantMail ($value) {
      $this->_addExtra('ROOMWANTMAIL',(string)$value);
   }

   function getRoomWantMail () {
      return $this->getOpenRoomWantMail();
   }

   /** get flag, if moderator wants a mail if he has to publish a material
    * this method returns the getaccountwantmail flag
    *
    * @return integer value 0, moderator doesn't want an e-mail
    *                       1, moderator wants an e-mail
    */
   function getPublishMaterialWantMail () {
      $retour = 'yes';
      if ($this->_issetExtra('PUBLISHWANTMAIL')) {
         $retour = $this->_getExtra('PUBLISHWANTMAIL');
      }
      return $retour;
   }

   /** set flag if moderator wants a mail if he has to publish a material
    * this method sets the comment of the moderator
    *
    * @param integer value no, moderator doesn't want an e-mail
    *                      yes, moderator wants an e-mail
    */
   function setPublishMaterialWantMail ($value) {
      $this->_addExtra('PUBLISHWANTMAIL',(string)$value);
   }

   /** get last login time
    * this method returns the last login in datetime format
    *
    * @return string last login
    */
   function getLastLogin () {
      return $this->_getValue('lastlogin');
   }

   /** get user language
    * this method returns the users language: de or en or ...
    *
    * @return string user language
    */
   function getLanguage () {
      $retour = 'de';
      if ($this->_issetExtra('LANGUAGE')) {
         $retour = $this->_getExtra('LANGUAGE');
      }
      return $retour;
   }

   /** set user language
    * this method sets the users language: de or en or ...
    *
    * @param string value user language
    */
   function setLanguage ($value) {
      $this->_addExtra('LANGUAGE',(string)$value);
   }

   /** get Visible of the user
    * this method returns the visible Property of the user
    *
    * @return integer visible of the user
    */
   function getVisible () {
      if ($this->isVisibleForAll()){
         return '2';
      } else {
         return '1';
      }
   }

   /** set visible property of the user
    * this method sets the visible Property of the user
    *
    * @param integer value visible of the user
    */
   function setVisible ($value) {
      if ($value =='2'){
         $this->_setValue('visible', $value);
      }else{
         $this->_setValue('visible', '1');
      }
   }

  /** set visible property of the user to LoggedIn
    */
  function setVisibleToLoggedIn () {
     $this->setVisible('1');
  }

  /** set visible property of the user to All
    * this method sets an order limit for the select statement to name
    */
  function setVisibleToAll () {
     $this->setVisible('2');
  }

  function isEmailVisible () {
     $retour = true;
     $value = $this->_getEmailVisibility();
     if ($value == '-1') {
        $retour = false;
     }
     return $retour;
  }

  function setEmailNotVisible () {
     $this->_setEmailVisibility('-1');
  }

  function setEmailVisible () {
     $this->_setEmailVisibility('1');
  }

  function _setEmailVisibility ($value) {
     $this->_addExtra('EMAIL_VISIBILITY', $value);
  }

  function _getEmailVisibility () {
     $retour = '';
     if ($this->_issetExtra('EMAIL_VISIBILITY')) {
      $retour = $this->_getExtra('EMAIL_VISIBILITY');
     }
     return $retour;
  }

  // need anymore ??? (TBD)
  function isCommSyContact () {
     $retour = false;
     if ($this->getVisible() == 1) {
        $retour = true;
     }
     return $retour;
  }

  // need anymore ??? (TBD)
  function isWorldContact () {
     $retour = false;
     if ($this->getVisible() == 2) {
        $retour = true;
     }
     return $retour;
  }

  /** reject a user
    * this method sets the status of the user to rejected
    */
   function reject () {
      $this->_setValue('status', 0);
   }

   /** request a user
    * this method sets the status of the user to request, an moderator must free the account
    */
   function request () {
      $this->_setValue('status', 1);
   }

   /** make a user normal user
    * this method sets the status of the user to normal
    */
   function makeUser () {
      $this->_setValue('status', 2);
   }

   /** make a user moderator
    * this method sets the status of the user to moderator
    */
   function makeModerator () {
      $this->_setValue('status', 3);
   }

   /** get status of user
    * this method returns an integer value corresponding with the users status
    *
    * @return int status
    */
   function getStatus () {
     return $this->_getValue('status');
   }

   /** get status of user
    * this method returns an integer value corresponding with the users status
    *
    * @return int status
    */
   function getLastStatus () {
     return $this->_getValue('status_last');
   }

   /** set user status last
    * this method sets the last status of the user, if status changed
    *
    * @param int status
    */
   function setLastStatus ($value) {
      $this->_setValue('status_last', (int)$value);
   }

   /** set user status
    * this method sets the status of the user
    *
    * @param int status
    */
   function setStatus ($value) {
      $this->setLastStatus($this->getStatus());
      $this->_setValue('status', (int)$value);
   }

   /** is user rejected ?
    * this method returns a boolean explaining if user is rejected or not
    *
    * @return boolean true, if user is rejected
    *                 false, if user is not rejected
    */
   function isRejected () {
      return $this->_getValue('status') == 0;
   }

   /** is user a guest ?
    * this method returns a boolean explaining if user is a guest or not
    *
    * @return boolean true, if user is a guest
    *                 false, if user is not a guest
    */
   function isGuest () {
      return $this->_getValue('status') == 0;
   }

   /** is user a guest ?
    * this method returns a boolean explaining if user is a guest or not
    *
    * @return boolean true, if user is a guest
    *                 false, if user is not a guest
    */
   function isReallyGuest () {
      return $this->_getValue('status') == 0 and mb_strtolower($this->_getValue('user_id'), 'UTF-8') == 'guest';
   }

      /** user has requested an account
    * this method returns a boolean explaining if user is still in request status
    *
    * @return boolean true, if user is in request status
    *                 false, if user is not in request status
    */
   function isRequested () {
      return $this->_getValue('status') == 1;
   }

   /** is user a normal user ?
    * this method returns a boolean explaining if user is a normal user or not
    *
    * @return boolean true, if user is a normal user or moderator
    *                 false, if user is not a normal user or moderator
    */
   function isUser () {
      return $this->_getValue('status') >= 2;
   }

   /** is user a moderator ?
    * this method returns a boolean explaining if user is a moderator or not
    *
    * @return boolean true, if user is a moderator
    *                 false, if user is not a moderator
    */
   function isModerator () {
      return $this->_getValue('status') == 3;
   }

   function getRelatedRoomItem () {
      $room_manager = $this->_environment->getProjectManager();
      return $room_manager->getItem($this->getRoomID());
   }

   function getRelatedCommunityList () {
      $manager = $this->_environment->getCommunityManager();
      $list = $manager->getRelatedCommunityListForUser($this);
      return $list;
   }

   function getRelatedProjectList () {
      $manager = $this->_environment->getProjectManager();
      $list = $manager->getRelatedProjectListForUser($this);
      return $list;
   }

   function getRelatedGroupList () {
      $manager = $this->_environment->getGrouproomManager();
      $list = $manager->getRelatedGroupListForUser($this);
      return $list;
   }

   function getRelatedProjectListSortByTime () {
      $manager = $this->_environment->getProjectManager();
      $list = $manager->getRelatedProjectListForUserSortByTime($this);
      return $list;
   }

   function getRelatedProjectListForMyArea () {
      $manager = $this->_environment->getProjectManager();
      $list = $manager->getRelatedProjectListForUserForMyArea($this);
      return $list;
   }

   function getRelatedProjectListSortByTimeForMyArea () {
      $manager = $this->_environment->getProjectManager();
      $list = $manager->getRelatedProjectListForUserSortByTimeForMyArea($this);
      return $list;
   }

   function _getTaskList () {
      $task_manager = $this->_environment->getTaskManager();
      return $task_manager->getTaskListForItem($this);
   }

  /** is user root ?
    * this method returns a boolean explaining if user is root or not
    *
    * @return boolean true, if user is root
    *                 false, if user is not root
    */
   function isRoot () {
      return ($this->_getValue('status') == 3)
             and ($this->getUserID() == 'root')
             and ($this->getContextID() == $this->_environment->getServerID());
   }

   /** is user VisibleForAll ?
    * this method returns a boolean explaining if user is Visible for everyone or not
    *
    * @return boolean true, if user is Visible for the Public
    *                 false, else
    */
   function isVisibleForAll () {
      return $this->_getValue('visible') == 2;
   }

   /** is user VisibleForLoggedIn ?
    * this method returns a boolean explaining if user is Visible for logged in members or not
    *
    * @return boolean true, if user is Visible for logged in members
    *                 false, else
    */
   function isVisibleForLoggedIn () {
      return true;
   }

   function save() {
      $user_manager = $this->_environment->getUserManager();
      $this->_save($user_manager);
      $item_id = $this->getItemID();
      if ( empty( $item_id ) ) {
         $this->setItemID($user_mananger->getCreateID());
      }

      // set old status to current status
      $this->_old_status = $this->getStatus();

      if(($this->getStatus() == 2) or ($this->getStatus() == 3)){
        // wenn $this->getStatus() einen freigeschalteten Benutzer angibt
        // 2 = normaler Benutzer
        // 3 = Moderator
          if($this->_environment->getCurrentContextItem()->WikiEnableDiscussion() == "1"){
            $this->updateWikiProfile();
          }

          if($this->_environment->getCurrentContextItem()->WikiEnableDiscussionNotification() == "1"){
            $this->updateWikiNotification();
          }
      } else {
        // Wenn der Benutzer gesperrt oder geloescht ist, müssen Profile und
        // Notification entsprechend angepasst werden
        // 0 = gesperrt & geloescht (+ deletion_date)
        //
        // Entscheidung 30.09.2008 - Eintraege bleiben unveraendert im Forum
        //$this->updateWikiRemoveUser();
      }
   }

   /**
    * This method only updates the LastLogin Of the User.
    * Only the LastLoginField will be touched.
    */
   function updateLastLogin() {
      $user_manager = $this->_environment->getUserManager();
      $user_manager->updateLastLoginOf($this);
   }

   function getOwnRoom () {
      if ( $this->isRoot() ) {
         return NULL;
      } else {
         $private_room_manager = $this->_environment->getPrivateRoomManager();
         return $private_room_manager->getRelatedOwnRoomForUser($this,$this->_environment->getCurrentPortalID());
      }
   }

   function delete () {

      // delete associated tasks
      $task_list = $this->_getTaskList();
      if (isset($task_list)) {
         $current_task = $task_list->getFirst();
         while ($current_task) {
            $current_task->delete();
            $current_task = $task_list->getNext();
         }
      }

      if ( $this->_environment->inPortal() ) {
         $own_room = $this->getOwnRoom();
         if ( isset($own_room) ) {
            $own_room->delete();
         }
      }

      $user_manager = $this->_environment->getUserManager();
      $this->_delete($user_manager);

      // set old status to current status
      $this->_old_status = $this->getStatus();

      if ( $this->_environment->inPortal() ) {
         $id_manager = $this->_environment->getExternalIdManager();
         $id_manager->deleteByCommSyID($this->getItemID());
         unset($id_manager);
      }
   }


   function maySee ($user_item) {
      if ( $this->_environment->inCommunityRoom() ) {  // Community room
         if ( $user_item->isRoot()
              or ( $user_item->isGuest() and $this->isVisibleForAll() )
              or ( $user_item->getContextID() == $this->getContextID()
                   and ( ( $user_item->isUser() and $this->isVisibleForLoggedIn() )
                          or ( $user_item->getUserID() == $this->getUserID()
                               and $user_item->getAuthSource == $this->getAuthSource()
                             )
                          or ($user_item->isModerator())
            ) ) ) {
            $access = true;
         } else {
            $access = false;
         }
      } else {    // Project room
         $access = parent::maySee($user_item);
      }
      return $access;
   }



   function mayEdit ($user_item) {
      if ( $user_item->isRoot() or
            ( $user_item->getContextID() == $this->getContextID()
              and ( $user_item->isModerator()
                    or ( $user_item->isUser()
                         and ( $this->getUserID() == $user_item->getUserID() )
                         and ( $this->getAuthSource() == $user_item->getAuthSource() )
                       )
                  )
            )
         ) {
         $access = true;
      } else {
         $access = false;
      }
      return $access;
   }

   function mayEditRegular ($user_item) {
      return $this->getUserID() == $user_item->getUserID() and $this->getAuthSource() == $user_item->getAuthSource();
   }

   /**
    * @param object cs_user User-Item with changed information
    */
   function changeRelatedUser ($dummy_item) {
      $related_user = $this->getRelatedUserList();
      if (!$related_user->isEmpty()) {
         $user_item = $related_user->getFirst();
         while ($user_item) {
            $value = $dummy_item->getFirstName();
            if (!empty($value)) {
               $user_item->setFirstName($value);
            }
            $value = $dummy_item->getLastName();
            if (!empty($value)) {
               $user_item->setLastName($value);
            }
            $value = $dummy_item->getTitle();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setTitle($value);
            }
            $value = $dummy_item->getTelephone();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setTelephone($value);
            }
            $value = $dummy_item->getBirthday();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setBirthday($value);
            }
            $value = $dummy_item->getCellularphone();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setCellularphone($value);
            }
            $value = $dummy_item->getHomepage();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setHomepage($value);
            }
            $value = $dummy_item->getStreet();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setStreet($value);
            }
            $value = $dummy_item->getZipCode();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setZipCode($value);
            }
            $value = $dummy_item->getCity();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setCity($value);
            }
            $value = $dummy_item->getDescription();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setDescription($value);
            }
            $value = $dummy_item->getPicture();
            if (!empty($value)) {
               if ($value == -1) {
                  $new_picture_name = '';
               } else {
       $value_array = explode('_',$value);
       $value_array[0] = 'cid'.$user_item->getContextID();
       $new_picture_name = implode('_',$value_array);
       $disc_manager = $this->_environment->getDiscManager();
       $disc_manager->copyImageFromRoomToRoom($value,$user_item->getContextID());
         }
               $user_item->setPicture($new_picture_name);
            }
            $value = $dummy_item->getEmail();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setEmail($value);

               if (!$dummy_item->isEmailVisible()) {
                  $user_item->setEmailNotVisible();
               } else {
                  $user_item->setEmailVisible();
               }
            }
            $value = $dummy_item->getRoom();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setRoom($value);
            }
            $value = $dummy_item->getICQ();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setICQ($value);
            }
            $value = $dummy_item->getJabber();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setJabber($value);
            }
            $value = $dummy_item->getMSN();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setMSN($value);
            }
            $value = $dummy_item->getSkype();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setSkype($value);
            }
            $value = $dummy_item->getYahoo();
            if (!empty($value)) {
               if ($value == -1) {
                  $value = '';
               }
               $user_item->setYahoo($value);
            }

            $user_item->save();

            $user_item = $related_user->getNext();
         }
      }
   }

   /**
    * @return object cs_list list of User-Items connected to this item
    */
   function getRelatedUserList () {

      $current_context_id = $this->getContextID();

      $room_id_array = array();
      if ($this->_environment->getCurrentPortalID() != $current_context_id) {
         $room_id_array[] = $this->_environment->getCurrentPortalID();
      }

      $community_manager = $this->_environment->getCommunityManager();
      $community_list = $community_manager->getRelatedCommunityListForUser($this);
      if ($community_list->isNotEmpty()) {
         $community_room = $community_list->getFirst();
         while ($community_room) {
            if ($community_room->getItemID() != $current_context_id) {
               $room_id_array[] = $community_room->getItemID();
            }
            unset($community_room);
            $community_room = $community_list->getNext();
         }
         unset($community_list);
      }
      unset($community_manager);

      $project_manager = $this->_environment->getProjectManager();
      $project_list = $project_manager->getRelatedProjectListForUser($this);
      if ($project_list->isNotEmpty()) {
         $project_room = $project_list->getFirst();
         while ($project_room) {
            if ($project_room->getItemID() != $current_context_id) {
               $room_id_array[] = $project_room->getItemID();
            }
            unset($project_room);
            $project_room = $project_list->getNext();
         }
         unset($project_list);
      }
      unset($project_manager);

      $private_room_manager = $this->_environment->getPrivateRoomManager();
      $own_room = $private_room_manager->getRelatedOwnRoomForUser($this,$this->_environment->getCurrentPortalID());
      if ( isset($own_room) and !empty($own_room) ) {
         $room_id = $own_room->getItemID();
         if ( !empty($room_id) ) {
            $room_id_array[] = $room_id;
         }
         unset($own_room);
      }
      unset($private_room_manager);

      if ( !empty($room_id_array) ) {
         $user_manager = $this->_environment->getUserManager();
         $user_manager->resetLimits();
         $user_manager->setContextArrayLimit($room_id_array);
         $user_manager->setUserIDLimit($this->getUserID());
         $user_manager->setAuthSourceLimit($this->getAuthSource());
         $user_manager->select();
         $user_list = $user_manager->get();
         unset($user_manager);
      } else {
         include_once('classes/cs_list.php');
         $user_list = new cs_list();
      }

      return $user_list;
   }

   public function getRelatedUserItemInContext ( $value ) {
      $retour = NULL;
      $user_manager = $this->_environment->getUserManager();
      $user_manager->resetLimits();
      $user_manager->setContextLimit($value);
      $user_manager->setUserIDLimit($this->getUserID());
      $user_manager->setAuthSourceLimit($this->getAuthSource());
      $user_manager->select();
      $user_list = $user_manager->get();
      if ( isset($user_list)
           and $user_list->isNotEmpty()
           and $user_list->getCount() == 1
         ) {
         $retour = $user_list->getFirst();
      }
      unset($user_manager);
      unset($user_list);
      return $retour;
   }

   /**
    * @return object user_item User-Item from the community room
    */
   function getRelatedCommSyUserItem () {
      if ( mb_strtoupper($this->getUserID(), 'UTF-8') == 'ROOT' ) {
         $retour = $this;
      } else {
         $item_manager = $this->_environment->getItemManager();
         $item = $item_manager->getItem($this->getContextID());

         if ( $item->getItemType() == CS_COMMUNITY_TYPE
              or $item->getItemType() == CS_PROJECT_TYPE
              or $item->getItemType() == CS_GROUPROOM_TYPE
            ) {
            $room_manager = $this->_environment->getManager(CS_ROOM_TYPE);
            $room = $room_manager->getItem($this->getContextID());
            $portal_id = $room->getContextID();
         } elseif ( $item->getItemType() == CS_PRIVATEROOM_TYPE ) {
            $room_manager = $this->_environment->getManager(CS_PRIVATEROOM_TYPE);
            $room = $room_manager->getItem($this->getContextID());
            $portal_id = $room->getContextID();
         } elseif ( $item->getItemType() == CS_PORTAL_TYPE ) {
            $portal_id = $this->getContextID();
         }

         $retour = NULL;
         $user_manager = $this->_environment->getUserManager();
         $user_manager->resetLimits();
         if ( !isset($portal_id) ) {
            $portal_id = $this->getContextID();
         }
         $user_manager->setContextLimit($portal_id);
         $user_manager->setAuthSourceLimit($this->getAuthSource());
         $user_manager->setUserIDLimit($this->getUserID());
         $user_manager->select();
         $user_list = $user_manager->get();
         if ( $user_list->getCount() == 1 ) {
            $retour = $user_list->getFirst();
         }
      }
      return $retour;
   }


  function getRelatedPrivateRoomUserItem() {
     $retour = NULL;
     $private_room_manager = $this->_environment->getPrivateRoomManager();
     $own_room = $private_room_manager->getRelatedOwnRoomForUser($this,$this->_environment->getCurrentPortalID());
     unset($private_room_manager);
     if ( isset($own_room) ) {
        $own_cid = $own_room->getItemID();
        $user_manager = $this->_environment->getUserManager();
        $user_manager->resetLimits();
        $user_manager->setContextLimit($own_cid);
        $user_manager->setUserIDLimit($this->getUserID());
        $user_manager->setAuthSourceLimit($this->getAuthSource());
        $user_manager->select();
        $user_list = $user_manager->get();
        unset($user_manager);
        if ($user_list->getCount() == 1) {
           $retour = $user_list->getFirst();
        }
        unset($user_list);
     }
     unset($own_room);
     return $retour;
  }

   function getModifiedItemIDArray($type, $creator_id) {
      $id_array = array();
      $link_manager = $this->_environment->getLinkItemManager();
      $context_item = $this->_environment->getCurrentContextItem();
      $link_ids = $link_manager->getModiefiedItemIDArray($type, $creator_id);
      foreach ($link_ids as $id) {
         $id_array[] = $id;
      }
      return $id_array;
   }

   function cloneData () {
      $new_room_user = clone $this;
      $new_room_user->unsetContextID();
      $new_room_user->unsetItemID();
      $new_room_user->unsetCreatorID();
      $new_room_user->unsetCreatorDate();
      $new_room_user->unsetAGBAcceptanceDate();
      return $new_room_user;
   }

   function unsetContextID () {
      $this->_unsetValue('context_id');
   }

   function unsetItemID () {
      $this->_unsetValue('item_id');
   }

   function unsetCreatorID () {
      $this->_unsetValue('creator_id');
   }

   function unsetCreatorDate () {
      $this->_unsetValue('creator_date');
   }

   function setCreatorID2ItemID () {
      $user_manager = $this->_environment->getUserManager();
      $user_manager->setCreatorID2ItemID($this);
   }

   function isDeletable () {
      $value = false;
      $item_manager = $this->_environment->getItemManager();
      $annotation_manager = $this->_environment->getAnnotationManager();
      $link_manager = $this->_environment->getLinkItemManager();
      $result1 = $item_manager->getCountExistingItemsOfUser($this->getItemID());
      $result2 = $annotation_manager->getCountExistingAnnotationsOfUser($this->getItemID());
      $result3 = $link_manager->getCountExistingLinkItemsOfUser($this->getItemID());
      if ($result1==0 and $result2==0 and $result3==0){
         $value = true;
      }
      return $value;
   }

   function deleteAllEntriesOfUser(){
      $announcement_manager = $this->_environment->getAnnouncementManager();
      $announcement_manager->deleteAnnouncementsofUser($this->getItemID());
      $dates_manager = $this->_environment->getDatesManager();
      $dates_manager->deleteDatesOfUser($this->getItemID());
      $discussion_manager = $this->_environment->getDiscussionManager();
      $discussion_manager->deleteDiscussionsOfUser($this->getItemID());
      $discarticle_manager = $this->_environment->getDiscussionarticleManager();
      $discarticle_manager->deleteDiscarticlesOfUser($this->getItemID());
      $material_manager = $this->_environment->getMaterialManager();
      $material_manager->deleteMaterialsOfUser($this->getItemID());
      $section_manager = $this->_environment->getSectionManager();
      $section_manager->deleteSectionsOfUser($this->getItemID());
      $annotation_manager = $this->_environment->getAnnotationManager();
      $annotation_manager->deleteAnnotationsOfUser($this->getItemID());
      $label_manager = $this->_environment->getLabelManager();
      $label_manager->deleteLabelsOfUser($this->getItemID());
      $tag_manager = $this->_environment->getTagManager();
      $tag_manager->deleteTagsOfUser($this->getItemID());
      $todo_manager = $this->_environment->getToDoManager();
      $todo_manager->deleteTodosOfUser($this->getItemID());
   }

   function setAGBAcceptance () {
      include_once('functions/date_functions.php');
      $this->_setAGBAcceptanceDate(getCurrentDateTimeInMySQL());
   }

   function unsetAGBAcceptanceDate () {
      $this->_setAGBAcceptanceDate('');
   }

   function _setAGBAcceptanceDate ($value) {
      $this->_addExtra('AGB_ACCEPTANCE_DATE',$value);
   }

   function getAGBAcceptanceDate () {
      $retour = '';
      if ($this->_issetExtra('AGB_ACCEPTANCE_DATE')) {
         $retour = $this->_getExtra('AGB_ACCEPTANCE_DATE');
      }
      return $retour;
   }

   public function isAutoSaveOn () {
      $retour = false;
      if ( $this->_environment->inPrivateRoom() ) {
         $value = $this->getAutoSaveStatus();
      } else {
         $priv_user = $this->getRelatedPrivateRoomUserItem();
         if ( isset($priv_user) and !empty($priv_user) ) {
            $value = $priv_user->getAutoSaveStatus();
            unset($priv_user);
         } else {
            $value = -1;
         }
      }
      if ( !empty($value) and $value == 1 ) {
         $retour = true;
      }
      return $retour;
   }

   public function getAutoSaveStatus () {
      $retour = '';
      if ($this->_issetExtra('CONFIG_AUTOSAVE_STATUS')) {
         $retour = $this->_getExtra('CONFIG_AUTOSAVE_STATUS');
      }
      return $retour;
   }

   protected function _setAutoSaveStatus ($value) {
      $this->_addExtra('CONFIG_AUTOSAVE_STATUS',$value);
   }

   public function turnAutoSaveOn () {
      $this->_setAutoSaveStatus(1);
   }

   public function turnAutoSaveOff () {
      $this->_setAutoSaveStatus(-1);
   }

   public function setICQ($number)
   {
         if($this->_issetExtra('ICQ'))
         {
            $this->_setExtra('ICQ', $number);
         } else {
            $this->_addExtra('ICQ', $number);
         }
   }

   public function getICQ()
   {
       $result = '';
       if($this->_issetExtra('ICQ'))
       {
            $result = $this->_getExtra('ICQ');
       }
       return $result;
   }

   public function setMSN($number)
   {
         if($this->_issetExtra('MSN'))
         {
            $this->_setExtra('MSN', $number);
         } else {
            $this->_addExtra('MSN', $number);
         }
   }

   public function getMSN()
   {
       $result = '';
       if($this->_issetExtra('MSN'))
       {
            $result = $this->_getExtra('MSN');
       }
       return $result;
   }

   public function setSkype($number)
   {
         if($this->_issetExtra('SKYPE'))
         {
            $this->_setExtra('SKYPE', $number);
         } else {
            $this->_addExtra('SKYPE', $number);
         }
   }

   public function getSkype()
   {
       $result = '';
       if($this->_issetExtra('SKYPE'))
       {
            $result = $this->_getExtra('SKYPE');
       }
       return $result;
   }

   public function setJabber($number)
   {
         if($this->_issetExtra('JABBER'))
         {
            $this->_setExtra('JABBER', $number);
         } else {
            $this->_addExtra('JABBER', $number);
         }
   }

   public function getJabber()
   {
       $result = '';
       if($this->_issetExtra('JABBER'))
       {
            $result = $this->_getExtra('JABBER');
       }
      return $result;
   }

   public function setYahoo($number)
   {
        if($this->_issetExtra('YAHOO'))
        {
            $this->_setExtra('YAHOO', $number);
        } else {
            $this->_addExtra('YAHOO', $number);
        }
   }

   public function getYahoo()
   {
      $result = '';
      if($this->_issetExtra('YAHOO'))
      {
            $result = $this->_getExtra('YAHOO');
      }
    return $result;
   }

   public function isInGroup ( $group_item ) {
      $retour = false;
      if ( isset($group_item)
           and $group_item->getItemID() > 0
         ) {
         $group_list = $this->getGroupList();
         $retour = $group_list->inList($group_item);
         unset($group_list);
         unset($group_item);
      }
      return $retour;
   }

   public function isActiveDuringLast99Days () {
      include_once('functions/date_functions.php');
      return $this->getLastLogin() >  getCurrentDateTimeMinusDaysInMySQL(99);
   }

   public function updateWikiProfile(){
        $wiki_manager = $this->_environment->getWikiManager();
        $wiki_manager->updateWikiProfileFile($this);
   }

   public function updateWikiNotification(){
        $wiki_manager = $this->_environment->getWikiManager();
        $wiki_manager->updateNotification();
   }

   // Entscheidung 30.09.2008 - Eintraege bleiben unveraendert im Forum
   //public function updateWikiRemoveUser(){
   //     $wiki_manager = $this->_environment->getWikiManager();
   //     $wiki_manager->updateWikiRemoveUser($this);
   //}

   public function isRoomMember () {
      $retour = false;

      // project rooms
      $list = $this->getRelatedProjectList();
      if ( isset($list) and $list->isNotEmpty() ) {
         $count = $list->getCount();
         if ( $count > 0 ) {
            $retour = true;
         }
      }
      unset($list);

      // community rooms
      if ( !$retour ) {
         $list = $this->getRelatedCommunityList();
         if ( isset($list) and $list->isNotEmpty() ) {
            $count = $list->getCount();
            if ( $count > 0 ) {
               $retour = true;
            }
         }
         unset($list);
      }

      // group room
      if ( !$retour ) {
         $list = $this->getRelatedGroupList();
         if ( isset($list) and $list->isNotEmpty() ) {
            $count = $list->getCount();
            if ( $count > 0 ) {
               $retour = true;
            }
         }
         unset($list);
      }

      return $retour;
   }

   function getDataAsXML(){
        return $this->_getDataAsXML();
   }
}
?>