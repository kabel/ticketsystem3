<?php

class Default_Model_AttributeValue extends Default_Model_Abstract
{
    protected static $_resourceNameInit = 'Default_Model_Db_AttributeValue';
    
    public static function find()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'find'), $args);
    }
    
    public static function findRow()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'findRow'), $args);
    }
    
    public static function fetchAll()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'fetchAll'), $args);
    }
    
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
     * @return Default_Model_Db_AttributeValue
     */
    public static function getResourceInstance()
    {
        return parent::getResourceInstance(self::$_resourceNameInit);
    }
    
    /**
     * 
     * @param string|array $attributeIds OPTIONAL
     * @param string|array $ticketIds OPTIONAL
     * @return Zend_Db_Table_Select
     */
    public static function getLatestSelect($attributeIds = null, $ticketIds = null, $beforeChangeset=null)
    {
        $resource = self::getResourceInstance();
        $db = $resource->getDbTable()->getAdapter();
        
        $joinCond = array('av.changeset_id = cs.changeset_id');
        if (!empty($attributeIds)) {
            if (!is_array($attributeIds)) {
                $attributeIds = array($attributeIds);
            }
            
            $op = 'IN (?)';
            if (count($attributeIds) == 1) {
                $op = '= ?';
            }
            
            $joinCond[] = $db->quoteInto("av.attribute_id {$op}", $attributeIds);
        }
        if (!empty($ticketIds)) {
            if (!is_array($ticketIds)) {
                $ticketIds = array($ticketIds);
            }
            
            $op = 'IN (?)';
            if (count($ticketIds) == 1) {
                $op = '= ?';
            }
            
            $joinCond[] = $db->quoteInto("cs.ticket_id {$op}", $ticketIds);
        }
        
        
        $select = $resource->select()
            ->setIntegrityCheck(false)
            ->from(array('av' => $resource->getDbTable()->info(Zend_Db_Table::NAME)), array('attribute_id'))
            ->join(array('cs' => 'changeset'), implode(' AND ', $joinCond), array('ticket_id', 'MAX(cs.changeset_id) AS changeset_id'))
            ->group(array('cs.ticket_id', 'av.attribute_id'));
        
        if ($beforeChangeset) {
            $select->where('cs.changeset_id < ?', $beforeChangeset);
        }
        
        return $select;
    }
    
    public static function getLatestByTicketId($ticketId)
    {
        $resource = self::getResourceInstance(); 
        $select = $resource->select()
            ->setIntegrityCheck(false)
            ->from(array('av1' => $resource->getDbTable()->info(Zend_Db_Table::NAME)))
            ->join(array('a' => 'attribute'), 'av1.attribute_id = a.attribute_id', array('name'))
            ->join(array('cs1' => 'changeset'), 'av1.changeset_id = cs1.changeset_id', array())
            ->join(array('lv' => self::getLatestSelect(null, $ticketId)),
            	'lv.attribute_id = av1.attribute_id AND lv.ticket_id = cs1.ticket_id AND lv.changeset_id = cs1.changeset_id', array());
        
        $rowset = $resource->fetchAll($select);
        $byName = array();
        if (count($rowset)) {
            foreach ($rowset as $row) {
                $byName[$row['name']] = $row;
            }
        }
        
        return $byName;
    }
    
    public static function getPrior($attributeId, $ticketId, $changesetId)
    {
        $resource = self::getResourceInstance(); 
        $select = $resource->select()
            ->from(array('av1' => $resource->getDbTable()->info(Zend_Db_Table::NAME)))
            ->join(array('lv' => self::getLatestSelect($attributeId, $ticketId, $changesetId)),
            	'lv.attribute_id = av1.attribute_id AND lv.changeset_id = av1.changeset_id', array());
        
        return $resource->fetchRow($select);
    }
    
    public static function flattenSrc($type, $ids, $not = false)
    {
        if (!is_array($ids)) {
            $ids = array($ids);
        }
        
        if (array_key_exists($type, Default_Model_Attribute::$supportedSrc)) {
            $select = Default_Model_Attribute::getResourceInstance()->select()
                ->where('extra REGEXP ?', '[{[:space:]]"src"[[:space:]]*:[[:space:]]*"' . $type . '"[,}[:space:]]');
            $attrs = Default_Model_Attribute::fetchAll($select);
            
            if (!empty($attrs)) {
                /* @var $resource Default_Model_Db_Abstract */
                $modelClass = Default_Model_Attribute::$supportedSrc[$type];
                $resource = call_user_func(array($modelClass, 'getResourceInstance'));
                $select = $resource->select()
                    ->where($resource->getIdFieldName() . ($not ? ' NOT' : '') . ' IN (?)', $ids);
                $collection = call_user_func(array($modelClass, 'fetchAll'), $select);
                
                if (!empty($collection)) {
                    foreach ($attrs as $attr) {
                        foreach ($collection as $model) {
                            $db = Zend_Registry::get('bootstrap')->getResource('db');
                            /* @var $db Zend_Db_Adapter_Pdo_Mysql */
                            $db->update('attribute_value', array('value' => (string)$model), array(
                                'attribute_id = ?' => $attr->getId(),
                                'value = ?' => $model->getId()
                            ));
                        }
                    }
                }
            }
        }
    }
    
    public static function prepareCc($cc)
    {
        $recipients = array();
        $items = explode(',', $cc);
        
        foreach ($items as $item) {
            $item = trim($item);
            if (empty($item)) {
                continue;
            }
            $validator = new Zend_Validate_EmailAddress();
            
            if ($validator->isValid($item)) {
                $recipients[] = array($item, '');
            } else {
                $pos = strpos($item, ':');
                if ($pos !== false) {
                    $username = substr($item, 0, $pos);
                    $loginType = substr($item, $pos + 1);
                    if ($loginType == Default_Model_User::LOGIN_TYPE_LEGACY || $loginType == Default_Model_User::LOGIN_TYPE_CAS) {
                        $select = self::getResourceInstance()->select()->where('username = ?', $item)->where('login_type = ?', $loginType);
                        $user = Default_Model_User::fetchRow($select);
                    } else {
                        $user = null;
                    }
                } else {
                    $select = self::getResourceInstance()->select()->where('username = ?', $item)->order('login_type');
                    $user = Default_Model_User::fetchRow($select);
                }
                
                if ($user !== null && !empty($user['email'])) {
                    $recipients[] = array($user['email'], $user['info']);
                }
            }
        }
        
        return $recipients;
    }
    
    public function __construct()
    {
        parent::_init(self::$_resourceNameInit);
    }
}