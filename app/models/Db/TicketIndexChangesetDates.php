<?php

class Default_Model_Db_TicketIndexChangesetDates extends Default_Model_Db_Abstract
{
    public function __construct()
    {
        $this->setDbTable('Default_Model_Table_TicketIndexChangesetDates');
    }

	/**
     * Retrieve singleton instance
     *
     * @return Default_Model_Db_TicketIndexChangesetDates
     */
    public static function getInstance()
    {
        return parent::_getInstance(__CLASS__);
    }
}