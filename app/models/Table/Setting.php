<?php

class Default_Model_Table_Setting extends Default_Model_Table_Abstract
{
    protected $_installSql = array(
    	"CREATE TABLE `setting` (
  `setting_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `value` tinytext NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `IX_SETTING_NAME` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;",
    "INSERT INTO `setting` VALUES
	(1, 'global_cc', '', 1),
	(2, 'restrict_guest', '1', 2),
	(3, 'restrict_view_user', '1', 2),
	(4, 'allow_view_group', '1', 2),
	(5, 'site_title', 'TicketSystem3', 1),
	(6, 'default_page_size', '15', 3),
	(7, 'restrict_late_uploads', '0', 2),
	(8, 'site_banner', 'TicketSystem3', 1),
	(9, 'allow_old_password', '1', 2),
	(10, 'always_notify_reporter', '1', 2),
	(11, 'always_notify_owner', '0', 2),
	(12, 'always_notify_updater', '0', 2),
	(13, 'use_public_cc', '1', 2),
	(14, 'notification_from', 'nobody@localhost', 1),
	(15, 'notification_from_name', 'TicketSystem3', 1),
	(16, 'notification_replyto', '', 1);"
    );
    
    protected $_name    = 'setting';
    protected $_primary = 'setting_id';
}