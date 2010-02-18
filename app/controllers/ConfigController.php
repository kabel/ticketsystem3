<?php

class ConfigController extends TicketSystem_Controller_ProtectedAction
{
    public function preDispatch()
    {
        if (!parent::preDispatch()) {
            return;
        }
        
        $toolbar = array();
        
        if ($this->_isAclAllowed((string)Zend_Auth::getInstance()->getIdentity()->level, 'config')) {
            $toolbar = array(
                array(
                    'label' => 'Users',
                    'action' => 'users',
                    'active' => $this->_isActiveTool('users'),
                    'class' => 'users'
                ),
                array(
                    'label' => 'Groups',
                    'action' => 'groups',
                    'active' => $this->_isActiveTool('groups'),
                    'class' => 'groups'
                ),
                array(
                    'label' => 'Maintenance',
                    'action' => 'maint',
                    'active' => $this->_isActiveTool('maint'),
                    'class' => 'maint'
                ),
                array(
                    'label' => 'Settings',
                    'action' => 'settings',
                    'active' => $this->_isActiveTool('settings'),
                    'class' => 'settings'
                )
            );
        }
        
        if ($this->_isAclAllowed((string)Zend_Auth::getInstance()->getIdentity()->level, 'config', 'profile')) {
            $toolbar[] = array(
                'label' => 'My Profile',
                'action' => 'profile',
                'active' => $this->_isActiveTool('profile'),
                'class' => 'profile'
            );
        }
        
        $this->view->toolbar = $toolbar;
        
        $session = new Zend_Session_Namespace('TicketSystem');
        if (isset($session->messages)) {
            $this->view->messages = $session->messages;
            unset($session->messages);
        }
    }
    
    public function indexAction() 
    {
        $this->_forward('profile');
    }
    
    public function profileAction() 
    {
        $auth = Zend_Auth::getInstance();
        $user = $this->view->user = $auth->getIdentity();
        
        $group = null;
        if (!empty($user->ugroup_id)) {
            $group = Default_Model_Ugroup::findRow($user->ugroup_id);
        }
        $this->view->group = $group;
        $this->view->screen = $this->_getParam('view');
        
        $this->view->form = $form = new Default_Form_Profile();
        if ($this->getRequest()->isPost()) {
            if (!$form->isValid($_POST)) {
                return;
            }
            
            $values = $form->getValues();
            $userModel = Default_Model_User::findRow($user->user_id);
            $data = array(
                'email' => $values['email'],
                'info' => $values['info']
            );
            if (!empty($values['passwd_new'])) {
                $data['passwd'] = md5($values['passwd_new']);
            }
            $userModel->setData($data);
            $userModel->save();
            
            $newIdentArray = $userModel->toArray();
            unset($newIdentArray['passwd']);
            $auth->getStorage()->write((object)$newIdentArray);
            
            $session = new Zend_Session_Namespace('TicketSystem');
            $session->messages = array(
                'type' => 'success',
                'content' => array('Profile successfully updated')
            );
            return $this->_helper->redirector('profile', 'config');
        }
        
        $form->populate(array(
            'email' => $user->email,
            'info' => $user->info
        ));
    }
    
    public function settingsAction()
    {
        $settings = $this->view->settings = Default_Model_Setting::fetchAll(null, 'name');
        $form = $this->view->form = new Default_Form_Settings();
        $form->setupForSettings($settings);
        
        //Check for postback
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($_POST)) {
                $values = $form->getValues();
                
                $realUpdate = false;
                foreach ($values['settings'] as $name => $elements) {
                    $id = (int)$name;
                    if ($id > 0 && isset($settings[$id]) && $settings[$id]['value'] !== $elements['value']) {
                        $settings[$id]['value'] = $elements['value'];
                        $settings[$id]->save();
                        $realUpdate = true;
                    }
                }
                
                if ($realUpdate) {
                    $session = new Zend_Session_Namespace('TicketSystem');
                    $session->messages = array(
                        'type' => 'success',
                        'content' => array("Settings successfully saved")
                    );
                }
                return $this->_helper->redirector('settings', 'config');
            }
        }
        
        $this->view->user = Zend_Auth::getInstance()->getIdentity();
        $this->view->ticketStatusCounts = Default_Model_Ticket::getStatusCounts();
        $this->view->userLevelCounts = Default_Model_User::getLevelCounts();
        $db = Zend_Registry::get('bootstrap')->getResource('db');
        $this->view->dbConfig = $db->getConfig();
        $this->view->dbVersion = $db->getServerVersion();
    }
    
    public function maintAction()
    {
        $form = $this->view->form = new Default_Form_Maint();
        
        if ($this->getRequest()->isPost()) {
            if (!$form->isValid($_POST)) {
                return;
            }
            $session = new Zend_Session_Namespace('TicketSystem');
            
            if ($form->getElement('optimize')->isChecked()) {
                $this->_optimizeTable();
                $session->messages = array(
                    'type' => 'success',
                    'content' => array('Databases successfully optimized')
                );
            } else {
                $db = Zend_Registry::get('bootstrap')->getResource('db');
                $content = array();
                if ($form->getElement('purge')->isChecked()) {
                    $tickets = Default_Model_Ticket::getClosed();
//                    if (!empty($tickets)) {
//                        /* @var $ticket Default_Model_Ticket */
//                        foreach ($tickets as $ticket) {
//                            $ticket->delete();
//                        }
//                    }
//                    $this->_optimizeTable(array('ticket', 'attribute_value', 'changeset', 'uploads'));
                    
                    $content[] = 'Successfully purged closed tickets';
                }
                if ($form->getElement('tickets')->isChecked()) {
                    $sql = 'TRUNCATE TABLE `ticket`';
                    $db->exec($sql);
                    $this->_optimizeTable(array('attribute_value', 'changeset', 'uploads'));
                }
                if ($form->getElement('users')->isChecked()) {
                    $user = Zend_Auth::getInstance()->getIdentity();
                    $keepIds = array($user->user_id, 1);
                    Default_Model_AttributeValue::flattenSrc('user', $keepIds, true);
                    $db->delete('user', 'user_id NOT IN (' . implode(',', $keepIds) . ')');
                    
                    $defaultAdmin = Default_Model_User::findRow(1);
                    if (empty($defaultAdmin)) {
                        $defaultAdmin = new Default_Model_User();
                        $data = array(
                            'user_id' => 1,
                            'username' => 'admin',
                            'passwd' => md5('admin'),
                            'info' => 'Administrator',
                            'email' => '',
                            'level' => Default_Model_User::LEVEL_ADMIN,
                            'login_type' => Default_Model_User::LOGIN_TYPE_LEGACY,
                            'status' => Default_Model_User::STATUS_ACTIVE
                        );
                        $defaultAdmin->setData($data)
                            ->save();
                    }
                    
                    $this->_optimizeTable('user');
                    
                    $content[] = 'Users successfully reset';
                }
                if ($form->getElement('settings')->isChecked()) {
                    Default_Model_Setting::resetDefaults();
                    $content[] = 'Settings successfully reset';
                }
                
                if (!empty($content)) {
                    $session->messages = array(
                        'type' => 'success',
                        'content' => $content
                    );
                }
            }
            return $this->_helper->redirector('maint', 'config');
        }
    }
    
    public function groupsAction()
    {
        $id = $this->view->id = $this->_getParam('id');
        
        //Check for postback
        if ($this->getRequest()->isPost()) {
            if (empty($id) || isset($_POST['cancel'])) {
                return $this->_helper->redirector('groups', 'config');
            }
            
            $form = $this->view->form = new Default_Form_Ugroup();
            if ($id === 'new') {
                $groupModel = new Default_Model_Ugroup();
                $form->setupForGroup();
                
                if (!$form->isValid($_POST)) {
                    return $this->render('groupEdit');
                }
                
                $values = $form->getValues();
                $session = new Zend_Session_Namespace('TicketSystem');
                
                $data = array(
                    'name' => $values['name'],
                    'shortname' => $values['shortname']
                );
                $groupModel->setData($data)
                    ->save();
                
                $session->messages = array(
                    'type' => 'success',
                    'content' => array("Group '{$this->view->escape($groupModel['name'])}' successfully added")
                );
            } else {
                if (isset($_POST['reset'])) {
                    return $this->_helper->redirector('groups', 'config', null, array('id' => $id));
                }
                
                $groupModel = Default_Model_Ugroup::findRow($id);
                $form->setupForGroup($groupModel);
                $this->view->users = $groupModel->getUsers();
                
                if (!$form->isValid($_POST)) {
                    return $this->render('groupEdit');
                }
                
                $values = $form->getValues();
                $session = new Zend_Session_Namespace('TicketSystem');
                
                if (isset($values['remove'])) {
                    Default_Model_AttributeValue::flattenSrc('ugroup', $groupModel->getId());
                    $session->messages = array(
                        'type' => 'success',
                        'content' => array("Group '{$this->view->escape($groupModel['name'])}' successfully deleted")
                    );
                    $groupModel->delete();
                } else {
                    $data = array(
                        'name' => $values['name'],
                        'shortname' => $values['shortname']
                    );
                    
                    $groupModel->setData($data)
                        ->save();
                    $session->messages = array(
                        'type' => 'success',
                        'content' => array("Group '{$this->view->escape($groupModel['name'])}' successfully updated")
                    );
                }
            }
            
            return $this->_helper->redirector('groups', 'config');
        }
        
        if (!empty($id)) {
            $form = $this->view->form = new Default_Form_Ugroup();
            if ($id === 'new') {
                $form->setupForGroup();
            } else {
                $group = Default_Model_Ugroup::findRow($id);
                $this->view->users = $group->getUsers();
                $form->setupForGroup($group);
            }
            $this->render('groupEdit');
        } else {
            $this->view->userCounts = Default_Model_Ugroup::getUserCounts();
            $this->view->groups = Default_Model_Ugroup::fetchAll();
        }
    }
    
    public function usersAction()
    {
        $id = $this->view->id = $this->_getParam('id');
        
        // Check for postback
        if ($this->getRequest()->isPost()) {
            if (empty($id) || $id == Zend_Auth::getInstance()->getIdentity()->user_id || isset($_POST['cancel'])) {
                return $this->_helper->redirector('users', 'config');
            }
            
            $form = $this->view->form = new Default_Form_User();
            if ($id === 'new') {
                $userModel = new Default_Model_User();
                $form->setupForUser();
                
                if (!$form->isValid($_POST)) {
                    return $this->render('userEdit');
                }
                
                $values = $form->getValues();
                $session = new Zend_Session_Namespace('TicketSystem');
                
                $data = array(
                    'username' => $values['username_new'],
                    'passwd' => md5($values['passwd_new']),
                    'info' => $values['info'],
                    'email' => $values['email'],
                    'level' => $values['level'],
                    'login_type' => Default_Model_User::LOGIN_TYPE_LEGACY,
                    'status' => Default_Model_User::STATUS_ACTIVE
                );
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
                if (isset($_POST['reset'])) {
                    return $this->_helper->redirector('users', 'config', null, array('id' => $id));
                }
                
                $userModel = Default_Model_User::findRow($id);
                $form->setupForUser($userModel);
                
                if (!$form->isValid($_POST)) {
                    return $this->render('userEdit');
                }
                
                $values = $form->getValues();
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
            
            return $this->_helper->redirector('users', 'config');
        }
        
        if (!empty($id)) {
            $form = new Default_Form_User();
            if ($id === 'new') {
                $form->setupForUser();
                $this->view->form = $form;
                $this->render('userEdit');
            } else if ($id == Zend_Auth::getInstance()->getIdentity()->user_id) {
                $this->view->user = Default_Model_User::findRow($id);
                $this->view->messages = array(
                    'type' => 'notice',
                    'content' => array('Editing the account you are using is dangerous and therefore has been disabled.')
                );
                $this->render('userView');
            } else {
                $user = Default_Model_User::findRow($id);
                $form->setupForUser($user);
                $this->view->form = $form;
                $this->render('userEdit');
            }
        } else {
            $this->view->users = Default_Model_User::fetchAll();
        }
    }
    
    protected function _optimizeTable($tables=array())
    {
        if (!is_array($tables)) {
            $tables = array($tables);
        }
        
        $db = Zend_Registry::get('bootstrap')->getResource('db');
        
        if (empty($tables)) {
            $tables = $db->listTables();
        }
        
        $sql = 'OPTIMIZE TABLE ' . implode(', ', $tables);
        $db->exec($sql);
    }
    
    protected function _isAllowed($userLevel)
    {
        $result = false;
        switch ($this->getRequest()->getActionName()) {
            case 'users':
            case 'groups':
            case 'maint':
            case 'settings':
                $result = $this->_isAclAllowed($userLevel, 'config');
                break;
            case 'profile':
                $result = $this->_isAclAllowed($userLevel, 'config', 'profile');
                break;
        }
        
        return $result;
    }
    
    protected function _isActiveTool($action)
    {
        return parent::_isActiveNav($action, 'config');
    }
}

