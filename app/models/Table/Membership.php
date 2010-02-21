<?php

class Default_Model_Table_Membership extends Default_Model_Table_Abstract
{
    protected $_installSql = "CREATE TABLE `membership` (
  `user_id` int(10) unsigned NOT NULL,
  `ugroup_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ugroup_id`,`user_id`),
  KEY `FK_MEMBERSHIP_USER` (`user_id`),
  CONSTRAINT `FK_MEMBERSHIP_USER` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_MEMBERSHIP_UGROUP` FOREIGN KEY (`ugroup_id`) REFERENCES `ugroup` (`ugroup_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8";
    protected $_installDepends = array(
        'Default_Model_Table_User',
    	'Default_Model_Table_Ugroup'
    );
    
    protected $_name    = 'membership';
    protected $_primary = array('user_id', 'ugroup_id');
    
    protected $_referenceMap = array(
        'User' => array(
            'columns' => array('user_id'),
            'refTableClass' => 'Default_Model_Table_User'
        ),
    	'Group' => array(
            'columns' => array('ugroup_id'),
            'refTableClass' => 'Default_Model_Table_Ugroup'
        )
    );
}