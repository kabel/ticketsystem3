<?php

class Default_Model_Table_Changeset extends Default_Model_Table_Abstract
{
    protected $_installSql = "CREATE TABLE `changeset` (
  `changeset_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `comment` text NOT NULL,
  `create_date` datetime NOT NULL,
  `ticket_id` int(10) unsigned NOT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`changeset_id`),
  KEY `FK_CHANGESET_TICKET_ID` (`ticket_id`),
  KEY `FK_CHANGESET_USER_ID` (`user_id`),
  CONSTRAINT `FK_CHANGESET_TICKET_ID` FOREIGN KEY (`ticket_id`) REFERENCES `ticket` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_CHANGESET_USER_ID` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    protected $_installDepends = array(
        'Default_Model_Table_Ticket',
        'Default_Model_Table_User'
    );
    
    protected $_name    = 'changeset';
    protected $_primary = 'changeset_id';
    
    protected $_referenceMap = array(
        'User' => array(
            'columns' => array('user_id'),
            'refTableClass' => 'Default_Model_Table_User'
        ),
        'Ticket' => array(
            'columns' => array('ticket_id'),
            'refTableClass' => 'Default_Model_Table_Ticket'
        )
    );
}