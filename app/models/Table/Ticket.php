<?php

class Default_Model_Table_Ticket extends Default_Model_Table_Abstract
{
    protected $_installSql = "CREATE TABLE `ticket` (
  `ticket_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `summary` tinytext NOT NULL,
  `reporter` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`ticket_id`),
  KEY `FK_TICKET_REPORTER` (`reporter`),
  CONSTRAINT `FK_TICKET_REPORTER` FOREIGN KEY (`reporter`) REFERENCES `user` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
	protected $_installDepends = array(
	    'Default_Model_Table_User'
	);
    
    protected $_name    = 'ticket';
    protected $_primary = 'ticket_id';
    
    protected $_referenceMap = array(
        'Reporter' => array(
            'columns' => array('reporter'),
            'refTableClass' => 'Default_Model_Table_User'
        )
    );
}