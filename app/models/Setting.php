<?php

class Default_Model_Setting extends Default_Model_Abstract
{
    const TYPE_STRING = 1;
    const TYPE_BOOL   = 2;
    const TYPE_INT    = 3;
    
    protected static $_resourceNameInit = 'Default_Model_Db_Setting';
    protected static $_cache = array();
    protected static $_hintCache;
    
    /**
     * Fetch a named setting from cache or DB
     * 
     * @param string $key The name of the setting to fetch from cache
     * @return mixed
     */
    public static function get($key)
    {
        if (!isset(self::$_cache[$key])) {
            $select = self::getResourceInstance()->select()->where('name = ?', $key);
            self::fetchRow($select);
        }
        
        if (!isset(self::$_cache[$key])) {
            $defaults = self::getDefaults(); 
            return isset($defaults[$key]) ? $defaults[$key] : false;
        }
        
        return self::$_cache[$key];
    }
    
    protected static function _saveToCache(Default_Model_Setting $obj) {
        if ($obj->hasData()) {
            self::$_cache[$obj->getData('name')] = $obj->getData('value');
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
     * @return Default_Model_Setting
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
     * @return Default_Model_Setting
     */
    public static function fetchRow()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        $obj = call_user_func_array(array('Default_Model_Abstract', 'fetchRow'), $args);
        
        if ($obj != null) {
            self::_saveToCache($obj);
        }
        
        return $obj;
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
    
    public static function getHint($name)
    {
        $hintArray = self::getHintArray();
        if (isset($hintArray[$name])) {
            return $hintArray[$name];
        }
        
        return '';
    }
    
    public static function getHintArray()
    {
        if (null === self::$_hintCache) {
            self::$_hintCache = array(
                'allow_old_password'     => 'Allow users to login with old password hash (on login failure)',    
                'allow_view_group'       => 'Users will also see tickets assigned to their group (ignored if restrict_view_user is NO)',
                'always_notify_owner'    => 'Always send notifications to the ticket owner',
                'always_notify_reporter' => 'Always send notifications to the ticket reporter',
                'always_notify_updater'  => 'Always send notifications to the user updating a ticket',
                'default_page_size'      => 'The default number of items to show on a pages with page control',
                'global_cc'              => 'A comma separated list of usernames/e-mails to copy ALL notifications to',
                'lockout_cas'            => 'Prevent new CAS accounts from login form',
                'notification_from'      => '',
                'notification_from_name' => '',
                'notification_replyto'   => '',
                'restrict_guest'         => 'Guest accounts will not be able to create tickets',
                'restrict_late_uploads'  => 'Disable uploading after a ticket is created',
                'restrict_view_user'     => 'Users will see only tickets reported by them',
                'site_banner'            => 'A string that is displayed on every page',
                'site_title'             => 'A string that is appended to every page title',
                'use_public_cc'          => 'Copies of noticiations should send via Cc header (otherwise Bcc - hidden)'
            );
        }
        
        return self::$_hintCache;
    }
    
    public static function getDefaults()
    {
        return array(
            'allow_old_password'     => array(1, self::TYPE_BOOL),
            'allow_view_group'       => array(1, self::TYPE_BOOL),
            'always_notify_owner'    => array(0, self::TYPE_BOOL),
            'always_notify_reporter' => array(1, self::TYPE_BOOL),
            'always_notify_updater'  => array(0, self::TYPE_BOOL),
            'default_page_size'      => array(15, self::TYPE_INT),
            'global_cc'              => array('', self::TYPE_STRING),
            'lockout_cas'            => array(0, self::TYPE_BOOL),
            'notification_from'      => array('nobody@localhost', self::TYPE_STRING),
            'notification_from_name' => array('TicketSystem3', self::TYPE_STRING),
            'notification_replyto'   => array('', self::TYPE_STRING),
            'restrict_guest'         => array(1, self::TYPE_BOOL),
            'restrict_late_uploads'  => array(0, self::TYPE_BOOL),
            'restrict_view_user'     => array(1, self::TYPE_BOOL),
            'site_banner'            => array('TicketSystem3', self::TYPE_STRING),
            'site_title'             => array('TicketSystem3', self::TYPE_STRING),
            'use_public_cc'          => array(1, self::TYPE_BOOL)
        );
    }
    
    public static function resetDefaults($onlyReload = false)
    {
        $settings = self::fetchAll();
        $byName = array();
        foreach ($settings as $setting) {
            $byName[$setting['name']] = $setting;
        }
        
        foreach (self::getDefaults() as $name => $def) {
            if (empty($byName[$name])) {
                $setting = new Default_Model_Setting();
                $setting->setData(array(
                    'name' => $name,
                    'value' => $def[0],
                    'type' => $def[1]
                ))->save();
            } elseif (!$onlyReload) {
                $setting = $byName[$name];
                if ($setting['value'] != $def[0]) {
                    $setting['value'] = $def[0];
                    $setting->save();
                }
            }
        }
    }
        
    public function __construct()
    {
        parent::_init(self::$_resourceNameInit);
    }
}