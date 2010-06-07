<?php

/* @var $this Bootstrap */
/* @var $db Zend_Db_Adapter_Abstract */
$db = $this->getResource('db');

$stmt = $db->query('ALTER TABLE `upload` DROP KEY `IX_UPLOAD_TICKET_ID_NAME`;');
$stmt->closeCursor();

$stmt = $db->query('ALTER TABLE `upload` ADD COLUMN `expired_date` DATETIME AFTER `create_date`,
 MODIFY COLUMN `content` MEDIUMBLOB DEFAULT NULL;');
$stmt->closeCursor();

$stmt = $db->query('ALTER TABLE `upload` ADD UNIQUE KEY `IX_UPLOAD_TICKET_ID_NAME` (`ticket_id`, `name`(255));');
$stmt->closeCursor();
