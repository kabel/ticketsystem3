<?php

class Default_Model_Attribute extends Default_Model_Abstract
{
    const TYPE_TEXT     = 1;
    const TYPE_CHECKBOX = 2;
    const TYPE_SELECT   = 3;
    const TYPE_RADIO    = 4;
    const TYPE_TEXTAREA = 5;
    
    protected static $_resourceNameInit = 'Default_Model_Db_Attribute';
    protected static $_cache = array();
    protected static $_typeMapping = array(
        self::TYPE_TEXT => 'text',
        self::TYPE_CHECKBOX => 'checkbox',
        self::TYPE_SELECT => 'select',
        self::TYPE_RADIO => 'radio',
        self::TYPE_TEXTAREA => 'textarea'
    );
    public static $supportedSrc = array(
        'user' => 'Default_Model_User',
        'ugroup' => 'Default_Model_Ugroup'
    );
    
    /**
     * 
     * @param $key The name of the attribute to fetch from cache
     * @return Default_Model_Attribute
     */
    public static function get($key)
    {
        if (!isset(self::$_cache[$key])) {
            $select = self::getResourceInstance()->select()->where('name = ?', $key);
            self::fetchRow($select);
        }
        
        return self::$_cache[$key];
    }
    
    /**
     * 
     * @return array
     */
    public static function getAll($fromCache = false)
    {
        if (!$fromCache) {
            $select = self::getResourceInstance()->select()
                ->order('sort_order');
                
            self::fetchAll($select);
        }
        
        return self::$_cache;
    }
    
    protected static function _saveToCache(Default_Model_Attribute $obj) {
        if ($obj->hasData()) {
            self::$_cache[$obj->getData('name')] = $obj;
        }
    }
    
    /**
     * 
     * @return array
     */
    public static function find()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        $objs = call_user_func_array(array('Default_Model_Abstract', 'find'), $args);
        
        foreach ($objs as $obj) {
            self::_saveToCache($obj);
        }
        
        return $objs;
    }
    
    /**
     * 
     * @return Default_Model_Attribute
     */
    public static function findRow()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        $obj = call_user_func_array(array('Default_Model_Abstract', 'findRow'), $args);
        
        if ($obj !== null) {
            self::_saveToCache($obj);
        }
        
        return $obj;
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
        $objs = call_user_func_array(array('Default_Model_Abstract', 'fetchAll'), $args);
        
        foreach ($objs as $obj) {
            self::_saveToCache($obj);
        }
        
        return $objs;
    }
    
    /**
     * 
     * @return Default_Model_Attribute
     */
    public static function fetchRow()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        $obj = call_user_func_array(array('Default_Model_Abstract', 'fetchRow'), $args);
        
        if ($obj !== null) {
            self::_saveToCache($obj);
        }
        
        return $obj;
    }
    
	/**
     * Retrieve model resource
     *
     * @return Default_Model_Db_Attribute
     */
    public static function getResourceInstance()
    {
        return parent::getResourceInstance(self::$_resourceNameInit);
    }
    
    public static function getElementType($type)
    {
        return self::$_typeMapping[$type];
    }
    
    /**
     * 
     * @param string $needle
     * @param string $haystack
     * @return boolean
     */
    public static function inList($needle, $haystack)
    {
        if (empty($haystack)) {
            return false;
        }
        
        if (empty($needle)) {
            return true;
        }
        
        $list = array_map('trim', explode(',', $haystack));
        return in_array($needle, $list);
    }
    
    public function __construct()
    {
        parent::_init(self::$_resourceNameInit);
    }
    
    public function handleListValue()
    {
        $extra = Zend_Json::decode($this['extra']);
        
        if (isset($extra['add']) && $extra['add'] == 'user') {
            $user = Zend_Auth::getInstance()->getIdentity();
            return $user->username;
        }
        
        $handlers = Zend_Registry::get('bootstrap')->getOption('handlers');
        if (!empty($handlers) && isset($handlers[$this['name']]) && isset($handlers[$this['name']]['listValue'])) {
            $handler = $handlers[$this['name']]['listValue'];
            $method = $handler['method'];
            if (!empty($handler['class'])) {
                $class = $handler['class'];
                if (!empty($handler['type']) && $handler['type'] == 'instance') {
                    $class = new $class();
                }
                return call_user_func(array($class, $method));
            } else {
                return call_user_func($method);
            }
        }
        
        return '';
    }
    
    public function getMultiOptions($allowEmpty = true)
    {
        $options = array();
        $extra = Zend_Json::decode($this['extra']);
        
        if (isset($extra['src']) && array_key_exists($extra['src'], self::$supportedSrc)) {
            $modelClass = self::$supportedSrc[$extra['src']];
            $options = call_user_func(array($modelClass, 'getSelectOptions'), $allowEmpty);
        } elseif (isset($extra['options'])) {
            if ($allowEmpty) {
                $options[''] = '';
            }
            foreach ($extra['options'] as $opt) {
                $options[$opt] = $opt;
            }
        }
        
        return $options;
    }
}