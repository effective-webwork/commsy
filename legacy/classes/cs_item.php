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

use App\Entity\Portal;
use App\Proxy\PortalProxy;
use App\Repository\MaterialsRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;

include_once('functions/misc_functions.php');
include_once('functions/text_functions.php');

class cs_item {

   /**
   * string - containing the type of the item
   */
   var $_type = 'item';

   /**
   * array - containing the data of this item, including lists of linked items

   */
   var $_data = array();

   /**
     * @var \cs_environment $_environment
    */
   var $_environment = null;
   /**

   * array - array of boolean values. TRUE if key is changed

   */
   var $_changed = array();

   var $_context_item;

  /** error array for detecting multiple errors.
   *
   */

   var $_error_array = array();

  /**
   * boolean - file list is changed, save new list
   */
   var $_filelist_changed = false;
   var $_filelist_changed_empty = false;
   var $_cache_on = true;

  /**
   * boolean - if true the modification_date will be updated - else not
   */
   var $_change_modification_on_save = true;

   public $_link_modifier = true;
   var $_db_load_extras = true;

   private $_external_viewer_user_array = null;

   /**
    * used to flag, if item is in an archived room or not
    * @var boolean
    */
   private $_is_archived = false;

   /** constructor
   * the only available constructor, initial values for internal variables
   */
   function __construct($environment) {
      $this->_environment = $environment;
      $this->_changed['general']=true;
      $this->_type = 'item';
   }

    public function getContextItem()
    {
        if ($this->_context_item == null) {
            $contextId = $this->getContextID();
            if (!empty($contextId)) {
                $item_manager = $this->_environment->getItemManager();
                $item = $item_manager->getItem($this->getContextID());

                if (isset($item) && is_object($item)) {
                    if ($item->isArchived() && !$this->_environment->isArchiveMode()) {
                        $this->_environment->toggleArchiveMode();
                        $manager = $this->_environment->getManager($item->getItemType());
                        $this->_environment->toggleArchiveMode();
                    } else {
                        $manager = $this->_environment->getManager($item->getItemType());
                    }

                    $this->_context_item = $manager->getItem($this->getContextId());
                    return $this->_context_item;
                }

                $item_manager = $this->_environment->getItemManager(true);
                $item = $item_manager->getItem($this->getContextID());

                if (isset($item) && is_object($item)) {
                    $manager = $this->_environment->getManager($item->getItemType());
                    $this->_context_item = $manager->getItem($this->getContextId());
                    return $this->_context_item;
                }

                global $symfonyContainer;
                /** @var EntityManagerInterface $entityManager */
                $entityManager = $symfonyContainer->get('doctrine.orm.entity_manager');
                $portal = $entityManager->getRepository(Portal::class)->find($contextId);

                if ($portal) {
                    $this->_context_item = new PortalProxy($portal, $this->_environment);
                    return $this->_context_item;
                }
            }
        }

        return $this->_context_item;
    }

   public function setContextItem ( $context_item ) {
      if ( is_object($context_item) ) {
         $this->_context_item = $context_item;
      }
   }

   public function setCacheOff () {
      $this->_cache_on = false;
   }

   public function setSaveWithoutLinkModifier () {
      $this->_link_modifier = false;
   }

   /** Sets the data of the item.
    *
    * @param $data_array Is the prepared array from "_buildItem($db_array)"
    * @return boolean TRUE if data is valid FALSE otherwise
    */
   function _setItemData($data_array) {
      $this->_data = $data_array;
      return $this->isValid();
   }

   /** Gets the data of the item.
    *
    * @param $data_array Is the prepared array from "_saveItem($db_array)"
    * @return boolean TRUE if data is valid FALSE otherwise
    */

   function _getItemData() {
      if ( $this->isValid() ){
         return $this->_data;
      } else {
      //TBD
        echo('Error in cs_item_new._getItemData(). Item not valid.');
      }
   }


   ###############
   # PUBLIC METHODS
   ############



   /** asks if item is editable by everybody ('1') or just creator ('0')
    *
    * @param value
    *
    * @author CommSy Development Group
    */
   function isPrivateEditing() {
      if ($this->_getValue('public') == 1) {
         return false;
      }
      return true;
   }

   /** sets if item is editable by everybody ('1') or just creator ('0')
    *
    * @param value
    */
   function setPrivateEditing ($value) {
      $this->_setValue('public', $value);
   }


    /** get buzzwords of a material
    * this method returns a list of buzzwords which are linked to the material
    *
    * @return object cs_list a list of buzzwords (cs_label_item)
    *
    * @author CommSy Development Group
    */
   function getBuzzwordArray () {
      $buzzword_array = $this->_getValue('buzzword_array');
      if(empty($buzzword_array)) {
         $label_manager = $this->_environment->getLabelManager();
         $label_manager->setTypeLimit('buzzword');
         $buzzword_list = $this->_getLinkedItemsForCurrentVersion($label_manager, 'buzzword_for');
         $buzzword = $buzzword_list->getFirst();
         while($buzzword) {
            $name = $buzzword->getName();
             if (!empty($name)) {
                 if (!is_array($this->_data['buzzword_array'])) {
                     $this->_data['buzzword_array'] = [];
                 }
                 $this->_data['buzzword_array'][] = $name;
            }
            $buzzword = $buzzword_list->getNext();
         }
      }
      return $this->_getValue('buzzword_array');
   }

    /** get buzzwords of a material
    * this method returns a list of buzzwords which are linked to the material
    *
    * @return object cs_list a list of buzzwords (cs_label_item)
    */
   function getBuzzwordList () {
      $label_manager = $this->_environment->getLabelManager();
      $label_manager->setTypeLimit('buzzword');
      return $this->_getLinkedItemsForCurrentVersion($label_manager, 'buzzword_for');
   }

  /** set buzzwords of a material
    * this method sets a list of buzzwords which are linked to the material
    *
    * @param string value title of the material
    *
    * @author CommSy Development Group
    */
   function setBuzzwordArray($value) {
      $this->_data['buzzword_array'] = $value;
   }

   function _saveBuzzwords() {
      if ( !isset($this->_setBuzzwordsByIDs) ) {
         $buzzword_array = $this->getBuzzwordArray();
         if (!empty($buzzword_array)) {
            array_walk($buzzword_array,create_function('$buzzword','return trim($buzzword);'));
            $label_manager = $this->_environment->getLabelManager();
            $label_manager->resetLimits();
            $label_manager->setTypeLimit('buzzword');
            $label_manager->setContextLimit($this->getContextID());
            $buzzword_exists_id_array = array();
            $buzzword_not_exists_name_array = array();
            foreach ($buzzword_array as $buzzword) {
               $buzzword_item = $label_manager->getItemByName($buzzword);
               if (!empty($buzzword_item)) {
                  $buzzword_exists_id_array[] = array('iid' => $buzzword_item->getItemID());
               } else {
                  $buzzword_not_exists_name_array[] = $buzzword;
               }
            }
            // make buzzword items to get ids
            if (count($buzzword_not_exists_name_array) > 0) {
               foreach($buzzword_not_exists_name_array as $new_buzzword) {
                  $item = $label_manager->getNewItem();
                  $item->setContextID($this->getContextID());
                  $item->setName($new_buzzword);
                  $item->setLabelType('buzzword');
                  $item->save();
                  $buzzword_exists_id_array[] = array('iid' => $item->getItemID());
               }
            }
            // set id array so the links to the items get saved
            $this->_setValue('buzzword_for', $buzzword_exists_id_array, FALSE);
         } else {
            $this->_setValue('buzzword_for', array(), FALSE); // to unset buzzword links
         }
      }
   }

   function setBuzzwordListByID($array){
      $this->_setValue('buzzword_for', $array, FALSE);
      $this->_setBuzzwordsByIDs = true;
   }


   /** get list of linked items
   * this method returns a list of items which are linked to this item
   *
   * @return object cs_list a list of cs_items
   * @access private
   * @author CommSy Development Group
   */
   function _getLinkedItemsForCurrentVersion ($item_manager, $link_type) {
      if (!isset($this->_data[$link_type]) or !is_object($this->_data[$link_type])) {
         $link_manager = $this->_environment->getLinkManager();
         // preliminary version: there should be something like 'getIDArray() in the link_manager'
         $id_array = array();
         $link_array = $link_manager->getLinks($link_type, $this, $this->getVersionID(), 'eq');
         $id_array = array();
            foreach($link_array as $link) {
               if ($link['to_item_id'] == $this->getItemID()) {
                  $id_array[] = $link['from_item_id'];
               } elseif ($link['from_item_id'] == $this->getItemID()) {
                  $id_array[] = $link['to_item_id'];
               }
            }
            $this->_data[$link_type] = $item_manager->getItemList($id_array, $this->getVersionID());
         }
      return $this->_data[$link_type];
   }


   /** get tags of a material
    * this method returns a list of tags which are linked to the material
    *
    * @return object cs_list a list of tags (cs_label_item)
    */
   function getTagArray () {
      $tag_array = $this->_getValue('tag_array');
      if ( empty($tag_array) ) {
         $tag_list = $this->getTagList();
         $tag = $tag_list->getFirst();
         while ($tag) {
            $linked_item = $tag->getLinkedItem($this);  // Get the linked item
            if ( isset($linked_item) ) {
               $title = $linked_item->getTitle();
               if ( !empty($title) ) {
                  $this->_data['tag_array'][] = $title;
               }
               unset($linked_item);
            }
            $tag = $tag_list->getNext();
         }
         unset($tag_list);
         unset($tag);
      }
      return $this->_getValue('tag_array');
   }

   function getTagsArray () {
      $return = array();
      $tag_list = $this->getTagList();
      $tag = $tag_list->getFirst();
      while ($tag) {
           $title = $tag->getTitle();
           if ( !empty($title) ) {
               $tmp_array = array();
               $tmp_array['id'] = $tag->getItemID();
               $tmp_array['title'] = $tag->getTitle();
               
               $return[] = $tmp_array;
           }
          $tag = $tag_list->getNext();
      }
      unset($tag_list);
      unset($tag);
      return $return;
   }


   /** get tags of a material
    * this method returns a list of tags which are linked to the material
    *
    * @return object cs_list a list of tags (cs_label_item)
    */
   function getTagList () {
      $list = new cs_list();
      $tag_list = $this->getLinkItemList(CS_TAG_TYPE);
      $tag = $tag_list->getFirst();
      while ($tag) {
         $linked_item = $tag->getLinkedItem($this);  // Get the linked item
         if ( isset($linked_item) ) {
            $list->add($linked_item);
            unset($linked_item);
         }
         $tag = $tag_list->getNext();
      }
      unset($tag_list);
      unset($tag);
      return $list;
   }

  /** set materials of a announcement item by item id and version id
   * this method sets a list of material item_ids and version_ids which are linked to the announcement
   *
   * @param array of material ids, index of id must be 'iid', index of version must be 'vid'
   * Example:
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   */
   function setTagListByID ($value) {
      $this->setLinkedItemsByID (CS_TAG_TYPE, $value);
   }

   /** set materials of a announcement
    * this method sets a list of materials which are linked to the news
    *
    * @param string value title of the news
    */
   function setTagList ($value) {
      $this->_setObject(CS_TAG_TYPE, $value, FALSE);
   }




   /** Checks the data of the item.
    *
    * @return boolean TRUE if data is valid FALSE otherwise
    * @author CommSy Development Group
    */
   function isValid() {
      $creator = $this->getCreatorID();
      #$creation_date = $this->getCreationDate();
      return !empty($creator); #and !empty($creation_date);
   }

   /** is the type of the item = $type ?
    * this method returns a boolean expressing if type of the item is $type or not
    *
    * @param string type string to compare with type of the item (_type)
    *
    * @return boolean   true - type of this item is $type
    *                   false - type of this item is not $type
    *
    * @author CommSy Development Group
    */
   function isA ($type) {
      return $this->_type == $type;
   }

   /** get item id
   * this method returns the id of the item
   *
   * @return integer id of the item
   *
   * @author CommSy Development Group
   */
   public function getItemID(): int
   {
      return (int) $this->_getValue('item_id');
   }

   /** set item id
    * this method sets the id of the item
    *
    * @param integer id of the item
    *
    * @author CommSy Development Group
    */
   function setItemID ($value) {
      $this->_setValue('item_id', (int)$value);
   }

   /** get version id
   * this method returns 0
   * it must be overwritten in case version ids are needed
   *
   * @return integer version id of the item
   *
   * @author CommSy Development Group
   */
   function getVersionID() {
      return 0;
   }


   /** set version id
    * this method sets the version id of the item
    *
    * @param integer version id of the item
    *
    * @author CommSy Development Group
    */
   function setVersionID ($value) {
      $this->_setValue('version_id', (integer)$value);
   }

    /** get context id
     * this function returns the id of the current context:
     */
    public function getContextID(): int
    {
        $context_id = $this->_getValue('context_id');
        if ($context_id === '') {
            $context_id = $this->_environment->getCurrentContextID();
        }
        return (int)$context_id;
    }

   /** set context id
   * this method sets the context id of the item
   *
   * @param integer value context id of the item
   */
   function setContextID ($value) {
      return $this->_setValue('context_id', $value);
   }

    /** get creator
     * this method returns the modificator of the item
     * By default the creator is returned.
     *
     * @return cs_user_item creator of the item
     */
    public function getModificatorItem()
    {
        $retour = $this->_getUserItem('modifier');
        if (!isset($retour)) {
            $retour = $this->getCreatorItem();
        } else {
            $iid = $retour->getItemID();
            if (empty($iid)) {
                $retour = $this->getCreatorItem();
            }
        }
        return $retour;
    }

   /** get creator-id
   * this method returns the modificator of the item
   * By default the creator is returned.
   *
   * @return cs_user_item creator of the item
   *
   * @author CommSy Development Group
   */
   function getModificatorID () {
      $modifier = $this->_getValue('modifier_id');
      if ( !empty($modifier)){
         return $this->_getValue('modifier_id');
      }else{
         return $this->_getValue('creator_id');
      }
   }


   /** get creation date
    * this method returns the creation date of the item
    *
    * @return string creation date of the item in datetime-FORMAT
    *
    * @author CommSy Development Group
    */
   function getCreationDate () {
      return $this->_getValue('creation_date');
   }

   /** set creation date
    * this method sets the creation date of the item
    *
    * @param string creation date in datetime-FORMAT of the item
    *
    * @author CommSy Development Group
    */
   function setCreationDate ($value) {
      $this->_setValue('creation_date', (string)$value);
   }

   /** get modification date
    * this method returns the modification date of the item
    *
    * @return string modification date of the item in datetime-FORMAT
    *
    * @author CommSy Development Group
    */
   function getModificationDate () {
      $date = $this->_getValue('modification_date');
      if (is_null($date) or $date=='0000-00-00 00:00:00') {
         $date = $this->_getValue('creation_date');
      }
      return $date;
   }

    /** get modification date
     * this method returns the modification date of the item
     *
     * @return string modification date of the item in datetime-FORMAT
     *
     * @author CommSy Development Group
     */
    function getActivationDate () {
        $date = $this->_getValue('activation_date');
        if (is_null($date) or $date=='0000-00-00 00:00:00') {
            $date = $this->_getValue('creation_date');
        }
        return $date;
    }


    /** set modification date
    * this method sets the modification date of the item
    *
    * @param string modification date in datetime-FORMAT of the item
    *
    * @author CommSy Development Group
    */
   function setModificationDate ($value) {
      $this->_setValue('modification_date', (string)$value);
   }

    /** set modification date
     * this method sets the modification date of the item
     *
     * @param string modification date in datetime-FORMAT of the item
     *
     * @author CommSy Development Group
     */
    function setActivationDate ($value) {
        $this->_setValue('activation_date', (string)$value);
    }

   /** get deletion date
    * this method returns the deletion date of the item
    *
    * @return string deletion date of the item in datetime-FORMAT
    *
    * @author CommSy Development Group
    */
   function getDeletionDate () {
      return $this->_getValue('deletion_date');

   }

   function isNotActivated(){
      $date = $this->getActivationDate();
      if ( $date > getCurrentDateTimeInMySQL() ) {
        return true;
      }else{
         return false;
      }
   }

   function getActivatingDate(){
      $retour = '';
      if ($this->isNotActivated()){
         $retour = $this->getActivationDate();
      }
      return $retour;
   }

   /** set deletion date
    * this method sets the deletion date of the item
    *
    * @param string deletion date in datetime-FORMAT of the item
    *
    * @author CommSy Development Group
    */
   function setDeletionDate ($value) {
      $this->_setValue('deletion_date', (string)$value);
   }

   /** get type, should be like getItemType (TBD)
    * this method returns the type of the item
    *
    * @return string type of the item
    */
   function getType () {
      return $this->_type;
   }


   function getTitle () {              //TBD: In Zukunft sollten alle Titel auch Titel sein!!!
     $title = $this->_getValue('title');
     if (!empty($title)){
        return($title);
     }
     else{
        return($this->_getValue('name'));
     }
   }

   /** set type
    * this method sets the type of the item
    *
    * @param string type of the item
    *
    * @author CommSy Development Group
    */
   function setType ($value) {
      $this->_type = (string)$value;
   }

   /** get item type form database tabel item
    * this method returns the type of the item form the database table item
    *
    * @return string type of the item out of the database table item
    */
   function getItemType () {
      $type = $this->_getValue('type');
      if (empty($type)){
          $type = $this->getType();
      }
      return $type;
   }

   /** add an extra to the item -- OLD, use setExtra
    * this method adds a value (string, integer or array) to the extra information
    *
    * @param string key   the key (name) of the value
    * @param *      value the value: string, integer, array
    */
   function _addExtra($key, $value) {
      $this->_setExtra ($key,$value);
   }

   /** set an extra in the item
    * this method sets a value (string, integer or array) to the extra information
    *
    * @param string key   the key (name) of the value
    * @param *      value the value: string, integer, array
    */
   function _setExtra ($key, $value) {
      $extras = $this->_getValue('extras');
      $extras[$key] = $value;
      $this->_setValue('extras', $extras);
   }

   /** unset a value
    * this method unsets a value of the extra information
    *
    * @param string key   the key (name) of the value
    */
   function _unsetExtra($key) {
      if ($this->_issetExtra($key)) {
         $extras = $this->_getValue('extras');
         unset($extras[$key]);
         $this->_setValue('extras', $extras);
      }
   }

   /** exists the extra information with the name $key ?
    * this method returns a boolean, if the value exists or not
    *
    * @param string key   the key (name) of the value
    *
    * @return boolean true, if value exists
    *                 false, if not
    */
   function _issetExtra($key) {
      $result = false;
      $extras = $this->_getValue('extras');
      if (isset($extras) and is_array($extras) and array_key_exists($key,$extras) and isset($extras[$key])) {
         $result = true;
      }
      return $result;
   }

   /** get an extra value
    * this method returns a value of the extra information
    *
    * @param string key the key (name) of the value
    *
    * @return * value of the extra information
    */
   function _getExtra($key) {
      $extras = $this->_getValue('extras');
      if ($this->_issetExtra($key)) {
         return $extras[$key];
      }
   }

   /** get all extra keys
    * this method returns an array with all keys in
    *
    * @return array returns an array with all keys in
    */
   function getExtraKeys () {
      $extras = $this->_getValue('extras');
      return array_keys($extras);
   }

   /** get extra information of an item
    * this method returns the extra information of an item
    *
    * @return string extra information of an item
    *
    * @author CommSy Development Group
    */

   function getExtraInformation () {
      return $this->_getValue('extras');
   }

   /** set extra information of an item
    * this method sets the extra information of an item
    *
    * @param string value extra information of an item
    *
    * @author CommSy Development Group
    */
   function setExtraInformation ($value) {
      $this->_setValue('extras', (array)$value);
   }

   function resetExtraInformation () {
      $this->_setValue('extras', array());
   }

   function isDeleted () {
      $is_deleted = false;
      $deletion_date = $this->getDeletionDate();
      if (!empty($deletion_date) and $deletion_date != '0000-00-00 00:00:00') {
         $is_deleted = true;
      }
      return $is_deleted;
   }

   function getDeleterID() {
      return $this->_getValue('deleter_id');
   }

   function setDeleterID($value) {
      return $this->_setValue('deleter_id',$value);
   }

   public function getCreatorID(): int
   {
      return (int) $this->_getValue('creator_id');
   }

   function setCreatorID($value) {
      return $this->_setValue('creator_id',$value);
   }

   function setModifierID($value) {
      return $this->_setValue('modifier_id',$value);
   }

    /** set creator of a material
     * this method sets the creator of the material
     *
     * @param cs_user_item|null $user
     */
    public function setCreatorItem(?cs_user_item $user)
    {
        $this->_setUserItem($user, 'creator');
    }

    /** get creator of a material
     * this method returns the creator of the material
     *
     * @return cs_user_item creator of a material
     *
     * @author CommSy Development Group
     */
    function getCreatorItem(): ?cs_user_item
    {
        return $this->_getUserItem('creator');
    }

    public function getCreator(): ?cs_user_item
    {
        return $this->getCreatorItem();
    }

   /** set deleter of a material
    * this method sets the deleter of the material
    *
    * @param user_object deleter of a material
    *
    * @author CommSy Development Group
    */
   function setDeleterItem ($user) {
       $this->_setUserItem($user,'deleter');
   }

   function setDeleter($user) {
      $this->setDeleterItem($user);
   }

  /** set modificator
   * this method set the modificator of the item
   *
   * @param cs_user_item modificator of the item
   *
   * @author CommSy Development Group
   */
   function setModificatorItem ($item) {
      $this->_setUserItem($item,'modifier');
   }

    /** get deleter of a material
    * this method returns the deleter of the material
    *
    * @return user_object deleter of a material
    *
    * @author CommSy Development Group
    */
   function getDeleterItem () {
      return $this->_getUserItem('deleter');
   }

   function getDeleter() {
      return $this->getDeleterItem();
   }

    /**
     * returns a list of annotations linked to this item
     *
     * @return cs_list|null
     */
    public function getAnnotationList():? cs_list
    {
        $annotation_manager = $this->_environment->getAnnotationManager();
        $annotation_manager->resetLimits();
        $annotation_manager->setContextLimit(null);
        $annotation_manager->setLinkedItemID($this->getItemID());
        $annotation_manager->select();

        return $annotation_manager->get();
    }

//********************************************************
//TBD: Nach der vollständigen Migration der Links kann diese Methode entfernt werden
//********************************************************
   /** get list of linked items
   * this method returns a list of items which are linked to the news item
   *
   * @return object cs_list a list of cs_items
   * @access private
   * @author CommSy Development Group
   */
   function _getLinkedItems ($item_manager, $link_type, $order='') {
      if (!isset($this->_data[$link_type]) or !is_object($this->_data[$link_type])) {

         global $environment;
         $link_manager = $environment->getLinkManager();
         // preliminary version: there should be something like 'getIDArray() in the link_manager'

         $id_array = array();
         $link_array = $link_manager->getLinks($link_type, $this, $this->getVersionID(), 'eq');
         $id_array = array();
         foreach($link_array as $link) {
            if ($link['to_item_id'] == $this->getItemID()) {
               $id_array[] = $link['from_item_id'];
            } elseif ($link['from_item_id'] == $this->getItemID()) {
               $id_array[] = $link['to_item_id'];
            }
         }
         $this->_data[$link_type] = $item_manager->getItemList($id_array);
      }
      return $this->_data[$link_type];
   }


   /** get data value
   * this method returns the value for the specified key or an empty string if it is not set.
   *
   * @param string key
   * @access private
   */
   function _getValue($key) {
      if ( !isset($this->_data[$key]) ) {
         if ( $key == 'extras' ) {
            if ( $this->_db_load_extras ) {
               $this->_data[$key] = array();
            } else {
               $this->_loadExtras();
            }
         } else {
            $this->_data[$key] = '';
         }
      }
      return $this->_data[$key];
   }

   function unsetLoadExtras () {
      $this->_db_load_extras = false;
   }

   function setLoadExtras () {
      $this->_db_load_extras = true;
   }

   function _loadExtras () {
      $this->setLoadExtras();
      if ( is_object($this)
           and method_exists($this,'getItemType')
         ) {
         $manager = $this->_environment->getManager($this->getItemType());
         if ( is_object($manager)
              and method_exists($manager,'getExtras')
            ) {
            $this->_data['extras'] = $manager->getExtras($this->getItemID());
            if ( empty($this->_data['extras'])
                 and method_exists($this,'isClosed')
                 and $this->isClosed()
                 and !$this->_environment->isArchiveMode()
               ) {
               $this->_environment->activateArchiveMode();
               $manager = $this->_environment->getManager($this->getItemType());
               $this->_environment->deactivateArchiveMode();
               if ( is_object($manager)
                    and method_exists($manager,'getExtras')
                  ) {
                  $this->_data['extras'] = $manager->getExtras($this->getItemID());
               }
               unset($manager);
            }
         }
         unset($manager);
      }
   }

    /** get data object
     * this method returns the object for the specified key or NULL if it is not set.
     *
     * @param string key
     * @access private
     */
    protected function _getObject($key)
    {
        if (!isset($this->_data[$key])) {
            $this->_data[$key] = null;
        }
        return $this->_data[$key];
    }

    private function _getUserItem($role): ?cs_user_item
    {
        $user = $this->_getObject($role);
        if ($user === null) {
            $user_manager = $this->_environment->getUserManager();

            $user_id = $this->_getValue($role . '_id');
            if ($user_id !== null) {
                $user = $user_manager->getItem($user_id);

                if ($user === null) {
                    $this->_environment->toggleArchiveMode();
                    $user_manager = $this->_environment->getUserManager();
                    $user = $user_manager->getItem($user_id);
                    $this->_environment->toggleArchiveMode();
                }

                $this->_data[$role] = $user;
            }
        }

        return $user;
    }

    private function _setUserItem($user, $role)
    {
        if (isset($user) and is_object($user)) {
            $this->_data[$role] = $user;
            $item_id = $user->getItemID();
            $this->_setValue($role . '_id', $item_id);
        }
    }

   /** set data value
   * this method sets values for the specified key and marks it as changed
   *
   * @param mixed value to be changed
   * @access private
   * @author CommSy Development Group
   */
   /*function _setValue($key, $value, $internal=TRUE) {
      $this->_data[$key] = $value;
      if ($internal) {
         $this->_changed['general'] = TRUE;
      } else {
         $this->_changed[$key] = TRUE;
      }
   }*/

   function _setValue($key, $value, $internal=TRUE) {
      /*
      if(is_string($value)){
         if(strpos($value,'<!-- KFC TEXT -->')!==false){
            $value = correctFCKTags($value);
         }
      }
      */
      $this->_data[$key] = $value;
      if ($internal) {
         $this->_changed['general'] = TRUE;
      } else {
         $this->_changed[$key] = TRUE;
      }
   }

   function _unsetValue ($key) {
      unset($this->_data[$key]);
   }

   /** set object
   * this method sets an object for the specified key and marks it as changed
   *
   * @param mixed object to be changed
   * @access private
   * @author CommSy Development Group
   */
   function _setObject($key, $value, $internal=TRUE) {
      $this->_data[$key] = $value;
      if ($internal) {
         $this->_changed['general'] = TRUE;
      } else {
         $this->_changed[$key] = TRUE;
      }
   }

    /** save item
     * this method saves the item to the database; if links to other items (e.g. relevant groups) are changed, they will be updated too.
     *
     * @param cs_manager the manager that should be used to save the item (e.g. cs_news_manager for cs_news_item)
     * @access private
     */
    public function _save($manager)
    {
        $saved = false;
        if (isset($this->_changed['general']) and $this->_changed['general'] == true) {
            $manager->setCurrentContextID($this->getContextID());
            if (!$this->_link_modifier) {
                $manager->setSaveWithoutLinkModifier();
            }
            $saved = $manager->saveItem($this);
        }

        $this->persistExternalViewer();

        foreach ($this->_changed as $changed_key => $is_changed) {
            if ($is_changed) {
                if ($changed_key != 'general' and $changed_key != 'section_for' and $changed_key != 'task_item' and $changed_key != 'copy_of') {
                    // Abfrage nötig wegen langsamer Migration auf die neuen LinkTypen.
                    if (in_array($changed_key, array(
                        CS_TOPIC_TYPE,
                        CS_GROUP_TYPE,
                        CS_PROJECT_TYPE,
                        CS_PRIVATEROOM_TYPE,
                        CS_MYROOM_TYPE,
                        CS_COMMUNITY_TYPE,
                        CS_ANNOUNCEMENT_TYPE,
                        CS_MATERIAL_TYPE,
                        CS_TAG_TYPE,
                        CS_TODO_TYPE,
                        CS_DATE_TYPE,
                        CS_DISCUSSION_TYPE,
                        CS_USER_TYPE
                    ))) {
                        $link_manager = $this->_environment->getLinkItemManager();
                        if (is_object($this->_data[$changed_key])) { // a list of objects or one object
                            $this->_setObjectLinkItems($changed_key);
                        } elseif (is_array($this->_data[$changed_key])) { // an array
                            $this->_setIDLinkItems($changed_key);
                        }
                    } else {   // sollte irgendwann überflüssig werden!!!!
                        $link_manager = $this->_environment->getLinkManager();
                        $version_id = $this->getVersionID();
                        $link_manager->deleteLinks($this->getItemID(), $version_id, $changed_key);
                        if (is_object($this->_data[$changed_key])) { // a list of objects or one object
                            $this->_setObjectLinks($changed_key);
                        } elseif (is_array($this->_data[$changed_key])) { // an array
                            $this->_setIDLinks($changed_key);
                        }
                    }
                }
            }
        }

        return $saved;
    }

    private function persistExternalViewer()
    {
        if (isset($this->_external_viewer_user_array) and !empty($this->_external_viewer_user_array)) {
            $item_manager = $this->_environment->getItemManager();

            $user_id_array = $item_manager->getExternalViewerUserArrayForItem($this->getItemID());

            // persist new external viewers
            $newExternalViewers = array_diff($this->_external_viewer_user_array, $user_id_array);
            foreach ($newExternalViewers as $newExternalViewer) {
                $item_manager->setExternalViewerEntry($this->getItemID(), $newExternalViewer);
            }

            // delete removed external viewers
            $removedExternalViewers = array_diff($user_id_array, $this->_external_viewer_user_array);
            foreach ($removedExternalViewers as $removedExternalViewer) {
                $item_manager->deleteExternalViewerEntry($this->getItemID(), $removedExternalViewer);
            }
        } else {
            $item_manager = $this->_environment->getItemManager();

            $user_id_array = $item_manager->getExternalViewerUserArrayForItem($this->getItemID());
            foreach ($user_id_array as $user_id) {
                $item_manager->deleteExternalViewerEntry($this->getItemID(), $user_id);
            }
        }
    }

   function _setObjectLinkItems($changed_key) {
      // $changed_key_item_list enthält die link_items EINES TYPS, die das Item aktuell bei sich trägt
      // $old_link_item_list die Link items EINES TYPS, die das Link Item vor der Bearbeitung besa
      $link_manager = $this->_environment->getLinkItemManager();
      $link_manager->resetLimits();
     if ( ($changed_key == CS_COMMUNITY_TYPE and $this->isA(CS_PROJECT_TYPE))
          or
         ($changed_key == CS_PROJECT_TYPE and $this->isA(CS_COMMUNITY_TYPE))
        ) {
         $link_manager->setContextLimit($this->getContextID());
     } else {
         $link_manager->setContextLimit($this->_environment->getCurrentContextID());
     }
      $link_manager->setLinkedItemLimit($this);
      $link_manager->setTypeLimit($changed_key);
      $link_manager->select();
      $old_link_item_list = $link_manager->get();
      $delete_link_item_list = $link_manager->get();
      $changed_key_item_list = $this->_data[$changed_key];
      $create_key_item_list = $this->_data[$changed_key];
      $old_link_item = $old_link_item_list->getFirst();
      //Beide Listen durchgehen und vergleichen
      while ($old_link_item) {
         $old_linked_item = $old_link_item->getLinkedItem($this);
         $changed_key_item = $changed_key_item_list->getFirst();
         while( $changed_key_item ){
            $changed_key_item_id = $changed_key_item->getItemID();
            #$changed_key_version_id = $changed_key_item->getVersionID();
            $old_linked_item_id = $old_linked_item->getItemID();
            #$old_linked_version_id = $old_linked_item->getVersionID();
            // gibt es keine Übereinstimmung
            #if ($changed_key_item_id == $old_linked_item_id AND $changed_key_version_id == $old_linked_version_id){
            if ($changed_key_item_id == $old_linked_item_id) {
               $create_key_item_list->removeElement($changed_key_item);
               $delete_link_item_list->removeElement($old_linked_item);
            }
            $changed_key_item = $changed_key_item_list->getNext();
         }
        $old_link_item = $old_link_item_list->getNext();
      }
      $changed_key_item = $create_key_item_list->getFirst();
      while( $changed_key_item ){
         //Das neue Link_item erzeugen und abspeichern
         $link_item = $link_manager->getNewItem();
         $link_item->setFirstLinkedItem($this);
         $link_item->setSecondLinkedItem($changed_key_item);
         $link_item->save();
         $changed_key_item = $create_key_item_list->getNext();
      }
      $delete_link_item = $delete_link_item_list->getFirst();
      while ($delete_link_item) {
         $delete_link_item->delete();
         $delete_link_item = $delete_link_item_list->getNext();
      }
   }

   function _setIDLinkItems($changed_key) {
      $link_manager = $this->_environment->getLinkItemManager();
      $link_manager->resetLimits();
      if (
          ( $changed_key == CS_COMMUNITY_TYPE
            and $this->isA(CS_PROJECT_TYPE)
         )
         or ( $changed_key == CS_PROJECT_TYPE
               and $this->isA(CS_COMMUNITY_TYPE)
            )
         ) {
         $link_manager->setContextLimit($this->getContextID());
      } else {
         $link_manager->setContextLimit($this->_environment->getCurrentContextID() );
      }
      if ($changed_key == CS_COMMUNITY_TYPE){
         $change_all_items_in_community_room = true;
      }else{
         $change_all_items_in_community_room = false;
      }
      $link_manager->setLinkedItemLimit($this);
      if ($changed_key == CS_MYROOM_TYPE){
         $type_array[0]='project';
         $type_array[1]='community';
         $link_manager->setTypeArrayLimit($type_array);
      }else{
         $link_manager->setTypeLimit($changed_key);
      }
      $link_manager->select();
      $old_link_item_list = $link_manager->get();
      $delete_link_item_list = clone $old_link_item_list;
      $changed_key_array = $this->_data[$changed_key];
      $create_key_array = $changed_key_array;
      $old_link_item = $old_link_item_list->getFirst();
      //Beide Listen durchgehen und vergleichen
      while ($old_link_item) {
         $old_linked_item = $old_link_item->getLinkedItem($this);
         if ( isset($old_linked_item) ) {
            foreach ($changed_key_array as $item_data) {
               $old_linked_item_id = $old_linked_item->getItemID();
               $changed_key_item_id = $item_data['iid'];
               if ($changed_key_item_id == $old_linked_item_id) {
                  foreach($create_key_array as $count => $create_data){
                     if ($create_data['iid'] == $old_linked_item_id) {
                        array_splice($create_key_array,$count,1);
                     }
                  }
                  $delete_link_item_list->removeElement($old_link_item);
               }
            }
         }
         $old_link_item = $old_link_item_list->getNext();
      }

      foreach( $create_key_array as $item_data ) {
         //Das neue Link_item erzeugen und abspeichern
         $link_item = $link_manager->getNewItem();
         $link_item->setFirstLinkedItem($this);
         $item_manager = $this->_environment->getManager($changed_key);
         $item = $item_manager->getItem($item_data['iid']);
         $link_item->setSecondLinkedItem($item);
         $link_item->save();
      }
      $delete_link_item = $delete_link_item_list->getFirst();
      while ($delete_link_item) {
         if ($change_all_items_in_community_room){
            $item_id = $delete_link_item->getFirstLinkedItemID();
            $context_id = $delete_link_item->getSecondLinkedItemID();
            $link_manager = $this->_environment->getLinkItemManager();
            $link_manager->deleteAllLinkItemsInCommunityRoom($item_id,$context_id);
         }
         $delete_link_item->delete();
         $delete_link_item = $delete_link_item_list->getNext();
      }
   }


//********************************************************
//TBD: Nach der vollständigen Migration der Links kann diese Methode entfernt werden
//********************************************************

   function _setObjectLinks($changed_key) {
      $link_manager = $this->_environment->getLinkManager();
      $item = $this->_data[$changed_key]->getFirst();
      // iterating through the list should be done by the link manager
      while ($item) {
         if ( $changed_key == 'material_for' ||
              $changed_key == 'member_of' ) {# ||
#              $changed_key == 'task_item'){
            $link_array = array();
            $link_array['room_id'] = $this->getContextID();
            $link_array['to_item_id'] = $this->getItemID();
            $link_array['to_version_id'] = $this->getVersionID();
            $link_array['from_item_id'] = $item->getItemID();
            $link_array['from_version_id'] = $this->getVersionID();
         } else {
            $link_array = array();
            $link_array['room_id'] = $this->getContextID();
            $link_array['from_item_id'] = $this->getItemID();
            $link_array['from_version_id'] = $this->getVersionID();
            $link_array['to_item_id'] = $item->getItemID();
            $link_array['to_version_id'] = $item->getVersionID();
         }
         // needed for import material !!!
         if ($item->getContextID() != $this->_environment->getCurrentContextID()) {
            $link_array['room_id'] = $item->getContextID();
         }
         $link_array['link_type']= $changed_key;
         $link_manager->save($link_array);
         $item = $this->_data[$changed_key]->getNext();
      }
   }


//********************************************************
//TBD: Nach der vollständigen Migration der Links kann diese Methode entfernt werden
//********************************************************
   function _setIDLinks($changed_key) {
      $link_manager = $this->_environment->getLinkManager();
      foreach ($this->_data[$changed_key] as $item_data) {
         if ( $changed_key == 'material_for' ||
              $changed_key == 'member_of' ) {# ||
#              $changed_key == 'task_item') {
            $link_array = array();
            $link_array['room_id'] = $this->getContextID();
            $link_array['to_item_id'] = $this->getItemID();
            $link_array['to_version_id'] = $this->getVersionID();
            $link_array['from_item_id'] = $item_data['iid'];
            if(isset($item_data['vid'])) {
               $link_array['from_version_id'] = $item_data['vid'];
            } else {
                $link_array['from_version_id'] = 0;
            }
         } else {
            $link_array = array();
            $link_array['room_id'] = $this->getContextID();
            $link_array['from_item_id'] = $this->getItemID();
            $link_array['from_version_id'] = $this->getVersionID();
            if ($changed_key == 'buzzword_for' and (!is_array($item_data)) ) {
               $link_array['to_item_id'] = $item_data;
            } else {
               $link_array['to_item_id'] = $item_data['iid'];
            }
            $link_array['to_version_id'] = 0;
         }
         $link_array['link_type'] = $changed_key;
         $link_manager->save($link_array);
      }
      // MERDE
   }

   function _setValueAsID ($key, $value) {
      $data[] = array('iid' => (int)$value, 'vid' => '0');
      $this->_setValue($key, $data, FALSE);
   }

   function _setValueAsIDArray ($key, $value) {
      $data = array();
      foreach ($value as $id) {
         $data[] = array('iid' => $id, 'vid' => '0');
      }
      $this->_setValue($key, $data, FALSE);
   }

   function _setObjectAsItem ($key, $value) {
      $list = new cs_list();
      $list->add((object)$value);
      $this->_setObject($key, $list, FALSE);
   }

   /** delete item
   * this method deletes the item to the database; if links to other items (e.g. relevant groups) are changed, they will be updated too.
   *
   * @param cs_manager the manager that should be used to delete the item (e.g. cs_news_manager for cs_news_item)
   * @access private
   *
   * @author CommSy Development Group
   */
   function _delete($manager) {
      $manager->delete($this->getItemID());
      $link_manager = $this->_environment->getLinkItemManager();
      $link_manager->deleteLinksBecauseItemIsDeleted($this->getItemID());

      $this->setDeletionDate(getCurrentDateTimeInMySQL());
      $this->setDeleterID($this->_environment->getCurrentUserItem()->getItemID());
   }

   function _undelete ($manager) {
      $manager->undelete($this->getItemID());
      $link_manager = $this->_environment->getLinkItemManager();
      $link_manager->undeleteLinks($this);
   }

    /**
     * Returns whether this item's content should get overwritten with some placeholder text.
     * @return bool Whether this item's content should get overwritten (true), or not (false)
     */
    public function getHasOverwrittenContent(): bool
    {
        return false;
    }

    function isPublic () {
      return false;
   }

   function getPublic() {
      return $this->_getValue('public');
   }



   function mayEdit (cs_user_item $user_item) {
      $access = false;
      if ( !$user_item->isOnlyReadUser() ) {
         if ( $user_item->isRoot() or
              ($user_item->getContextID() == $this->getContextID()
               and ($user_item->isModerator()
                    or ($user_item->isUser()
                        and ($user_item->getItemID() == $this->getCreatorID()
                             or !$this->isPrivateEditing()))))
            ) {
            $access = true;
         }
      }

      if ( $access === true ) {
          // check locking
          global $symfonyContainer;
          $checkLocking = $symfonyContainer->getParameter('commsy.settings.item_locking');
          $checkLocking &= !$this->_issetExtra('etherpad_id'); // don't check locking for etherpads
          
          if ($checkLocking && !$user_item->isRoot() && method_exists($this, "getLockingDate") && method_exists($this, "getLockingUserId") && $this->hasLocking()) {
              $lockingUserId = $this->getLockingUserId();

              // grant access if there is no lock or we are the user who has created it
              if (!$lockingUserId || $lockingUserId == $user_item->getItemId()) {
                  $access = true;
              } else {
                  // if there is a lock, check the date
                  $lockingDate = $this->getLockingDate();
                  if ($lockingDate) {
                    $editDate = new DateTime($lockingDate);
                    $compareDate = new DateTime();
                    $compareDate->modify("-20 minutes");

                    $access = ($compareDate >= $editDate);
                  }
              }
          }
      } else {
        // NOTE: for guest users, $privateRoomUserItem will be null
      	$privateRoomUserItem = $user_item->getRelatedPrivateRoomUserItem();

      	// check for sub-types
      	switch ( $this->getType() )
      	{
      		case CS_SECTION_TYPE:
      		case CS_STEP_TYPE:
      			$linkedItem = $this->getLinkedItem();
      			$mayEdit = $linkedItem->mayEdit($user_item);
      			if (!$mayEdit && $privateRoomUserItem) {
                    $mayEdit = $linkedItem->mayEdit($privateRoomUserItem);
                }
      			return $mayEdit;
      			break;
      	}
      }

      return $access;
   }

   public function mayEditByUserID ($user_id,$auth_source) {
      $user_manager = $this->_environment->getUserManager();
      $user_manager->resetLimits();
      $user_manager->setUserIDLimit($user_id);
      $user_manager->setAuthSourceLimit($auth_source);
      $user_manager->setContextLimit($this->getContextID());
      $user_manager->select();
      $user_list = $user_manager->get();
      if ($user_list->getCount() == 1) {
         $user_in_room = $user_list->getFirst();
         return $this->mayEdit($user_in_room);
      } elseif ($user_list->getCount() > 1) {
         include_once('functions/error_functions.php');
         trigger_error('ambiguous user data in database table "user" for user-id "'.$user_id.'"',E_USER_WARNING);
      } else {
         include_once('functions/error_functions.php');
         trigger_error('can not find user data in database table "user" for user-id "'.$user_id.'", auth_source "'.$auth_source.'", context_id "'.$this->getContextID().'"',E_USER_WARNING);
      }
   }

   /** \brief	check via portfolio permission
    *
    * This Method checks for item <=> activated portfolio - relationships
    */
   public function mayPortfolioSee(string $username)
   {
   	$portfolioManager = $this->_environment->getPortfolioManager();

   	// get all ids from portfolios we are allow to see
   	$portfolioIds = $portfolioManager->getPortfolioForExternalViewer($username);

   	// now we get all item tags and their ids
   	$tagList = $this->getTagList();
   	$tagIdArray = array();

   	$tagEntry = $tagList->getFirst();
   	while ( $tagEntry )
   	{
   		$tagIdArray[] = $tagEntry->getItemID();

   		$tagEntry = $tagList->getNext();
   	}

   	if ( empty($portfolioIds) || empty($tagIdArray) ) return false;

   	// get row and column information for all portfolios with given tags
   	$portfolioInformation = $portfolioManager->getPortfolioData($portfolioIds, $tagIdArray);

   	// if user is allowed to see, there must be two tags for one portfolioId in this array, one for column, one for row
   	foreach ( $portfolioIds as $portfolioId )
   	{
   		if ( isset($portfolioInformation[$portfolioId]) )
   		{
   			$entryArray = $portfolioInformation[$portfolioId];

   			if ( sizeof($entryArray) > 1 )
   			{
   				$hasRow = $hasColumn = false;
   				foreach ( $entryArray as $entry )
   				{
   					if ( $entry["row"] == 0) $hasColumn = true;
   					if ( $entry["column"] == 0) $hasRow = true;
   				}

   				if ( $hasRow === true && $hasColumn === true) return true;
   			}
   		}
   	}

   	return false;
   }

    /**
     * @param int $itemId
     * @param string $username
     * @return bool
     * @throws \Doctrine\DBAL\Exception
     */
    public function mayExternalSee(int $itemId, string $username): bool
    {
        $item_manager = $this->_environment->getItemManager();
        $retour = $item_manager->getExternalViewerForItem($itemId, $username);
        if ($retour) {
            return true;
        } else {
            return $this->mayPortfolioSee($username);
        }
    }

    /** is the given user allowed to see this item?
     *
     * @param cs_user_item $userItem
     */
    public function maySee(cs_user_item $userItem)
    {
        // Deny access, if the item's context is deleted
        $contextItem = $this->getContextItem();
        if ($contextItem === null || $contextItem->isDeleted()) {
            return false;
        }

        // Root
        if ($userItem->isRoot()) {
           return true;
        }

        // Room user
        $userInContext = ($userItem->getContextID() === $this->getContextID()) ? $userItem:
            $userItem->getRelatedUserItemInContext($this->getContextID());
        if ($userInContext !== null && $userInContext->isUser()) {
            // deactivated entries can be only viewed by a moderator or by their creator
            if ($this->isNotActivated()) {
                if ($userInContext->isModerator()) {
                    return true;
                }

                if ($this->getCreatorID() == $userInContext->getItemId()) {
                    return true;
                }
            } else {
                return true;
            }
        }

        // External viewer
        if ($this->mayExternalSee($this->getItemID(), $userItem->getUserID())) {
            return true;
        }

        // Guest
        $currentContextItem = $this->_environment->getCurrentContextItem();
        if ($currentContextItem->isOpenForGuests()) {
            if ($userItem->isGuest() || $userItem->isRequested()) {
                if (!$this->isNotActivated()) {
                    return true;
                }
            }
        }

        return false;
    }

   function getLatestLinkItemList ($count) {
      $link_list = new cs_list();
      $link_item_manager = $this->_environment->getLinkItemManager();
      $link_item_manager->setLinkedItemLimit($this);
      $link_item_manager->setEntryLimit($count);

      $context_item = $this->_environment->getCurrentContextItem();
      $conf = $context_item->getHomeConf();
      if ( !empty($conf) ) {
         $rubrics = explode(',', $conf);
      } else {
         $rubrics = array();
      }
      $type_array = array();
      foreach ( $rubrics as $rubric ) {
         $rubric_array = explode('_', $rubric);
         if ( $rubric_array[1] != 'none' and $rubric_array[0] != CS_USER_TYPE) {
            $type_array[] = $rubric_array[0];
         }
      }
      $link_item_manager->setTypeArrayLimit($type_array);
      $link_item_manager->setRoomLimit($this->getContextID());
      $link_item_manager->select();
      $link_list = $link_item_manager->get();
      $link_item_manager->resetLimits();
      return $link_list;
   }

    public function getAllLinkItemList(): cs_list
    {
        $link_item_manager = $this->_environment->getLinkItemManager();
        $link_item_manager->setLinkedItemLimit($this);

        $context_item = $this->_environment->getCurrentContextItem();
        $conf = $context_item->getHomeConf();

        // translation of entry to rubrics for new private room
        if ($this->_environment->inPrivateRoom() && mb_stristr($conf, CS_ENTRY_TYPE)) {
            $temp_array = [];
            $temp_array3 = [];
            $rubric_array2 = [];
            $temp_array[] = CS_ANNOUNCEMENT_TYPE;
            $temp_array[] = CS_TODO_TYPE;
            $temp_array[] = CS_DISCUSSION_TYPE;
            $temp_array[] = CS_MATERIAL_TYPE;
            $temp_array[] = CS_DATE_TYPE;
            foreach ($temp_array as $temp_rubric) {
                if (!mb_stristr($conf, $temp_rubric)) {
                    $temp_array3[] = $temp_rubric . '_nodisplay';
                }
            }
            $rubric_array = explode(',', $conf);
            foreach ($rubric_array as $temp_rubric) {
                if (!mb_stristr($temp_rubric, CS_ENTRY_TYPE)) {
                    $rubric_array2[] = $temp_rubric;
                } else {
                    $rubric_array2 = array_merge($rubric_array2, $temp_array3);
                }
            }
            $conf = implode(',', $rubric_array2);
        }

        $rubrics = !empty($conf) ? explode(',', $conf) : [];

        $type_array = [];
        foreach ($rubrics as $rubric) {
            $rubric_array = explode('_', $rubric);
            if (($rubric_array[1] != 'none' && $rubric_array[0] != CS_USER_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->_environment->getCurrentModule() == CS_DATE_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->_environment->getCurrentModule() == CS_TODO_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->_environment->getCurrentModule() == CS_GROUP_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->_environment->getCurrentModule() == CS_MATERIAL_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->_environment->getCurrentModule() == CS_TASK_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->_environment->getCurrentModule() == CS_TOPIC_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->getItemType() == CS_DATE_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->getItemType() == CS_TODO_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->getItemType() == CS_GROUP_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->getItemType() == CS_MATERIAL_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->getItemType() == CS_ANNOUNCEMENT_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->getItemType() == CS_TASK_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->getItemType() == CS_DISCUSSION_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->getItemType() == CS_TOPIC_TYPE) ||
                ($rubric_array[0] == CS_USER_TYPE && $this->getItemType() == CS_LABEL_TYPE)
            ) {
                $type_array[] = $rubric_array[0];
            }
        }

        $link_item_manager->setTypeArrayLimit($type_array);
        $link_item_manager->setRoomLimit($this->getContextID());
        $link_item_manager->select();
        $link_list = $link_item_manager->get();
        $link_item_manager->resetLimits();

        return $link_list;
    }

    /**
     * @param string $type
     * @return cs_list|null
     */
    public function getLinkItemList(string $type):? cs_list
    {
        $link_item_manager = $this->_environment->getLinkItemManager();
        $link_item_manager->setLinkedItemLimit($this);
        if ($type == CS_MYROOM_TYPE) {
            $type_array[0] = 'project';
            $type_array[1] = 'community';
            $link_item_manager->setTypeArrayLimit($type_array);
        } else {
            $link_item_manager->setTypeLimit($type);
        }

        if (
            ($type == CS_COMMUNITY_TYPE && $this->isA(CS_PROJECT_TYPE)) ||
            ($type == CS_PROJECT_TYPE && $this->isA(CS_COMMUNITY_TYPE) ||
                ($type == CS_COMMUNITY_TYPE && $this->isA(CS_PROJECT_TYPE) && $this->_environment->inServer()) ||
                ($type == CS_COMMUNITY_TYPE && $this->isA(CS_PROJECT_TYPE) && $this->_environment->inGroupRoom() &&
                    $this->_environment->getCurrentContextItem()->getLinkedProjectItem()->getItemId() == $this->getItemId())
            )
        ) {
            $link_item_manager->setRoomLimit($this->getContextID());
        } elseif ($this->isA(CS_LABEL_TYPE) && $this->getLabelType() == CS_GROUP_TYPE) {
            // müsste dies nicht für alle Fälle gelten ???
            $link_item_manager->setRoomLimit($this->getContextID());
        } elseif ($this->isA(CS_USER_TYPE) || $this->isA(CS_DATE_TYPE) || $this->isA(CS_TODO_TYPE)) {
            $link_item_manager->setRoomLimit($this->getContextID());
        } else {
            $link_item_manager->setRoomLimit($this->_environment->getCurrentContextID());
        }

        $link_item_manager->select();
        return $link_item_manager->get();
    }

   function getLinkedItemList ($type) {
      $link_list = $this->getLinkItemList($type);

      $result_list = new cs_list();
      $link_item = $link_list->getFirst();
      while ($link_item) {
         $result_list->add($link_item->getLinkedItem($this));
         $link_item = $link_list->getNext();
      }
      return $result_list;
   }

   function getAllLinkedItemIDArray() {
      $id_array = array();
      $link_list = $this->getAllLinkItemList();
      $link_item = $link_list->getFirst();
      while ($link_item) {
         $link_item_id = $link_item->getFirstLinkedItemID();
         if ($link_item_id == $this->getItemID()){
            $id_array[] = $link_item->getSecondLinkedItemID();
         } else {
            $id_array[] = $link_item->getFirstLinkedItemID();
         }
         $link_item = $link_list->getNext();
      }
      return $id_array;
   }

   function isSystemLabel () {
      $retour = false;
      return $retour;
   }

   function getLinkedItemIDArray($type) {
      $id_array = array();
      $link_list = $this->getLinkItemList($type);
      $link_item = $link_list->getFirst();
      while ($link_item) {
         $link_item_id = $link_item->getFirstLinkedItemID();
         if ($link_item_id == $this->getItemID()){
            $id_array[] = $link_item->getSecondLinkedItemID();
         } else {
            $id_array[] = $link_item->getFirstLinkedItemID();
         }
         $link_item = $link_list->getNext();
      }
      return $id_array;
   }

   function setLinkedItemsByID ($rubric, $value) {
      $data = array();
      foreach ( $value as $iid ) {
         $tmp['iid'] = $iid;
         $data[] = $tmp;
      }
      $this->_setValue($rubric, $data, FALSE);
   }

    public function setLinkedItemsByIDArray(array $id_array): void
    {
        $item_manager = $this->_environment->getItemManager();

        // Get the typed item for all id's and group them by rubric
        $itemsByRubric = [];
        foreach ($id_array as $iid) {
            $item = $item_manager->getItem($iid);
            $rubric = $item->getItemType();
            if ($rubric == CS_LABEL_TYPE) {
                $label_manager = $this->_environment->getLabelManager();
                $label_item = $label_manager->getItem($iid);
                $rubric = $label_item->getLabelType();
            }

            $itemsByRubric[$rubric][] = [
                'iid' => $iid,
            ];
        }

        $context_item = $this->_environment->getCurrentContextItem();
        $current_room_modules = $context_item->getHomeConf();
        $roomModules = !empty($current_room_modules) ? explode(',', $current_room_modules) : [];

        $rubric_array = [];
        foreach ($roomModules as $module) {
            $link_name = explode('_', $module);
            if ($link_name[1] != 'none') {
                if (!($this->_environment->inPrivateRoom() and $link_name == 'user')) {
                    $rubric_array[] = $link_name[0];
                }
            }
        }

        // translation of entry to rubrics for new private room
        if ($this->_environment->inPrivateRoom() && in_array(CS_ENTRY_TYPE, $rubric_array)) {
            $temp_array = [];
            $temp_array[] = CS_ANNOUNCEMENT_TYPE;
            $temp_array[] = CS_TODO_TYPE;
            $temp_array[] = CS_DISCUSSION_TYPE;
            $temp_array[] = CS_MATERIAL_TYPE;
            $temp_array[] = CS_DATE_TYPE;

            $temp_array2 = array_filter($temp_array, function ($rubric) use ($rubric_array) {
                return !in_array($rubric, $rubric_array);
            });

            $rubric_array2 = [];
            foreach ($rubric_array as $temp_rubric) {
                if ($temp_rubric != CS_ENTRY_TYPE) {
                    $rubric_array2[] = $temp_rubric;
                } else {
                    $rubric_array2 = array_merge($rubric_array2, $temp_array2);
                }
            }
            $rubric_array = $rubric_array2;
        }

        foreach ($rubric_array as $rubric) {
            if (
                $this->_environment->getCurrentModule() == CS_DATE_TYPE ||
                $this->_environment->getCurrentModule() == CS_TODO_TYPE ||
                $this->_environment->getCurrentModule() == CS_GROUP_TYPE ||
                $this->_environment->getCurrentModule() == CS_ANNOUNCEMENT_TYPE ||
                $this->_environment->getCurrentModule() == CS_TASK_TYPE ||
                $this->_environment->getCurrentModule() == CS_DISCUSSION_TYPE ||
                $this->_environment->getCurrentModule() == CS_TOPIC_TYPE ||              $this->getItemType() == CS_DATE_TYPE ||
                $this->getItemType() == CS_MATERIAL_TYPE ||
                $this->getItemType() == CS_GROUP_TYPE ||
                $this->getItemType() == CS_ANNOUNCEMENT_TYPE ||
                $this->getItemType() == CS_TASK_TYPE ||
                $this->getItemType() == CS_DISCUSSION_TYPE ||
                $this->getItemType() == CS_TOPIC_TYPE ||
                $this->getItemType() == CS_TODO_TYPE
            ) {
                if (isset($itemsByRubric[$rubric])) {
                    $this->_setValue($rubric, $itemsByRubric[$rubric], false);
                } else {
                    $this->_setValue($rubric, array(), false);
                }
            }
        }
    }

  /** change creator and modificator - INTERNAL should be called from methods in subclasses
   * change creator and modificator after item was saved for the first time
   */
   function _changeCreatorItemAndModificatorItemTo ($user, $manager) {
      $this->setCreatorItem($user);
      $this->setModificatorItem($user);
      $manager->setCurrentContextID($this->getContextID());
      $manager->saveItemNew($this);
   }

   function hasBeenClicked($user){
      $user_array = $this->getArrayNew4User();
      $id = $user->getItemID();
      if (!empty($user_array) and in_array($id,$user_array)){
         return true;
      }else{
         return false;
      }
   }

   function HasBeenClickedSinceChanged ($user) {
      $user_array = $this->getArrayChanged4User();
      $id = $user->getItemID();
      if (!empty($user_array) and in_array($id, $user_array)){
         return true;
      } else {
         return false;
      }
   }

   function undelete () {
     $manager = $this->_environment->getManager($this->getItemType());
     $manager->undeleteItemByItemID($this->getItemID());
   }

    /** delete item
     * this method deletes an item
     */
    public function delete()
    {
        $manager = $this->_environment->getManager($this->getItemType());
        $this->_delete($manager);
    }

   function deleteAssociatedAnnotations() {
      $annotation_manager = $this->_environment->getAnnotationManager();

      // get all annotations linked with the item
      $annotation_list = $annotation_manager->getAnnotatedItemList($this->getItemID());

      // delete them
      $item = $annotation_list->getFirst();
      while($item){
         $item->delete();

         $item = $annotation_list->getNext();
      }
   }

   ################## file handling ############################

  /** get list of files attached o this item
      if a list of files has been set (@see setFileList()), get it
      if an array of file-ids has been set (@see setFileIDArray()),
      get corresponding files, otherwise get files linked in material_link_file
      @return cs_list list of file items
   */
   function getFileList() {
      $file_list = new cs_list;
   	  if ($this->getPublic()=='-1' || $this->getHasOverwrittenContent()) {
		 $translator = $this->_environment->getTranslationObject();
   	  	 return $file_list;
   	  }else{
	      if ( !empty($this->_data['file_list']) ) {
	         $file_list = $this->_data['file_list'];
	      } else {
	         if ( isset($this->_data['file_id_array']) and !empty($this->_data['file_id_array']) ) {
	            $file_id_array = $this->_data['file_id_array'];
	         } else {
	            $file_id_array = array();
	            $link_manager = $this->_environment->getLinkManager();
	            $file_links = $link_manager->getFileLinks($this);
	            if ( !empty($file_links) ) {
	               foreach ($file_links as $link) {
	                  $file_id_array[] = $link['file_id'];
	               }
	            }
	            if ( isset($file_id_array) ) {
	               $this->_data['file_id_array'] = $file_id_array;
	            }
	         }
	         if ( !empty($file_id_array) ) {
	            $file_id_array = array_unique($file_id_array);
	            $file_manager = $this->_environment->getFileManager();
	            $file_manager->setIDArrayLimit($file_id_array);
	            $file_manager->setContextLimit('');
	            $file_manager->select();
	            $file_list = $file_manager->get();
	            if ( isset($file_list)
	                 and !empty($file_list)
	               ) {
	               $this->_data['file_list'] = $file_list;
	            }
	         }
	      }
	      $file_list->sortby('filename');
	      return $file_list;
	  }
   }

   /**get array of file ids
      if an array of file-ids has been set (@see setFileIDArray()), get it
      if a list of files has been set (@see setFileList()), get corresponding file-ids,
      otherwise get file-ids according to links in material_link_file
      @return array file_id_array
   */
   function getFileIDArray () {
      $file_id_array = array();
      if ( isset($this->_data['file_id_array']) and !empty($this->_data['file_id_array']) ) { // check if file_id_array has been set by user or this method has been called before
         $file_id_array = $this->_data['file_id_array'];
      } elseif ( isset($this->_data['file_id_array'])
                 and empty($this->_data['file_id_array'])
                 and $this->_filelist_changed
               ) { // alle dateien bewusst abhängen
         $file_id_array = $this->_data['file_id_array'];
      } elseif ( isset($this->_data['file_list']) and is_object($this->_data['file_list']) ) {
         $file = $this->_data['file_list']->getFirst();
         while($file) {
            $file_id_array[] = $file->getFileID();
            $file = $this->_data['file_list']->getNext();
         }
      } else {
         $link_manager = $this->_environment->getLinkManager();
         $file_links = $link_manager->getFileLinks($this);
         if ( !empty($file_links) ) {
            foreach ($file_links as $link) {
               $file_id_array[] = $link['file_id'];
            }
         }
      }
      return $file_id_array;
   }

   function setFileIDArray ($value) {
      $this->_data['file_id_array'] = $value;
      $this->_data['file_list'] = NULL;
      $this->_filelist_changed = TRUE;
      if ( empty($value) ) {
         $this->_filelist_changed_empty = true;
      }
   }

   function setFileList ($value) {
      $this->_data['file_list'] = $value;
      $this->_data['file_id_array'] = '';
      $this->_filelist_changed = TRUE;
   }

   function _saveFileLinks() {   // das ist so komplex, weil wir die filelinks nicht aus der db löschen können
                                 // wenn jemandem was eleganteres einfällt: nur zu
      if ( $this->_filelist_changed ) {
         if (!$this->isNotActivated()){
            $this->setModificationDate(NULL);
         }
         $link_manager = $this->_environment->getLinkManager();
         $file_id_array = $this->getFileIDArray();
         if ( $file_id_array === '' or $this->_filelist_changed_empty ) {
            $link_manager->deleteFileLinks($this);
         } else {
            $current_file_links = $link_manager->getFileLinks($this);
            $keep_links = array();
            if ( !empty($current_file_links) ) {
               foreach ($current_file_links as $cur_link) {
                  if ( in_array($cur_link['file_id'], $file_id_array) ) {
                     $keep_links[] = $cur_link['file_id'];
                  } else {
                     $link_manager->deleteFileLinkByID($this, $cur_link['file_id']);
                  }
               }
            }
            $add_links = array_diff($file_id_array, $keep_links);
            if( !empty($add_links) ) {
               foreach ($add_links as $file_id) {
                  $link_manager->linkFileByID($this, $file_id);
               }
            }
         }
      }
   }

   function _saveFiles () {
      $file_id_array = array();
      $result = false;
      if ( $this->_filelist_changed
           and isset($this->_data['file_list'])
           and $this->_data['file_list']->getCount() > 0
         ) {
         $file_id_array = array();
         $file_item = $this->_data['file_list']->getFirst();
         while ( $file_item ) {
            if ( $file_item->getContextID() != $this->getContextID() ) {
               $file_item->setContextID($this->getContextID());
            }
            $file_item->setCreatorItem($this->getCreatorItem());
            $result = $file_item->save();
            if ($result) {
               $file_item_id = $file_item->getFileID();
               if ( !empty($file_item_id) ) {
                  $file_id_array[] = $file_item_id;
               } else {
                  $this->_error_array[] = $file_item->getDisplayName();
               }
            } else {
               $this->_error_array[] = $file_item->getDisplayName();
            }
            $file_item = $this->_data['file_list']->getNext();
         }
         $this->setFileIDArray($file_id_array);
      }

      global $c_indexing,$c_indexing_cron;
      if ( isset($c_indexing)
           and !empty($c_indexing)
           and $c_indexing
           and isset($c_indexing_cron)
           and !$c_indexing_cron
         ) {
         $ftsearch_manager = $this->_environment->getFTSearchManager();
         $ftsearch_manager->buildFTIndex();
      }
   }

   function _copyFileList () {
      $file_list = $this->getFileList();		
		$file_new_list = new cs_list();

		// archive
		if ( $file_list->isEmpty() 
		     and $this->isArchived()
			  and !$this->_environment->isArchiveMode()
		   ) {
			$this->_environment->toggleArchiveMode();
         $file_list = $this->getFileList();		
			$this->_environment->toggleArchiveMode();
		}
		// archive		
      
      if ( !empty($file_list) and $file_list->getCount() > 0 ) {
         $file_item = $file_list->getFirst();
         while ( $file_item ) {
            $user = $this->getCreatorItem();
            $file_item->setItemID('');
            $file_item->setTempName($file_item->getDiskFilename());
            $file_item->setContextID($this->getContextID());
            $file_item->setCreatorItem($user);
            $file_new_list->add($file_item);
            $file_item = $file_list->getNext();
         }
      }
      return $file_new_list;
   }

   function isPublished () {
      return true;
   }

   function getErrorArray () {
      return $this->_error_array;
   }

   function setErrorArray ($error_array) {
      $this->_error_array = $error_array;
   }

   public function getDescriptionWithoutHTML () {
      $retour = $this->getDescription();
      $retour = str_replace('<!-- KFC TEXT -->','',$retour);
      $retour = preg_replace('~<[A-Za-z][^>.]+>~u','',$retour);
      return $retour;
   }

   /** save item
    * this methode save the item into the database
    */
   public function save () {
      $manager = $this->_environment->getManager($this->getItemType());
      $this->_save($manager);
   }

   /** save item
    * this methode only saves the cs_item itself
    */
   public function saveAsItem () {
      $manager = $this->_environment->getItemManager();
      $this->_save($manager);
   }

   /**
    * returns true if the modification_date should be saved
    *
    * @param boolean
    */
   function isChangeModificationOnSave() {
      return $this->_change_modification_on_save;
   }

   function setChangeModificationOnSave($save) {
      $this->_change_modification_on_save = $save;
   }


   function getTopicList() {
      $topic_list = $this->getLinkedItemList(CS_TOPIC_TYPE);
      $topic_list->sortBy('name');
      return $topic_list;
   }

   function setTopicListByID ($value) {
      $topic_array = array();
      foreach ( $value as $iid ) {
         $tmp_data = array();
         $tmp_data['iid'] = $iid;
         $topic_array[] = $tmp_data;
      }
      $this->_setValue(CS_TOPIC_TYPE, $topic_array, FALSE);
   }

   function setTopicList($value) {
      $this->_setObject(CS_TOPIC_TYPE, $value, FALSE);
   }

    public function setExternalViewerAccounts(array $user_id_array)
    {
        $this->_external_viewer_user_array = $user_id_array;
    }

    public function unsetExternalViewerAccounts()
    {
        $this->_external_viewer_user_array = null;
    }

    public function getExternalViewerString()
    {
        $item_manager = $this->_environment->getItemManager();
        return $item_manager->getExternalViewerUserStringForItem($this->getItemID());
    }

   function getGroupList () {
      $group_list = $this->getLinkedItemList(CS_GROUP_TYPE);
      $group_list->sortBy('name');
      return $group_list;
   }

   function setGroupListByID ($value) {
      $this->setLinkedItemsByID (CS_GROUP_TYPE, $value);
   }

   function setGroupList($value) {
      $this->_setObject(CS_GROUP_TYPE, $value, FALSE);
   }

   function getMaterialList () {
      return $this->getLinkedItemList(CS_MATERIAL_TYPE);
   }

   function setMaterialListByID ($value) {
      $this->setLinkedItemsByID (CS_MATERIAL_TYPE, $value);
   }

   function setMaterialList ($value) {
      $this->_setObject(CS_MATERIAL_TYPE, $value, FALSE);
   }

   //------------------------------------------
   //------------- Wordpressexport -------------
   function setExportToWordpress($value) {
      $this->_addExtra('EXPORT_TO_WORDPRESS', (string)$value);
   }

   //------------- Wordpressexport -------------
   //------------------------------------------

   public function getItemUrl () {
      $type = $this->getType();
      $fct = 'detail';
      $params = array();
      if ( $type == CS_FILE_TYPE ) {
         $mod = type2Module(CS_MATERIAL_TYPE);
         $fct = 'getfile';
         $params['iid'] = $this->getFileID();
      } elseif ( $type == CS_LABEL_TYPE ) {
         $mod = type2Module($this->getLabelType());
         $params['iid'] = $this->getItemID();
      } else {
         $mod = type2Module($type);
         $params['iid'] = $this->getItemID();
      }
      $session_item = $this->_environment->getSessionItem();
      if ( isset($session_item)
           and $session_item->issetValue('cookie')
           and $session_item->issetValue('cookie') != 1
         ) {
         $params['SID'] = $session_item->getSessionID();
      }
      global $c_commsy_domain;
      global $c_commsy_url_path;
      $retour = $c_commsy_domain.$c_commsy_url_path.'/'._curl(false,$this->getContextID(),$mod, $fct, $params);
      return $retour;
   }

   public function getModifierList () {
      $retour = NULL;
      $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
      $modifiers = $link_modifier_item_manager->getModifiersOfItem($this->getItemID());
      if ( !empty($modifiers) ) {
         $user_manager = $this->_environment->getUserManager();
         $user_manager->resetLimits();
         $user_manager->setContextLimit($this->_environment->getCurrentContextID());
         $user_manager->setIDArrayLimit($modifiers);
         $user_manager->select();
         $retour = $user_manager->get();
         unset($user_manager);
      }
      unset($link_modifier_item_manager);
      return $retour;
   }

   function setWorkflowTrafficLight($value) {
      $this->_setValue('workflow_status', (string)$value);
   }
   function getWorkflowTrafficLight() {
      return $this->_getValue('workflow_status');
   }

   function isReadByUser($user){
      $item_manager = $this->_environment->getItemManager();
      return $item_manager->isItemMarkedAsWorkflowRead($this->getItemId(), $user->getItemID());
   }

   function setWorkflowResubmission($value) {
      $this->_setExtra('WORKFLOWRESUBMISSION', (string)$value);
   }
   function getWorkflowResubmission() {
      $result = false;
      if($this->_issetExtra('WORKFLOWRESUBMISSION')){
         $result = $this->_getExtra('WORKFLOWRESUBMISSION');
      }
      return $result;
   }

   function setWorkflowResubmissionDate($value) {
      $this->_setValue('workflow_resubmission_date', (string)$value);
   }
   function getWorkflowResubmissionDate() {
      return $this->_getValue('workflow_resubmission_date');
   }

   function setWorkflowResubmissionWho($value) {
      $this->_setExtra('WORKFLOWRESUBMISSIONWHO', (string)$value);
   }
   function getWorkflowResubmissionWho() {
      $result = 'creator';
      if($this->_issetExtra('WORKFLOWRESUBMISSIONWHO')){
         $result = $this->_getExtra('WORKFLOWRESUBMISSIONWHO');
      }
      return $result;
   }

   function setWorkflowResubmissionWhoAdditional($value) {
      $value = str_replace(array("\t", " "), '', $value);
      $value_array = explode(',', $value);
      $this->_setExtra('WORKFLOWRESUBMISSIONWHOADDITIONAL', $value_array);
   }
   function getWorkflowResubmissionWhoAdditional() {
      $result = false;
      if($this->_issetExtra('WORKFLOWRESUBMISSIONWHOADDITIONAL')){
         $result = implode(', ', $this->_getExtra('WORKFLOWRESUBMISSIONWHOADDITIONAL'));
      }
      return $result;
   }

   function setWorkflowResubmissionTrafficLight($value) {
      $this->_setExtra('WORKFLOWRESUBMISSIONTRAFFICLIGHT', (string)$value);
   }
   function getWorkflowResubmissionTrafficLight() {
      $result = '3_none';
      if($this->_issetExtra('WORKFLOWRESUBMISSIONTRAFFICLIGHT')){
         $result = $this->_getExtra('WORKFLOWRESUBMISSIONTRAFFICLIGHT');
      }
      return $result;
   }

   function setWorkflowValidity($value) {
      $this->_setExtra('WORKFLOWVALIDITY', (string)$value);
   }
   function getWorkflowValidity() {
      $result = false;
      if($this->_issetExtra('WORKFLOWVALIDITY')){
         $result = $this->_getExtra('WORKFLOWVALIDITY');
      }
      return $result;
   }

   function setWorkflowValidityDate($value) {
      $this->_setValue('workflow_validity_date', (string)$value);
   }
   function getWorkflowValidityDate() {
      return $this->_getValue('workflow_validity_date');
   }

   function setWorkflowValidityWho($value) {
      $this->_setExtra('WORKFLOWVALIDITYWHO', (string)$value);
   }
   function getWorkflowValidityWho() {
      $result = 'creator';
      if($this->_issetExtra('WORKFLOWVALIDITYWHO')){
         $result = $this->_getExtra('WORKFLOWVALIDITYWHO');
      }
      return $result;
   }

   function setWorkflowValidityWhoAdditional($value) {
      $value = str_replace(array("\t", " "), '', $value);
      $value_array = explode(',', $value);
      $this->_setExtra('WORKFLOWVALIDITYWHOADDITIONAL', $value_array);
   }
   function getWorkflowValidityWhoAdditional() {
      $result = false;
      if($this->_issetExtra('WORKFLOWVALIDITYWHOADDITIONAL')){
         $result = implode(', ', $this->_getExtra('WORKFLOWVALIDITYWHOADDITIONAL'));
      }
      return $result;
   }

   function setWorkflowValidityTrafficLight($value) {
      $this->_setExtra('WORKFLOWVALIDITYTRAFFICLIGHT', (string)$value);
   }
   function getWorkflowValidityTrafficLight() {
      $result = '3_none';
      if($this->_issetExtra('WORKFLOWVALIDITYTRAFFICLIGHT')){
         $result = $this->_getExtra('WORKFLOWVALIDITYTRAFFICLIGHT');
      }
      return $result;
   }

   /** get draft status
    */
   function isDraft() {
      $isDraft = $this->_getValue('draft');

      if (empty($isDraft)) {
         return 0;
      }

      return $isDraft;
   }

   /** set set draft
    */
   function setDraftStatus ($value) {
      $this->_setValue('draft', (string)$value);
   }

   // archive
   public function setArchiveStatus () {
      $this->_is_archived = true;
   }

   public function isArchived () {
      return $this->_is_archived;
   }

   ##########################################
   # plugin configuration
   ############# START ######################
   
   /** get part of the plugin config array, INTERNAL
    *
    * @param string type: PLUGIN for the plugin
    *                     whole for the whole array
    *
    * @return string the configuration
    */
   public function getPluginConfigForPlugin ($type) {
   	if ( $type == 'whole' ) {
   		$retour = array();
   	} else {
   		$retour = '';
   	}
   	if ( $this->_issetExtra('PLUGIN_CONFIG_DATA') ) {
   		$config_array = $this->_getExtra('PLUGIN_CONFIG_DATA');
   		if ( $type == 'whole' ) {
   			$retour = $config_array;
   		} elseif ( isset($config_array[mb_strtoupper($type, 'UTF-8')]) ) {
   			$retour = $config_array[mb_strtoupper($type, 'UTF-8')];
   		}
   	}
   	return $retour;
   }
   
   /** set part of the plugin config array, INTERNAL
    *
    * @param string part: PLUGIN for the plugin
    *                     whole for the whole array
    * @param array or string value the configuration
    */
   public function setPluginConfigForPlugin ($type, $value) {
   	if ($type == 'whole') {
   		$this->_addExtra('PLUGIN_CONFIG_DATA',$value);
   	} else {
   		$config_array = $this->getPluginConfigForPlugin('whole');
   		$config_array[mb_strtoupper($type, 'UTF-8')] = $value;
   		$this->setPluginConfigForPlugin('whole',$config_array);
   	}
   }
   
   public function getPluginConfigData () {
   	return $this->getPluginConfigForPlugin('whole');
   }
   
   public function setPluginConfigData ($value) {
   	$this->setPluginConfigForPlugin('whole',$value);
   }
   
   ############### END ######################
   # plugin configuration
   ##########################################

    /**
    * returns the locking date
    *
    * @return Date
    */
   function getLockingDate() {
      return $this->_getValue('locking_date');
   }

   /*
    * returns the locking user id
    *
    * @return int
    */
   function getLockingUserId() {
      return $this->_getValue('locking_user_id');
   }

   function hasLocking() {
      return in_array($this->getItemType(), array(
          CS_MATERIAL_TYPE,
          CS_ANNOUNCEMENT_TYPE,
          CS_DATE_TYPE,
          CS_DISCUSSION_TYPE,
          CS_GROUP_TYPE,
          CS_TODO_TYPE,
          CS_TOPIC_TYPE,
//          CS_STEP_TYPE,
//          CS_DISCARTICLE_TYPE,
//          CS_SECTION_TYPE,
      ));
   }

   function lock() {
       $this->_environment->getManager($this->getItemType())->updateLocking($this->getItemId(), date("Y-m-d H:i:s"));
   }

   function unlock() {
       $this->_environment->getManager($this->getItemType())->clearLocking($this->getItemId());
   }

   function isLocked() {
      if ($this->getLockingDate() && $this->getLockingDate() != '') {
         $editDate = new DateTime($this->getLockingDate());
         $compareDate = new DateTime();
         $compareDate->modify("-20 minutes");

         if ($compareDate < $editDate) {
            if ($this->getLockingUserId() == $this->_environment->getCurrentUser()->getItemId()) {
               return false;
            } else {
                return true;
            }
         } else {
            $this->unlock();
            return false;
         }
      } else {
         return false;
      }
   }

    protected function replaceElasticItem(ObjectPersisterInterface $objectPersister, $repository)
    {
        $elasticHost = $_ENV['ELASTICSEARCH_URL'];

        if ($elasticHost) {
            if ($repository instanceof MaterialsRepository) {
                $object = $repository->findLatestVersionByItemId($this->getItemID());
            } else {
                $object = $repository->findOneByItemId($this->getItemID());
            }

            if ($object && $object->isIndexable() && !$this->isDraft()) {
                // Replacing delete + insert with replace will not call the ingest pipeline and
                // will not process any file attachments
                $objectPersister->deleteOne($object);
                $objectPersister->insertOne($object);
            }
        }
    }

    protected function deleteElasticItem($objectPersister, $repository)
    {
        $elasticHost = $_ENV['ELASTICSEARCH_URL'];

        if ($elasticHost) {
            $object = $repository->findOneByItemId($this->getItemID());

            if ($object) {
                $objectPersister->deleteOne($object);
            }
        }
    }

    public function getPath()
    {
        return null;
    }
}