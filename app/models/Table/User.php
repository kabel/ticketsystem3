<?php

class Default_Model_Table_User extends Default_Model_Table_Abstract
{
    protected $_installSql = array(
    	"CREATE TABLE `user` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL,
  `passwd` varchar(45) NOT NULL,
  `info` tinytext NOT NULL,
  `email` tinytext NOT NULL,
  `level` tinyint(3) unsigned NOT NULL DEFAULT '2',
  `login_type` tinyint(3) unsigned NOT NULL DEFAULT '1',
  `ugroup_id` int(10) unsigned DEFAULT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `IX_USER_LOGIN_TYPE_USERNAME` (`login_type`,`username`),
  KEY `IX_USER_PASSWD` (`passwd`),
  KEY `FK_USER_UGROUP_ID` (`ugroup_id`),
  CONSTRAINT `FK_USER_UGROUP_ID` FOREIGN KEY (`ugroup_id`) REFERENCES `ugroup` (`ugroup_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
    "INSERT INTO `user` VALUES 
	(1, 'admin', MD5('admin'), 'Administrator', '', 1, 1, NULL, 1);"
    );
    protected $_installDepends = array(
        'Default_Model_Table_Ugroup'
    );
    
    protected $_name    = 'user';
    protected $_primary = 'user_id';
    
    protected $_referenceMap = array(
        'Group' => array(
            'columns' => array('ugroup_id'),
            'refTableClass' => 'Default_Model_Table_Ugroup'
        )
    );
}