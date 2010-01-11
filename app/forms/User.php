<?php

class Default_Form_User extends Default_Form_Profile
{
    public function init()
    {
        $this->setMethod('post');
        $this->setAttrib('class', 'form-full');
    }
    
    public function setupForUser(Default_Model_User $user=null)
    {
        if (is_null($user) || !$user->hasData()) {
            $this->_addFields();
        } else {
            $this->_addFields($user['user_id'], $user['login_type'] == Default_Model_User::LOGIN_TYPE_CAS, $user['status'] == Default_Model_User::STATUS_BANNED);
            $this->populate(array(
                'username_new' => $user['username'],
                'level' => $user['level'],
                'email' => $user['email'],
                'info' => $user['info'],
                'group' => $user['ugroup_id']
            ));
        }
    }
    
    protected function _addFields($userId=-1, $isCAS=false, $isBanned=false)
    {
        $isNew = ($userId === -1);
        if (!$isCAS) {
            $db = Zend_Registry::get('bootstrap')->getResource('db');
            $clause = array($db->quoteInto('login_type != ?', Default_Model_User::LOGIN_TYPE_CAS));
            if (!$isNew) {
                $clause[] = $db->quoteInto('user_id != ?', $userId);
            }
            $this->addElement('text', 'username_new', array(
                'validators' => array(
    				'alnum',
    				array('regex', false, array('/^[a-z]/i')),
    				array('stringLength', false, array(4, 20)),
    				array('Db_NoRecordExists', false, array(
    				    'table' => 'user',
    				    'field' => 'username',
    				    'exclude' => implode(' AND ', $clause),
				        'messages' => array(Zend_Validate_Db_Abstract::ERROR_RECORD_FOUND => 'A user with this name already exists')
    				))
    			),
    			'filters' => array(
    				'StringToLower',
    				'StripWhitespace'
    			),
    			'required' => true,
    			'label' => 'Username:'
            ));
            $this->getElement('username_new')->addPrefixPath('TicketSystem_Filter', 'TicketSystem/Filter/', 'filter');
            
            $this->_addPasswordFields($isNew);
        }
        
        $this->addElement('radio', 'level', array(
            'required' => true,
            'multiOptions' => Default_Model_User::getLevelStringArray(),
            'label' => 'Level:',
            'errorMessages' => array('You must select a permission level')
        ));
        
        $this->_addUserFields();
        
        $this->addElement('select', 'group', array(
            'multiOptions' => $this->_getUgroupOptions(),
            'label' => 'Group:'
        ));
        
        $buttons = array();
        if ($isNew) {
            $this->addElement('submit', 'save', array(
    			'label' => 'Add',
            	'decorators' => $this->_getButtonDecorators()
            ));
            
            $buttons[] = 'save';
        } else {
            $this->addElement('submit', 'save', array(
    			'label' => 'Apply',
            	'decorators' => $this->_getButtonDecorators()
            ));
            
            $this->addElement('submit', 'remove', array(
                'label' => 'Remove',
            	'decorators' => $this->_getButtonDecorators()
            ));
            
            $this->addElement('submit', 'statuschange', array(
                'label' => ($isBanned) ? 'Enable' : 'Disable',
            	'decorators' => $this->_getButtonDecorators()
            ));
            
            $this->addElement('submit', 'reset', array(
                'label' => 'Reset',
                'decorators' => $this->_getButtonDecorators(),
                'onclick' => "window.location.href = '" . $this->getView()->url() . "'; return false;"
            ));
            
            $buttons += array('save', 'remove', 'statuschange', 'reset');
        }
        
        $this->addElement('submit', 'cancel', array(
            'label' => 'Cancel',
            'decorators' => $this->_getButtonDecorators(),
            'onclick' => "window.location.href = '" . $this->getView()->url(array(
            	'action' => 'users', 
            	'controller' => 'config'
            ), 'default', true) . "'; return false;"
        ));
        
        $buttons[] = 'cancel';
        
        $this->addDisplayGroup($buttons, 'buttons', array(
            'decorators' => array(
                'FormElements',
                array('HtmlTag', array('tag' => 'div')),
                'DtDdWrapper'
            )
        ));
        
        $this->addElement('hash', 'csrf_user', array(
            'ignore' => true
        ));
    }
    
    private function _getUgroupOptions()
    {
        $options = array('');
        
        $realOptions = array();
        $groups = Default_Model_Ugroup::fetchAll();
        foreach ($groups as $id => $group) {
            $realOptions[$group['name']] = array('id' => $id, 'shortname' => $group['shortname']);
        }
        
        ksort($realOptions);
        
        foreach ($realOptions as $name => $option) {
            $options[$option['id']] = $name . (empty($option['shortname']) ? '' : ' (' . $option['shortname'] . ')');
        }
        
        return $options;
    }
    
    protected function _getButtonDecorators()
    {
        return array(
            'Tooltip',
            'ViewHelper'
        );
    }
}