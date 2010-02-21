<?php

class Default_Model_Membership extends Default_Model_Abstract
{
    protected static $_resourceNameInit = 'Default_Model_Db_Membership';
    
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
     * @return Default_Model_Membership
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
     * @return Default_Model_Membership
     */
    public static function fetchRow()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'fetchRow'), $args);
    }
    
    public static function getUserCounts()
    {
        $counts = array();
        $resource = self::getResourceInstance();
        $select = $resource->select()
            ->setIntegrityCheck(false)
            ->from(array('m' => $resource->getDbTable()->info(Zend_Db_Table::NAME)), array (
                'ugroup_id',
            	'COUNT(`user_id`) AS count'
            ))
            ->group('m.ugroup_id');
        
        $rowset = $resource->fetchAll($select);
        if (count($rowset)) {
            foreach ($rowset as $row) {
                $counts[$row['ugroup_id']] = $row['count'];
            }
        }
        
        return $counts;
    }
    
    /**
     * Retrieve model resource
     *
     * @return Default_Model_Db_User
     */
    public static function getResourceInstance()
    {
        return parent::getResourceInstance(self::$_resourceNameInit);
    }
    
    public function __construct()
    {
        parent::_init(self::$_resourceNameInit);
    }
}