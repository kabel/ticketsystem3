<?php

class Default_Model_User extends Default_Model_Abstract
{   
    const LOGIN_TYPE_LEGACY = 1;
    const LOGIN_TYPE_CAS    = 2;
    
    const STATUS_ACTIVE     = 1;
    const STATUS_BANNED     = 2;
    
    const LEVEL_ADMIN       = 1;
    const LEVEL_USER        = 2;
    const LEVEL_GUEST       = 3;
    const LEVEL_MODERATOR   = 4;
    
    protected static $_resourceNameInit = 'Default_Model_Db_User';
    protected static $_levelStringCache;
    protected static $_loginTypeStringCache;
    
    protected $_group;
    protected $_membership;
    
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
     * @return Default_Model_User
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
     * @return Default_Model_User
     */
    public static function fetchRow()
    {
        $class = __CLASS__;
        $args = func_get_args();
        array_unshift($args, $class);
        return call_user_func_array(array('Default_Model_Abstract', 'fetchRow'), $args);
    }
    
    /**
     * 
     * @return Default_Model_User
     */
    public static function fetchActive()
    {
        $user = Zend_Auth::getInstance()->getIdentity();
        $userModel = self::findRow($user->user_id);
        
        if (empty($userModel) || $userModel['status'] == self::STATUS_BANNED) {
            /* @var $redirector Zend_Controller_Action_Helper_Redirector */
            $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
            $redirector->gotoSimple('logout', 'auth', null, array('revoke' => true));
        }
        
        return $userModel;
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
                    if ($loginType == self::LOGIN_TYPE_LEGACY || $loginType == self::LOGIN_TYPE_CAS) {
                        $select = self::getResourceInstance()->select()->where('username = ?', $item)->where('login_type = ?', $loginType);
                        $user = self::fetchRow($select);
                    } else {
                        $user = null;
                    }
                } else {
                    $select = self::getResourceInstance()->select()->where('username = ?', $item)->order('login_type');
                    $user = self::fetchRow($select);
                }
                
                if ($user !== null && !empty($user['email'])) {
                    $recipients[] = array($user['email'], $user['info']);
                }
            }
        }
        
        return $recipients;
    }
    
    /**
     * Returns the string representation of a user level
     * 
     * @param int $level
     * @return string
     */
    public static function getLevelStringValue($level)
    {
        $levelArray = self::getLevelStringArray();
        return $levelArray[$level];
    }
    
    public static function getSelectOptions($withEmpty = false)
    {
        $options = array();
        
        if ($withEmpty) {
            $options[] = '';
        }
        
        $select = self::getResourceInstance()->select()->order('username');
        $users = self::fetchAll($select);
        
        foreach ($users as $user) {
            $options[(string)$user['user_id']] = $user['username'];
        }
        
        return $options;
    }
    
    /**
     * Returns an array of the uses levels with the value being
     * human readable
     *  
     * @return array
     */
    public static function getLevelStringArray()
    {
        if (null === self::$_levelStringCache) {
            self::$_levelStringCache = array(
                self::LEVEL_GUEST     => 'Guest',
                self::LEVEL_USER      => 'User',
                self::LEVEL_MODERATOR => 'Moderator',
                self::LEVEL_ADMIN     => 'Admin'
            );
        }
        
        return self::$_levelStringCache;
    }
    
    public static function getLoginTypeStringArray()
    {
    	if (null === self::$_loginTypeStringCache) {
    		self::$_loginTypeStringCache = array(
    			self::LOGIN_TYPE_LEGACY => 'Legacy',
    			self::LOGIN_TYPE_CAS    => 'CAS'
    		);
    	}
    	
    	return self::$_loginTypeStringCache;
    }
    
    public static function getStatusStringValue($status)
    {
        $statusArray = self::getStatusStringArray();
        return $statusArray[$status];
    }
    
    public static function getStatusStringArray()
    {
        return array(
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_BANNED => 'Disabled'
        );
    }
    
    public static function getLevelCounts()
    {
        $counts = array(
            self::LEVEL_GUEST     => 0,
            self::LEVEL_USER      => 0,
            self::LEVEL_MODERATOR => 0,
            self::LEVEL_ADMIN     => 0
        );
        
        $resource = self::getResourceInstance();
        $select = $resource->select()
            ->from($resource->getDbTable()->info(Zend_Db_Table::NAME), array (
                'level',
            	'COUNT(user_id) AS count'
            ))
            ->group('level');
        
        $rowset = $resource->fetchAll($select);
        if (count($rowset)) {
            foreach ($rowset as $row) {
                $counts[$row['level']] = $row['count'];
            }
        }
        
        return $counts;
    }
    
    public function __construct()
    {
        parent::_init(self::$_resourceNameInit);
    }
    
    /**
     * Gets the group row for this user
     * 
     * @return Zend_Db_Table_Row_Abstract
     */
    public function getGroup()
    {
        if (null === $this->_group) {
            $this->_group = parent::findParent('Default_Model_Table_Ugroup');
        }
        
        return $this->_group;
    }
    
    public function getMembership()
    {
        if (null === $this->_membership) {
            $this->_membership = parent::findManyToManyRowset('Default_Model_Table_Ugroup', 'Default_Model_Table_Membership');
        }
        
        return $this->_membership;
    }
    
    /**
     * 
     * @return array
     */
    public function getAllGroups($onlyMembership = false)
    {
        $groups = array();
        
        $home = $this->getGroup();
        
        if (empty($home)) {
            return $groups;
        }
        
        if (!$onlyMembership) {
            $groups[] = $home;
        }
        
        $membership = $this->getMembership();
        
        if (!empty($membership)) {
            foreach ($membership as $row) {
                $groups[] = $row;
            }
        }
        
        return $groups;
    }
    
    /**
     * 
     * @return array
     */
    public function getGroupIds($onlyMembership = false)
    {
        $ids = array();
        foreach ($this->getAllGroups($onlyMembership) as $group) {
            $ids[] = $group['ugroup_id'];
        }
        
        return $ids;
    }
    
    public function __toString()
    {
        return $this['username'];
    }
}