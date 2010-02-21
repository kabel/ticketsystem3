<?php

class Default_Model_Db_Membership extends Default_Model_Db_Abstract
{
    public function __construct()
    {
        $this->setDbTable('Default_Model_Table_Membership');
    }
    
	/**
     * Retrieve singleton instance
     * 
     * @return Default_Model_Db_Membership
     */
    public static function getInstance()
    {
        return parent::_getInstance(__CLASS__);
    }
}