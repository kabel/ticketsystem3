<?php

class Default_Model_Table_Ugroup extends Default_Model_Table_Abstract
{
    protected $_installSql = "CREATE TABLE `ugroup` (
  `ugroup_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` tinytext NOT NULL,
  `shortname` varchar(45) NOT NULL,
  `notify_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ugroup_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";

    protected $_name    = 'ugroup';
    protected $_primary = 'ugroup_id';
}