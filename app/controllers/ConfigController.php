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
        $userModel = Default_Model_User::fetchActive();
        
        $this->view->form = $form = new Default_Form_Profile();
        if ($this->getRequest()->isPost() && $form->handlePost($userModel)) {
            return $this->_helper->redirector('profile', 'config');
        }
        
        $group = $this->view->group = new Default_Model_Ugroup();
        if ($data = $userModel->getGroup()) {
            $group->setData($data);
        }
        $this->view->membership = $userModel->getGroupIds(true); 
        $this->view->screen = $this->_getParam('view');
    }
    
    public function settingsAction()
    {
        $settings = $this->view->settings = Default_Model_Setting::fetchAll(null, 'name');
        $form = $this->view->form = new Default_Form_Settings();
        $form->setupForSettings($settings);
        
        //Check for postback
        if ($this->getRequest()->isPost() && $form->handlePost()) {
            return $this->_helper->redirector('settings', 'config');
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
        
        if ($this->getRequest()->isPost() && $form->handlePost()) {
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
            
            if (isset($_POST['reset'])) {
                return $this->_helper->redirector('groups', 'config', null, array('id' => $id));
            }
            
            $form = $this->view->form = new Default_Form_Ugroup();
            if ($form->handlePost($this->view, $id)) {
                return $this->_helper->redirector('groups', 'config');
            } else {
                return $this->render('groupEdit');
            }
        }
        
        if (!empty($id)) {
            $form = $this->view->form = new Default_Form_Ugroup();
            if ($id === 'new') {
                $form->setupForGroup();
            } else {
                $group = Default_Model_Ugroup::findRow($id);
                
                if (null === $group) {
                    return $this->_helper->redirector('groups', 'config');
                }
                
                $form->setupForGroup($group);
                $this->view->users = $group->getUsers();
                $this->view->membership = $group->getMembership();
            }
            $this->render('groupEdit');
        } else {
            $form = new Default_Form_Grid_Groups($this->view, $this->getRequest());
            if ($this->_getParam('ajax')) {
                $this->_helper->layout()->disableLayout();
                return $this->render('grid/groups');
            }
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
            
        	if (isset($_POST['reset'])) {
                return $this->_helper->redirector('users', 'config', null, array('id' => $id));
            }
            
            $form = $this->view->form = new Default_Form_User();
            if ($form->handlePost($id)) {
            	return $this->_helper->redirector('users', 'config');
            } else {
            	return $this->render('userEdit');
            }            
        }
        
        if (!empty($id)) {
            $form = $this->view->form = new Default_Form_User();
            if ($id === 'new') {
                $form->setupForUser();
                $this->render('userEdit');
            } else if ($id == Zend_Auth::getInstance()->getIdentity()->user_id) {
                $userModel = $this->view->user = Default_Model_User::fetchActive();
                $group = $this->view->group = new Default_Model_Ugroup();
                if ($data = $userModel->getGroup()) {
                    $group->setData($data);
                }
                $this->view->membership = $userModel->getGroupIds(true);
                $this->view->messages = array(
                    'type' => 'notice',
                    'content' => array('Editing the account you are using is dangerous and therefore has been disabled.')
                );
                $this->render('userView');
            } else {
                $user = Default_Model_User::findRow($id);
                
                if (null === $user) {
                    return $this->_helper->redirector('users', 'config');
                }
                
                $form->setupForUser($user);
                $this->render('userEdit');
            }
        } else {
            $form = new Default_Form_Grid_Users($this->view, $this->getRequest());
            if ($this->_getParam('ajax')) {
                $this->_helper->layout()->disableLayout();
                return $this->render('grid/users');
            }
        }
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

