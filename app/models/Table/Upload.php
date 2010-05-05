<?php

class Default_Model_Table_Upload extends Default_Model_Table_Abstract
{
    protected $_installSql = "CREATE TABLE `upload` (
  `upload_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` tinytext NOT NULL,
  `mimetype` tinytext NOT NULL,
  `content_length` int(10) unsigned NOT NULL,
  `content` mediumblob NOT NULL,
  `ticket_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`upload_id`),
  KEY `FK_UPLOAD_TICKET_ID` (`ticket_id`),
  CONSTRAINT `FK_UPLOAD_TICKET_ID` FOREIGN KEY (`ticket_id`) REFERENCES `ticket` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE,
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    protected $_installDepends = array(
        'Default_Model_Table_Ticket',
        'Default_Model_Table_User'
    );
    
    protected $_name    = 'upload';
    protected $_primary = 'upload_id';
    
    protected $_referenceMap = array(
        'Ticket' => array(
            'columns' => array('ticket_id'),
            'refTableClass' => 'Default_Model_Table_Ticket'
        ),
        'Uploader' => array(
            'columns' => array('uploader'),
            'refTableClass' => 'Default_Model_Table_User'
        )
    );
}