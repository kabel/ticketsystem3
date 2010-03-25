<?php

class Default_Model_Db_Version extends Default_Model_Db_Abstract
{
    public function __construct()
    {
        $this->setDbTable('Default_Model_Table_Version');
    }
    
	/**
     * Retrieve singleton instance
     * 
     * @return Default_Model_Db_Version
     */
    public static function getInstance()
    {
        return parent::_getInstance(__CLASS__);
    }
}