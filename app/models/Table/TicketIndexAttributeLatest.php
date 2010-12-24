<?php

class Default_Model_Table_TicketIndexAttributeLatest extends Default_Model_Table_Abstract
{
    protected $_installSql = array(
    	"CREATE TABLE `ticket_index_attribute_latest` (
  `ticket_id` int(10) unsigned NOT NULL,
  `attribute_id` int(10) unsigned NOT NULL,
  `changeset_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ticket_id`,`attribute_id`),
  KEY `FK_TICKET_INDEX_CHANGESET_ID` (`changeset_id`),
  KEY `FK_TICKET_INDEX_ATTRIBUTE_ID` (`attribute_id`),
  CONSTRAINT `FK_TICKET_INDEX_ATTRIBUTE_ID` FOREIGN KEY (`attribute_id`) REFERENCES `attribute` (`attribute_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_TICKET_INDEX_CHANGESET_ID` FOREIGN KEY (`changeset_id`) REFERENCES `changeset` (`changeset_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_TICKET_INDEX_TICKET_ID` FOREIGN KEY (`ticket_id`) REFERENCES `ticket` (`ticket_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
    );
    protected $_installDepends = array(
        'Default_Model_Table_Ticket',
        'Default_Model_Table_AttributeValue' //implicit dependency on changeset and attribute
    );

    protected $_name    = 'ticket_index_attribute_latest';
    protected $_primary = array('ticket_id', 'attribute_id');

    protected $_referenceMap = array(
        'Ticket' => array(
            'columns' => array('ticket_id'),
            'refTableClass' => 'Default_Model_Table_Ticket'
        ),
        'Changeset' => array(
            'columns' => array('changeset_id'),
            'refTableClass' => 'Default_Model_Table_Changeset'
        ),
        'Attribute' => array(
        	'columns' => array('attribute_id'),
            'refTableClass' => 'Default_Model_Table_Attribute'
        ),
        'AttributeValue' => array(
            'columns' => array('changeset_id', 'attribute_id'),
            'refTableClass' => 'Default_Model_Table_AttributeValue'
        )
    );
}