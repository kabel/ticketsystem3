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
    
    public function handlePost($id)
    {
        if ($id === 'new') {
           $userModel = new Default_Model_User();
           $this->setupForUser();
           
           if (!$this->isValid($_POST)) {
               return false;
           }
           
           $values = $this->getValues();
           $session = new Zend_Session_Namespace('TicketSystem');
           
           $data = array(
               'username' => $values['username_new'],
               'info' => $values['info'],
               'email' => $values['email'],
               'level' => $values['level'],
               'login_type' => $values['login_type'],
               'status' => Default_Model_User::STATUS_ACTIVE
           );
           
           if ($values['login_type'] == Default_Model_User::LOGIN_TYPE_LEGACY) {
               $data['passwd'] = md5($values['passwd_new']);
           } else {
               $data['passwd'] = '';
               
               $pf = new UNL_Peoplefinder();
               $pfResult = $pf->getUID($user);
               if ($prResult) {
                   $data['info'] = (!empty($pfResult->eduPersonNickname)) ? $pfResult->eduPersonNickname :  $pfResult->displayName;
                   if (isset($pfResult->mail)) {
                        if (isset($pfResult->unlEmailAlias)) {
                            $data['email'] = $pfResult->unlEmailAlias . '@unl.edu';
                        } else {
                            $data['email'] = $pfResult->mail;
                        }
                    }
               }
           }
           
           if (!empty($values['group'])) {
               $data['ugroup_id'] = $values['group'];
           }
           $userModel->setData($data);
           $userModel->save();
           $session->messages = array(
               'type' => 'success',
               'content' => array("User '{$userModel['username']}' successfully added")
           );
        } else {
           $userModel = Default_Model_User::findRow($id);
           $this->setupForUser($userModel);
           
           if (!$this->isValid($_POST)) {
               return false;
           }
           
           $values = $this->getValues();
           $session = new Zend_Session_Namespace('TicketSystem');
           
           if (isset($values['remove'])) {
               Default_Model_AttributeValue::flattenSrc('user', $userModel->getId());
               $session->messages = array(
                   'type' => 'success',
                   'content' => array("User '{$userModel['username']}' successfully deleted")
               );
               $userModel->delete();
           } else if (isset($values['statuschange'])) {
               if ($values['statuschange'] == 'Enable') {
                   $userModel['status'] = Default_Model_User::STATUS_ACTIVE;
                   $verb = 'enabled';
               } else {
                   $userModel['status'] = Default_Model_User::STATUS_BANNED;
                   $verb = 'disabled';
               }
               $userModel->save();
               $session->messages = array(
                   'type' => 'success',
                   'content' => array("User '{$userModel['username']}' successfully $verb")
               );
           } else {
               $data = array(
                   'level' => $values['level'],
                   'email' => $values['email'],
                   'info' => $values['info'],
                   'ugroup_id' => (empty($values['group']) ? null : $values['group'])
               );
               
               if (!empty($values['username_new']) && $values['username_new'] !== $userModel['username']) {
                   $data['username'] = $values['username_new'];
               }
               
               if (!empty($values['passwd_new'])) {
                   $data['passwd'] = md5($values['passwd_new']);
               }
               
               $userModel->setData($data);
               $userModel->save();
               $session->messages = array(
                   'type' => 'success',
                   'content' => array("User '{$userModel['username']}' successfully updated")
               );
           }
        }
        
        return true;
    }
    
    protected function _addFields($userId=-1, $isCAS=false, $isBanned=false)
    {
        $isNew = ($userId === -1);
        $_loginType = Default_Model_User::LOGIN_TYPE_LEGACY;
        if ($isNew) {
        	$this->addElement('select', 'login_type', array(
        		'required' => true,
        		'multiOptions' => Default_Model_User::getLoginTypeStringArray(),
        		'label' => 'Login Type:',
        		'onchange' => 'loginTypeChange();'
        	));
        	if (!empty($_POST['login_type']) && array_key_exists($_POST['login_type'], Default_Model_User::getLoginTypeStringArray())) {
        		$_loginType = $_POST['login_type'];
        	}
        }
        
        if (!$isCAS) {
            $db = Zend_Registry::get('bootstrap')->getResource('db');
            $clause = array($db->quoteInto('login_type = ?', $_loginType));
            if (!$isNew) {
                $clause[] = $db->quoteInto('user_id != ?', $userId);
            }
            $this->addElement('text', 'username_new', array(
                'validators' => array(
    				array('regex', false, array('/^[a-z][a-z0-9\-]+$/i')),
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
            
            $this->_addPasswordFields($isNew && $_loginType == Default_Model_User::LOGIN_TYPE_LEGACY);
        }
        
        $this->addElement('radio', 'level', array(
            'required' => true,
            'multiOptions' => Default_Model_User::getLevelStringArray(),
            'label' => 'Level:',
            'errorMessages' => array('Must select a permission level')
        ));
        
        $this->_addUserFields(!$isNew || ($isNew && $_loginType == Default_Model_User::LOGIN_TYPE_LEGACY));
        
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