<?php

/* @var $this Bootstrap */
/* @var $db Zend_Db_Adapter_Abstract */
$db = $this->getResource('db');

$stmt = $db->query('ALTER TABLE `attribute` ADD COLUMN `is_grid` TINYINT(1) NOT NULL DEFAULT 1 AFTER `is_required`;');
$stmt->closeCursor();

$names = array('description', 'status', 'resolution');

foreach ($names as $name) {
    $attr = Default_Model_Attribute::get($name);
    if (null === $attr) {
        continue;
    }
    
    $attr->setData('is_grid', 0);
    $attr->save();
}
