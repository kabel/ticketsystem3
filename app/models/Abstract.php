<?php

abstract class Default_Model_Abstract implements ArrayAccess
{
    /**
     * Setter/Getter underscore transformation cache
     *
     * @var array
     */
    protected static $_underscoreCache = array();
    
    /**
     * Gets a collection from on primary key(s)
     * 
     * @param mixed $key The value(s) of the primary keys.
     * @return array
     */
    public static function find()
    {
        $args = func_get_args();
        if (count($args) < 1 || empty($args[0])) {
            throw new Exception('Missing leaf class for instantiation');
        }
        
        $class = $args[0];
        unset($args[0]);
        
        return self::_getCollection($class, 'find', $args);
    }
    
    /**
     * 
     * @return Default_Model_Abstract
     */
    public static function findRow()
    {
        $args = func_get_args();
        $collection = call_user_func_array(array('Default_Model_Abstract', 'find'), $args);
        return array_shift($collection);
    }
    
    /**
     * 
     * @return Default_Model_Abstract
     */
    public static function fetchRow()
    {
        $args = func_get_args();
        if (count($args) < 1 || empty($args[0])) {
            throw new Exception('Missing leaf class for instantiation');
        }
        
        $class = $args[0];
        unset($args[0]);
        
        $obj = new $class;
        $row = call_user_func_array(array($obj->getResource(), 'fetchRow'), $args);
        
        if (!empty($row)) {
            $obj->setData($row);
            return $obj;
        }
        
        return null;
    }
    
    /**
     * 
     * @return array
     */
    public static function fetchAll()
    {
        $args = func_get_args();
        if (count($args) < 1 || empty($args[0])) {
            throw new Exception('Missing leaf class for instantiation');
        }
        
        $class = $args[0];
        unset($args[0]);
        
        return self::_getCollection($class, 'fetchAll', $args);
    }
    
    private static function _getCollection($class, $func, $args)
    {
        $template = new $class();
        $rowset = call_user_func_array(array($template->getResource(), $func), $args);
        
        $collection = array();
        if (count($rowset)) {
            $usedTemplate = false;
            foreach ($rowset as $row) {
                if (!$usedTemplate) {
                    $obj = $template;
                    $usedTemplate = true;
                } else {
                    $obj = new $class();
                }
                
                $obj->setData($row);
                $collection[$obj[$obj->getIdFieldName()]] = $obj;
            }
        }
        
        return $collection;
    }
    
    /**
     * Retrieve model resource
     *
     * @return Default_Model_Db_Abstract
     */
    public static function getResourceInstance()
    {
        $args = func_get_args();
        if (empty($args[0])) {
            throw new Exception('Missing the name of the resource to get');
        }
        return call_user_func(array($args[0], 'getInstance'));
    }
    
    protected static function _where($condition, $value, $type = null)
    {
        /* @var $db Zend_Db_Adapter_Pdo_Mysql */
        $db = Zend_Registry::get('bootstrap')->getResource('db');
        $condition = $db->quoteInto($condition, $value, $type);
        
        return "($condition)";
    }
    
    protected static function _processWhere($values, $cond=true)
    {
        if (!is_array($values)) {
            $values = array($values);
        }
        
        $where = array();
        
        if (isset($values['cond'])) {
            $where[] = self::_processWhere($values['values'], $values['cond']);
        } else {
            foreach ($values as $value) {
                $prefix = "";
                if (!empty($where)) {
                    if ($cond) {
                        $prefix = 'AND' . ' ';
                    } else {
                        $prefix = 'OR' . ' ';
                    }
                }
                
                if (is_array($value)) {
                    $where[] = $prefix . self::_processWhere($value);
                } else {
                    $where[] = $prefix . $value;
                }
            }
        }
        
        $count = count($where);
        $where = implode(' ', $where);
        
        if ($count > 1) {
            $where = "($where)";
        }
        return $where;
    }
    
    /**
     * Object attributes
     *
     * @var Zend_Db_Table_Row_Abstract
     */
    protected $_data = null;
    
    /**
     * Name of the resource model
     *
     * @var string
     */
    protected $_resourceName;

    /**
     * Standard model initialization
     *
     * @param string $resourceModel
     * @param string $idFieldName
     * @return Default_Model_Abstract
     */
    protected function _init($resourceModel)
    {
        $this->_setResourceModel($resourceModel);
        return $this;
    }
    
	/**
     * Set/Get attribute wrapper
     *
     * @param   string $method
     * @param   array $args
     * @return  mixed
     */
    public function __call($method, $args)
    {
        switch (substr($method, 0, 3)) {
            case 'get' :
                $key = $this->_underscore(substr($method,3));
                $data = $this->getData($key);
                return $data;

            case 'set' :
                $key = $this->_underscore(substr($method,3));
                $result = $this->setData($key);
                return $result;

            case 'uns' :
                $key = $this->_underscore(substr($method,3));
                $result = $this->unsetData($key);
                return $result;

            case 'has' :
                $key = $this->_underscore(substr($method,3));
                return $this->hasData($key);
        }
        throw new Exception("Invalid method ".get_class($this)."::".$method."(".print_r($args,1).")");
    }

    /**
     * Set resource name
     *
     * @param string $resourceName
     */
    protected function _setResourceModel($resourceName)
    {
        $this->_resourceName = $resourceName;
    }

    /**
     * Get resource instance
     *
     * @return Default_Model_Db_Abstract
     */
    protected function _getResource()
    {
        if (empty($this->_resourceName)) {
            throw new Zend_Application_Exception('Resource is not set');
        }

        return self::getResourceInstance($this->_resourceName);
    }
    
    /**
     * 
     * @param $withFromPart
     * @return Zend_Db_Table_Select
     */
    public function select($withFromPart=false)
    {
        return $this->_getResource()->select($withFromPart);
    }

    /**
     * Retrieve identifier field name for model
     *
     * @return string
     */
    public function getIdFieldName()
    {
        return $this->_getResource()->getIdFieldName();
    }

    /**
     * Retrieve model object identifier
     *
     * @return mixed
     */
    public function getId()
    {
        if ($fieldName = $this->getIdFieldName()) {
            return $this->getData($fieldName);
        } 
        
        return null;
    }

    /**
     * Declare model object identifier value
     *
     * @param   mixed $id
     * @return  Default_Model_Abstract
     */
    public function setId($id)
    {
        if ($this->getIdFieldName()) {
            $this->setData($this->getIdFieldName(), $id);
        }
        
        return $this;
    }
    
    /**
     * Get row data
     * 
     * @param string $key
     * @return mixed
     */
    public function getData($key='')
    {
        if (empty($key)) {
            return $this->_data;
        }
        
        return $this->_data[$key];
    }
    
	/**
     * Overwrite data in the object.
     *
     * $key can be string or array.
     * If $key is string, the attribute value will be overwritten by $value
     *
     * If $key is an array, it will overwrite all the data in the object.
     *
     * $isChanged will specify if the object needs to be saved after an update.
     *
     * @param string|array $key
     * @param mixed $value
     * @return Default_Model_Abstract
     */
    public function setData($key, $value=null)
    {
        if ($key instanceof Zend_Db_Table_Row_Abstract) {
            $this->_data = $key;
        } else {
            if (is_null($this->_data)) {
                $this->_data = $this->_getResource()->getDbTable()->createRow();
            }
            
            if(is_array($key)) {
                $this->_data->setFromArray($key);
            } else {
                $this->_data[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * Retrieve model resource name
     *
     * @return string
     */
    public function getResourceName()
    {
        return $this->_resourceName;
    }

    /**
     * Save object data
     *
     * @return Default_Model_Abstract
     */
    public function save()
    {
        //$this->_getResource()->beginTransaction();
        try {
            $this->_data->save();
            //$this->_getResource()->commit();
        }
        catch (Exception $e){
            //$this->_getResource()->rollBack();
            throw $e;
        }
        return $this;
    }

    /**
     * Delete object from database
     *
     * @return Default_Model_Abstract
     */
    public function delete()
    {
        //$this->_getResource()->beginTransaction();
        try {
            $this->_data->delete($this);
            //$this->_getResource()->commit();
        }
        catch (Exception $e){
            //$this->_getResource()->rollBack();
            throw $e;
        }
        return $this;
    }

    /**
     * Retrieve model resource
     *
     * @return Default_Model_Db_Abstract
     */
    public function getResource()
    {
        return $this->_getResource();
    }
    
    /**
     * 
     * @param string|Zend_Db_Table_Abstract  $dependentTable
     * @param string                         OPTIONAL $ruleKey 
     * @param Zend_Db_Table_Select           OPTIONAL $select
     * @return Zend_Db_Table_Rowset_Abstract
     */
    public function findDependents($dependentTable, $ruleKey = null, Zend_Db_Table_Select $select = null)
    {
        if (!$this->hasData()) {
            return null;
        }
        
        return $this->_data->findDependentRowset($dependentTable, $ruleKey, $select);
    }
    
	/**
     * 
     * @param string|Zend_Db_Table_Abstract  $parentTable
     * @param string                         OPTIONAL $ruleKey 
     * @param Zend_Db_Table_Select           OPTIONAL $select
     * @return Zend_Db_Table_Row_Abstract
     */
    public function findParent($parentTable, $ruleKey = null, Zend_Db_Table_Select $select = null)
    {
        if (!$this->hasData()) {
            return null;
        }
        
        return $this->_data->findParentRow($parentTable, $ruleKey, $select);
    }
    
    /**
     * If $key is empty, checks whether there's any data in the object
     * Otherwise checks if the specified attribute is set.
     *
     * @param string $key
     * @return boolean
     */
    public function hasData($key='')
    {
        if (empty($key) || !is_string($key)) {
            return !empty($this->_data);
        }
        return isset($this->_data[$key]);
    }
    
    /**
     * Unset data from the object.
     *
     * $key can be a string only. Array will be ignored.
     *
     * $isChanged will specify if the object needs to be saved after an update.
     *
     * @param string $key
     * @param boolean $isChanged
     * @return Default_Model_Abstract
     */
    public function unsetData($key=null)
    {
        if (is_null($key)) {
            $this->_data = null;
        } else {
            unset($this->_data[$key]);
        }
        return $this;
    }
    
    public function offsetExists($offset)
    {
        return $this->hasData($offset);
    }
    
    public function offsetUnset($offset)
    {
        $this->unsetData($offset);
    }
    
    public function offsetGet($offset)
    {
        return $this->getData($offset);
    }
    
    public function offsetSet($offset, $value)
    {
        $this->setData($offset, $value);
    }
    
    public function toArray()
    {
        return $this->_data->toArray();
    }
    
    /**
     * Converts field names for setters and geters
     *
     * $this->setMyField($value) === $this->setData('my_field', $value)
     * Uses cache to eliminate unneccessary preg_replace
     *
     * @param string $name
     * @return string
     */
    protected function _underscore($name)
    {
        if (isset(self::$_underscoreCache[$name])) {
            return self::$_underscoreCache[$name];
        }
        $result = strtolower(preg_replace('/(.)([A-Z])/', "$1_$2", $name));
        self::$_underscoreCache[$name] = $result;
        return $result;
    }
}