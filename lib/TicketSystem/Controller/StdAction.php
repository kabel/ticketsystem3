<?php

require_once 'Zend/Controller/Front.php';
require_once 'TicketSystem/Controller/EmptyAction.php';
require_once 'Zend/Auth.php';

class TicketSystem_Controller_StdAction extends TicketSystem_Controller_EmptyAction
{
    public function postDispatch()
    {
        $isAuthenticated = Zend_Auth::getInstance()->hasIdentity();

        $metanav = array();
        if ($isAuthenticated) {
            $metanav[] = array(
                'label' => 'Configuration',
                'controller' => 'config',
                'action' => 'profile',
                'class' => 'configuration',
                'active' => $this->_isActiveNav('*', 'config')
            );
            $metanav[] = array(
                'label' => 'Help',
                'controller' => 'help',
                'action' => 'index',
                'class' => 'help',
                'active' => $this->_isActiveNav('*','help')
            );
            $metanav[] = array(
                'label' => 'Logout',
                'controller' => 'auth',
                'action' => 'logout',
                'class' => 'logout'
            );
        } else {
            $metanav[] = array(
                'label' => 'Help',
                'controller' => 'help',
                'action' => 'index',
                'class' => 'help',
                'active' => $this->_isActiveNav('*','help')
            );
            $metanav[] = array(
                'label' => 'Login',
                'controller' => 'auth',
                'action' => 'index',
                'class' => 'login'
            );
        }
        $this->view->metanav = $metanav;

        $nav = array();
        if ($isAuthenticated) {
            $user = Default_Model_User::fetchActive();
        	$nav[] = array(
                'label' => 'View Tickets ',
                'controller' => 'index',
                'action' => 'index',
        		'class' => 'viewtickets',
                'active' => ($this->_isActiveNav('index', 'index') || $this->_isActiveNav('view', 'ticket') ||
                             $this->_isActiveNav('index', 'report')|| $this->_isActiveNav('view', 'report'))
            );
            if ($this->_isAclAllowed((string)$user['level'], 'ticket', 'create')) {
                $nav[] = array(
                    'label' => 'New Ticket ',
                    'controller' => 'ticket',
                    'action' => 'new',
        			'class' => 'newticket',
                    'active' => $this->_isActiveNav('new', 'ticket')
                );
            }
            $nav[] = array(
                'label' => 'Search',
                'controller' => 'ticket',
                'action' => 'search',
        		'class' => 'searchtickets',
                'active' => ($this->_isActiveNav('search', 'ticket') || $this->_isActiveNav('results', 'ticket'))
            );
        }

        $this->view->mainnav = $nav;

        parent::postDispatch();
    }

    protected function _isActiveNav($action, $controller)
    {
        if ($controller !== $this->getRequest()->getControllerName()) {
            return false;
        }

        if ($action !== '*' && $action !== $this->getRequest()->getActionName()) {
            return false;
        }

        return true;
    }

    protected function _isAllowed($userLevel)
    {
        return true;
    }

    protected function _isAclAllowed($userLevel, $resource=null, $privledge=null)
    {
        $acl = Zend_Registry::get('bootstrap')->getResource('acl');
        return $acl->isAllowed($userLevel, $resource, $privledge);
    }
}