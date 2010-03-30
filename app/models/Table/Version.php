<?php

class Default_Model_Table_Version extends Default_Model_Table_Abstract
{
    protected $_installSql = array(
        "CREATE TABLE `version` (
  `code` VARCHAR(50) NOT NULL,
  `version` VARCHAR(50) NOT NULL,
  PRIMARY KEY (`code`)
)
ENGINE = InnoDB CHARSET utf8;",
        "INSERT INTO `version` VALUES ('core', '0.1.2');"
    );
    
    protected $_name    = 'version';
    protected $_primary = 'code';
}