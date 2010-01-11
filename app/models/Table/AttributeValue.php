<?php

class Default_Model_Table_AttributeValue extends Default_Model_Table_Abstract
{
    protected $_installSql = "CREATE TABLE `attribute_value` (
  `attribute_value_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `changeset_id` int(10) unsigned NOT NULL,
  `attribute_id` int(10) unsigned DEFAULT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`attribute_value_id`),
  KEY `FK_ATTRIBUTE_VALUE_CHANGESET_ID` (`changeset_id`),
  KEY `FK_ATTRIBUTE_VALUE_ATTRIBUTE_ID` (`attribute_id`),
  CONSTRAINT `FK_ATTRIBUTE_VALUE_ATTRIBUTE_ID` FOREIGN KEY (`attribute_id`) REFERENCES `attribute` (`attribute_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_ATTRIBUTE_VALUE_CHANGESET_ID` FOREIGN KEY (`changeset_id`) REFERENCES `changeset` (`changeset_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
    protected $_installDepends = array(
        'Default_Model_Table_Attribute',
        'Default_Model_Table_Changeset'
    );
    
    protected $_name    = 'attribute_value';
    protected $_primary = 'attribute_value_id';
    
    protected $_referenceMap = array(
        'Attribute' => array(
            'columns' => array('attribute_id'),
            'refTableClass' => 'Default_Model_Table_Ugroup'
        ),
        'Changeset' => array(
            'columns' =>  array('changeset_id'),
            'refTableClass' => 'Default_Model_Table_Changeset'
        )
    );
}