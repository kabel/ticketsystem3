<?php

class Default_Model_Changeset extends Default_Model_Abstract
{
    protected static $_resourceNameInit = 'Default_Model_Db_Changeset';
    
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
     * @return Default_Model_Changeset
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
     * @return Default_Model_Changeset
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
     * @return Default_Model_Db_Changeset
     */
    public static function getResourceInstance()
    {
        return parent::getResourceInstance(self::$_resourceNameInit);
    }
    
    /**
     * 
     * @param string|array $ticketIds
     * @return array
     */
    public static function getDatesByTicketId($ticketIds)
    {
        $resource = self::getResourceInstance();
        $select = $resource->select()
            ->setIntegrityCheck(false)
            ->from(array('cs1' => $resource->getDbTable()->info(Zend_Db_Table::NAME)), array('ticket_id','created' => 'create_date'))
            ->join(array('d' => self::getDatesSelect($ticketIds)), 'cs1.changeset_id = d.created', array())
            ->join(array('cs2' => $resource->getDbTable()->info(Zend_Db_Table::NAME)), 'cs2.changeset_id = d.modified', array('modified' => 'create_date'));
        
        $rowset = $resource->fetchAll($select);
        $byTicketId = array();
        if (count($rowset)) {
            foreach ($rowset as $row) {
                $byTicketId[$row['ticket_id']] = $row;
            }
        }
        
        return $byTicketId;
    }
    
    /**
     * 
     * @param string|array $ticketIds The tickets searching by
     * @return Zend_Db_Table_Select
     */
    public static function getDatesSelect($ticketIds)
    {
        if (!is_array($ticketIds)) {
            $ticketIds = array($ticketIds);
        }
        
        $resource = self::getResourceInstance();
        $select = $resource->select()
            ->from(array('c' => $resource->getDbTable()->info(Zend_Db_Table::NAME)), array('ticket_id', 'MAX(changeset_id) AS modified', 'MIN(changeset_id) AS created'))
            ->where('ticket_id IN (?)', $ticketIds)
            ->group('ticket_id');
        
        return $select;
    }
    
    public static function getExpiredTicketIds($ticketIds)
    {
        $timeout = intval(Default_Model_Setting::get('expire_timeout'));
        if ($timeout < 1) {
            $timeout = 3;
        }
        
        $resource = self::getResourceInstance();
        $select = $resource->select()
            ->from(array('cs1' => $resource->getDbTable()->info(Zend_Db_Table::NAME)), array('ticket_id'))
            ->join(array('d' => self::getDatesSelect($ticketIds)), 'cs1.changeset_id = d.modified', array())
            ->where('cs1.create_date < DATE_SUB(NOW(), INTERVAL ? MONTH)', $timeout);
            
        $ids = array();
        $rowset = $resource->fetchAll($select);
        if (count($rowset)) {
            foreach ($rowset as $row) {
                $ids[] = $row['ticket_id'];
            }
        }
        
        return $ids;
    }
    
    public function __construct()
    {
        parent::_init(self::$_resourceNameInit);
    }
    
    public function getAttributeValues()
    {
        $values = array();
        $rowset = parent::findDependents('Default_Model_Table_AttributeValue');
        if (count($rowset)) {
            foreach ($rowset as $row) {
                $values[$row['attribute_id']] = $row['value'];
            }
        }
        
        return $values;
    }
    
    public function getChanger()
    {
        return parent::findParent('Default_Model_Table_User');
    }
}