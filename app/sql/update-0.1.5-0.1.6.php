<?php

/* @var $this Bootstrap */
/* @var $db Zend_Db_Adapter_Abstract */
$db = $this->getResource('db');

$stmt = $db->query("ALTER TABLE `ugroup` ADD COLUMN `notify_admin` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `shortname`;");
$stmt->closeCursor();
