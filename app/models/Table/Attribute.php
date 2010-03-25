<?php

class Default_Model_Table_Attribute extends Default_Model_Table_Abstract
{
    protected $_installSql = array(
    	"CREATE TABLE `attribute` (
  `attribute_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(3) unsigned NOT NULL,
  `name` varchar(45) NOT NULL,
  `label` tinytext NOT NULL,
  `value` text NOT NULL,
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT '50',
  `extra` text NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `is_hidden` tinyint(1) NOT NULL DEFAULT '0',
  `is_required` tinyint(1) NOT NULL DEFAULT '0',
  `is_grid` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`attribute_id`),
  UNIQUE KEY `IX_ATTRIBUTE_NAME` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
    "INSERT INTO `attribute` VALUES
	(1, 5, 'description', 'Description', '', 1, '{\"format\": \"wiki\"}', 1, 1, 1, 0),
	(2, 3, 'status', 'Status', '', 1, '{\"options\": [\"new\", \"assigned\", \"on hold\", \"closed\", \"reopened\"]}', 1, 1, 1, 0),
	(3, 3, 'resolution', 'Resolution', '', 1, '{\"options\": [\"fixed\", \"invalid\", \"wontfix\", \"duplicate\", \"worksforme\"]}', 1, 1, 0, 0),
	(4, 3, 'priority', 'Priority', '', 1, '{\"options\": [\"minor\", \"major\", \"critical\"]}', 1, 0, 1, 1),
	(5, 3, 'group', 'Group', '', 1, '{\"src\": \"ugroup\"}', 1, 0, 0, 1),
	(6, 3, 'owner', 'Owner', '', 1, '{\"src\": \"user\"}', 1, 1, 0, 1),
	(7, 1, 'cc', 'Cc', '', 2, '{\"format\": \"text\", \"list-acl\": \"edit-cc\", \"add\": \"user\"}', 1, 0, 0, 1);"
    );
    
    protected $_name    = 'attribute';
    protected $_primary = 'attribute_id';
}