<?php

class Default_Model_Ugroup extends Default_Model_Abstract
{
    protected static $_resourceNameInit = 'Default_Model_Db_Ugroup';
    
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
     * @return Default_Model_Ugroup
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
     * @return Default_Model_Ugroup
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
            ->from(array('g' => $resource->getDbTable()->info(Zend_Db_Table::NAME)), array (
                'ugroup_id',
            	'COUNT(`user_id`) AS count'
            ))
            ->joinLeft(array('u' => 'user'), 'u.ugroup_id = g.ugroup_id', array())
            ->group('g.ugroup_id');
        
        $rowset = $resource->fetchAll($select);
        if (count($rowset)) {
            foreach ($rowset as $row) {
                $counts[$row['ugroup_id']] = $row['count'];
            }
        }
        
        foreach (Default_Model_Membership::getUserCounts() as $id => $count) {
            $counts[$id] += $count;
        }
        
        return $counts;
    }
    
    public static function getSelectOptions($withEmpty = true)
    {
        $options = array();
        
        if ($withEmpty) {
            $options[] = '';
        }
        
        $select = self::getResourceInstance()->select()->order('name');
        $groups = self::fetchAll($select);
        
        foreach ($groups as $group) {
            $options[(string)$group['ugroup_id']] = (string)$group;
        }
        
        return $options;
    }
    
	/**
     * Retrieve model resource
     *
     * @return Default_Model_Db_Abstract
     */
    public static function getResourceInstance()
    {
        return parent::getResourceInstance(self::$_resourceNameInit);
    }
    
    public function __construct()
    {
        parent::_init(self::$_resourceNameInit);
    }
    
    /**
     * Gets a rowset of Users that are members of this group
     * 
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function getUsers()
    {
        return parent::findDependents('Default_Model_Table_User');
    }
    
    public function getMembership()
    {
        return parent::findManyToManyRowset('Default_Model_Table_User', 'Default_Model_Table_Membership');
    }
    
    public function __toString()
    {
        if (!$this->hasData()) {
            return 'None';
        }
        
        return $this['name'] . (empty($this['shortname']) ? '' : ' (' . $this['shortname'] . ')');
    }
    
    public function toHtml()
    {
        if (!$this->hasData()) {
            return (string)$this;
        }
        
        $output = $this['name'];
        
        if (!empty($this['shortname'])) {
            $output .= ' <span class="shortname">(' . $this['shortname'] . ')</span>';
        }
        
        return $output;
    }
}