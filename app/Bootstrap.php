<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initAutoload()
    {
        $autoloader = new Zend_Application_Module_Autoloader(array(
            'namespace' => 'Default_',
            'basePath'  => dirname(__FILE__),
        ));
        return $autoloader;
    }
    
    protected function _initRegistry()
    {
        $registry = Zend_Registry::getInstance();
        Zend_Registry::set('bootstrap', $this);
        
        return $registry;
    }
    
    protected function _initDoctype()
    {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('XHTML1_STRICT');
        $view->env = $this->getEnvironment();
    }
    
    protected function _initRoutes()
    {
        $this->bootstrap('frontController');
        /* @var $front Zend_Controller_Front */
        $front = $this->frontController;
        $routes = new Zend_Config($this->getOption('routes'));
        $router = $front->getRouter();
        $router->addConfig($routes);
    }
    
    protected function _initTheme()
    {
        $basePath = dirname(__FILE__);
        $theme = $this->getTheme();
        
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->setBasePath($basePath . '/design/' . $theme . '/templates');
        $view->addHelperPath('TicketSystem/View/Helper', 'TicketSystem_View_Helper');
        
        $this->bootstrap('layout');
        $layout = $this->getResource('layout');
        $layout->setLayoutPath($basePath . '/design/' . $theme . '/layouts');
        //$layout->setView = $view;
    }
    
    protected function _initAcl()
    {
        $this->bootstrap('db');
        $acl = new Zend_Acl();
        
        //ACL Resources
        $acl->addResource('config');
        $acl->addResource('ticket');
        
        //ACL Roles
        $acl->addRole((string)Default_Model_User::LEVEL_GUEST);
        $acl->addRole((string)Default_Model_User::LEVEL_USER, (string)Default_Model_User::LEVEL_GUEST);
        $acl->addRole((string)Default_Model_User::LEVEL_MODERATOR, (string)Default_Model_User::LEVEL_USER);
        $acl->addRole((string)Default_Model_User::LEVEL_ADMIN);
        
        //ACL Rules
        $acl->allow((string)Default_Model_User::LEVEL_ADMIN);
        $acl->allow((string)Default_Model_User::LEVEL_GUEST, 'config', 'profile');
        if (!Default_Model_Setting::get('restrict_guest')) {
            $acl->allow((string)Default_Model_User::LEVEL_GUEST, 'ticket', 'create');
        } else {
            $acl->allow((string)Default_Model_User::LEVEL_USER, 'ticket', 'create');
        }
        
        if (!Default_Model_Setting::get('restrict_view_user')) {
            $acl->allow((string)Default_Model_User::LEVEL_GUEST, 'ticket', 'view-all');
        } else {
            if (Default_Model_Setting::get('allow_view_group')) {
                $acl->allow((string)Default_Model_User::LEVEL_GUEST, 'ticket', 'view-group');
            } else {
                $acl->allow((string)Default_Model_User::LEVEL_MODERATOR, 'ticket', 'view-group');
            }
        }
        
        $acl->allow((string)Default_Model_User::LEVEL_MODERATOR, 'ticket', 'edit-cc');
        $acl->allow((string)Default_Model_User::LEVEL_MODERATOR, 'ticket', 'reassign');
        
        return $acl;
    }
    
    public function getTheme()
    {
        $skinConfig = $this->getOption('skin');
        $theme = is_null($skinConfig['theme']) ? 'default' : $skinConfig['theme'];
        
        return $theme;
    }
}

