<?php

/* @var $this Bootstrap */
/* @var $db Zend_Db_Adapter_Abstract */
$db = $this->getResource('db');

$processed = array();

$resource = Default_Model_Upload::getResourceInstance();
$select = $resource->select()
    ->setIntegrityCheck(false)
    ->from(array('u' => 'upload'), array('ticket_id', 'name'))
    ->join(array('u2' => 'upload'), 'u.upload_id != u2.upload_id AND u.ticket_id = u2.ticket_id AND u.name = u2.name AND u.upload_id < u2.upload_id ', array('upload_id'));

$rowset = $resource->fetchAll($select);
if (!empty($rowset)) {
    foreach ($rowset as $row) {
        if (!in_array($row['upload_id'], $processed)) {
            $name = Default_Model_Upload::getUniqueName($row['name'], $row['ticket_id'], true);
            $db->update('upload', array('name' => $name), array('upload_id = ?' => $row['upload_id']));
            $processed[] = $row['upload_id'];
        }
    }
}

$stmt = $db->query('ALTER TABLE `upload` ADD COLUMN `uploader` INT UNSIGNED DEFAULT NULL AFTER `ticket_id`,
 ADD COLUMN `create_date` DATETIME DEFAULT NULL AFTER `uploader`,
 ADD UNIQUE KEY `IX_UPLOAD_TICKET_ID_NAME` (`ticket_id`, `name`(255)),
 ADD CONSTRAINT `FK_UPLOAD_UPLOADER` FOREIGN KEY `FK_UPLOAD_UPLOADER` (`uploader`)
    REFERENCES `user` (`user_id`)
    ON DELETE SET NULL
    ON UPDATE CASCADE;');
$stmt->closeCursor();
