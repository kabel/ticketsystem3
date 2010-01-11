<?php

class Default_Model_Db_AttributeValue extends Default_Model_Db_Abstract
{
    public function __construct()
    {
        $this->setDbTable('Default_Model_Table_AttributeValue');
    }
    
	/**
     * Retrieve singleton instance
     * 
     * @return Default_Model_Db_AttributeValue
     */
    public static function getInstance()
    {
        return parent::_getInstance(__CLASS__);
    }
}