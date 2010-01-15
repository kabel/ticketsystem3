<?php

class Default_Model_Upload extends Default_Model_Abstract
{
    protected static $_resourceNameInit = 'Default_Model_Db_Upload';
	
    /**
     * 
     * @return array
     */
	public static function find()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'find'), $args);
    }
    
    /**
     * 
     * @return Default_Model_Upload
     */  
    public static function findRow()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'findRow'), $args);
    }
    
    /**
     * 
     * @return array
     */
    public static function fetchAll()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'fetchAll'), $args);
    }
    
    /**
     * 
     * @return Default_Model_Upload
     */    
    public static function fetchRow()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'fetchRow'), $args);
    }
    
	/**
     * Retrieve model resource
     *
     * @return Default_Model_Db_Upload
     */
    public static function getResourceInstance()
    {
        return parent::getResourceInstance(self::$_resourceNameInit);
    }
    
    public static function getIdFromNameAndTicket($name, $ticketId)
    {
    	$select = self::getResourceInstance()->select()->where('name = ?', $name)->where('ticket_id = ?', $ticketId);
    	if ($upload = self::fetchRow($select)) {
    		return $upload->getId();
    	}
    	
    	return null;
    }
    
    public function __construct()
    {
        parent::_init(self::$_resourceNameInit);
    }
}