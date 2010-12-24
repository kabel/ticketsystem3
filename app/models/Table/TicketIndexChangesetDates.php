<?php

class Default_Model_Table_TicketIndexChangesetDates extends Default_Model_Table_Abstract
{
    protected $_installSql = array(
    	"CREATE TABLE `ticket_index_changeset_dates` (
  `ticket_id` int(10) unsigned NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `changeset_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ticket_id`,`type`),
  KEY `FK_TICKET_INDEX_CHANGESETS_CHANGESET_ID` (`changeset_id`),
  CONSTRAINT `FK_TICKET_INDEX_CHANGESETS_TICKET_ID` FOREIGN KEY (`ticket_id`) REFERENCES `ticket` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_TICKET_INDEX_CHANGESETS_CHANGESET_ID` FOREIGN KEY (`changeset_id`) REFERENCES `changeset` (`changeset_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
    );
    protected $_installDepends = array(
        'Default_Model_Table_Ticket',
        'Default_Model_Table_Changeset'
    );

    protected $_name    = 'ticket_index_changeset_dates';
    protected $_primary = array('ticket_id', 'type');

    protected $_referenceMap = array(
        'Ticket' => array(
            'columns' => array('ticket_id'),
            'refTableClass' => 'Default_Model_Table_Ticket'
        ),
        'Changeset' => array(
            'columns' => array('changeset_id'),
            'refTableClass' => 'Default_Model_Table_Changeset'
        )
    );
}