<?php

class Default_Model_Db_User extends Default_Model_Db_Abstract
{
    public function __construct()
    {
        $this->setDbTable('Default_Model_Table_User');
    }
    
	/**
     * Retrieve singleton instance
     * 
     * @return Default_Model_Db_User
     */
    public static function getInstance()
    {
        return parent::_getInstance(__CLASS__);
    }
}