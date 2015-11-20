<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
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

// headline
$this->_flushHeadline('db: add workflow_read table');

$success = true;

$sql = "
	CREATE TABLE IF NOT EXISTS `workflow_read` (
	  `item_id` int(11) NOT NULL,
	  `user_id` int(11) DEFAULT NULL,
	  KEY `item_id` (`item_id`),
	  KEY `user_id` (`user_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

$success = $success AND $this->_select($sql);

$sql = "
	CREATE TABLE IF NOT EXISTS `zzz_workflow_read` (
	  `item_id` int(11) NOT NULL,
	  `user_id` int(11) DEFAULT NULL,
	  KEY `item_id` (`item_id`),
	  KEY `user_id` (`user_id`)
	) ENGINE=MyISAM DEFAULT CHARSET=utf8;";

$success = $success AND $this->_select($sql);

$this->_flushHTML(BRLF);
?>